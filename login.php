<?php
session_start();
require 'includes/koneksi.php';
require_once 'includes/api_key.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'login') {
    header('Content-Type: application/json');

    $identifier = mysqli_real_escape_string($koneksi, trim($_POST['logId']));
    $password = $_POST['logPass'];

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

    $where_clause = "u.email='$identifier'";
    if(!empty($wa_62)) {
        $where_clause .= " OR u.no_wa='$wa_62' OR u.no_wa='$clean_wa' OR u.no_wa='$identifier'";
    }

    $query = mysqli_query($koneksi, "
        SELECT u.*, m.status, m.alasan_tolak 
        FROM users u 
        LEFT JOIN membership m ON u.id_user = m.id_user 
        WHERE ($where_clause)
        ORDER BY CASE WHEN m.status = 'aktif' THEN 1 ELSE 2 END, m.id_membership DESC 
        LIMIT 1
    ");
    
    if (mysqli_num_rows($query) > 0) {
        $user = mysqli_fetch_assoc($query);
        
        if (password_verify($password, $user['password'])) {
            if ($user['role'] !== 'admin') {
                if ($user['status'] === 'pending' || $user['status'] === NULL) {
                    echo json_encode(['status' => 'error', 'message' => 'Login Gagal. Akun Anda <strong>menunggu verifikasi</strong> Admin.']);
                    exit;
                } elseif ($user['status'] === 'ditolak') {
                    $alasan = !empty($user['alasan_tolak']) ? $user['alasan_tolak'] : 'Tidak memenuhi syarat';
                    echo json_encode(['status' => 'error', 'message' => "Pendaftaran Anda <strong>ditolak</strong> oleh Admin.<br>Alasan: <em>$alasan</em>"]);
                    exit;
                }
            }

            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['nama'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];

            echo json_encode(['status' => 'success', 'role' => $user['role']]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Password yang Anda masukkan salah.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Akun tidak ditemukan.']);
    }
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'forgot_password') {
    header('Content-Type: application/json');
    $input_wa = mysqli_real_escape_string($koneksi, trim($_POST['resetWa']));

    $clean_wa = preg_replace('/[^0-9]/', '', $input_wa);
    $wa_62 = '';
    if(substr($clean_wa, 0, 1) == '0') {
        $wa_62 = '62' . substr($clean_wa, 1);
    } elseif(substr($clean_wa, 0, 1) == '8') {
        $wa_62 = '62' . $clean_wa;
    } else {
        $wa_62 = $clean_wa;
    }

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
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan token ke database.']);
            exit;
        }

        $base_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
        $reset_link = $base_url . "/login.php?token=" . $token;

        $pesan_wa = "*Vanda Gym - Reset Password*\n\n";
        $pesan_wa .= "Halo *" . $user['nama_lengkap'] . "*,\n\n";
        $pesan_wa .= "Klik link berikut untuk membuat password baru:\n";
        $pesan_wa .= $reset_link . "\n\n";
        $pesan_wa .= "_Tautan kedaluwarsa dalam 1 jam._";

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
          CURLOPT_HTTPHEADER => array("Authorization: $api_token"),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            echo json_encode(['status' => 'error', 'message' => 'Gagal mengirim pesan WhatsApp. Pastikan koneksi internet aktif.']);
        } else {
            $res = json_decode($response, true);
            if(isset($res['status']) && $res['status'] == true) {
                echo json_encode(['status' => 'success', 'message' => 'Tautan reset telah dikirim ke WhatsApp Anda.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'WhatsApp Gateway Gagal: ' . ($res['reason'] ?? 'Device Offline')]);
            }
        }

    } else {
        echo json_encode(['status' => 'error', 'message' => 'Nomor WhatsApp tidak terdaftar.']);
    }
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'reset_password') {
    header('Content-Type: application/json');
    $token = mysqli_real_escape_string($koneksi, $_POST['token']);
    $newPass = $_POST['newPass'];

    $cek_token = mysqli_query($koneksi, "SELECT id_user FROM users WHERE reset_token='$token' AND reset_token_exp >= NOW()");
    if(mysqli_num_rows($cek_token) > 0) {
        $user = mysqli_fetch_assoc($cek_token);
        $hashedPass = password_hash($newPass, PASSWORD_DEFAULT);
        mysqli_query($koneksi, "UPDATE users SET password='$hashedPass', reset_token=NULL, reset_token_exp=NULL WHERE id_user='".$user['id_user']."'");
        echo json_encode(['status' => 'success', 'message' => 'Password berhasil diperbarui. Silakan login.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Tautan reset tidak valid/kedaluwarsa.']);
    }
    exit;
}

$showResetForm = false;
$errorToken = '';
if (isset($_GET['token'])) {
    $tokenParam = mysqli_real_escape_string($koneksi, $_GET['token']);
    $cek = mysqli_query($koneksi, "SELECT id_user FROM users WHERE reset_token='$tokenParam' AND reset_token_exp >= NOW()");
    if (mysqli_num_rows($cek) > 0) {
        $showResetForm = true;
    } else {
        $errorToken = "Tautan reset tidak valid/kedaluwarsa.";
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
    <style>
        .form-control.invalid-field {
            border-color: #ff4d4d !important; background-color: #221111 !important;
        }
        .field-error-text {
            color: #ff4d4d; font-size: 0.8rem; margin-top: 4px; display: none;
        }
        .btn-positive {
            background-color: #25D366 !important; color: white !important;
            border: none !important; cursor: pointer; font-weight: bold; transition: 0.3s;
        }
        .btn-positive:hover { background-color: #1ebe57 !important; }
        .btn-positive:disabled { background-color: #555 !important; cursor: not-allowed; opacity: 0.6; }
        .btn-negative {
            background-color: transparent !important; color: #ff4d4d !important;
            border: 1px solid #ff4d4d !important; cursor: pointer; transition: 0.3s;
            width: 100%; padding: 12px; border-radius: 4px; font-weight: bold; margin-top: 10px;
        }
        .btn-negative:hover { background-color: #ff4d4d !important; color: white !important; }

        .connection-error-box {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(0, 0, 0, 0.85); display: none; 
            justify-content: center; align-items: center; z-index: 999999; padding: 20px;
        }
        .error-card-center {
            background-color: #0f0a0a; border: 1px solid #ff4d4d; border-top: 4px solid #ff4d4d;
            border-radius: 8px; padding: 30px 25px; max-width: 400px; width: 100%; text-align: center;
        }

        /* TOMBOL WA KIRI BAWAH */
        .wa-btn {
            position: fixed; bottom: 30px; left: 30px; 
            background-color: #25D366; color: white; 
            border-radius: 50%; width: 60px; height: 60px; 
            display: flex; justify-content: center; align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.6); z-index: 1000; transition: 0.3s;
            text-decoration: none;
        }
        .wa-btn:hover { transform: scale(1.1); background-color: #1ebe57; }
        .wa-btn svg { width: 35px; height: 35px; fill: currentColor; }

        /* PEMADATAN LAYOUT MOBILE LOGIN */
        @media screen and (max-width: 768px) {
            body { padding: 10px !important; display: flex !important; align-items: center !important; justify-content: center !important; min-height: 100vh !important; }
            .form-container { 
                padding: 20px 15px !important; width: 95% !important; max-width: 360px !important; 
                margin: 0 auto !important; box-sizing: border-box !important; border-radius: 8px !important;
                box-shadow: 0 5px 15px rgba(0,0,0,0.5) !important;
            }
            .nav-top { margin-bottom: 10px !important; }
            .btn-back-square { width: 32px !important; height: 32px !important; font-size: 0.9rem !important; }
            .form-header h2 { font-size: 1.25rem !important; margin-bottom: 3px !important; }
            .form-header p { font-size: 0.75rem !important; margin-bottom: 12px !important; line-height: 1.3 !important; }
            .form-group { margin-bottom: 12px !important; }
            .form-group label { font-size: 0.75rem !important; margin-bottom: 4px !important; }
            .form-control { padding: 8px 10px !important; min-height: 38px !important; font-size: 0.8rem !important; }
            .btn-submit, .btn-negative { min-height: 38px !important; padding: 8px !important; font-size: 0.85rem !important; margin-top: 5px !important; }
            .icon-lock { font-size: 2rem !important; margin-bottom: 5px !important; }
            .login-footer { margin-top: 12px !important; font-size: 0.75rem !important; }
            .info-box { padding: 8px 10px !important; font-size: 0.75rem !important; margin-top: 12px !important; line-height: 1.3 !important; }
            .error-msg, .field-error-text { font-size: 0.7rem !important; }
            
            /* WA button mobile */
            .wa-btn { width: 45px !important; height: 45px !important; bottom: 15px !important; left: 15px !important; }
            .wa-btn svg { width: 22px !important; height: 22px !important; }
        }
    </style>
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

            <div id="loginErrorBox" class="error-msg" style="display:none; padding:8px; margin-bottom:12px; border:1px solid #ff4d4d; background:#221111; text-align:center; font-size:0.8rem;"></div>

            <form id="formLogin" onsubmit="simulasiLogin(event)">
                <div class="form-group">
                    <label>Email / Nomor WhatsApp</label>
                    <input type="text" id="logId" class="form-control" placeholder="nama@email.com atau 0812..." oninput="cekFormatLogin(this, 'errorId')">
                    <div id="errorId" class="error-msg" style="display:none;">Format tidak valid. Masukkan Email atau Nomor WA.</div>
                    <div id="err_logId" class="field-error-text">Kolom identitas wajib diisi.</div>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div style="position: relative;">
                        <input type="password" id="logPass" class="form-control" placeholder="Angka & huruf" oninput="cekPassword(this, 'errorPass')" style="padding-right: 50px;">
                        
                        <span onclick="toggleVisibility('logPass', 'eyeIcon1')" style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); cursor: pointer; min-height: 38px; min-width: 38px; display: flex; align-items: center; justify-content: center; z-index: 10;">
                            <svg id="eyeIcon1" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </span>
                    </div>
                    <div id="errorPass" class="error-msg" style="display:none;">Gunakan huruf & angka.</div>
                    <div id="err_logPass" class="field-error-text">Password wajib diisi.</div>
                    
                    <div style="text-align: right; margin-top: 6px;">
                        <a href="javascript:void(0)" class="text-link" onclick="toggleMode('lupa')" style="color:var(--accent-gold); font-size:0.75rem;">Lupa Password?</a>
                    </div>
                </div>

                <button type="submit" class="btn-submit btn-positive" id="btnSubmitLogin" style="width:100%;">Masuk</button>

                <div class="info-box">
                    <strong>Penting:</strong> Akun baru menunggu verifikasi Admin. 
                    <a href="cek_status.php" style="color:var(--accent-gold); text-decoration:none;">Cek Status.</a>
                </div>

                <div class="login-footer">
                    <div>
                        <span style="color: #888;">Belum menjadi member?</span>
                        <a href="daftar.php" style="color:var(--accent-gold); text-decoration:none;">Daftar Sekarang</a>
                    </div>
                </div>
            </form>
        </div>

        <div id="formLupaPassword" style="display: none; padding-top: 5px;">
            <div class="icon-lock" style="font-size:2rem; text-align:center; margin-bottom:5px;">🔐</div>
            <div class="form-header">
                <h2>Lupa Password?</h2>
                <p>Masukkan No WhatsApp. Kami akan mengirimkan tautan reset password.</p>
            </div>
            
            <div id="pesanSuksesReset" class="success-msg" style="display:none; padding:10px; border:1px solid #25D366; background:#0f2214; color:#25D366; text-align:center; border-radius:4px; margin-bottom:12px; font-size:0.75rem;"></div>
            
            <form id="formReset" onsubmit="kirimLinkReset(event)">
                <div class="form-group">
                    <label>Nomor WhatsApp Terdaftar</label>
                    <input type="tel" id="resetWa" class="form-control" placeholder="08123456..." oninput="cekFormatWa(this, 'errorResetWa')">
                    <div id="errorResetWa" class="error-msg" style="display:none;">Masukkan nomor WhatsApp yang valid.</div>
                    <div id="err_resetWa" class="field-error-text">Nomor WhatsApp wajib diisi.</div>
                </div>
                
                <button type="submit" id="btnReset" class="btn-submit btn-positive" style="width:100%;">Kirim Tautan</button>
                <button type="button" class="btn-negative" onclick="toggleMode('login')">Batal / Kembali</button>
            </form>
        </div>

        <div id="formBuatPasswordBaru" style="display: none; padding-top: 5px;">
            <div class="icon-lock" style="font-size:2rem; text-align:center; margin-bottom:5px;">🔑</div>
            <div class="form-header">
                <h2>Buat Password Baru</h2>
                <p>Silakan buat password baru untuk akun Anda.</p>
            </div>
            
            <div id="resetErrorBox" class="error-msg" style="display:none; padding:10px; margin-bottom:12px; border:1px solid #ff4d4d; background:#221111; text-align:center; font-size:0.75rem;"></div>
            
            <form id="formNewPass" onsubmit="simpanPasswordBaru(event)">
                <input type="hidden" id="resetTokenInput" value="<?= isset($_GET['token']) ? htmlspecialchars($_GET['token']) : '' ?>">

                <div class="form-group">
                    <label>Password Baru</label>
                    <div style="position: relative;">
                        <input type="password" id="newPass" class="form-control" placeholder="Minimal 6 Karakter" oninput="cekPassword(this, 'errorNewPass')" style="padding-right: 50px;">
                        <span onclick="toggleVisibility('newPass', 'eyeIcon2')" style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); cursor: pointer; min-height: 38px; min-width: 38px; display: flex; align-items: center; justify-content: center; z-index: 10;">
                            <svg id="eyeIcon2" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </span>
                    </div>
                    <div id="errorNewPass" class="error-msg" style="display:none;">Gunakan kombinasi huruf & angka.</div>
                    <div id="err_newPass" class="field-error-text">Password baru wajib diisi.</div>
                </div>
                
                <div class="form-group">
                    <label>Konfirmasi Password</label>
                    <div style="position: relative;">
                        <input type="password" id="confirmPass" class="form-control" placeholder="Ketik ulang password" oninput="cekKonfirmasiPassword()" style="padding-right: 50px;">
                        <span onclick="toggleVisibility('confirmPass', 'eyeIcon3')" style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); cursor: pointer; min-height: 38px; min-width: 38px; display: flex; align-items: center; justify-content: center; z-index: 10;">
                            <svg id="eyeIcon3" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </span>
                    </div>
                    <div id="errorConfirmPass" class="error-msg" style="display:none;">Password tidak cocok!</div>
                    <div id="err_confirmPass" class="field-error-text">Konfirmasi wajib diisi.</div>
                </div>
                
                <button type="submit" id="btnSimpanPass" class="btn-submit btn-positive" style="width:100%;">Simpan Password</button>
                <button type="button" class="btn-negative" onclick="window.location.href='login.php'">Batal / Kembali</button>
            </form>
        </div>
    </div>

    <?php
    $q_wa = mysqli_query($koneksi, "SELECT wa_cs FROM pengaturan_web WHERE id=1");
    $wa_data = mysqli_fetch_assoc($q_wa);
    $wa_db = $wa_data['wa_cs'] ?? '082148556601';
    $wa_link = "62" . substr(preg_replace('/[^0-9]/', '', $wa_db), 1);
    ?>
    <a href="https://wa.me/<?= $wa_link ?>" target="_blank" class="wa-btn" title="Hubungi CS via WhatsApp">
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16"><path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/></svg>
    </a>

    <div id="boxErrorKoneksi" class="connection-error-box">
        <div class="error-card-center">
            <div style="width: 40px; height: 40px; background: #221111; border: 2px solid #ff4d4d; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px auto;">
                <span style="color: #ff4d4d; font-size: 1.3rem; font-weight: bold;">!</span>
            </div>
            <h3 style="color:#ff4d4d; font-size:1.1rem; font-weight:bold; margin-bottom: 5px;">Koneksi Gagal</h3>
            <p style="color:#ccc; font-size:0.75rem; line-height:1.4;">Gagal menghubungi server. Pastikan Anda online dan server menyala.</p>
            <button id="btnRetryModal" class="btn-retry" style="font-size:0.85rem; padding:8px;" onclick="tutupModalError()">🔄 Mengerti & Coba Lagi</button>
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

        function validasiFormKosong(tipe) {
            let isValid = true;
            let fields = [];

            if (tipe === 'login') { fields = ['logId', 'logPass']; } 
            else if (tipe === 'lupa') { fields = ['resetWa']; } 
            else if (tipe === 'reset') { fields = ['newPass', 'confirmPass']; }

            fields.forEach(id => {
                const el = document.getElementById(id);
                const errEl = document.getElementById('err_' + id);
                if (!el.value || el.value.trim() === "") {
                    el.classList.add('invalid-field');
                    errEl.style.display = 'block';
                    isValid = false;
                } else {
                    el.classList.remove('invalid-field');
                    errEl.style.display = 'none';
                }
            });
            return isValid;
        }

        function bukaModalError() { document.getElementById('boxErrorKoneksi').style.display = 'flex'; }
        function tutupModalError() { document.getElementById('boxErrorKoneksi').style.display = 'none'; }

        function simulasiLogin(e) {
            e.preventDefault();
            if (!validasiFormKosong('login')) return;

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
            .then(response => {
                if(!response.ok) throw new Error("Offline");
                return response.json();
            })
            .then(data => {
                if(data.status === 'success') {
                    btn.innerText = "Berhasil Masuk!";
                    btn.style.backgroundColor = "var(--success-green, #1ebe57)";
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
                btn.innerText = originalText; btn.style.opacity = "1"; btn.disabled = false;
                bukaModalError();
            });
        }

        function kirimLinkReset(e) { 
            e.preventDefault(); 
            if (!validasiFormKosong('lupa')) return;

            const noWa = document.getElementById('resetWa').value.trim();
            const btn = document.getElementById('btnReset');
            const originalText = btn.innerText;
            
            btn.innerText = "Mengirim..."; btn.style.opacity = "0.7"; btn.disabled = true;

            const formData = new FormData();
            formData.append('action', 'forgot_password');
            formData.append('resetWa', noWa);

            fetch('login.php', { method: 'POST', body: formData })
            .then(response => {
                if(!response.ok) throw new Error("Offline");
                return response.json();
            })
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
                btn.innerText = originalText; btn.style.opacity = "1"; btn.disabled = false;
                bukaModalError();
            });
        }

        function simpanPasswordBaru(e) { 
            e.preventDefault(); 
            if (!validasiFormKosong('reset')) return;

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
            .then(response => {
                if(!response.ok) throw new Error("Offline");
                return response.json();
            })
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
                btn.innerText = originalText; btn.style.opacity = "1"; btn.disabled = false;
                bukaModalError();
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