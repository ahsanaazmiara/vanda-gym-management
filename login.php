<?php
session_start();
require 'includes/koneksi.php';
require_once 'includes/api_key.php'; // Tambahkan baris ini

// =========================================================
// BLOK PHP: MENANGKAP PERMINTAAN DARI JAVASCRIPT AJAX
// =========================================================

// 1. PROSES LOGIN (BISA EMAIL ATAU NO WA)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'login') {
    header('Content-Type: application/json');

    $identifier = mysqli_real_escape_string($koneksi, trim($_POST['logId']));
    $password = $_POST['logPass'];

    // Bypass khusus Admin
    if (strtolower($identifier) === 'admin' && strtolower($password) === 'admin') {
        $cek_admin = mysqli_query($koneksi, "SELECT * FROM users WHERE role='admin' LIMIT 1");
        if(mysqli_num_rows($cek_admin) > 0) {
            $admin = mysqli_fetch_assoc($cek_admin);
            $_SESSION['id_user'] = $admin['id_user'];
            $_SESSION['nama'] = $admin['nama_lengkap'];
            $_SESSION['role'] = 'admin';
        } else {
            $_SESSION['id_user'] = 1;
            $_SESSION['nama'] = 'Super Admin';
            $_SESSION['role'] = 'admin';
        }
        echo json_encode(['status' => 'success', 'role' => 'admin']);
        exit;
    }

    // Bersihkan format WA untuk pengecekan database (ubah 08 ke 628)
    $clean_wa = preg_replace('/[^0-9]/', '', $identifier);
    $wa_62 = '';
    if(!empty($clean_wa)) {
        if(substr($clean_wa, 0, 1) == '0') {
            $wa_62 = '62' . substr($clean_wa, 1);
        } elseif(substr($clean_wa, 0, 1) == '8') {
            $wa_62 = '62' . $clean_wa;
        } else {
            $wa_62 = $clean_wa;
        }
    }

    // Query pencarian berdasarkan Email ATAU Nomor WA
    $where_clause = "u.email='$identifier'";
    if(!empty($wa_62)) {
        $where_clause .= " OR u.no_wa='$wa_62' OR u.no_wa='$clean_wa' OR u.no_wa='$identifier'";
    }

    // QUERY DIPERBARUI: Prioritaskan status 'aktif' jika member sedang melakukan perpanjangan
    $query = mysqli_query($koneksi, "
        SELECT u.*, m.status, m.alasan_tolak 
        FROM users u 
        LEFT JOIN membership m ON u.id_user = m.id_user 
        WHERE ($where_clause)
        ORDER BY 
            CASE 
                WHEN m.status = 'aktif' THEN 1
                ELSE 2
            END,
            m.id_membership DESC 
        LIMIT 1
    ");
    
    if (mysqli_num_rows($query) > 0) {
        $user = mysqli_fetch_assoc($query);
        
        if (password_verify($password, $user['password'])) {
            
            // CEK STATUS JIKA ROLE BUKAN ADMIN
            if ($user['role'] !== 'admin') {
                if ($user['status'] === 'pending' || $user['status'] === NULL) {
                    echo json_encode([
                        'status' => 'error', 
                        'message' => 'Login Gagal. Akun Anda masih <strong>menunggu verifikasi</strong> dari Admin.'
                    ]);
                    exit;
                } elseif ($user['status'] === 'ditolak') {
                    $alasan = !empty($user['alasan_tolak']) ? $user['alasan_tolak'] : 'Tidak memenuhi syarat';
                    echo json_encode([
                        'status' => 'error', 
                        'message' => "Pendaftaran Anda <strong>ditolak</strong> oleh Admin.<br>Alasan: <em>$alasan</em>"
                    ]);
                    exit;
                }
            }

            // Jika status Aktif, Kedaluwarsa, atau role Admin -> Lolos
            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['nama'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];

            echo json_encode(['status' => 'success', 'role' => $user['role']]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Password yang Anda masukkan salah.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Akun tidak ditemukan. Pastikan Email atau No. WhatsApp benar.']);
    }
    exit;
}

// 2. PROSES KIRIM LINK RESET PASSWORD (VIA WHATSAPP FONNTE)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'forgot_password') {
    header('Content-Type: application/json');
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

    // Cek akun berdasarkan nomor WA
    $cek_akun = mysqli_query($koneksi, "SELECT id_user, nama_lengkap, no_wa FROM users WHERE no_wa='$wa_62' OR no_wa='$clean_wa' OR no_wa='$input_wa'");
    if(mysqli_num_rows($cek_akun) > 0) {
        $user = mysqli_fetch_assoc($cek_akun);
        $id_user = $user['id_user'];
        $no_wa_tujuan = $user['no_wa'];

        if(substr($no_wa_tujuan, 0, 1) == '0') {
            $no_wa_tujuan = '62' . substr($no_wa_tujuan, 1);
        }

        $token = bin2hex(random_bytes(16));
        $simpan_token = mysqli_query($koneksi, "UPDATE users SET reset_token='$token', reset_token_exp=DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id_user='$id_user'");
        
        if(!$simpan_token) {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan token ke database. Hubungi Admin.']);
            exit;
        }

        $base_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
        $reset_link = $base_url . "/login.php?token=" . $token;

        $pesan_wa = "*Vanda Gym Classic - Reset Password*\n\n";
        $pesan_wa .= "Halo *" . $user['nama_lengkap'] . "*,\n\n";
        $pesan_wa .= "Kami menerima permintaan reset password untuk akun Anda.\n\n";
        $pesan_wa .= "Klik link di bawah ini untuk membuat password baru:\n";
        $pesan_wa .= $reset_link . "\n\n";
        $pesan_wa .= "_Link ini kedaluwarsa dalam 1 jam._\n";
        $pesan_wa .= "Abaikan pesan ini jika Anda tidak merasa meminta reset password.";

        // --- INTEGRASI WHATSAPP API FONNTE ---
        $api_token = $fonnte_api_key; // Mengambil dari api_key.php
        
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
        curl_close($curl);

        if ($err) {
            echo json_encode(['status' => 'error', 'message' => 'Gagal mengirim pesan WhatsApp. Pastikan koneksi internet aktif.']);
        } else {
            $res = json_decode($response, true);
            if(isset($res['status']) && $res['status'] == true) {
                echo json_encode(['status' => 'success', 'message' => 'Tautan reset password telah dikirim ke WhatsApp ('.$no_wa_tujuan.'). Silakan periksa pesan Anda.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'WhatsApp Gateway Gagal: ' . ($res['reason'] ?? 'Device Offline')]);
            }
        }

    } else {
        echo json_encode(['status' => 'error', 'message' => 'Nomor WhatsApp tidak terdaftar di sistem kami.']);
    }
    exit;
}

// 3. PROSES SIMPAN PASSWORD BARU
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'reset_password') {
    header('Content-Type: application/json');
    $token = mysqli_real_escape_string($koneksi, $_POST['token']);
    $newPass = $_POST['newPass'];

    $cek_token = mysqli_query($koneksi, "SELECT id_user FROM users WHERE reset_token='$token' AND reset_token_exp >= NOW()");
    if(mysqli_num_rows($cek_token) > 0) {
        $user = mysqli_fetch_assoc($cek_token);
        $hashedPass = password_hash($newPass, PASSWORD_DEFAULT);

        mysqli_query($koneksi, "UPDATE users SET password='$hashedPass', reset_token=NULL, reset_token_exp=NULL WHERE id_user='".$user['id_user']."'");

        echo json_encode(['status' => 'success', 'message' => 'Password berhasil diperbarui. Silakan login dengan password baru Anda.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Tautan reset tidak valid atau sudah kedaluwarsa.']);
    }
    exit;
}

// =========================================================
// CEK JIKA ADA TOKEN RESET DI URL SAAT HALAMAN DIMUAT
// =========================================================
$showResetForm = false;
$errorToken = '';
if (isset($_GET['token'])) {
    $tokenParam = mysqli_real_escape_string($koneksi, $_GET['token']);
    $cek = mysqli_query($koneksi, "SELECT id_user FROM users WHERE reset_token='$tokenParam' AND reset_token_exp >= NOW()");
    if (mysqli_num_rows($cek) > 0) {
        $showResetForm = true;
    } else {
        $errorToken = "Tautan reset password tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru dari form Lupa Password.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Vanda Gym Classic</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <div class="form-container">
        <div class="nav-top">
            <a href="index.php" id="btnBackUtama" class="btn-back-square" title="Kembali ke Beranda">←</a>
        </div>

        <div id="formLoginUtama">
            <div class="form-header">
                <h2>Login Sistem</h2>
                <p>Masuk ke dasbor member Vanda Gym</p>
            </div>

            <div id="loginErrorBox"></div>

            <form id="formLogin" onsubmit="simulasiLogin(event)">
                <div class="form-group">
                    <label>Email / Nomor WhatsApp</label>
                    <input type="text" id="logId" class="form-control" required placeholder="nama@email.com atau 0812..." oninput="cekFormatLogin(this, 'errorId')">
                    <div id="errorId" class="error-msg">Format tidak valid. Masukkan Email atau Nomor WA.</div>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div style="position: relative;">
                        <input type="password" id="logPass" class="form-control" required 
                               placeholder="Angka & huruf" oninput="cekPassword(this, 'errorPass')" 
                               style="padding-right: 50px;">
                        
                        <span onclick="toggleVisibility('logPass', 'eyeIcon1')" 
                              style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); 
                                     cursor: pointer; min-height: 44px; min-width: 44px; 
                                     display: flex; align-items: center; justify-content: center; z-index: 10;">
                            <svg id="eyeIcon1" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </span>
                    </div>
                    <div id="errorPass" class="error-msg">Gunakan huruf & angka.</div>
                    
                    <div style="text-align: right; margin-top: 8px;">
                        <a class="text-link" onclick="toggleMode('lupa')">Lupa Password?</a>
                    </div>
                </div>

                <button type="submit" class="btn-submit" id="btnSubmitLogin">Masuk</button>

                <div class="info-box">
                    <strong>Penting:</strong> Jika Anda baru mendaftar, akun dapat digunakan login setelah diverifikasi Admin. 
                    <a href="cek_status.php">Cek Status Pendaftaran.</a>
                </div>

                <div class="login-footer">
                    <div>
                        <span style="color: #888;">Belum menjadi member?</span>
                        <a href="daftar.php">Daftar Sekarang</a>
                    </div>
                </div>
            </form>
        </div>

        <div id="formLupaPassword" style="display: none; padding-top: 10px;">
            <div class="icon-lock">🔐</div>
            <div class="form-header">
                <h2>Lupa Password?</h2>
                <p>Masukkan Nomor WhatsApp Anda. Kami akan mengirimkan tautan pengaturan ulang password.</p>
            </div>
            
            <div id="pesanSuksesReset" class="success-msg"></div>
            
            <form id="formReset" onsubmit="kirimLinkReset(event)">
                <div class="form-group">
                    <label>Nomor WhatsApp Terdaftar</label>
                    <input type="tel" id="resetWa" class="form-control" placeholder="08123456..." required oninput="cekFormatWa(this, 'errorResetWa')">
                    <div id="errorResetWa" class="error-msg">Masukkan nomor WhatsApp yang valid.</div>
                </div>
                <button type="submit" id="btnReset" class="btn-submit">Kirim Tautan Reset</button>
            </form>
            
            <div style="margin-top: 25px; text-align: center;">
                <span style="color: #666; font-size: 0.85rem;">Ingat password Anda?</span>
                <a class="text-link" style="color: var(--accent-gold); font-weight: bold; margin-left: 5px;" onclick="toggleMode('login')">Kembali Login</a>
            </div>
        </div>

        <div id="formBuatPasswordBaru" style="display: none; padding-top: 10px;">
            <div class="icon-lock">🔑</div>
            <div class="form-header">
                <h2>Buat Password Baru</h2>
                <p>Silakan buat password baru untuk akun Anda.</p>
            </div>
            
            <div id="resetErrorBox"></div>
            
            <form id="formNewPass" onsubmit="simpanPasswordBaru(event)">
                <input type="hidden" id="resetTokenInput" value="<?= isset($_GET['token']) ? htmlspecialchars($_GET['token']) : '' ?>">

                <div class="form-group">
                    <label>Password Baru</label>
                    <div style="position: relative;">
                        <input type="password" id="newPass" class="form-control" required 
                               placeholder="Minimal 6 Karakter (Angka & Huruf)" oninput="cekPassword(this, 'errorNewPass')" 
                               style="padding-right: 50px;">
                        <span onclick="toggleVisibility('newPass', 'eyeIcon2')" style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); cursor: pointer; min-height: 44px; min-width: 44px; display: flex; align-items: center; justify-content: center; z-index: 10;">
                            <svg id="eyeIcon2" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </span>
                    </div>
                    <div id="errorNewPass" class="error-msg">Gunakan kombinasi huruf & angka (min 6).</div>
                </div>
                
                <div class="form-group">
                    <label>Konfirmasi Password Baru</label>
                    <div style="position: relative;">
                        <input type="password" id="confirmPass" class="form-control" required 
                               placeholder="Ketik ulang password baru" oninput="cekKonfirmasiPassword()" 
                               style="padding-right: 50px;">
                        <span onclick="toggleVisibility('confirmPass', 'eyeIcon3')" style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); cursor: pointer; min-height: 44px; min-width: 44px; display: flex; align-items: center; justify-content: center; z-index: 10;">
                            <svg id="eyeIcon3" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </span>
                    </div>
                    <div id="errorConfirmPass" class="error-msg">Password tidak cocok!</div>
                </div>
                
                <button type="submit" id="btnSimpanPass" class="btn-submit">Simpan Password</button>
            </form>
        </div>

    </div>

    <script>
        function toggleMode(mode) {
            const btnBack = document.getElementById('btnBackUtama');
            document.getElementById('formLoginUtama').style.display = mode === 'login' ? 'block' : 'none';
            document.getElementById('formLupaPassword').style.display = mode === 'lupa' ? 'block' : 'none';
            document.getElementById('formBuatPasswordBaru').style.display = mode === 'buat_password' ? 'block' : 'none';
            
            if (mode === 'login') {
                btnBack.href = "index.php"; btnBack.onclick = null;
                document.getElementById('formReset').style.display = 'block';
                document.getElementById('pesanSuksesReset').style.display = 'none';
            } else if (mode === 'lupa') {
                btnBack.href = "javascript:void(0)";
                btnBack.onclick = function(e) { e.preventDefault(); toggleMode('login'); };
            } else if (mode === 'buat_password') {
                btnBack.href = "javascript:void(0)";
                btnBack.onclick = function(e) { e.preventDefault(); window.location.href='login.php'; };
            }
        }

        function simulasiLogin(e) {
            e.preventDefault();
            const identifier = document.getElementById('logId').value.trim();
            const pass = document.getElementById('logPass').value.trim();
            const errorBox = document.getElementById('loginErrorBox');
            
            errorBox.style.display = 'none';
            const btn = document.getElementById('btnSubmitLogin');
            const originalText = btn.innerText;
            btn.innerText = "Memeriksa..."; btn.style.opacity = "0.7"; btn.disabled = true;

            const formData = new FormData();
            formData.append('action', 'login');
            formData.append('logId', identifier);
            formData.append('logPass', pass);

            fetch('login.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    btn.innerText = "Berhasil Masuk!";
                    btn.style.backgroundColor = "var(--success-green)";
                    setTimeout(() => {
                        window.location.href = data.role === 'admin' ? 'admin_dasbor.php' : 'member_dasbor.php';
                    }, 800);
                } else {
                    errorBox.innerHTML = `❌ ${data.message}`;
                    errorBox.style.display = 'block';
                    btn.innerText = originalText; btn.style.opacity = "1"; btn.disabled = false;
                }
            })
            .catch(error => {
                errorBox.innerHTML = `❌ Kesalahan server.`;
                errorBox.style.display = 'block';
                btn.innerText = originalText; btn.disabled = false;
            });
        }

        function kirimLinkReset(e) { 
            e.preventDefault(); 
            const noWa = document.getElementById('resetWa').value.trim();
            const btn = document.getElementById('btnReset');
            const originalText = btn.innerText;
            
            btn.innerText = "Mengirim..."; btn.style.opacity = "0.7"; btn.disabled = true;

            const formData = new FormData();
            formData.append('action', 'forgot_password');
            formData.append('resetWa', noWa);

            fetch('login.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    document.getElementById('pesanSuksesReset').innerText = data.message;
                    document.getElementById('pesanSuksesReset').style.display = 'block';
                    document.getElementById('formReset').style.display = 'none';
                } else {
                    alert("❌ " + data.message);
                    btn.innerText = originalText; btn.style.opacity = "1"; btn.disabled = false;
                }
            })
            .catch(error => {
                alert("Terjadi kesalahan pada server saat mengirim WhatsApp.");
                btn.innerText = originalText; btn.style.opacity = "1"; btn.disabled = false;
            });
        }

        function simpanPasswordBaru(e) { 
            e.preventDefault(); 
            const pass = document.getElementById('newPass').value;
            const confirm = document.getElementById('confirmPass').value;
            const token = document.getElementById('resetTokenInput').value;

            if(pass !== confirm) {
                document.getElementById('errorConfirmPass').style.display = 'block';
                return;
            }

            const errorBox = document.getElementById('resetErrorBox');
            errorBox.style.display = 'none';

            const btn = document.getElementById('btnSimpanPass');
            const originalText = btn.innerText;
            btn.innerText = "Menyimpan..."; btn.style.opacity = "0.7"; btn.disabled = true;

            const formData = new FormData();
            formData.append('action', 'reset_password');
            formData.append('token', token);
            formData.append('newPass', pass);

            fetch('login.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    alert("✅ " + data.message);
                    window.location.href = 'login.php';
                } else {
                    errorBox.innerHTML = `❌ ${data.message}`;
                    errorBox.style.display = 'block';
                    btn.innerText = originalText; btn.style.opacity = "1"; btn.disabled = false;
                }
            })
            .catch(error => {
                errorBox.innerHTML = `❌ Kesalahan server.`;
                errorBox.style.display = 'block';
                btn.innerText = originalText; btn.style.opacity = "1"; btn.disabled = false;
            });
        }

        function cekFormatLogin(input, errorId) {
            const error = document.getElementById(errorId);
            const val = input.value.trim();
            const isEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
            const isPhone = /^[0-9\-\+]{9,15}$/.test(val);
            
            if (val.toLowerCase() === 'admin') {
                error.style.display = 'none'; input.classList.remove('invalid'); return;
            }
            if (!isEmail && !isPhone && val.length > 0) {
                error.style.display = 'block'; input.classList.add('invalid');
            } else {
                error.style.display = 'none'; input.classList.remove('invalid');
            }
        }

        function cekFormatWa(input, errorId) {
            const error = document.getElementById(errorId);
            const val = input.value.trim();
            const isPhone = /^[0-9\-\+]{9,15}$/.test(val);
            
            if (!isPhone && val.length > 0) {
                error.style.display = 'block'; input.classList.add('invalid');
            } else {
                error.style.display = 'none'; input.classList.remove('invalid');
            }
        }

        function cekPassword(input, errorId) {
            const error = document.getElementById(errorId);
            const regex = /^(?=.*[0-9])(?=.*[a-zA-Z])([a-zA-Z0-9]+)$/;
            if (input.value.toLowerCase() === 'admin') {
                error.style.display = 'none'; input.classList.remove('invalid'); return;
            }
            if (!regex.test(input.value) && input.value.length > 0) {
                error.style.display = 'block'; input.classList.add('invalid');
            } else {
                error.style.display = 'none'; input.classList.remove('invalid');
            }
        }

        function cekKonfirmasiPassword() {
            const pass = document.getElementById('newPass').value;
            const confirm = document.getElementById('confirmPass').value;
            const error = document.getElementById('errorConfirmPass');
            if(confirm.length > 0 && pass !== confirm) {
                error.style.display = 'block';
            } else {
                error.style.display = 'none';
            }
        }

        function toggleVisibility(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const eyeIcon = document.getElementById(iconId);
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML = `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>`;
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>`;
            }
        }

        // --- SCRIPT AUTO-OPEN SAAT HALAMAN SELESAI DIMUAT ---
        window.onload = function() {
            <?php if ($showResetForm): ?>
                toggleMode('buat_password');
            <?php elseif (!empty($errorToken)): ?>
                document.getElementById('loginErrorBox').innerHTML = `❌ <?= $errorToken ?>`;
                document.getElementById('loginErrorBox').style.display = 'block';
            <?php endif; ?>
        };
    </script>
</body>
</html>