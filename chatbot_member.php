<?php
/**
 * ================================================================
 * VANDA AI - LOGIKA BACKEND CHATBOT MEMBER
 * TEKNIK PROMPT ENGINEERING : HYBRID (FEW-SHOT + CHAIN-OF-THOUGHT)
 * ----------------------------------------------------------------
 * Ciri teknik ini:
 * - Gabungan penuh: aturan + LANGKAH penalaran eksplisit tersembunyi
 *   (seperti versi CoT) DITAMBAH 7 contoh percakapan nyata, satu per
 *   skenario uji (seperti versi Few-Shot).
 * Ekspektasi saat evaluasi 7 skenario:
 * - Prompt PALING PANJANG dari 4 varian -> token request per
 *   panggilan PALING TINGGI. Ini varian yang paling relevan untuk
 *   dicek terhadap risiko "lonjakan pemakaian token" yang disebut
 *   di tujuan evaluasi.
 * - Berpotensi PALING KONSISTEN secara visual (format/emoji/struktur
 *   list meniru contoh) SEKALIGUS paling akurat kalkulasinya (karena
 *   langkah hitung tetap dipaksa eksplisit) -- tapi perlu dibuktikan
 *   lewat pengujian nyata, bukan diasumsikan otomatis lebih baik
 *   hanya karena lebih panjang.
 * - Jadikan varian ini sebagai baseline "upper bound kualitas" untuk
 *   dibandingkan trade-off token-nya dengan 3 varian lain.
 *
 * CARA PAKAI: salin isi file ini menggantikan blok PHP di bagian
 * paling atas chatbot_member.php milik Anda. Bagian HTML & CSS di
 * bawahnya (setelah tag "?>") TIDAK diubah, tetap pakai yang lama.
 * ================================================================
 */

define('VANDA_AI_DEBUG_TOKEN', true);

session_start();
require 'includes/koneksi.php';
require 'includes/api_key.php';

// Proteksi Keamanan: Pastikan member sudah login
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'member') {
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
        echo json_encode(['status' => 'error', 'message' => 'Sesi login habis. Silakan muat ulang halaman.']);
        exit;
    }
    header("Location: login.php");
    exit;
}

// Ambil Data Pengaturan Web
$q_pengaturan = mysqli_query($koneksi, "SELECT wa_cs FROM pengaturan_web WHERE id=1");
$web_data     = mysqli_fetch_assoc($q_pengaturan);
$wa_db        = $web_data['wa_cs'] ?? '082148556601';
$wa_link      = "62" . substr(preg_replace('/[^0-9]/', '', $wa_db), 1);

// Cek status membership
$q_member      = mysqli_query($koneksi, "SELECT status, tgl_berakhir FROM membership WHERE id_user = {$_SESSION['id_user']} ORDER BY id_membership DESC LIMIT 1");
$d_member      = mysqli_fetch_assoc($q_member);
$status_member = $d_member['status'] ?? 'belum_daftar';

// Hitung status lonceng notifikasi (disamakan persis dengan member_dasbor.php)
$peringatan_merah = false;
if ($status_member === 'aktif' && !empty($d_member['tgl_berakhir'])) {
    $sisa_hari_notif = max(0, round((strtotime($d_member['tgl_berakhir']) - time()) / 86400));
    if ($sisa_hari_notif <= 7) $peringatan_merah = true;
}

$q_pending_notif   = mysqli_query($koneksi, "SELECT id_membership FROM membership WHERE id_user = {$_SESSION['id_user']} AND status='pending' AND jenis_pengajuan='perpanjang' LIMIT 1");
$sedang_perpanjang = ($q_pending_notif && mysqli_num_rows($q_pending_notif) > 0);

$notif_merah = (($peringatan_merah || $status_member !== 'aktif') && !$sedang_perpanjang);

// =========================================================
// BLOK PHP: HANDLING AJAX REQUEST KE API GEMINI
// VERSI PROMPT: HYBRID (FEW-SHOT + CHAIN-OF-THOUGHT)
// =========================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'chat_ai') {
    header('Content-Type: application/json; charset=utf-8');
    header('X-Prompt-Technique: Hybrid');

    $pesan        = $_POST['pesan'] ?? '';
    $gambarBase64 = $_POST['gambar'] ?? '';

    $api_key = $gemini_api_key;
    $url     = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $api_key;

    $parts = [];

    // ---------------------------------------------------------
    // PROMPT SISTEM — TEKNIK HYBRID (CoT + Few-Shot)
    // ---------------------------------------------------------
    $prompt_sistem = "Kamu adalah Vanda AI, asisten virtual dan personal trainer untuk Vanda Gym Classic di Palangka Raya.
Jawablah dengan ramah, enerjik, bahasa Indonesia santai tapi profesional (sapaan 'Kak').

SEBELUM MENJAWAB, WAJIB lakukan proses penalaran internal berikut secara berurutan. Proses ini HANYA untuk membentuk jawaban akhir kamu -- TIDAK BOLEH ditulis atau ditampilkan ke member dalam bentuk apa pun:

LANGKAH 1 - KLASIFIKASI TOPIK: Apakah pesan/foto member membahas gym, fitness, diet, atau nutrisi, dan apakah maksudnya jelas (tidak ambigu)?
   - Jika TIDAK (di luar topik atau ambigu) -> lompat ke LANGKAH 5 opsi A.
   - Jika YA -> lanjut LANGKAH 2.

LANGKAH 2 - DETEKSI JENIS PERMINTAAN: Apakah member minta program/rekomendasi kebugaran? Apakah ia mengirim foto makanan atau foto alat gym? Apakah ia menyebut kondisi medis (diabetes, darah tinggi, jantung, cedera, hamil, dll)? Catat semua yang relevan.

LANGKAH 3 - CEK KELENGKAPAN DATA (khusus permintaan program/target kebugaran): Apakah berat badan, tinggi badan, usia, jenis kelamin, DAN tingkat aktivitas SEMUA disebutkan member?
   - Jika lengkap -> lanjut LANGKAH 4 untuk menghitung.
   - Jika tidak lengkap -> siapkan rekomendasi umum yang aman, dan catat bahwa di akhir jawaban kamu perlu meminta data yang kurang.

LANGKAH 4 - KALKULASI GIZI (hanya jika data lengkap di LANGKAH 3), gunakan rumus Mifflin-St Jeor secara berurutan:
   a. Hitung BMR:
      BMR Pria  = (10 x berat_kg) + (6.25 x tinggi_cm) - (5 x usia) + 5
      BMR Wanita = (10 x berat_kg) + (6.25 x tinggi_cm) - (5 x usia) - 161
   b. Kalikan BMR dengan faktor aktivitas untuk dapat TDEE: sedentari (aktivitas minimal)=1.2, ringan (olahraga 1-3 hari/minggu)=1.375, sedang (olahraga 3-5 hari/minggu)=1.55, berat (olahraga 6-7 hari/minggu)=1.725, sangat berat (atlet/2x sehari)=1.9.
   c. Sesuaikan TDEE dengan tujuan member: bulking = TDEE + 300 s/d 500 kkal; cutting = TDEE - 300 s/d 500 kkal; kondisi medis/maintenance = TDEE apa adanya.
   d. Simpan HANYA angka target kalori akhir (dan angka TDEE-nya) untuk ditampilkan nanti di LANGKAH 5. JANGAN simpan/tulis rincian perkalian atau rumus baris demi baris -- itu murni proses internal.

LANGKAH 5 - SUSUN JAWABAN FINAL yang benar-benar dikirim ke member, pilih opsi yang sesuai hasil LANGKAH 1-4:
   OPSI A (di luar topik/ambigu): Tolak dengan sopan, singkat, ramah, lalu WAJIB tambahkan [TOMBOL_ADMIN] persis di akhir jawaban. Tanpa terkecuali.
   OPSI B (ada permintaan target/program kebugaran): WAJIB format lengkap 🎯 TARGET KAMU / 🏋️ REKOMENDASI LATIHAN / 🍱 REKOMENDASI MENU MAKANAN / 💡 TIPS TAMBAHAN. Jika LANGKAH 4 sudah menghasilkan angka, tampilkan HANYA hasil akhirnya beserta cara singkat ia didapat, JANGAN tampilkan proses hitung baris demi baris. Jika data belum lengkap, tambahkan permintaan data di bagian akhir jawaban.
   OPSI C (ada indikasi kondisi medis khusus): WAJIB sisipkan disclaimer singkat untuk konsultasi ke dokter/ahli gizi, digabung secara natural dengan rekomendasi umum yang aman.
   OPSI D (foto makanan): Jawab dengan format 🍽️ Nama Makanan / 🔥 Kalori / 🥩 Protein / 🥑 Lemak / 🍚 Karbohidrat, ringkas.
   OPSI E (foto alat gym): Sebutkan nama alat + cara pakai singkat, aman, memotivasi. DILARANG KERAS mendeskripsikan detail visual gambar yang tidak relevan untuk fitness (warna lantai, cermin, pencahayaan, dst).
   Opsi B sampai E bisa digabung dalam satu jawaban kalau memang relevan (misal: goal + kondisi medis sekaligus).

ATURAN PENTING SOAL PENYEMBUNYIAN PENALARAN: LANGKAH 1 sampai 4 di atas HANYA untuk proses berpikir kamu sendiri. JANGAN PERNAH menuliskan kata 'LANGKAH', label 'OPSI', rincian rumus BMR/TDEE, atau penjelasan proses penalaran apa pun ke member. Member HANYA menerima hasil akhir dari LANGKAH 5, ditulis seolah-olah kamu langsung tahu jawabannya -- dengan gaya bahasa dan format PERSIS seperti pada contoh-contoh di bawah ini.

FORMAT PENULISAN: emoji relevan di tiap poin penting, **teks tebal** untuk angka/poin penting. DILARANG KERAS tanda pagar (#) -- pakai HURUF KAPITAL untuk judul. List HANYA 1 tingkat: angka (1. 2. 3.) untuk judul poin, strip (-) untuk sub-datanya SAJA -- DILARANG KERAS list bersarang, DILARANG KERAS bullet (•) atau bintang (*). WAJIB 1 baris kosong sebelum & sesudah list atau kelompok kalimat baru.

---
CONTOH GAYA JAWABAN (tiru nada bicara & strukturnya, JANGAN tiru angka mentahnya -- angka WAJIB dihitung ulang sesuai LANGKAH 4 di atas) -- satu contoh mewakili tiap jenis skenario:

CONTOH 1 (Skenario: Pertanyaan Di Luar Topik):
Input: \"Bro, laptop gaming budget 10 juta apa ya yg bagus?\"
Output:
Wah, maaf banget Kak! Vanda AI cuma diprogram untuk bantu soal fitness, gym, dan nutrisi aja nih. 🙏
Kalau ada pertanyaan soal fasilitas atau membership gym, langsung DM admin kami ya!
[TOMBOL_ADMIN]

CONTOH 2 (Skenario: Pesan Ambigu):
Input: \"eh gimana ya wkwk\"
Output:
Hai Kak! Vanda kurang paham maksud pesannya nih. 😅
Boleh diperjelas lagi Kak, mau tanya soal program latihan, menu makan, atau analisis foto makanan/alat gym?
[TOMBOL_ADMIN]

CONTOH 3 (Skenario: Permintaan Program TANPA Data Diri):
Input: \"aku mau nambah stamina buat lari, gimana ya programnya\"
Output:
Semangat ya, Kak! Nambah stamina itu goal yang seru. 🔥

🎯 TARGET KAMU: Meningkatkan Stamina/Endurance

🏋️ REKOMENDASI LATIHAN:
1. Jenis Latihan - Kardio Zona 2 dikombinasi interval ringan
2. Frekuensi - 3 sampai 4 kali seminggu
3. Contoh Latihan - Jogging 20 menit, lanjut interval 4x2 menit cepat-lambat
4. Estimasi Durasi - 30 sampai 40 menit per sesi

🍱 REKOMENDASI MENU MAKANAN HARIAN:
1. Sarapan - Oatmeal dengan buah dan sedikit madu
2. Makan Siang - Nasi merah, dada ayam, sayur bening
3. Makan Malam - Ikan panggang dan salad
4. Camilan - Pisang atau kurma sebelum latihan

💡 TIPS TAMBAHAN:
Jangan lupa pemanasan dinamis sebelum kardio ya, Kak, biar performa maksimal! 💧

⚠️ Catatan: Kirim data BB, TB, usia, dan aktivitas harianmu ya Kak, biar Vanda bisa hitungkan target kalori yang lebih presisi! 🔥

CONTOH 4 (Skenario: Permintaan Program DENGAN Data Diri Lengkap):
Input: \"BB 51kg TB 153cm umur 22 cewek aktivitas ringan, target bulking, rekomendasiin dong\"
Output:
Siap Kak! Datanya lengkap, Vanda hitungkan langsung ya. 💪

🎯 TARGET KAMU: Bulking (Wanita, 51kg/153cm/22th)

🔥 TARGET KALORI HARIAN: **kurang lebih 2.043 kkal** (TDEE kurang lebih 1.643 kkal ditambah surplus 400 kkal)

🏋️ REKOMENDASI LATIHAN:
1. Jenis Latihan - Angkat beban (fokus hipertrofi)
2. Frekuensi - 3 sampai 4 kali seminggu
3. Contoh Gerakan - Squat, Deadlift, Bench Press, Overhead Press
4. Estimasi Durasi - 45 hingga 60 menit per sesi

🍱 CONTOH PEMBAGIAN MAKRO:
1. Protein - sekitar 100 gram (2 gram per kg berat badan)
2. Lemak - sekitar 65 gram
3. Karbohidrat - sisanya sekitar 250 gram

💡 TIPS TAMBAHAN:
Pastikan asupan protein cukup ya Kak, biar otot bisa tumbuh maksimal. Jangan lupa istirahat 7-8 jam per hari! 😴

CONTOH 5 (Skenario: Permintaan dengan Kondisi Medis Khusus):
Input: \"aku ada riwayat asma tapi pengen mulai olahraga buat nurunin BB\"
Output:
Halo Kak! Semangat banget mau mulai bergerak, itu langkah bagus! 💪

⚠️ **PENTING:** Karena Kakak punya riwayat asma, konsultasi dulu ke dokter soal jenis & intensitas olahraga yang aman ya, Kak, supaya nggak memicu sesak napas.

Secara umum, ini panduan amannya:

🏋️ REKOMENDASI LATIHAN UMUM:
1. Jenis Latihan - Kardio intensitas ringan-sedang (jalan cepat, sepeda santai, berenang)
2. Frekuensi - 3 sampai 4 kali seminggu
3. Durasi - 20 sampai 30 menit, mulai pelan dulu

🍱 PANDUAN MAKANAN UMUM:
1. Kalori - Defisit ringan 300 kkal dari kebutuhan harian
2. Protein - Dada ayam, telur, tahu, tempe
3. Hindari - Makanan pemicu alergi/asma jika ada

💡 TIPS TAMBAHAN:
Selalu bawa inhaler saat olahraga dan hentikan latihan kalau mulai sesak ya, Kak! 🩺

CONTOH 6 (Skenario: Analisis Foto Makanan):
Input: [Member mengirim foto seporsi nasi goreng ayam, tanpa teks]
Output:
Nih Kak, Vanda coba analisis dari foto makanannya ya! 🍽️

🍽️ NAMA MAKANAN: Nasi Goreng Ayam (perkiraan 1 porsi sedang)

🔥 KALORI: kurang lebih 550 kkal
🥩 PROTEIN: sekitar 20 gram
🥑 LEMAK: sekitar 18 gram
🍚 KARBOHIDRAT: sekitar 75 gram

💡 TIPS TAMBAHAN:
Kalau lagi defisit kalori, coba kurangi porsi nasi atau minta minyaknya lebih sedikit ya, Kak! 😉

CONTOH 7 (Skenario: Analisis Foto Alat Gym):
Input: [Member mengirim foto mesin lat pulldown, tanpa teks]
Output:
Itu namanya Lat Pulldown Machine, Kak! 💪

🏋️ NAMA ALAT: Lat Pulldown Machine
🎯 OTOT SASARAN: Punggung (Latissimus Dorsi) dan Bisep

CARA PAKAI SINGKAT:
1. Duduk dan kunci paha di bantalan
2. Pegang bar lebih lebar dari bahu
3. Tarik bar ke arah dada bagian atas, kontraksikan punggung
4. Kembalikan perlahan dengan kontrol, jangan dihentak

💡 TIPS TAMBAHAN:
Jaga badan tetap tegak, jangan pakai momentum ayunan badan ya, Kak, biar target ototnya kena maksimal! 🔥
---

Pertanyaan/Pernyataan member: " . $pesan;

    if (!empty($pesan)) {
        $parts[] = ['text' => $prompt_sistem];
    } else if (!empty($gambarBase64)) {
        $parts[] = ['text' => $prompt_sistem . "\n\nTolong analisis gambar yang saya lampirkan ini."];
    }

    if (!empty($gambarBase64)) {
        $image_parts = explode(";base64,", $gambarBase64);
        $mime_type   = explode("data:", $image_parts[0])[1];
        $base64_data = $image_parts[1];
        $parts[] = ['inline_data' => ['mime_type' => $mime_type, 'data' => $base64_data]];
    }

    // thinkingConfig disamakan dengan varian lain agar variabel uji tetap
    // murni pada teknik prompt/teks
    $data = [
        'contents' => [['parts' => $parts]],
        'generationConfig' => [
            'thinkingConfig' => [
                'thinkingBudget' => -1,
            ],
        ],
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($err) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal terhubung ke Server AI: ' . $err]);
        exit;
    }

    $result = json_decode($response, true);

    // --- INSTRUMENTASI TOKEN (untuk perbandingan 4 teknik prompt) ---
    if (VANDA_AI_DEBUG_TOKEN && isset($result['usageMetadata'])) {
        $u = $result['usageMetadata'];
        error_log(sprintf(
            "[VANDA-AI][HYBRID] user=%s prompt_tokens=%d thinking_tokens=%d output_tokens=%d total_tokens=%d",
            $_SESSION['id_user'] ?? '-',
            $u['promptTokenCount']     ?? 0,
            $u['thoughtsTokenCount']   ?? 0,
            $u['candidatesTokenCount'] ?? 0,
            $u['totalTokenCount']      ?? 0
        ));
    }

    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $balasan = $result['candidates'][0]['content']['parts'][0]['text'];

        // Lapisan pengaman: bersihkan sisa instruksi lama jika AI masih memunculkannya
        $balasan = preg_replace('/🧠\s*Analisis Vanda\s*:.*?(\n\s*\n|\z)/su', '', $balasan, 1);

        // Lapisan pengaman TAMBAHAN: bersihkan jika model tetap membocorkan
        // label jejak penalaran internal (LANGKAH x / OPSI x)
        $balasan = preg_replace('/^\s*(LANGKAH|OPSI)\s*[0-9A-Za-z]*\s*[:\-\.].*$/mu', '', $balasan);
        $balasan = preg_replace('/\n{3,}/', "\n\n", $balasan);
        $balasan = ltrim($balasan);

        // Amankan karakter khusus dan ubah styling ke HTML
        $balasanHTML = htmlspecialchars($balasan, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $balasanHTML = preg_replace('/^#+\s+(.*)$/mu', '<strong>$1</strong>', $balasanHTML);
        $balasanHTML = preg_replace('/\*\*(.*?)\*\*/su', '<strong>$1</strong>', $balasanHTML);
        $balasanHTML = preg_replace('/^[\*\-]\s+(.*)$/mu', '&bull; $1', $balasanHTML);
        $balasanHTML = preg_replace('/(?<![\*\s])\*(?!\s)(.*?)(?<!\s)\*(?![\*\s])/su', '<em>$1</em>', $balasanHTML);
        $balasanHTML = nl2br(trim($balasanHTML));

        $btnAdminHTML = '<br><br><a href="https://instagram.com/vandagympky_classic" target="_blank" style="display:inline-flex; align-items:center; justify-content:center; background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); color:white; padding:8px 15px; border-radius:6px; text-decoration:none; font-weight:bold; font-size:0.85rem; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.3); transition: 0.3s;">📸 DM Admin di Instagram</a>';

        $balasanHTML = str_replace('[TOMBOL_ADMIN]', $btnAdminHTML, $balasanHTML);

        $response_payload = ['status' => 'success', 'message' => $balasanHTML];
        if (VANDA_AI_DEBUG_TOKEN && isset($result['usageMetadata'])) {
            $response_payload['_debug_token_usage'] = $result['usageMetadata'];
            $response_payload['_debug_teknik']      = 'hybrid';
        }

        echo json_encode($response_payload, JSON_UNESCAPED_UNICODE);
    } else {
        $error_msg = $result['error']['message'] ?? 'AI tidak dapat memproses permintaan ini.';
        echo json_encode(['status' => 'error', 'message' => $error_msg]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot AI - Vanda Gym Classic</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* CSS DISESUAIKAN DAN DIRAPIKAN */
        .bottom-nav-mobile { display: none !important; }

        .header-right { display: flex; align-items: center; gap: 10px; margin-left: auto; }

        .bell-icon {
            position: relative; color: #888; display: flex; align-items: center;
            justify-content: center; width: 40px; height: 40px; border-radius: 4px;
            transition: 0.3s; text-decoration: none;
        }
        .bell-icon:hover { color: var(--text-light); }
        .bell-icon.active { color: var(--primary-red); animation: ring 2s infinite ease-in-out; }
        .bell-badge {
            position: absolute; top: 4px; right: 4px;
            background: var(--primary-red); color: white;
            font-size: 10px; font-weight: bold;
            width: 14px; height: 14px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
        }

        @keyframes ring {
            0%  { transform: rotate(0); }
            10% { transform: rotate(15deg); }
            20% { transform: rotate(-10deg); }
            30% { transform: rotate(5deg); }
            40% { transform: rotate(-5deg); }
            50% { transform: rotate(0); }
            100%{ transform: rotate(0); }
        }

        header .menu-toggle {
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            width: 42px; height: 42px; background-color: #1a1a1a; border: 1px solid #333;
            border-radius: 10px; cursor: pointer; z-index: 1001; padding: 0;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }
        header .menu-toggle:hover { border-color: var(--accent-gold); }
        header .menu-toggle .bar {
            display: block; width: 20px; height: 2px; background-color: #E8C999;
            border-radius: 2px; transition: all 0.3s ease-in-out; margin: 2px 0;
        }
        header .menu-toggle.active .bar:nth-child(1) { transform: translateY(6px) rotate(45deg); }
        header .menu-toggle.active .bar:nth-child(2) { opacity: 0; }
        header .menu-toggle.active .bar:nth-child(3) { transform: translateY(-6px) rotate(-45deg); }

        #nav-menu {
            display: none; position: absolute; top: 70px; right: 20px; left: auto;
            width: 220px; box-sizing: border-box; background-color: #1a1a1a;
            padding: 12px; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.8);
            border: 1px solid #333; flex-direction: column; z-index: 1000;
        }
        #nav-menu.active {
            display: flex;
            animation: slideDownFade 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }
        @keyframes slideDownFade {
            from { opacity: 0; transform: translateY(-15px) scale(0.95); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        #nav-menu a.menu-link {
            display: flex; align-items: center; justify-content: flex-start; gap: 10px;
            width: 100%; box-sizing: border-box; color: #ccc; text-decoration: none;
            font-weight: 600; padding: 8px 10px; border-radius: 6px; transition: all 0.3s ease;
            font-size: 0.85rem; margin-left: 0;
        }
        #nav-menu a.menu-link:hover { background-color: rgba(232, 201, 153, 0.1); color: var(--accent-gold); }
        #nav-menu a.menu-link.active-link { color: var(--accent-gold); background-color: rgba(232, 201, 153, 0.07); }
        #nav-menu a.menu-link.locked-link { opacity: 0.45; cursor: not-allowed; pointer-events: none; }

        .menu-divider { height: 1px; background-color: #333; margin: 4px 0; width: 100%; }

        .nav-actions-dasbor { display: flex; flex-direction: column; width: 100%; gap: 8px; margin-top: 4px; }
        .nav-actions-dasbor .btn-keluar-menu {
            display: block; box-sizing: border-box; padding: 9px 15px;
            border: 1px solid var(--primary-red); color: var(--primary-red);
            border-radius: 6px; font-weight: bold; cursor: pointer; text-align: center;
            transition: all 0.3s ease; font-size: 0.6rem; text-decoration: none;
        }
        .nav-actions-dasbor .btn-keluar-menu:hover { background-color: var(--primary-red); color: white; }

        /* AREA CHAT */
        .chat-wrapper {
            display: flex; justify-content: center; align-items: center;
            padding: 20px; min-height: calc(100vh - 70px);
        }
        .chat-container {
            width: 100%; max-width: 480px; height: 78vh; max-height: 760px;
            background-color: #0a0a0a; border: 1px solid #333;
            border-top: 4px solid var(--primary-red); border-radius: 12px;
            display: flex; flex-direction: column; overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.8); position: relative;
        }
        .chat-inner-header {
            background-color: #050505; padding: 14px 18px; display: flex;
            align-items: center; border-bottom: 1px solid #222; z-index: 10;
        }
        .btn-back-chat {
            text-decoration: none; color: var(--accent-gold); font-weight: bold;
            font-size: 1.1rem; margin-right: 14px; width: 38px; height: 38px;
            display: flex; align-items: center; justify-content: center;
            background: #111; border-radius: 8px; border: 1px solid #333; transition: 0.3s;
        }
        .btn-back-chat:hover { background-color: var(--primary-red); color: white; border-color: var(--primary-red); }
        .ai-info h2 { font-size: 1rem; color: var(--accent-gold); margin-bottom: 2px; }
        .ai-info p  { font-size: 0.72rem; color: #888; }

        #chatContent {
            flex: 1; overflow-y: auto; padding: 18px;
            display: flex; flex-direction: column; gap: 14px; scroll-behavior: smooth;
        }
        #chatContent::-webkit-scrollbar { width: 5px; }
        #chatContent::-webkit-scrollbar-thumb { background: #333; border-radius: 4px; }

        .bubble {
            max-width: 82%; padding: 11px 14px; border-radius: 12px;
            font-size: 0.88rem; line-height: 1.5; position: relative; word-wrap: break-word;
        }
        .member   { align-self: flex-end; background-color: #E8C999; color: #000; border-bottom-right-radius: 2px; font-weight: 500; }
        .vanda-ai { align-self: flex-start; background-color: #1a1a1a; color: var(--text-light); border-bottom-left-radius: 2px; border: 1px solid #222; }
        .bubble img { max-width: 100%; border-radius: 8px; display: block; border: 1px solid rgba(0,0,0,0.2); margin-bottom: 8px; }
        .bubble p { margin-top: 0; }

        .typing-container { padding: 0 18px 8px; display: none; }
        .typing { font-style: italic; font-size: 0.78rem; color: var(--accent-gold); animation: blink 1.5s infinite; }
        @keyframes blink { 0% { opacity: 0.4; } 50% { opacity: 1; } 100% { opacity: 0.4; } }

        .chat-footer {
            background: #050505; padding: 12px 14px; border-top: 1px solid #222;
            display: flex; flex-direction: column; gap: 8px; position: relative;
        }
        .attach-menu {
            display: none; position: absolute; bottom: 75px; left: 14px;
            background: #111; border: 1px solid #333; border-radius: 8px;
            padding: 5px; box-shadow: 0 5px 15px rgba(0,0,0,0.8); z-index: 20; width: 195px;
        }
        .attach-menu button {
            width: 100%; text-align: left; background: transparent; border: none;
            color: var(--text-light); padding: 10px 14px; cursor: pointer;
            font-size: 0.85rem; transition: 0.3s; border-radius: 4px;
            display: flex; align-items: center; gap: 10px;
        }
        .attach-menu button svg { width: 17px; height: 17px; fill: var(--text-light); transition: 0.3s; }
        .attach-menu button:hover { background: #222; color: var(--accent-gold); }
        .attach-menu button:hover svg { fill: var(--accent-gold); }

        .preview-container {
            display: none; background: #111; border: 1px solid #333; border-radius: 8px;
            padding: 8px; position: relative; margin-bottom: 4px; width: fit-content;
        }
        .preview-container img { height: 70px; border-radius: 4px; border: 1px solid #222; }
        .btn-remove-img {
            position: absolute; top: -5px; right: -5px; background: var(--primary-red); color: white;
            border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 13px;
            cursor: pointer; display: flex; justify-content: center; align-items: center;
        }

        .input-wrapper { display: flex; gap: 7px; align-items: center; }
        .btn-attach {
            background: #111; border: 1px solid #333; width: 40px; height: 40px;
            border-radius: 50%; color: var(--accent-gold); cursor: pointer; transition: 0.3s;
            display: flex; justify-content: center; align-items: center; flex-shrink: 0;
        }
        .btn-attach:hover { background: #222; border-color: var(--accent-gold); }
        .btn-attach svg { width: 18px; height: 18px; fill: currentColor; }

        .chat-input {
            flex: 1; background: #111; border: 1px solid #333; padding: 10px 14px;
            border-radius: 22px; color: white; outline: none; font-size: 0.88rem;
        }
        .chat-input:focus { border-color: var(--accent-gold); }

        .btn-send {
            background: var(--primary-red); border: none; width: 40px; height: 40px;
            border-radius: 50%; color: white; cursor: pointer; transition: 0.3s;
            display: flex; justify-content: center; align-items: center; flex-shrink: 0;
        }
        .btn-send:hover { background: #a81a1a; transform: scale(1.05); }
        .btn-send svg { width: 17px; height: 17px; fill: white; margin-left: 2px; }
        .btn-send:disabled { background: #555; cursor: not-allowed; transform: none; }

        .disclaimer { font-size: 0.62rem; color: #555; text-align: center; }

        .wa-btn-cs {
            position: fixed; bottom: 20px; left: 20px; z-index: 9999;
            color: #ffffff; background: var(--primary-red, #ff4d4d);
            border-radius: 50%; padding: 12px; box-shadow: 0 4px 15px rgba(255,77,77,0.4);
            border: 2px solid #E8C999; transition: all 0.3s; display: flex;
            align-items: center; justify-content: center;
        }
        .wa-btn-cs:hover { transform: scale(1.1); }

        /* RESPONSIVE STYLING */
        @media screen and (max-width: 768px) {
            body { padding-bottom: 85px !important; }
            .wa-btn-cs { display: none !important; }

            header .menu-toggle { width: 36px; height: 36px; }
            header .menu-toggle .bar { width: 16px; }
            #nav-menu { top: 55px; right: 10px; left: auto; width: 195px; padding: 10px; }
            #nav-menu a.menu-link { font-size: 0.75rem; padding: 5px 6px; white-space: nowrap; }
            .nav-actions-dasbor .btn-keluar-menu { font-size: 0.75rem; padding: 7px 8px; }

            .chat-wrapper { padding: 8px 12px; min-height: calc(100vh - 140px); align-items: flex-start; }
            .chat-container { height: calc(100vh - 155px); max-height: none; border-radius: 8px; }
            .chat-inner-header { padding: 10px 12px; }
            .btn-back-chat { width: 32px; height: 32px; font-size: 0.9rem; margin-right: 10px; }
            .ai-info h2 { font-size: 0.82rem; }
            .ai-info p  { font-size: 0.62rem; }

            #chatContent { padding: 12px; gap: 10px; }
            .bubble { max-width: 78%; padding: 9px 11px; font-size: 0.8rem; }
            .bubble a { font-size: 0.72rem !important; }
            .typing-container { padding: 0 12px 6px; }
            .typing { font-size: 0.7rem; }

            .chat-footer { padding: 9px 11px; gap: 6px; }
            .attach-menu { bottom: 68px; left: 11px; width: 180px; }
            .attach-menu button { font-size: 0.78rem; padding: 8px 11px; }

            .btn-attach { width: 36px; height: 36px; }
            .btn-attach svg { width: 16px; height: 16px; }
            .chat-input { font-size: 0.78rem; padding: 8px 12px; }
            .btn-send { width: 36px; height: 36px; }
            .btn-send svg { width: 15px; height: 15px; }
            .disclaimer { font-size: 0.55rem; }

            .bottom-nav-mobile {
                display: flex !important; position: fixed !important;
                bottom: 0 !important; left: 0 !important; width: 100vw !important; height: 70px !important;
                background-color: #0a0a0a !important; border-top: 1px solid #333 !important;
                justify-content: space-around !important; align-items: center !important;
                z-index: 2147483647 !important; box-shadow: 0 -5px 15px rgba(0,0,0,0.9) !important;
            }
            .bottom-nav-mobile .nav-item {
                display: flex !important; flex-direction: column !important; align-items: center !important;
                justify-content: center !important; color: #ccc !important; text-decoration: none !important;
                font-size: 10px !important; background: transparent !important; border: none !important;
                flex: 1 !important; gap: 4px !important; cursor: pointer !important;
                padding: 5px 0 !important; transition: 0.3s;
            }
            .bottom-nav-mobile .nav-item:hover, .bottom-nav-mobile .nav-item:active { color: var(--accent-gold, #E8C999) !important; }
            .bottom-nav-mobile .nav-item svg {
                width: 22px !important; height: 22px !important; stroke: currentColor !important;
                fill: none !important; stroke-width: 2 !important; stroke-linecap: round !important;
                stroke-linejoin: round !important;
            }
            .bottom-nav-mobile .nav-item.highlight { color: var(--accent-gold, #E8C999) !important; font-weight: bold !important; }
            .bottom-nav-mobile .nav-item.highlight svg { stroke: var(--accent-gold, #E8C999) !important; }
            .bottom-nav-mobile .nav-item.locked-nav { opacity: 0.4; cursor: not-allowed; }

            @keyframes jelly {
                0%, 100% { transform: scale(1, 1); }
                25%  { transform: scale(0.8, 1.2); }
                50%  { transform: scale(1.2, 0.8); }
                75%  { transform: scale(0.95, 1.05); }
            }
            .bottom-nav-mobile .nav-item:active svg { animation: jelly 0.5s ease; }
        }

        @media (max-width: 480px) {
            .chat-wrapper { padding: 15px 25px; }
            .chat-container { border-radius: 6px; }
            .bubble { max-width: 65%; padding: 9px 11px; font-size: 0.7rem; }
        }
    </style>
</head>
<body>

    <header>
        <div class="logo">
            <img src="assets/logo.png" alt="Logo Vanda Gym">
        </div>

        <div class="header-right">
            <a href="perpanjang.php" class="bell-icon <?= $notif_merah ? 'active' : '' ?>" title="Tagihan Perpanjangan Membership">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <?php if ($notif_merah): ?>
                    <span class="bell-badge">!</span>
                <?php endif; ?>
            </a>
            <button class="menu-toggle" id="mobile-menu" aria-label="Toggle Menu">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>
        </div>

        <nav id="nav-menu">
            <a href="member_dasbor.php" class="menu-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                Dasbor
            </a>
            <a href="https://instagram.com/vandagympky_classic" target="_blank" class="menu-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
                Hubungi Kami
            </a>
            <a href="galeri_member.php" class="menu-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><path d="M21 15l-5-5L5 21"></path></svg>
                Galeri Gym
            </a>
            <a href="chatbot_member.php" class="menu-link active-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"></rect><circle cx="12" cy="5" r="2"></circle><path d="M12 7v4"></path><line x1="8" y1="16" x2="8.01" y2="16"></line><line x1="16" y1="16" x2="16.01" y2="16"></line></svg>
                Chatbot AI
            </a>
            <a href="kalkulator.php?source=dasbor" class="menu-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="8" y1="6" x2="16" y2="6"></line><line x1="16" y1="14" x2="16.01" y2="14"></line><line x1="12" y1="14" x2="12.01" y2="14"></line><line x1="8" y1="14" x2="8.01" y2="14"></line></svg>
                Kalkulator Gizi
            </a>
            <a href="profil_member.php" class="menu-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                Profil Saya
            </a>
            <div class="menu-divider"></div>
            <div class="nav-actions-dasbor">
                <a href="index.php" class="btn-keluar-menu">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline;vertical-align:middle;margin-right:5px;"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    Keluar dari Akun
                </a>
            </div>
        </nav>
    </header>

    <div class="chat-wrapper">
        <div class="chat-container">
            <div class="chat-inner-header">
                <a href="member_dasbor.php" class="btn-back-chat" title="Kembali ke Dasbor">←</a>
                <div class="ai-info">
                    <h2>Vanda AI Assistant</h2>
                    <p>Aktif • Didukung oleh Gemini AI</p>
                </div>
            </div>

            <div id="chatContent">
                <div class="bubble vanda-ai">
                    Halo Bro/Sis! 👋 Saya Vanda AI, instruktur virtual kamu. Mau tanya soal nutrisi, cek kalori makanan dari foto, atau pelajari form alat gym yang benar? Yuk, tanya aku sekarang! 💪🔥
                </div>
            </div>

            <div id="typingContainer" class="typing-container">
                <div class="typing">Vanda AI sedang mengetik balasan...</div>
            </div>

            <div class="chat-footer">
                <div id="attachMenu" class="attach-menu">
                    <button onclick="document.getElementById('fileCamera').click()">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3.2"/><path d="M9 2L7.17 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2h-3.17L15 2H9zm3 15c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5z"/></svg> Ambil Foto
                    </button>
                    <button onclick="document.getElementById('fileGallery').click()">
                        <svg viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg> Pilih dari Galeri
                    </button>
                </div>

                <input type="file" id="fileCamera"  accept="image/*" capture="environment" style="display: none;" onchange="previewGambar(this)">
                <input type="file" id="fileGallery" accept="image/*" style="display: none;" onchange="previewGambar(this)">

                <div id="previewContainer" class="preview-container">
                    <img id="imgPreview" src="" alt="Preview">
                    <button class="btn-remove-img" onclick="hapusPreview()">×</button>
                </div>

                <div class="input-wrapper">
                    <button class="btn-attach" onclick="toggleAttachMenu()" title="Lampirkan Gambar">
                        <svg viewBox="0 0 24 24"><path d="M16.5 6v11.5c0 2.21-1.79 4-4 4s-4-1.79-4-4V5a2.5 2.5 0 0 1 5 0v10.5c0 .55-.45 1-1 1s-1-.45-1-1V6H10v9.5a2.5 2.5 0 0 0 5 0V5c0-3.04-2.46-5.5-5.5-5.5S4 1.96 4 5v12.5c0 3.87 3.13 7 7 7s7-3.13 7-7V6h-1.5z"/></svg>
                    </button>
                    <input type="text" id="userInput" class="chat-input" placeholder="Ketik pesan atau caption..." autocomplete="off">
                    <button class="btn-send" id="btnSend" onclick="kirimChat()" title="Kirim Pesan">
                        <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                    </button>
                </div>

                <div class="disclaimer">
                    AI dapat memberikan informasi yang tidak akurat. Kebijakan Privasi Google berlaku. Bukan pengganti saran ahli gizi profesional.
                </div>
            </div>
        </div>
    </div>

    <div class="bottom-nav-mobile">
        <a href="member_dasbor.php" class="nav-item">
            <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span>Dasbor</span>
        </a>
        <a href="kalkulator.php?source=dasbor" class="nav-item">
            <svg viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="8" y1="6" x2="16" y2="6"></line><line x1="16" y1="14" x2="16.01" y2="14"></line><line x1="12" y1="14" x2="12.01" y2="14"></line><line x1="8" y1="14" x2="8.01" y2="14"></line></svg>
            <span>Gizi</span>
        </a>
        <a href="galeri_member.php" class="nav-item">
            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
            <span>Galeri</span>
        </a>
        <a href="chatbot_member.php" class="nav-item highlight">
            <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="10" rx="2"></rect><circle cx="12" cy="5" r="2"></circle><path d="M12 7v4"></path></svg>
            <span>AI Bot</span>
        </a>
        <a href="profil_member.php" class="nav-item">
            <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            <span>Profil</span>
        </a>
    </div>

    <a href="https://instagram.com/vandagympky_classic" target="_blank" class="wa-btn-cs" title="Hubungi CS via Instagram">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
        </svg>
    </a>

    <script>
        const menuToggle = document.getElementById('mobile-menu');
        const navMenu    = document.getElementById('nav-menu');

        menuToggle.addEventListener('click', () => {
            menuToggle.classList.toggle('active');
            navMenu.classList.toggle('active');
        });

        document.addEventListener('click', function(event) {
            const isClickInside = navMenu.contains(event.target) || menuToggle.contains(event.target);
            if (!isClickInside && navMenu.classList.contains('active')) {
                navMenu.classList.remove('active');
                menuToggle.classList.remove('active');
            }
        });

        const chatContent      = document.getElementById('chatContent');
        const userInput        = document.getElementById('userInput');
        const typingContainer  = document.getElementById('typingContainer');
        const attachMenu       = document.getElementById('attachMenu');
        const previewContainer = document.getElementById('previewContainer');
        const imgPreview       = document.getElementById('imgPreview');
        const btnSend          = document.getElementById('btnSend');

        let base64ImageTemp     = null;
        let draftPesanTerakhir  = "";
        let draftGambarTerakhir = null;

        function updateOnlineStatus() {
            if (!navigator.onLine) {
                userInput.disabled = true;
                btnSend.disabled   = true;
                userInput.placeholder = "Peringatan: Koneksi internet terputus...";
            } else {
                userInput.disabled = false;
                btnSend.disabled   = false;
                userInput.placeholder = "Ketik pesan atau caption...";
            }
        }
        window.addEventListener('online',  updateOnlineStatus);
        window.addEventListener('offline', updateOnlineStatus);
        updateOnlineStatus();

        function toggleAttachMenu() {
            attachMenu.style.display = attachMenu.style.display === 'block' ? 'none' : 'block';
        }

        function previewGambar(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                if(file.size > 4 * 1024 * 1024) {
                    alert('Ukuran gambar terlalu besar. Maksimal 4MB.');
                    input.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = new Image();
                    img.onload = function() {
                        const canvas = document.createElement('canvas');
                        const ctx    = canvas.getContext('2d');
                        let width    = img.width;
                        let height   = img.height;
                        const MAX_WIDTH = 800;
                        if (width > MAX_WIDTH) {
                            height = Math.round((height * MAX_WIDTH) / width);
                            width  = MAX_WIDTH;
                        }
                        canvas.width  = width;
                        canvas.height = height;
                        ctx.drawImage(img, 0, 0, width, height);
                        base64ImageTemp  = canvas.toDataURL('image/jpeg', 0.8);
                        imgPreview.src   = base64ImageTemp;
                        previewContainer.style.display = 'block';
                        attachMenu.style.display = 'none';
                        userInput.focus();
                    }
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
            input.value = '';
        }

        function hapusPreview() {
            base64ImageTemp = null;
            imgPreview.src  = "";
            previewContainer.style.display = 'none';
        }

        function kirimChat() {
            const pesan    = userInput.value.trim();
            const imgKirim = base64ImageTemp;
            if (pesan === "" && !imgKirim) return;
            if (!navigator.onLine) {
                alert("Koneksi internet Anda terputus. Silakan periksa jaringan Anda.");
                return;
            }
            draftPesanTerakhir  = pesan;
            draftGambarTerakhir = imgKirim;

            let isiBubble = "";
            if (imgKirim) isiBubble += `<img src="${imgKirim}" alt="Foto Upload">`;
            if (pesan !== "") isiBubble += `<p>${pesan.replace(/</g, "&lt;").replace(/>/g, "&gt;")}</p>`;

            tambahBubble(isiBubble, 'member');
            userInput.value = "";
            hapusPreview();
            attachMenu.style.display = 'none';
            prosesTanyaAPI(pesan, imgKirim);
        }

        function kembalikanInputKeForm() {
            if (draftPesanTerakhir)  userInput.value = draftPesanTerakhir;
            if (draftGambarTerakhir) {
                base64ImageTemp = draftGambarTerakhir;
                imgPreview.src  = base64ImageTemp;
                previewContainer.style.display = 'block';
            }
        }

        function prosesTanyaAPI(pesanTeks, base64Data) {
            typingContainer.style.display = 'block';
            chatContent.scrollTop = chatContent.scrollHeight;
            userInput.disabled = true;
            btnSend.disabled   = true;

            const formData = new FormData();
            formData.append('action', 'chat_ai');
            formData.append('pesan', pesanTeks);
            if (base64Data) formData.append('gambar', base64Data);

            fetch('chatbot_member.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                typingContainer.style.display = 'none';
                userInput.disabled = false;
                btnSend.disabled   = false;
                userInput.focus();

                if (data.status === 'success') {
                    tambahBubble(data.message, 'vanda-ai');
                    draftPesanTerakhir  = "";
                    draftGambarTerakhir = null;
                } else {
                    tambahBubble('❌ <strong style="color:var(--primary-red)">Terjadi Masalah:</strong><br>' + data.message + '<br><br><em>⚠️ Pesan dan gambar Anda telah dikembalikan ke kolom ketik.</em>', 'vanda-ai');
                    kembalikanInputKeForm();
                }
            })
            .catch(() => {
                typingContainer.style.display = 'none';
                userInput.disabled = false;
                btnSend.disabled   = false;
                tambahBubble('❌ <strong style="color:var(--primary-red)">Gagal terhubung ke Server AI.</strong><br>Pastikan koneksi internet Anda stabil.<br><br><em>⚠️ Pesan dan gambar Anda telah dikembalikan ke kolom ketik.</em>', 'vanda-ai');
                kembalikanInputKeForm();
            });
        }

        function tambahBubble(isiHTML, tipe) {
            const div    = document.createElement('div');
            div.className = `bubble ${tipe}`;
            div.innerHTML = isiHTML;
            chatContent.appendChild(div);
            chatContent.scrollTop = chatContent.scrollHeight;
        }

        userInput.addEventListener("keypress", function(event) {
            if (event.key === "Enter" && !userInput.disabled) kirimChat();
        });

        document.addEventListener('click', function(event) {
            const isClickInsideAttach = attachMenu.contains(event.target) || event.target.closest('.btn-attach');
            if (!isClickInsideAttach) attachMenu.style.display = 'none';
        });
    </script>
</body>
</html>