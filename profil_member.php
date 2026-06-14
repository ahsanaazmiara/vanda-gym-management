<?php
session_start();
require 'includes/koneksi.php';
require_once 'includes/api_key.php'; 

// 1. PROTEKSI: Cek apakah user sudah login
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'member') {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// =========================================================
// BLOK PHP: HANDLING AJAX UPDATE DATA
// =========================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    // --- A. UPDATE PROFIL (NAMA & WA) ---
    if ($action === 'update_profil') {
        $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
        $wa   = mysqli_real_escape_string($koneksi, $_POST['wa']);

        $q = mysqli_query($koneksi, "UPDATE users SET nama_lengkap='$nama', no_wa='$wa' WHERE id_user='$id_user'");
        
        if ($q) {
            $_SESSION['nama'] = $nama; // Update nama di session juga
            echo json_encode(['status' => 'success', 'message' => 'Profil berhasil diperbarui!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui database.']);
        }
        exit;
    }

    // --- B. UPDATE PASSWORD DENGAN PASS LAMA ---
    if ($action === 'update_password') {
        $passLama = $_POST['passLama'];
        $passBaru = $_POST['passBaru'];

        // Ambil password asli di DB
        $res = mysqli_query($koneksi, "SELECT password FROM users WHERE id_user='$id_user'");
        $user = mysqli_fetch_assoc($res);

        if (password_verify($passLama, $user['password'])) {
            $hashBaru = password_hash($passBaru, PASSWORD_DEFAULT);
            mysqli_query($koneksi, "UPDATE users SET password='$hashBaru' WHERE id_user='$id_user'");
            echo json_encode(['status' => 'success', 'message' => 'Password berhasil diubah!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Password lama salah!']);
        }
        exit;
    }

    // --- C. KIRIM LINK LUPA PASSWORD VIA WHATSAPP (FONNTE) ---
    if ($action === 'forgot_password') {
        $input_wa = mysqli_real_escape_string($koneksi, trim($_POST['resetWa']));

        // Bersihkan & Format Nomor WA ke awalan 62
        $clean_wa = preg_replace('/[^0-9]/', '', $input_wa);
        $wa_62 = '';
        if(substr($clean_wa, 0, 1) == '0') {
            $wa_62 = '62' . substr($clean_wa, 1);
        } elseif(substr($clean_wa, 0, 1) == '8') {
            $wa_62 = '62' . $clean_wa;
        } else {
            $wa_62 = $clean_wa;
        }

        // Cek apakah WA yang diinput cocok dengan profil user yang sedang login
        $cek_akun = mysqli_query($koneksi, "SELECT id_user, nama_lengkap, no_wa FROM users WHERE id_user='$id_user' AND (no_wa='$wa_62' OR no_wa='$clean_wa' OR no_wa='$input_wa')");
        
        if(mysqli_num_rows($cek_akun) > 0) {
            $user_reset = mysqli_fetch_assoc($cek_akun);
            $no_wa_tujuan = $user_reset['no_wa']; 

            // Pastikan format WA DB valid untuk dikirim ke Fonnte
            if(substr($no_wa_tujuan, 0, 1) == '0') {
                $no_wa_tujuan = '62' . substr($no_wa_tujuan, 1);
            }

            // Buat token unik
            $token = bin2hex(random_bytes(16));

            // Simpan token ke database menggunakan DATE_ADD
            $simpan_token = mysqli_query($koneksi, "UPDATE users SET reset_token='$token', reset_token_exp=DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id_user='$id_user'");
            
            if(!$simpan_token) {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan token ke database. Hubungi Admin.']);
                exit;
            }

            // Buat link reset dinamis menuju halaman login.php
            $base_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
            $reset_link = $base_url . "/login.php?token=" . $token;

            // Siapkan Teks Pesan WhatsApp
            $pesan_wa = "*Vanda Gym Classic - Reset Password*\n\n";
            $pesan_wa .= "Halo *" . $user_reset['nama_lengkap'] . "*,\n\n";
            $pesan_wa .= "Anda meminta reset password melalui halaman Profil Akun.\n\n";
            $pesan_wa .= "Klik link di bawah ini untuk membuat password baru Anda:\n";
            $pesan_wa .= $reset_link . "\n\n";
            $pesan_wa .= "_Link ini kedaluwarsa dalam 1 jam._\n";
            $pesan_wa .= "Abaikan pesan ini jika Anda tidak merasa memintanya.";

            // --- INTEGRASI WHATSAPP API FONNTE ---
            $api_token = $fonnte_api_key; 
            
            $curl = curl_init();
            curl_setopt_array($curl, array(
              CURLOPT_URL => 'https://api.fonnte.com/send',
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'POST',
              CURLOPT_POSTFIELDS => array(
                'target' => $no_wa_tujuan,
                'message' => $pesan_wa,
                'countryCode' => '62',
              ),
              CURLOPT_HTTPHEADER => array(
                "Authorization: $api_token"
              ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            if ($err) {
                echo json_encode(['status' => 'error', 'message' => 'Gagal mengirim pesan WhatsApp. Pastikan koneksi internet aktif.']);
            } else {
                $res = json_decode($response, true);
                if(isset($res['status']) && $res['status'] == true) {
                    echo json_encode(['status' => 'success', 'message' => 'Tautan reset telah berhasil dikirim ke WhatsApp Anda.']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'WhatsApp Gateway Gagal: ' . ($res['reason'] ?? 'Device Offline')]);
                }
            }

        } else {
            echo json_encode(['status' => 'error', 'message' => 'Nomor WhatsApp tidak cocok dengan data profil Anda saat ini.']);
        }
        exit;
    }
}

// 2. AMBIL DATA USER TERBARU UNTUK DITAMPILKAN DI FORM
$query = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = '$id_user'");
$u = mysqli_fetch_assoc($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Vanda Gym Classic</title>
    
    <style>
        :root {
            --bg-dark: #000000;
            --primary-red: #8E1616;
            --accent-gold: #E8C999;
            --text-light: #F8EEDF;
            --input-bg: #111111;
            --success-green: #28a745;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: var(--bg-dark); 
            color: var(--text-light); 
            min-height: 100vh; 
            display: flex; 
            flex-direction: column; 
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        /* Penyesuaian Kotak Utama Desktop */
        .form-container {
            background-color: #0a0a0a;
            border: 1px solid #333; 
            border-top: 4px solid var(--primary-red);
            border-radius: 8px; 
            padding: 35px 30px; 
            width: 100%; 
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.8);
        }

        .nav-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px; }
        .btn-back-square { 
            width: 40px; height: 40px; 
            background-color: #1a1a1a; border: 1px solid #333; 
            color: var(--accent-gold); border-radius: 4px;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; font-weight: bold; font-size: 1.2rem;
            transition: 0.3s;
        }
        .btn-back-square:hover { background-color: var(--primary-red); color: #fff; border-color: var(--primary-red); }

        .section-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            border-bottom: 1px solid #333; 
            padding-bottom: 8px; 
            margin: 25px 0 15px;
        }
        .section-header h3 { 
            color: var(--accent-gold); 
            text-transform: uppercase; 
            font-size: 1.05rem; 
            margin: 0; 
        }

        .btn-profil-action {
            padding: 6px 12px; border-radius: 4px; font-weight: bold; 
            cursor: pointer; transition: 0.3s; font-size: 0.8rem; 
            border: 1px solid var(--accent-gold); background: transparent; 
            color: var(--accent-gold); width: auto; margin: 0;
        }
        .btn-profil-action:hover { background: var(--accent-gold); color: #000; }
        
        .form-group { margin-bottom: 15px; position: relative; }
        .form-group label { display: block; margin-bottom: 6px; color: #ccc; font-size: 0.85rem; font-weight: 600; }
        .form-control {
            width: 100%; padding: 10px 12px; min-height: 42px;
            background-color: var(--input-bg); border: 1px solid #333;
            border-radius: 4px; color: white; font-size: 0.95rem;
            transition: 0.3s;
        }
        .form-control:focus { outline: none; border-color: var(--accent-gold); }
        .form-control:disabled { background-color: #050505; color: #666; cursor: not-allowed; border-color: #222; }
        
        /* TOMBOL AKSI POSITIF (HIJAU) & NEGATIF (MERAH) */
        .action-buttons { display: none; gap: 10px; margin-top: 20px; }
        .btn-save, .btn-save-wa { 
            background-color: var(--success-green); color: white; 
            border: none; width: 100%; min-height: 44px; text-transform: uppercase; 
            cursor: pointer; font-weight: bold; border-radius: 4px; transition: 0.3s; font-size: 0.9rem;
        }
        .btn-save-wa { display: block; margin-top: 20px; }
        .btn-save:hover, .btn-save-wa:hover { background-color: #218838; }
        
        .btn-cancel { 
            background-color: var(--primary-red); color: white; 
            border: none; width: 100%; min-height: 44px; text-transform: uppercase; 
            cursor: pointer; font-weight: bold; border-radius: 4px; transition: 0.3s; font-size: 0.9rem;
        }
        .btn-cancel:hover { background-color: #b01c1c; }

        .toast {
            position: fixed; top: 20px; right: 20px; background: var(--success-green);
            color: white; padding: 15px 25px; border-radius: 4px; font-weight: bold;
            display: none; z-index: 1000; box-shadow: 0 5px 15px rgba(0,0,0,0.5);
        }

        .eye-toggle {
            position: absolute; right: 5px; top: 28px; transform: translateY(0); 
            cursor: pointer; min-height: 44px; min-width: 44px; 
            display: flex; align-items: center; justify-content: center; z-index: 10;
        }

        .text-link { color: var(--accent-gold); text-decoration: none; font-size: 0.8rem; transition: 0.3s; }
        .text-link:hover { text-decoration: underline; }
        .error-msg { color: var(--primary-red); font-size: 0.75rem; margin-top: 5px; display: none; }
        .success-msg { color: var(--success-green); background: rgba(40,167,69,0.1); border: 1px solid var(--success-green); padding: 10px; border-radius: 4px; font-size: 0.85rem; margin-bottom: 15px; display: none; text-align: center; }
        
        /* =========================================
           MOBILE RESPONSIVE (DIPERKECIL LAGI)
           ========================================= */
        @media (max-width: 768px) {
            body { padding: 50px; }
            .form-container { 
                padding: 20px 15px; 
                max-width: 100%; 
                border-radius: 6px;
            }
            .nav-top { margin-bottom: 15px; }
            .btn-back-square { width: 32px; height: 32px; font-size: 0.9rem; }
            
            #blokProfilUtama h2, #blokResetPassword h2 { font-size: 1.15rem !important; margin-bottom: 8px !important; }
            #blokResetPassword p { font-size: 0.75rem !important; }
            
            .section-header { margin: 15px 0 10px; padding-bottom: 5px; }
            .section-header h3 { font-size: 0.85rem; }
            .btn-profil-action { font-size: 0.7rem; padding: 4px 8px; }
            
            .form-group { margin-bottom: 10px; }
            .form-group label { font-size: 0.75rem; margin-bottom: 4px; }
            .form-control { font-size: 0.8rem; padding: 6px 10px; min-height: 36px; }
            
            .action-buttons { flex-direction: column; gap: 8px; margin-top: 10px; }
            .btn-save, .btn-save-wa, .btn-cancel { min-height: 38px; font-size: 0.8rem; }
            
            .eye-toggle { top: 20px; min-width: 36px; min-height: 36px; }
            .eye-toggle svg { width: 16px; height: 16px; }
            
            .text-link { font-size: 0.75rem; }
            .error-msg, .success-msg { font-size: 0.7rem; }
        }
    </style>
</head>

<body>

    <div id="toastNotif" class="toast">Data berhasil disimpan!</div>

    <div class="form-container">
        <div class="nav-top">
            <a href="member_dasbor.php" id="btnBackTop" class="btn-back-square" title="Kembali ke Dasbor">←</a>
            
        </div>

        <div id="blokProfilUtama">
            <h2 style="text-align:center; color:var(--text-light); text-transform:uppercase; letter-spacing:1px; margin-bottom: 5px;">Pengaturan Akun</h2>

            <!-- BAGIAN DATA PRIBADI -->
            <div class="section-header">
                <h3>Data Pribadi</h3>
                <button type="button" class="btn-profil-action" id="btnEditProfil" onclick="toggleEdit('profil')">Edit Profil</button>
            </div>

            <form id="formProfil" onsubmit="handleSimpan(event, 'profil')">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" id="profNama" class="form-control" value="<?= htmlspecialchars($u['nama_lengkap']) ?>" disabled required>
                </div>

                <div class="form-group">
                    <label>Nomor WhatsApp</label>
                    <input type="text" id="profHp" class="form-control" value="<?= htmlspecialchars($u['no_wa']) ?>" disabled required oninput="validasiAngka(this)">
                    <div id="errorHp" class="error-msg">Wajib angka saja.</div>
                </div>

                <!-- Tombol Positif (Hijau) & Negatif (Merah) -->
                <div id="actionProfil" class="action-buttons">
                    <button type="submit" id="saveProfil" class="btn-save">Simpan Perubahan</button>
                    <button type="button" id="cancelProfil" class="btn-cancel" onclick="toggleEdit('profil')">Batal</button>
                </div>
            </form>

            <!-- BAGIAN KEAMANAN AKUN -->
            <div class="section-header">
                <h3>Keamanan Akun</h3>
                <button type="button" class="btn-profil-action" id="btnEditKeamanan" onclick="toggleEdit('keamanan')">Ubah Password</button>
            </div>

            <form id="formKeamanan" onsubmit="handleSimpan(event, 'keamanan')">
                <div class="form-group">
                    <label>Email Login</label>
                    <input type="email" id="profEmail" class="form-control" value="<?= htmlspecialchars($u['email']) ?>" disabled style="background-color: #050505;">
                    <small style="color: #666; font-size: 0.7rem; margin-top: 5px; display: block;">Hubungi Admin jika ingin mengubah email.</small>
                </div>

                <div id="groupPassDummy">
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" class="form-control" value="********" disabled>
                    </div>
                </div>

                <div id="groupEditPass" style="display: none;">
                    <div class="form-group" style="position: relative;">
                        <label>Password Lama</label>
                        <input type="password" id="profPassLama" class="form-control" placeholder="Masukkan password saat ini" disabled required oninput="cekPassword(this, 'errorPassLama')" style="padding-right: 45px;">
                        <span class="eye-toggle" onclick="toggleVisibility('profPassLama', 'eyeIconLama')">
                            <svg id="eyeIconLama" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </span>
                        <div id="errorPassLama" class="error-msg">Format salah (Kombinasi huruf & angka).</div>
                        
                        <div style="text-align: right; margin-top: 8px;">
                            <a href="#" class="text-link" onclick="toggleResetForm(true, event)">Lupa password lama?</a>
                        </div>
                    </div>

                    <div class="form-group" style="position: relative;">
                        <label>Password Baru</label>
                        <input type="password" id="profPass" class="form-control" placeholder="Min. 6 karakter (Huruf & Angka)" disabled required oninput="cekPassword(this, 'errorPassBaru')" style="padding-right: 45px;">
                        <span class="eye-toggle" onclick="toggleVisibility('profPass', 'eyeIconBaru')">
                            <svg id="eyeIconBaru" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </span>
                        <div id="errorPassBaru" class="error-msg">Gunakan huruf & angka (min 6).</div>
                    </div>
                </div>

                <!-- Tombol Positif (Hijau) & Negatif (Merah) -->
                <div id="actionKeamanan" class="action-buttons">
                    <button type="submit" id="saveKeamanan" class="btn-save">Simpan Password Baru</button>
                    <button type="button" id="cancelKeamanan" class="btn-cancel" onclick="toggleEdit('keamanan')">Batal</button>
                </div>
            </form>
        </div>

        <!-- BAGIAN RESET PASSWORD -->
        <div id="blokResetPassword" style="display: none; padding-top: 10px;">
            <div style="display: flex; justify-content: center; margin-bottom: 10px; color: var(--accent-gold);">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            </div>
            <div style="text-align: center; margin-bottom: 20px;">
                <h2 style="color: var(--accent-gold); text-transform: uppercase; font-size: 1.25rem; margin-bottom: 5px;">Lupa Password?</h2>
                <p style="color: #888; font-size: 0.8rem; line-height: 1.4;">Kami akan mengirimkan tautan pengaturan ulang password <strong>ke nomor WhatsApp</strong> Anda.</p>
            </div>
            
            <div id="pesanSuksesReset" class="success-msg"></div>
            
            <form id="formResetPass" onsubmit="kirimLinkReset(event)">
                <div class="form-group">
                    <label>Nomor WhatsApp Terdaftar</label>
                    <input type="tel" id="resetWaProf" class="form-control" value="<?= htmlspecialchars($u['no_wa']) ?>" required oninput="validasiAngka(this)">
                </div>
                <div style="display: flex; gap: 8px; flex-direction: column; margin-top: 15px;">
                    <button type="submit" id="btnKirimReset" class="btn-save" style="display: block; margin: 0;">Kirim Tautan Reset</button>
                    <button type="button" class="btn-cancel" onclick="toggleResetForm(false, event)" style="margin: 0;">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleResetForm(tampilkanLupaPass, e) {
            if (e) e.preventDefault();
            const btnBack = document.getElementById('btnBackTop');
            document.getElementById('blokProfilUtama').style.display = tampilkanLupaPass ? 'none' : 'block';
            document.getElementById('blokResetPassword').style.display = tampilkanLupaPass ? 'block' : 'none';
            if (tampilkanLupaPass) {
                btnBack.onclick = function(e) { e.preventDefault(); toggleResetForm(false); };
            } else {
                btnBack.onclick = null;
                btnBack.href = "member_dasbor.php";
            }
        }

        function kirimLinkReset(e) {
            e.preventDefault();
            const btn = document.getElementById('btnKirimReset');
            const originalText = btn.innerText;
            const waInput = document.getElementById('resetWaProf').value.trim();

            btn.innerText = "Mengirim..."; btn.disabled = true;

            const formData = new FormData();
            formData.append('action', 'forgot_password');
            formData.append('resetWa', waInput);

            fetch('profil_member.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    document.getElementById('formResetPass').style.display = 'none'; 
                    const msg = document.getElementById('pesanSuksesReset');
                    msg.style.display = 'block'; 
                    msg.innerHTML = data.message;
                } else {
                    alert('❌ ' + data.message);
                    btn.innerText = originalText; btn.disabled = false;
                }
            })
            .catch(() => {
                alert('Terjadi kesalahan jaringan atau server.');
                btn.innerText = originalText; btn.disabled = false;
            });
        }

        function toggleEdit(tipe) {
            if (tipe === 'profil') {
                const isDis = document.getElementById('profNama').disabled;
                document.getElementById('profNama').disabled = !isDis;
                document.getElementById('profHp').disabled = !isDis;
                
                document.getElementById('btnEditProfil').style.display = isDis ? 'none' : 'block';
                document.getElementById('actionProfil').style.display = isDis ? 'flex' : 'none';
            } else {
                const isDis = document.getElementById('profPassLama').disabled;
                document.getElementById('profPassLama').disabled = !isDis;
                document.getElementById('profPass').disabled = !isDis;
                
                document.getElementById('groupPassDummy').style.display = isDis ? 'none' : 'block';
                document.getElementById('groupEditPass').style.display = isDis ? 'block' : 'none';
                
                document.getElementById('btnEditKeamanan').style.display = isDis ? 'none' : 'block';
                document.getElementById('actionKeamanan').style.display = isDis ? 'flex' : 'none';
            }
        }

        function validasiAngka(input) {
            const error = document.getElementById('errorHp');
            input.value = input.value.replace(/\D/g, ''); 
            if(error) error.style.display = (/\D/g.test(input.value)) ? 'block' : 'none';
        }

        function cekPassword(input, errorId) {
            const error = document.getElementById(errorId);
            const regex = /^(?=.*[0-9])(?=.*[a-zA-Z])([a-zA-Z0-9]{6,})$/;
            error.style.display = (!regex.test(input.value) && input.value.length > 0) ? 'block' : 'none';
        }

        function toggleVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>`;
            } else {
                input.type = 'password';
                icon.innerHTML = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>`;
            }
        }

        function handleSimpan(e, tipe) {
            e.preventDefault();
            const btn = (tipe === 'profil') ? document.getElementById('saveProfil') : document.getElementById('saveKeamanan');
            const originalText = btn.innerText;
            
            btn.innerText = "Menyimpan..."; btn.disabled = true;

            const formData = new FormData();
            formData.append('action', (tipe === 'profil' ? 'update_profil' : 'update_password'));

            if (tipe === 'profil') {
                formData.append('nama', document.getElementById('profNama').value);
                formData.append('wa', document.getElementById('profHp').value);
            } else {
                formData.append('passLama', document.getElementById('profPassLama').value);
                formData.append('passBaru', document.getElementById('profPass').value);
            }

            fetch('profil_member.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast(data.message);
                    setTimeout(() => location.reload(), 1000); 
                } else {
                    alert('❌ ' + data.message);
                    btn.innerText = originalText; btn.disabled = false;
                }
            })
            .catch(() => {
                alert('Terjadi kesalahan jaringan.');
                btn.innerText = originalText; btn.disabled = false;
            });
        }

        function showToast(pesan) {
            const toast = document.getElementById('toastNotif');
            toast.innerText = pesan; toast.style.display = 'block';
            setTimeout(() => { toast.style.display = 'none'; }, 3000);
        }
    </script>
</body>
</html>