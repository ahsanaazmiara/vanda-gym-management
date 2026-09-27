<?php
require_once __DIR__ . '/session_init.php';
require_once __DIR__ . '/includes/koneksi.php';

// =========================================================
// AMBIL HARGA TERBARU DARI PENGATURAN WEB
// =========================================================
$q_pengaturan_harga = mysqli_query(
    $koneksi,
    "SELECT harga_bulanan, harga_harian, harga_senam
     FROM pengaturan_web
     WHERE id = 1
     LIMIT 1"
);

$pengaturan_harga = $q_pengaturan_harga
    ? mysqli_fetch_assoc($q_pengaturan_harga)
    : [];

$harga_bulanan = (int)($pengaturan_harga['harga_bulanan'] ?? 175000);
$harga_harian  = (int)($pengaturan_harga['harga_harian'] ?? 25000);
$harga_senam   = (int)($pengaturan_harga['harga_senam'] ?? 25000);

// =========================================================
// FUNGSI BANTU: VALIDASI NOMOR WA & PENGIRIMAN KODE OTP EMAIL
// =========================================================

/**
 * Normalisasi nomor WhatsApp ke bentuk lokal tanpa kode negara dan tanpa 0 depan.
 * Contoh untuk Indonesia (+62):
 * 081234567890   -> 81234567890
 * 81234567890    -> 81234567890
 * +6281234567890 -> 81234567890
 * 6281234567890  -> 81234567890
 */
function normalisasiNomorWALokal($kodeNegara, $nomorInput) {
    $kodeNegara = preg_replace('/\D/', '', (string) $kodeNegara);
    $raw = trim((string) $nomorInput);
    $digits = preg_replace('/\D/', '', $raw);

    if ($kodeNegara === '' || $digits === '') {
        return '';
    }

    // Format internasional yang jelas: +62..., 0062..., dst.
    if (strpos($raw, '+') === 0 && strpos($digits, $kodeNegara) === 0) {
        $digits = substr($digits, strlen($kodeNegara));
    } elseif (strpos($raw, '00') === 0) {
        $tanpa00 = substr($digits, 2);
        if (strpos($tanpa00, $kodeNegara) === 0) {
            $digits = substr($tanpa00, strlen($kodeNegara));
        }
    } elseif ($kodeNegara === '62' && strpos($digits, '62') === 0 && strlen($digits) >= 11) {
        // Pengguna Indonesia sering menulis 62812... tanpa tanda +
        $digits = substr($digits, 2);
    }

    // Hilangkan trunk prefix 0, mis. 0812... -> 812...
    $digits = preg_replace('/^0+/', '', $digits);

    return $digits;
}

/**
 * Validasi nomor HP/WhatsApp berdasarkan kode negara yang dipilih.
 * $kodeNegara: kode negara tanpa tanda plus, mis. "62", "1", "44".
 * $nomorLokal harus sudah dinormalisasi oleh normalisasiNomorWALokal().
 */
function validasiNomorWA($kodeNegara, $nomorLokal) {
    $kodeNegara = preg_replace('/\D/', '', (string) $kodeNegara);
    $nomorLokal = preg_replace('/\D/', '', (string) $nomorLokal);

    if ($kodeNegara === '' || $nomorLokal === '') {
        return false;
    }

    if ($kodeNegara === '62') {
        // Nomor seluler Indonesia: diawali 8, digit kedua 1-9, panjang wajar
        if (!preg_match('/^8[1-9][0-9]{7,10}$/', $nomorLokal)) {
            return false;
        }
    } else {
        // Negara lain: panjang wajar (6-14 digit), tidak diawali 0
        if (!preg_match('/^[1-9][0-9]{5,13}$/', $nomorLokal)) {
            return false;
        }
    }

    // Tolak pola asal-asalan: 8 digit terakhir sama semua (mis. ...11111111)
    $delapanAkhir = substr($nomorLokal, -8);
    if (strlen($delapanAkhir) === 8 && preg_match('/^(\d)\1{7}$/', $delapanAkhir)) {
        return false;
    }

    return true;
}

/**
 * Gabungkan kode negara + nomor lokal menjadi satu string nomor internasional
 * tanpa tanda plus/spasi, mis. "6281234567890". Format ini yang paling
 * kompatibel dipakai untuk link wa.me atau API WhatsApp lainnya.
 */
function gabungNomorWaLengkap($kodeNegara, $nomorLokal) {
    $kodeNegara = preg_replace('/\D/', '', (string) $kodeNegara);
    $nomorLokal = preg_replace('/\D/', '', (string) $nomorLokal);
    return $kodeNegara . $nomorLokal;
}

/**
 * Kirim kode OTP ke email menggunakan fungsi mail() bawaan PHP.
 * CATATAN: mail() membutuhkan MTA/mail server aktif di server hosting.
 * Ini biasanya bekerja di hosting produksi, tapi SERING TIDAK BEKERJA
 * di localhost/XAMPP tanpa konfigurasi tambahan. Jika mail() tidak
 * terkirim di lingkungan Anda, ganti isi fungsi ini dengan PHPMailer
 * + SMTP (mis. Gmail, Mailtrap, dsb).
 */
function kirimEmailOtp($emailTujuan, $kodeOtp) {
    $subjek = 'Kode Verifikasi Pendaftaran - Vanda Gym';
    $pesan  = "Kode verifikasi pendaftaran Anda di Vanda Gym adalah: $kodeOtp\n\n"
            . "Kode ini berlaku selama 5 menit. Jangan berikan kode ini kepada siapa pun, "
            . "termasuk pihak yang mengaku dari Vanda Gym.";

    $headers  = "From: Vanda Gym Classic <no-reply@vandagym.my.id>\r\n";
    $headers .= "Reply-To: no-reply@vandagym.my.id\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    $hasil = @mail($emailTujuan, $subjek, $pesan, $headers);

    if (!$hasil) {
        error_log('Gagal mengirim OTP pendaftaran ke: ' . $emailTujuan);
    }

    return $hasil;
}

// =========================================================
// AKSI: KIRIM KODE OTP KE EMAIL (verifikasi kepemilikan email)
// =========================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'kirim_otp') {
    header('Content-Type: application/json');

    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Format email tidak valid.']);
        exit;
    }

    // Cegah spam kirim ulang kode (jeda 60 detik)
    if (isset($_SESSION['otp_last_sent']) && (time() - $_SESSION['otp_last_sent']) < 60) {
        $sisa = 60 - (time() - $_SESSION['otp_last_sent']);
        echo json_encode(['status' => 'error', 'message' => "Tunggu $sisa detik sebelum meminta kode baru."]);
        exit;
    }

    $kode_otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    $_SESSION['otp_kode']      = $kode_otp;
    $_SESSION['otp_email']     = $email;
    $_SESSION['otp_exp']       = time() + (5 * 60); // berlaku 5 menit
    $_SESSION['otp_last_sent'] = time();

    if (kirimEmailOtp($email, $kode_otp)) {
        echo json_encode(['status' => 'success', 'message' => 'Kode verifikasi telah dikirim ke email Anda.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengirim email verifikasi. Periksa kembali alamat email Anda atau coba lagi.']);
    }
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'register') {
    header('Content-Type: application/json'); 

    $email_raw      = trim($_POST['regEmail'] ?? '');
    $kode_negara_raw = trim($_POST['regKodeNegara'] ?? '62');
    $wa_lokal_raw   = trim($_POST['regHp'] ?? '');
    $kode_otp_kirim = trim($_POST['kodeOtp'] ?? '');

    // -----------------------------------------------------
    // VALIDASI FORMAT EMAIL
    // -----------------------------------------------------
    if (!filter_var($email_raw, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Format email tidak valid.']);
        exit;
    }

    // -----------------------------------------------------
    // VERIFIKASI KODE OTP EMAIL (memastikan email benar milik pendaftar)
    // -----------------------------------------------------
    if (
        $kode_otp_kirim === '' ||
        !isset($_SESSION['otp_kode'], $_SESSION['otp_email'], $_SESSION['otp_exp']) ||
        $_SESSION['otp_email'] !== $email_raw ||
        time() > $_SESSION['otp_exp'] ||
        $kode_otp_kirim !== $_SESSION['otp_kode']
    ) {
        echo json_encode(['status' => 'error', 'message' => 'Kode verifikasi email salah, sudah kedaluwarsa, atau belum diminta. Silakan minta kode baru.']);
        exit;
    }

    // Kode OTP sudah valid dan hanya berlaku sekali pakai
    unset($_SESSION['otp_kode'], $_SESSION['otp_email'], $_SESSION['otp_exp']);

    // -----------------------------------------------------
    // NORMALISASI + VALIDASI NOMOR WHATSAPP
    // Pengguna boleh mengetik 0812..., 812..., +62812..., atau 62812...
    // -----------------------------------------------------
    $wa_lokal_normal = normalisasiNomorWALokal($kode_negara_raw, $wa_lokal_raw);

    if (!validasiNomorWA($kode_negara_raw, $wa_lokal_normal)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Nomor WhatsApp tidak valid. Periksa kode negara dan nomor yang Anda masukkan.'
        ]);
        exit;
    }

    $wa_lengkap_raw = gabungNomorWaLengkap($kode_negara_raw, $wa_lokal_normal); // mis. "6281234567890"

    $nama      = mysqli_real_escape_string($koneksi, $_POST['regNama']);
    $email     = mysqli_real_escape_string($koneksi, $email_raw);
    $wa        = mysqli_real_escape_string($koneksi, $wa_lengkap_raw);
    $password  = password_hash($_POST['regPass'], PASSWORD_DEFAULT);
    $durasi    = (int) ($_POST['regPaket'] ?? 0);
    $tgl_mulai = $_POST['regTgl'] ?? '';
    $metode    = $_POST['metodeBayar'] ?? '';

    // Form hanya mengirim durasi. Harga selalu dihitung ulang oleh server.
    if (!in_array($durasi, [1, 2, 3], true)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Paket membership tidak valid.'
        ]);
        exit;
    }

    if (!in_array($metode, ['qris', 'tunai'], true)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Metode pembayaran tidak valid.'
        ]);
        exit;
    }

    $q_harga_submit = mysqli_query(
        $koneksi,
        "SELECT harga_bulanan
         FROM pengaturan_web
         WHERE id = 1
         LIMIT 1"
    );

    $data_harga_submit = $q_harga_submit
        ? mysqli_fetch_assoc($q_harga_submit)
        : [];

    $harga_base = (int)($data_harga_submit['harga_bulanan'] ?? 175000);
    $harga = $harga_base * $durasi;

    $tgl_berakhir = date('Y-m-d', strtotime($tgl_mulai . " + $durasi months"));

    $id_user = 0;
    $cek_email = mysqli_query($koneksi, "SELECT id_user, role FROM users WHERE email='$email'");
    
    if (mysqli_num_rows($cek_email) > 0) {
        $data_u = mysqli_fetch_assoc($cek_email);
        
        if ($data_u['role'] === 'admin') {
            echo json_encode(['status' => 'error', 'message' => 'Email <strong>'.$email.'</strong> tidak dapat digunakan.']);
            exit;
        }

        $id_existing = $data_u['id_user'];
        $cek_m = mysqli_query($koneksi, "SELECT status FROM membership WHERE id_user='$id_existing' AND status != 'ditolak'");
        
        if (mysqli_num_rows($cek_m) > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Email <strong>'.$email.'</strong> sudah terdaftar dan masih memiliki transaksi Aktif/Pending/Kedaluwarsa. Sistem mencegah pembayaran ganda.']);
            exit;
        } else {
            $query_update = "UPDATE users SET nama_lengkap='$nama', no_wa='$wa', password='$password', role='calon_member' WHERE id_user='$id_existing'";
            if(mysqli_query($koneksi, $query_update)) {
                $id_user = $id_existing;
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui data user lama.']);
                exit;
            }
        }
    } else {
        $query_insert = "INSERT INTO users (nama_lengkap, email, no_wa, password, role) VALUES ('$nama', '$email', '$wa', '$password', 'calon_member')";
        if (mysqli_query($koneksi, $query_insert)) {
            $id_user = mysqli_insert_id($koneksi);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data user baru.']);
            exit;
        }
    }

    if ($id_user > 0) {
        $nama_file_bukti = NULL;
        if ($metode == 'qris' && isset($_FILES['regBukti']['name']) && $_FILES['regBukti']['name'] != '') {
            $ext = pathinfo($_FILES['regBukti']['name'], PATHINFO_EXTENSION);
            $nama_bersih = str_replace(' ', '_', preg_replace('/[^A-Za-z0-9 ]/', '', $nama));
            $nama_file_bukti = "Bukti_Daftar_" . $nama_bersih . "_" . date('dmy_His') . "." . $ext;
            
            // Menggunakan direktori uploads/ sesuai preferensi pengguna
            move_uploaded_file($_FILES['regBukti']['tmp_name'], 'uploads/' . $nama_file_bukti);
        }

        $query_member = "INSERT INTO membership (id_user, jenis_pengajuan, paket_bulan, total_harga, tgl_mulai, tgl_berakhir, metode_bayar, bukti_bayar, status) 
                         VALUES ($id_user, 'daftar', $durasi, $harga, '$tgl_mulai', '$tgl_berakhir', '$metode', '$nama_file_bukti', 'pending')";
        
        if (mysqli_query($koneksi, $query_member)) {
            echo json_encode(['status' => 'success']); 
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal memproses data paket.']);
        }
    }
    exit; 
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Member - Vanda Gym Classic</title>
    <style>
        :root { 
            --bg-dark: #000000; --primary-red: #dc3545; --accent-gold: #E8C999; 
            --text-light: #F8EEDF; --input-bg: #111111; --success-green: #28a745; 
            --warning-yellow: #ffc107;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--bg-dark); color: var(--text-light); display: flex; justify-content: center; align-items: flex-start; min-height: 100vh; padding: 40px 20px; }
        
        .pay-container { background-color: #0a0a0a; border: 1px solid #333; border-top: 4px solid var(--accent-gold); border-radius: 8px; padding: 30px; width: 100%; max-width: 650px; box-shadow: 0 10px 30px rgba(0,0,0,0.8); position: relative; }
        
        .nav-top { margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; }
        .btn-back-square { width: 40px; height: 40px; background-color: #1a1a1a; border: 1px solid #333; color: var(--accent-gold); border-radius: 4px; display: flex; align-items: center; justify-content: center; text-decoration: none; font-weight: bold; font-size: 1.2rem; transition: 0.3s; }
        .btn-back-square:hover { background-color: var(--primary-red); color: white; border-color: var(--primary-red); }
        
        .form-header { text-align: center; margin-bottom: 25px; }
        .form-header h2 { color: var(--text-light); text-transform: uppercase; font-size: 1.4rem; letter-spacing: 1px; margin-bottom: 5px;}
        .form-header h2 span { color: var(--accent-gold); }
        .form-header p { color: #888; font-size: 0.85rem; }
        
        .section-divider { border-bottom: 1px solid #222; margin: 25px 0 15px; padding-bottom: 8px; color: var(--accent-gold); font-weight: bold; text-transform: uppercase; font-size: 0.9rem; display: flex; justify-content: space-between; align-items: center;}
        
        .form-group { margin-bottom: 15px; text-align: left; }
        .form-group label { display: block; margin-bottom: 6px; color: #ccc; font-weight: 600; font-size: 0.8rem; }
        .form-control { width: 100%; padding: 10px 12px; background-color: var(--input-bg); border: 1px solid #333; border-radius: 4px; color: white; font-size: 0.9rem; transition: 0.3s; }
        .form-control:focus { outline: none; border-color: var(--accent-gold); }
        input[type="date"] { color-scheme: dark; cursor: pointer; }
        select { cursor: pointer; }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }

        .wa-input-group { display: flex; gap: 8px; align-items: stretch; }
        .wa-input-group .wa-nomor-lokal { flex: 1; min-width: 0; }

        /* Dropdown kode negara: tombol ringkas, daftar lengkap saat diklik */
        .country-select { position: relative; flex: 0 0 108px; }
        .country-select-button {
            width: 100%; height: 100%; min-height: 40px; padding: 8px 9px;
            background: var(--input-bg); border: 1px solid #333; border-radius: 4px;
            color: white; cursor: pointer; display: flex; align-items: center;
            justify-content: space-between; gap: 6px; font-size: 0.82rem;
            transition: 0.3s; white-space: nowrap;
        }
        .country-select-button:hover,
        .country-select.open .country-select-button { border-color: var(--accent-gold); }
        .country-select-arrow { color: var(--accent-gold); font-size: 0.72rem; transition: transform 0.2s; }
        .country-select.open .country-select-arrow { transform: rotate(180deg); }
        .country-menu {
            position: absolute; top: calc(100% + 6px); left: 0; z-index: 1200;
            width: 265px; max-height: 280px; overflow-y: auto;
            background: #111; border: 1px solid #444; border-radius: 6px;
            box-shadow: 0 12px 30px rgba(0,0,0,0.75); padding: 5px; display: none;
        }
        .country-select.open .country-menu { display: block; }
        .country-option {
            width: 100%; border: 0; background: transparent; color: #ddd;
            padding: 9px 10px; border-radius: 4px; cursor: pointer;
            display: flex; justify-content: space-between; align-items: center;
            gap: 12px; text-align: left; font-size: 0.78rem;
        }
        .country-option:hover, .country-option.active { background: #1d1d1d; color: white; }
        .country-option-name { font-weight: 600; }
        .country-option-code { color: var(--accent-gold); font-size: 0.72rem; white-space: nowrap; }
        .wa-helper { color: #777; font-size: 0.69rem; margin-top: 5px; line-height: 1.35; }
        
        /* Validasi Formulir Pendaftaran */
        .form-control.invalid-field { border-color: var(--primary-red) !important; background-color: #221111 !important; }
        .field-error-text { color: var(--primary-red); font-size: 0.75rem; margin-top: 4px; display: none; }
        .error-msg { color: var(--primary-red); font-size: 0.75rem; margin-top: 4px; display: none; }

        .payment-methods { display: flex; gap: 10px; margin-bottom: 15px; }
        .pay-method { flex: 1; border: 1px solid #333; border-radius: 4px; padding: 12px 10px; text-align: center; cursor: pointer; transition: 0.3s; background: #151515; position: relative; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .pay-method input { position: absolute; opacity: 0; cursor: pointer; }
        .pay-method span { font-weight: bold; color: #888; font-size: 0.85rem;}
        .pay-method.active { border-color: var(--accent-gold); background: rgba(232, 201, 153, 0.1); }
        .pay-method.active span { color: var(--accent-gold); }
        
        .pay-details { background: #111; border: 1px solid #222; padding: 20px; border-radius: 4px; margin-bottom: 20px; display: none; text-align: center; }
        .qris-box img { max-width: 150px; border-radius: 8px; margin: 10px 0; border: 2px solid white; background: #fff; padding: 5px; }
        .file-upload-wrapper { position: relative; margin-top: 15px; text-align: left; }
        .file-upload-wrapper input[type="file"] { position: absolute; left: 0; top: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
        .btn-upload { display: flex; align-items: center; justify-content: center; gap: 10px; background: #1a1a1a; border: 1px dashed var(--accent-gold); color: var(--accent-gold); padding: 10px; border-radius: 4px; width: 100%; font-size: 0.85rem; transition: 0.3s; }
        
        /* TOMBOL AKSI */
        .btn-action { width: 100%; border: none; min-height: 44px; font-size: 0.9rem; font-weight: bold; border-radius: 4px; cursor: pointer; text-transform: uppercase; transition: 0.3s; display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 15px; }
        .btn-success { background-color: var(--success-green); color: white; }
        .btn-success:hover { background-color: #218838; }
        .btn-success:disabled { background-color: #1e5c2b; color: #888; cursor: not-allowed; }
        .btn-outline { background-color: transparent; border: 1px solid #444; color: #aaa; margin-top: 10px; text-decoration: none;}
        .btn-outline:hover { border-color: var(--primary-red); color: var(--primary-red); }
        
        /* CHECKBOX KONFIRMASI */
        .checkbox-container { display: flex; align-items: flex-start; gap: 10px; margin: 20px 0; background: #151515; padding: 12px; border-radius: 4px; border: 1px solid #333; text-align: left; }
        .checkbox-container input { margin-top: 3px; cursor: pointer; width: 16px; height: 16px; accent-color: var(--success-green); }
        .checkbox-container label { font-size: 0.8rem; color: #ccc; cursor: pointer; line-height: 1.4; }

        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); display: none; justify-content: center; align-items: center; z-index: 1000; padding: 20px; }
        .modal-box { background: #111; border: 1px solid var(--accent-gold); padding: 25px; border-radius: 8px; width: 100%; max-width: 400px; }
        
        .draf-item { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .login-footer { margin-top: 25px; text-align: center; font-size: 0.85rem; display: flex; flex-direction: column; gap: 10px; }
        .login-footer a { color: var(--accent-gold); text-decoration: none; font-weight: bold; }

        .wa-btn { position: fixed; bottom: 30px; left: 30px; width: 55px; height: 55px;  color: white; border-radius: 50%; display: flex; justify-content: center; align-items: center; box-shadow: 0 4px 10px rgba(0,0,0,0.5); z-index: 1000; text-decoration: none; transition: 0.3s; }
        .wa-btn:hover { transform: scale(1.1) }
        .wa-btn svg { width: 30px; height: 30px; }

        /* ====================================================
           OPTIMASI TAMPILAN MOBILE (LAYAR KECIL)
           ==================================================== */
        /* ====================================================
           OPTIMASI TAMPILAN MOBILE (SUPER PADAT SEPERTI LOGIN)
           ==================================================== */
        @media (max-width: 768px) {
            body { 
                padding: 5px !important; 
                align-items: flex-start !important; /* Biarkan form mulai dari atas agar bisa di-scroll */
                min-height: 100vh !important;
            }
            
            .pay-container { 
                padding: 15px 15px !important; 
                margin: 10px auto 70px auto !important; 
                width: 92% !important; 
                max-width: 300px !important; /* Disamakan persis dengan form login */
                border-top-width: 3px !important;
                box-shadow: 0 5px 15px rgba(0,0,0,0.5) !important;
            }
            
            /* Navigasi & Header */
            .nav-top { margin-bottom: 8px !important; }
            .btn-back-square { width: 28px !important; height: 28px !important; font-size: 0.8rem !important; border-radius: 4px !important; }
            
            .form-header { margin-bottom: 15px !important; }
            .form-header h2 { font-size: 1.1rem !important; margin-bottom: 2px !important; }
            .form-header p { font-size: 0.65rem !important; line-height: 1.2 !important; }
            
            /* Section Divider */
            .section-divider { margin: 15px 0 8px 0 !important; padding-bottom: 4px !important; font-size: 0.7rem !important; }
            
            /* Form Input */
            .grid-2 { grid-template-columns: 1fr !important; gap: 8px !important; } /* 1 kolom dengan gap kecil */
            .form-group { margin-bottom: 8px !important; }
            .form-group label { font-size: 0.65rem !important; margin-bottom: 2px !important; }
            
            /* Tinggi form disamakan 32px */
            .form-control { padding: 6px 10px !important; font-size: 0.75rem !important; min-height: 32px !important; border-radius: 4px !important; }

            .wa-input-group { gap: 4px !important; }
            .country-select { flex: 0 0 76px !important; }
            .country-select-button { min-height: 32px !important; padding: 4px 6px !important; font-size: 0.66rem !important; }
            .country-menu { width: 230px !important; max-height: 230px !important; }
            .country-option { padding: 7px 8px !important; font-size: 0.68rem !important; }
            .country-option-code { font-size: 0.62rem !important; }
            .wa-helper { font-size: 0.58rem !important; margin-top: 3px !important; }
            
            /* Toggle Password */
            #togglePassword { min-height: 32px !important; min-width: 32px !important; right: 2px !important; }
            #eyeIcon { width: 14px !important; height: 14px !important; }
            
            /* Error text */
            .error-msg, .field-error-text { font-size: 0.6rem !important; margin-top: 2px !important; }
            
            /* Box Nominal & Pembayaran */
            #boxNominal { padding: 8px !important; margin-bottom: 8px !important; }
            #boxNominal span { font-size: 0.7rem !important; }
            #textNominal { font-size: 0.85rem !important; }
            
            .payment-methods { gap: 6px !important; margin-bottom: 8px !important; flex-direction: row !important; }
            .pay-method { padding: 8px 4px !important; flex: 1 !important; }
            .pay-method span { font-size: 0.65rem !important; white-space: nowrap !important; }
            
            .pay-details { padding: 10px !important; margin-bottom: 10px !important; font-size: 0.65rem !important; line-height: 1.3 !important; }
            .qris-box img { max-width: 120px !important; }
            .btn-upload { padding: 6px !important; font-size: 0.7rem !important; min-height: 32px !important; }
            
            /* Tombol Aksi */
            .btn-action { min-height: 32px !important; font-size: 0.75rem !important; margin-top: 8px !important; padding: 6px !important; border-radius: 4px !important; }
            
            /* Footer Login */
            .login-footer { margin-top: 15px !important; font-size: 0.7rem !important; flex-direction: column !important; }
            .login-footer a { padding: 4px 10px !important; font-size: 0.7rem !important; }
            
            /* Tombol WA Melayang */
            .wa-btn { bottom: 12px !important; left: 12px !important; width: 38px !important; height: 38px !important; padding: 8px !important; }
            .wa-btn svg { width: 18px !important; height: 18px !important; }
            
            /* Modal / Draf Konfirmasi */
            .modal-box { padding: 15px !important; width: 92% !important; max-width: 300px !important; }
            .draf-item { font-size: 0.7rem !important; margin-bottom: 4px !important; }
            .checkbox-container { padding: 8px !important; margin: 10px 0 !important; gap: 6px !important; }
            .checkbox-container input { width: 12px !important; height: 12px !important; margin-top: 2px !important; }
            .checkbox-container label { font-size: 0.65rem !important; line-height: 1.2 !important; }
        }
    </style>
</head>
<body>

    <div class="pay-container">
        <div class="nav-top">
            <a href="index.php" class="btn-back-square" title="Kembali ke Beranda">←</a>
        </div>

        <div class="form-header">
            <h2>Daftar <span>Membership</span></h2>
            <p>Lengkapi formulir untuk bergabung di Vanda Gym</p>
        </div>

        <form id="formPendaftaran" onsubmit="validasiDanBukaDraf(event)">
            
            <div class="section-divider">1. Data Pribadi</div>
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" id="regNama" name="regNama" class="form-control" placeholder="Masukkan nama lengkap">
                <div id="err_regNama" class="field-error-text">Nama tidak boleh kosong</div>
            </div>
            
            <div class="grid-2">
                <div class="form-group">
                    <label>Nomor WhatsApp</label>
                    <div class="wa-input-group">
                        <!-- Nilai kode negara yang dikirim ke PHP -->
                        <input type="hidden" id="regKodeNegara" name="regKodeNegara" value="62">

                        <!-- Di form hanya tampil kode singkat, mis. ID +62 -->
                        <div class="country-select" id="countrySelect">
                            <button type="button" class="country-select-button" id="countrySelectButton"
                                    onclick="toggleCountryMenu()" aria-haspopup="listbox" aria-expanded="false">
                                <span id="countrySelectedText">ID +62</span>
                                <span class="country-select-arrow">▼</span>
                            </button>

                            <!-- Saat diklik, nama negara diperjelas -->
                            <div class="country-menu" id="countryMenu" role="listbox">
                                <button type="button" class="country-option active" data-code="62" onclick="pilihNegara('62','ID','Indonesia','82123456789')"><span class="country-option-name">Indonesia</span><span class="country-option-code">ID · +62</span></button>
                                <button type="button" class="country-option" data-code="60" onclick="pilihNegara('60','MY','Malaysia','123456789')"><span class="country-option-name">Malaysia</span><span class="country-option-code">MY · +60</span></button>
                                <button type="button" class="country-option" data-code="65" onclick="pilihNegara('65','SG','Singapore','81234567')"><span class="country-option-name">Singapore</span><span class="country-option-code">SG · +65</span></button>
                                <button type="button" class="country-option" data-code="63" onclick="pilihNegara('63','PH','Philippines','9171234567')"><span class="country-option-name">Philippines</span><span class="country-option-code">PH · +63</span></button>
                                <button type="button" class="country-option" data-code="66" onclick="pilihNegara('66','TH','Thailand','812345678')"><span class="country-option-name">Thailand</span><span class="country-option-code">TH · +66</span></button>
                                <button type="button" class="country-option" data-code="84" onclick="pilihNegara('84','VN','Vietnam','912345678')"><span class="country-option-name">Vietnam</span><span class="country-option-code">VN · +84</span></button>
                                <button type="button" class="country-option" data-code="91" onclick="pilihNegara('91','IN','India','9876543210')"><span class="country-option-name">India</span><span class="country-option-code">IN · +91</span></button>
                                <button type="button" class="country-option" data-code="86" onclick="pilihNegara('86','CN','China','13800138000')"><span class="country-option-name">China</span><span class="country-option-code">CN · +86</span></button>
                                <button type="button" class="country-option" data-code="81" onclick="pilihNegara('81','JP','Japan','9012345678')"><span class="country-option-name">Japan</span><span class="country-option-code">JP · +81</span></button>
                                <button type="button" class="country-option" data-code="82" onclick="pilihNegara('82','KR','South Korea','1012345678')"><span class="country-option-name">South Korea</span><span class="country-option-code">KR · +82</span></button>
                                <button type="button" class="country-option" data-code="61" onclick="pilihNegara('61','AU','Australia','412345678')"><span class="country-option-name">Australia</span><span class="country-option-code">AU · +61</span></button>
                                <button type="button" class="country-option" data-code="1" onclick="pilihNegara('1','US','United States','2125550123')"><span class="country-option-name">United States</span><span class="country-option-code">US · +1</span></button>
                                <button type="button" class="country-option" data-code="44" onclick="pilihNegara('44','UK','United Kingdom','7123456789')"><span class="country-option-name">United Kingdom</span><span class="country-option-code">UK · +44</span></button>
                                <button type="button" class="country-option" data-code="971" onclick="pilihNegara('971','AE','United Arab Emirates','501234567')"><span class="country-option-name">United Arab Emirates</span><span class="country-option-code">AE · +971</span></button>
                                <button type="button" class="country-option" data-code="966" onclick="pilihNegara('966','SA','Saudi Arabia','501234567')"><span class="country-option-name">Saudi Arabia</span><span class="country-option-code">SA · +966</span></button>
                            </div>
                        </div>

                        <input type="tel" id="regHp" name="regHp" class="form-control wa-nomor-lokal"
                               inputmode="tel" autocomplete="tel"
                               oninput="formatInputWA(this)" onblur="cekFormatWA()"
                               placeholder="82123456789">
                    </div>
                    <div id="waHelper" class="wa-helper">Contoh: 82123456789</div>
                    <div id="errorHp" class="error-msg">Nomor hanya boleh berisi angka dan tanda pemisah umum.</div>
                    <div id="errorFormatHp" class="error-msg">Nomor WhatsApp tidak valid untuk kode negara yang dipilih.</div>
                    <div id="err_regHp" class="field-error-text">Nomor WhatsApp tidak boleh kosong</div>
                </div>
                <div class="form-group">
                    <label>Alamat Email</label>
                    <input type="email" id="regEmail" name="regEmail" class="form-control" placeholder="nama@email.com">
                    <div id="err_regEmail" class="field-error-text">Alamat Email tidak boleh kosong</div>
                </div>
            </div>

            <div class="section-divider">2. Keamanan Akun</div>
            <div class="form-group">
                <label>Password Akun</label>
                <div style="position: relative;">
                    <input type="password" id="regPass" name="regPass" class="form-control" placeholder="Kombinasi angka & huruf" oninput="cekPassword(this)" style="padding-right: 50px;">
                    <span id="togglePassword" onclick="toggleVisibility()" style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); cursor: pointer; min-height: 38px; min-width: 38px; display: flex; align-items: center; justify-content: center; z-index: 10;">
                        <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </span>
                </div>
                <div id="errorPass" class="error-msg">Gunakan minimal satu huruf dan satu angka.</div>
                <div id="err_regPass" class="field-error-text">Password tidak boleh kosong</div>
                <p style="font-size: 0.75rem; color: #888; margin-top: 4px;">Gunakan email Anda sebagai identitas login nantinya.</p>
            </div>

            <div class="section-divider">3. Paket Latihan & Pembayaran</div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Pilih Durasi</label>
                    <select id="regPaket" name="regPaket" class="form-control" onchange="updateNominal()">
                        <option value="" disabled selected>-- Pilih Paket --</option>
                        <option value="1" data-harga="<?= $harga_bulanan ?>" data-nama="1 Bulan Gym">
                            1 Bulan Gym (Rp <?= number_format($harga_bulanan, 0, ',', '.') ?>)
                        </option>
                        <option value="2" data-harga="<?= $harga_bulanan * 2 ?>" data-nama="2 Bulan Gym">
                            2 Bulan Gym (Rp <?= number_format($harga_bulanan * 2, 0, ',', '.') ?>)
                        </option>
                        <option value="3" data-harga="<?= $harga_bulanan * 3 ?>" data-nama="3 Bulan Gym">
                            3 Bulan Gym (Rp <?= number_format($harga_bulanan * 3, 0, ',', '.') ?>)
                        </option>
                    </select>
                    <div id="err_regPaket" class="field-error-text">Paket latihan wajib dipilih</div>
                </div>
                <div class="form-group">
                    <label>Tanggal Mulai</label>
                    <input type="date" id="regTgl" name="regTgl" class="form-control">
                    <div id="err_regTgl" class="field-error-text">Tanggal mulai wajib diisi</div>
                </div>
            </div>

            <div id="boxNominal" style="display: none; justify-content: space-between; align-items: center; background: rgba(232, 201, 153, 0.1); border: 1px dashed var(--accent-gold); padding: 12px; border-radius: 4px; margin-bottom: 15px;">
                <span style="font-size: 0.85rem; color: var(--text-light);">Total Tagihan:</span>
                <span id="textNominal" style="font-size: 1.1rem; font-weight: bold; color: var(--accent-gold);">Rp 0</span>
            </div>

            <div class="form-group">
                <label>Metode Pembayaran</label>
                <div class="payment-methods">
                    <label class="pay-method active" id="labelQris">
                        <input type="radio" name="metodeBayar" value="qris" checked onchange="ubahMetode()">
                        <span>📱 QRIS / Transfer</span>
                    </label>
                    <label class="pay-method" id="labelTunai">
                        <input type="radio" name="metodeBayar" value="tunai" onchange="ubahMetode()">
                        <span>💵 Tunai (Kasir)</span>
                    </label>
                </div>
            </div>

            <div id="detailQris" class="pay-details" style="display: block;">
                <div class="qris-box">
                    <p style="font-size: 0.8rem; color: #ccc;">Scan QR Code atau transfer ke:<br><strong>BCA 123-456-789 a.n Vanda Gym</strong></p>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=Pembayaran+Member+Baru+Vanda+Gym" alt="QRIS Vanda Gym">
                </div>
                <div class="file-upload-wrapper">
                    <div class="btn-upload">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/></svg>
                        <span id="namaFile">Upload Bukti Transfer *</span>
                    </div>
                    <input type="file" id="regBukti" name="regBukti" accept="image/*" onchange="tampilkanNamaFile(this)">
                    <div id="err_regBukti" class="field-error-text">Bukti transfer wajib diunggah</div>
                </div>
            </div>

            <div id="detailTunai" class="pay-details">
                <p style="font-size: 0.8rem; color: #888;">
                    <strong>Kirim draf pendaftaran ini</strong> lalu datang ke resepsionis untuk melakukan pembayaran tunai agar akun dapat aktif.
                </p>
            </div>

            <button type="submit" class="btn-action btn-success">Kirim Pendaftaran</button>

            <div class="login-footer" style="display: flex; flex-direction: column; gap: 15px; margin-top: 20px;">
    <!-- Tombol Cek Status -->
    <div>
        <span style="color: #888;">Menunggu verifikasi?</span>
        <a href="cek_status.php" style="
            color: #E8C999; 
            padding: 5px 15px; 
            border-radius: 5px; 
            text-decoration: none; 
            margin-left: 10px;
            transition: 0.3s;
        " onmouseover="this.style.background='#E8C999'; this.style.color='#000';" 
           onmouseout="this.style.background='transparent'; this.style.color='#E8C999';">
           Cek Status
        </a>
    </div>

    <!-- Tombol Login -->
    <div>
        <span style="color: #888;">Sudah punya akun?</span>
        <a href="login.php" style="
            color: #E8C999; 
            padding: 5px 15px; 
            border-radius: 5px; 
            text-decoration: none; 
            margin-left: 10px;
            transition: 0.3s;
        " onmouseover="this.style.background='#E8C999'; this.style.color='#000';" 
           onmouseout="this.style.background='transparent'; this.style.color='#E8C999';">
           Login
        </a>
    </div>
</div>
</div>
                
            </div>
        </form>
    </div>

    <div class="modal-overlay" id="modalOverlay">
        <div class="modal-box" id="modalContent"></div>
    </div>

    <a href="https://instagram.com/vandagympky_classic" target="_blank" class="wa-btn" title="Hubungi CS via Instagram" style="position: fixed; bottom: 20px; left: 20px; z-index: 9999; color: #ffffff; background: var(--primary-red, #ff4d4d); border-radius: 50%; padding: 12px; box-shadow: 0 4px 15px rgba(255, 77, 77, 0.4); border: 2px solid #E8C999; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
        <circle cx="12" cy="7" r="4"></circle>
    </svg>
</a>

    <script>
        // ====================================================
        // KODE NEGARA + FORMAT NOMOR WHATSAPP
        // ====================================================
        function toggleCountryMenu() {
            const wrapper = document.getElementById('countrySelect');
            const btn = document.getElementById('countrySelectButton');
            const isOpen = wrapper.classList.toggle('open');
            btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }

        function tutupCountryMenu() {
            const wrapper = document.getElementById('countrySelect');
            const btn = document.getElementById('countrySelectButton');
            if (!wrapper || !btn) return;
            wrapper.classList.remove('open');
            btn.setAttribute('aria-expanded', 'false');
        }

        function pilihNegara(kode, singkat, nama, contoh) {
            document.getElementById('regKodeNegara').value = kode;
            document.getElementById('countrySelectedText').innerText = `${singkat} +${kode}`;

            const input = document.getElementById('regHp');
            input.placeholder = contoh;

            const helper = document.getElementById('waHelper');
            helper.innerText = `Contoh: ${contoh}`;

            document.querySelectorAll('.country-option').forEach(item => {
                item.classList.toggle('active', item.dataset.code === kode);
            });

            tutupCountryMenu();
            cekFormatWA();
            input.focus();
        }

        // Biarkan pengguna mengetik format yang familiar: 0812..., +62..., spasi, -, (), dll.
        function formatInputWA(input) {
            const error = document.getElementById('errorHp');
            const awal = input.value;

            // Hapus karakter selain angka dan pemisah nomor yang umum.
            let bersih = awal.replace(/[^0-9+()\s.-]/g, '');

            // Tanda + hanya boleh satu kali dan hanya di paling depan.
            if (bersih.includes('+')) {
                bersih = (bersih.startsWith('+') ? '+' : '') + bersih.replace(/\+/g, '');
            }

            input.value = bersih;

            if (awal !== bersih) {
                error.style.display = 'block';
            } else {
                error.style.display = 'none';
            }

            // Hilangkan status error format saat pengguna masih memperbaiki input.
            document.getElementById('errorFormatHp').style.display = 'none';
            input.classList.remove('invalid-field');
        }

        function normalisasiNomorWALokal(kodeNegara, nomorInput) {
            kodeNegara = (kodeNegara || '').replace(/\D/g, '');
            const raw = (nomorInput || '').trim();
            let digits = raw.replace(/\D/g, '');

            if (!kodeNegara || !digits) return '';

            if (raw.startsWith('+') && digits.startsWith(kodeNegara)) {
                digits = digits.slice(kodeNegara.length);
            } else if (raw.startsWith('00')) {
                const tanpa00 = digits.slice(2);
                if (tanpa00.startsWith(kodeNegara)) {
                    digits = tanpa00.slice(kodeNegara.length);
                }
            } else if (kodeNegara === '62' && digits.startsWith('62') && digits.length >= 11) {
                digits = digits.slice(2);
            }

            digits = digits.replace(/^0+/, '');
            return digits;
        }

        function validasiNomorWA(kodeNegara, nomorInput) {
            kodeNegara = (kodeNegara || '').replace(/\D/g, '');
            const nomorLokal = normalisasiNomorWALokal(kodeNegara, nomorInput);

            if (!kodeNegara || !nomorLokal) return false;

            if (kodeNegara === '62') {
                if (!/^8[1-9][0-9]{7,10}$/.test(nomorLokal)) return false;
            } else {
                if (!/^[1-9][0-9]{5,13}$/.test(nomorLokal)) return false;
            }

            const delapanAkhir = nomorLokal.slice(-8);
            if (delapanAkhir.length === 8 && /^(\d)\1{7}$/.test(delapanAkhir)) return false;

            return true;
        }

        function formatNomorInternasional(kodeNegara, nomorInput) {
            const lokal = normalisasiNomorWALokal(kodeNegara, nomorInput);
            if (!lokal) return `+${kodeNegara}`;

            // Hanya untuk tampilan ringkas di modal konfirmasi.
            if (kodeNegara === '62' && lokal.length >= 7) {
                const awal = lokal.slice(0, 3);
                const tengah = lokal.slice(3, 7);
                const akhir = lokal.slice(7);
                return `+62 ${awal} ${tengah}${akhir ? ' ' + akhir : ''}`;
            }

            return `+${kodeNegara} ${lokal.replace(/(\d{3})(?=\d)/g, '$1 ').trim()}`;
        }

        function cekFormatWA() {
            const kodeNegara = document.getElementById('regKodeNegara').value;
            const input = document.getElementById('regHp');
            const errorFormat = document.getElementById('errorFormatHp');

            if (input.value.trim().length > 0 && !validasiNomorWA(kodeNegara, input.value)) {
                errorFormat.style.display = 'block';
                input.classList.add('invalid-field');
                return false;
            }

            errorFormat.style.display = 'none';
            input.classList.remove('invalid-field');
            return true;
        }

        // Tutup dropdown jika pengguna klik di luar atau menekan Escape.
        document.addEventListener('click', function(e) {
            const wrapper = document.getElementById('countrySelect');
            if (wrapper && !wrapper.contains(e.target)) tutupCountryMenu();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') tutupCountryMenu();
        });

        function cekPassword(input) {
            const error = document.getElementById('errorPass');
            const regex = /^(?=.*[0-9])(?=.*[a-zA-Z])([a-zA-Z0-9]+)$/;
            if (!regex.test(input.value) && input.value.length > 0) {
                error.style.display = 'block';
                input.classList.add('invalid-field');
            } else {
                error.style.display = 'none';
                input.classList.remove('invalid-field');
            }
        }

        function toggleVisibility() {
            const passwordInput = document.getElementById('regPass');
            const eyeIcon = document.getElementById('eyeIcon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML = `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>`;
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>`;
            }
        }

        function updateNominal() {
            const paket = document.getElementById('regPaket');
            const boxNominal = document.getElementById('boxNominal');
            const textNominal = document.getElementById('textNominal');

            if (paket.value) {
                const option = paket.options[paket.selectedIndex];
                const harga = parseInt(option.dataset.harga || 0);

                boxNominal.style.display = 'flex';
                textNominal.innerText = "Rp " + harga.toLocaleString('id-ID');
            } else {
                boxNominal.style.display = 'none';
            }
        }

        function ubahMetode() {
            const isQris = document.querySelector('input[name="metodeBayar"]:checked').value === 'qris';
            document.getElementById('labelQris').classList.toggle('active', isQris);
            document.getElementById('labelTunai').classList.toggle('active', !isQris);
            document.getElementById('detailQris').style.display = isQris ? 'block' : 'none';
            document.getElementById('detailTunai').style.display = isQris ? 'none' : 'block';
        }

        function tampilkanNamaFile(input) {
            const namaFileEl = document.getElementById('namaFile');
            if (input.files && input.files[0]) {
                namaFileEl.innerText = input.files[0].name;
                namaFileEl.style.color = "var(--text-light)";
            } else {
                namaFileEl.innerText = "Upload Bukti Transfer *";
                namaFileEl.style.color = "var(--accent-gold)";
            }
        }

        function validasiFormKosong() {
            let isValid = true;
            const fields = [
                { id: 'regNama' }, { id: 'regHp' }, { id: 'regEmail' }, 
                { id: 'regPass' }, { id: 'regPaket' }, { id: 'regTgl' }
            ];

            fields.forEach(field => {
                const el = document.getElementById(field.id);
                const errEl = document.getElementById('err_' + field.id);
                if (!el.value || el.value.trim() === "") {
                    el.classList.add('invalid-field');
                    errEl.style.display = 'block';
                    isValid = false;
                } else {
                    el.classList.remove('invalid-field');
                    errEl.style.display = 'none';
                }
            });

            // Nomor WA sudah diisi, tapi cek juga formatnya benar-benar valid untuk kode negara yang dipilih
            const elHp = document.getElementById('regHp');
            const kodeNegaraDipilih = document.getElementById('regKodeNegara').value;
            if (elHp.value.trim() !== "" && !validasiNomorWA(kodeNegaraDipilih, elHp.value)) {
                elHp.classList.add('invalid-field');
                document.getElementById('errorFormatHp').style.display = 'block';
                isValid = false;
            }

            const metode = document.querySelector('input[name="metodeBayar"]:checked').value;
            if (metode === 'qris') {
                const bukti = document.getElementById('regBukti');
                const errBukti = document.getElementById('err_regBukti');
                if (bukti.files.length === 0) {
                    document.querySelector('.btn-upload').style.borderColor = "var(--primary-red)";
                    document.querySelector('.btn-upload').style.color = "var(--primary-red)";
                    errBukti.style.display = 'block';
                    isValid = false;
                } else {
                    document.querySelector('.btn-upload').style.borderColor = "var(--accent-gold)";
                    document.querySelector('.btn-upload').style.color = "var(--accent-gold)";
                    errBukti.style.display = 'none';
                }
            }
            return isValid;
        }

        let otpCooldownTimer = null;

        // Cek apakah checkbox persetujuan dicentang DAN kode OTP sudah diisi 6 digit
        function cekSyaratFinal() {
            const chk = document.getElementById('chkYakin');
            const otp = document.getElementById('inputOtp');
            const btn = document.getElementById('btnFinalBayar');
            if (!chk || !otp || !btn) return;
            btn.disabled = !(chk.checked && otp.value.trim().length === 6);
        }

        function mulaiCooldownOtp(btn, detik) {
            let sisa = detik;
            btn.innerText = `Kirim Ulang (${sisa}s)`;
            clearInterval(otpCooldownTimer);
            otpCooldownTimer = setInterval(() => {
                sisa--;
                if (sisa <= 0) {
                    clearInterval(otpCooldownTimer);
                    btn.innerText = 'Kirim Ulang Kode';
                    btn.disabled = false;
                } else {
                    btn.innerText = `Kirim Ulang (${sisa}s)`;
                }
            }, 1000);
        }

        function kirimOtpEmail(email) {
            const btn = document.getElementById('btnKirimOtp');
            const statusEl = document.getElementById('otpStatusMsg');
            if (!btn || !statusEl) return;

            btn.disabled = true;
            statusEl.style.color = '#888';
            statusEl.innerText = 'Mengirim kode...';

            fetch('daftar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=kirim_otp&email=' + encodeURIComponent(email)
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    statusEl.style.color = 'var(--success-green)';
                    statusEl.innerText = data.message;
                    const wrapper = document.getElementById('otpInputWrapper');
                    if (wrapper) wrapper.style.display = 'block';
                    mulaiCooldownOtp(btn, 60);
                } else {
                    statusEl.style.color = 'var(--primary-red)';
                    statusEl.innerText = data.message;
                    btn.disabled = false;
                }
            })
            .catch(() => {
                statusEl.style.color = 'var(--primary-red)';
                statusEl.innerText = 'Gagal terhubung ke server.';
                btn.disabled = false;
            });
        }

        function validasiDanBukaDraf(e) {
            e.preventDefault();
            if (!validasiFormKosong()) {
                alert("Harap lengkapi seluruh kolom formulir yang bertanda merah.");
                return;
            }

            const namaLengkap = document.getElementById('regNama').value;
            const noHp = document.getElementById('regHp').value;
            const kodeNegara = document.getElementById('regKodeNegara').value;
            const nomorTampil = formatNomorInternasional(kodeNegara, noHp);
            const email = document.getElementById('regEmail').value;
            const pass = document.getElementById('regPass').value;
            const tglMulai = document.getElementById('regTgl').value;
            const metode = document.querySelector('input[name="metodeBayar"]:checked').value;
            
            const regex = /^(?=.*[0-9])(?=.*[a-zA-Z])([a-zA-Z0-9]+)$/;
            if (!regex.test(pass)) {
                alert("Harap perbaiki kriteria password sebelum melanjutkan.");
                return;
            }

            const selectPaket = document.getElementById('regPaket');
            const selectedOption = selectPaket.options[selectPaket.selectedIndex];
            const namaPaket = selectedOption.getAttribute('data-nama');
            const harga = parseInt(selectedOption.getAttribute('data-harga') || 0);
            const hargaPaket = "Rp " + harga.toLocaleString('id-ID');

            const modal = document.getElementById('modalOverlay');
            const content = document.getElementById('modalContent');
            modal.style.display = 'flex';
            
            // Draf ringkasan ditampilkan sebelum checkbox persetujuan
            content.innerHTML = `
                <h3 style="color:var(--text-light); text-transform:uppercase; text-align:center; font-size:1.1rem; letter-spacing:1px; margin-bottom:5px;">Konfirmasi Data</h3>
                <div style="margin:20px 0; font-size: 0.85rem; color:#ccc;">
                    <div class="draf-item"><span style="color:#888;">Nama:</span> <span style="text-align:right; color:white;">${namaLengkap}</span></div>
                    <div class="draf-item"><span style="color:#888;">Kontak:</span> <span style="text-align:right; color:white;">${nomorTampil} <br> ${email}</span></div>
                    <div class="draf-item"><span style="color:#888;">Paket Latihan:</span> <span style="text-align:right; color:white;">${namaPaket} <br> Mulai: ${tglMulai}</span></div>
                    <div class="draf-item"><span style="color:#888;">Metode:</span> <span style="text-align:right; color:white; text-transform: uppercase;">${metode}</span></div>
                    <div class="draf-item" style="border-top:1px dashed #333; margin-top:10px; padding-top:15px;">
                        <span style="color:var(--text-light); font-weight:bold;">Total Tagihan:</span> 
                        <span style="color:var(--accent-gold); font-weight:bold; font-size:1.1rem;">${hargaPaket}</span>
                    </div>
                </div>
                
                <div class="form-group" style="margin-top:20px;">
                    <label>Verifikasi Email</label>
                    <p style="font-size:0.75rem; color:#888; margin-bottom:8px;">Kami akan mengirim kode 6 digit ke <strong style="color:var(--accent-gold);">${email}</strong> untuk memastikan email ini benar-benar milik Anda.</p>
                    <button type="button" id="btnKirimOtp" class="btn-action" style="background:#1a1a1a; border:1px solid var(--accent-gold); color:var(--accent-gold);" onclick="kirimOtpEmail('${email}')">Kirim Kode ke Email</button>
                    <div id="otpStatusMsg" style="font-size:0.75rem; margin-top:6px;"></div>
                    <div id="otpInputWrapper" style="display:none; margin-top:10px;">
                        <input type="text" id="inputOtp" class="form-control" maxlength="6" inputmode="numeric" placeholder="Masukkan 6 digit kode" oninput="cekSyaratFinal()">
                    </div>
                </div>

                <div class="checkbox-container">
                    <input type="checkbox" id="chkYakin" onchange="cekSyaratFinal()">
                    <label for="chkYakin">Saya yakin data dan bukti pembayaran yang saya masukkan sudah benar dan sesuai.</label>
                </div>
                
                <button id="btnFinalBayar" class="btn-action btn-success" style="margin-top:0;" onclick="kirimFinal('${metode}', '${email}')" disabled>Kirim Pendaftaran</button>
                <button type="button" class="btn-action btn-outline" onclick="document.getElementById('modalOverlay').style.display='none'">Batal & Edit</button>
            `;
        }

        
        function kirimFinal(metode, email) {
            const content = document.getElementById('modalContent');
            const form = document.getElementById('formPendaftaran');

            // Ambil kode OTP dari modal SEBELUM isi modal diganti
            const otpEl = document.getElementById('inputOtp');
            const kodeOtp = otpEl ? otpEl.value.trim() : '';

            content.innerHTML = `<div style="text-align:center;"><p style="font-weight:bold; font-size:0.9rem; color:var(--accent-gold);">Menyimpan data...</p><p style="color:#888; font-size:0.8rem; margin-top:10px;">Mohon tunggu sebentar.</p></div>`;

            const formData = new FormData(form);
            formData.append('action', 'register');
            formData.append('kodeOtp', kodeOtp);

            fetch('daftar.php', { method: 'POST', body: formData })
            .then(response => {
                if(!response.ok) throw new Error('Offline');
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    let pesanStatus = (metode === 'tunai') ? `<strong style="color: var(--warning-yellow);">Menunggu Pembayaran</strong>` : `<strong style="color: var(--warning-yellow);">Sedang Diproses</strong>`;
                    let instruksi = (metode === 'tunai') ? `Silakan datang ke resepsionis Vanda Gym untuk melakukan pembayaran tunai.` : `Admin sedang memverifikasi bukti pembayaran Anda.`;
                    let tombolIg = "";

                    if (metode !== 'tunai') {
                        // Siapkan pesan yang akan di-copy
                        const pesanIg = `Halo Admin Vanda Gym, saya baru saja melakukan pendaftaran member baru dengan email ${email}. Tolong dicek ya. Terima kasih.`;

                        // Panggil fungsi salin otomatis lalu buka IG
                        tombolIg = `
                        <button onclick="salinDanBukaIG('${pesanIg}')" class="btn-action" style="background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); color: white; text-decoration: none; font-size: 0.8rem; margin-top: 15px; border: none; width: 100%; cursor: pointer;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 5px;">
                                <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                                <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                                <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                            </svg>
                            Konfirmasi via DM IG
                        </button>
                        <p style="font-size:0.65rem; color:#888; text-align:center; margin-top:5px;">(Teks akan disalin otomatis, cukup tekan 'Paste/Tempel' di IG)</p>`;
                    }

                    content.innerHTML = `
                        <h3 style="color:var(--success-green); text-align:center; font-size:1.2rem; text-transform:uppercase;">Berhasil!</h3>
                        <p style="margin:5px 0 15px 0; text-align:center; font-size:0.85rem; color:#ccc;">Status: ${pesanStatus}</p>
                        <div style="background:#151515; padding:15px; border:1px solid #333; border-radius:4px; font-size:0.8rem; line-height:1.5;">
                            <strong style="color:white; display:block; margin-bottom:5px;">Langkah Selanjutnya:</strong>
                            <span style="color:#aaa;">${instruksi}</span>
                            ${tombolIg}
                        </div>
                        <button class="btn-action btn-success" onclick="window.location.href='cek_status.php'">Cek Status Pendaftaran</button>
                    `;
                } else {
                    content.innerHTML = `
                        <h3 style="color:var(--primary-red); text-align:center; font-size:1.2rem; text-transform:uppercase;">Gagal!</h3>
                        <p style="margin:15px 0; text-align:center; font-size: 0.85rem; color:#ccc;">${data.message}</p>
                        <button class="btn-action btn-outline" onclick="document.getElementById('modalOverlay').style.display='none'">Kembali</button>
                    `;
                }
            })
            .catch(error => {
                content.innerHTML = `
                    <div style="text-align:center; padding: 5px;">
                        <h3 style="color:var(--primary-red); font-weight:bold; margin-bottom:10px; font-size:1.1rem; text-transform:uppercase;">Koneksi Gagal!</h3>
                        <p style="font-size:0.8rem; color:#ccc; margin-bottom:20px; line-height:1.5;">Sistem gagal terhubung ke server. Periksa koneksi internet Anda.</p>
                        <button class="btn-action btn-success" onclick="kirimFinal('${metode}', '${email}')">🔄 Coba Lagi</button>
                        <button class="btn-action btn-outline" onclick="document.getElementById('modalOverlay').style.display='none'">Batal</button>
                    </div>`;
            });
        }

        // Tambahkan fungsi baru ini di bawah kirimFinal()
        function salinDanBukaIG(pesan) {
            navigator.clipboard.writeText(pesan).then(() => {
                alert("Pesan otomatis telah disalin (Copied)! ✅\n\nSilakan klik 'Paste' (Tempel) di kolom pesan Instagram Vanda Gym.");
                window.open("https://ig.me/m/csweb_testing", "_blank");
            }).catch(err => {
                // Jika browser tidak support auto-copy, tetap buka IG
                window.open("https://ig.me/m/csweb_testing", "_blank");
            });
        }
    </script>
</body>
</html>