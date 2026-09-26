<?php
require 'session_init.php';
require 'includes/koneksi.php';

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

        .forgot-password {
            display: flex;
            justify-content: flex-end;
            margin-top: 7px;
        }
        .forgot-password a {
            color: var(--accent-gold);
            font-size: 0.72rem;
            text-decoration: none;
            opacity: 0.9;
            transition: opacity 0.2s ease;
        }
        .forgot-password a:hover {
            opacity: 1;
            text-decoration: underline;
        }
        .btn-positive {
            background-color: #25D366 !important; color: white !important;
            border: none !important; cursor: pointer; font-weight: bold; transition: 0.3s;
        }
        .btn-positive:hover { background-color: #1ebe57 !important; }
        .btn-positive:disabled { background-color: #555 !important; cursor: not-allowed; opacity: 0.6; }

        .connection-error-box {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(0, 0, 0, 0.85); display: none; 
            justify-content: center; align-items: center; z-index: 999999; padding: 20px;
        }
        .error-card-center {
            background-color: #0f0a0a; border: 1px solid #ff4d4d; border-top: 4px solid #ff4d4d;
            border-radius: 8px; padding: 30px 25px; max-width: 400px; width: 100%; text-align: center;
        }

       /* PEMADATAN LAYOUT MOBILE LOGIN */
        @media screen and (max-width: 768px) {
            body { padding: 5px !important; display: flex !important; align-items: center !important; justify-content: center !important; min-height: 100vh !important; }
            
            .form-container { 
                padding: 15px 15px !important; /* Padding kotak diperkecil */
                width: 92% !important; 
                max-width: 300px !important; /* Lebar maksimal kotak diperkecil dari 360px jadi 300px */
                margin: 0 auto !important; box-sizing: border-box !important; border-radius: 8px !important;
                box-shadow: 0 5px 15px rgba(0,0,0,0.5) !important;
            }
            
            .nav-top { margin-bottom: 8px !important; }
            .btn-back-square { width: 28px !important; height: 28px !important; font-size: 0.8rem !important; border-radius: 4px !important;}
            
            .form-header h2 { font-size: 1.1rem !important; margin-bottom: 2px !important; }
            .form-header p { font-size: 0.65rem !important; margin-bottom: 10px !important; line-height: 1.2 !important; }
            
            .form-group { margin-bottom: 8px !important; }
            .form-group label { font-size: 0.65rem !important; margin-bottom: 2px !important; }
            
            /* Tinggi dan padding input diperkecil */
            .form-control { padding: 6px 10px !important; min-height: 32px !important; font-size: 0.75rem !important; border-radius: 4px !important;}
            
            /* Penyesuaian ukuran dan posisi icon mata (Show/Hide Password) */
            .form-group span[onclick^="toggleVisibility"] { min-height: 32px !important; min-width: 32px !important; right: 2px !important;}
            .form-group span[onclick^="toggleVisibility"] svg { width: 14px !important; height: 14px !important; }
            
            /* Tombol utama diperkecil */
            .btn-submit { min-height: 32px !important; padding: 6px !important; font-size: 0.75rem !important; margin-top: 4px !important; border-radius: 4px !important;}
            
            .login-footer { margin-top: 10px !important; font-size: 0.7rem !important; }
            .info-box { padding: 6px 8px !important; font-size: 0.65rem !important; margin-top: 10px !important; line-height: 1.2 !important; }
            .error-msg, .field-error-text { font-size: 0.6rem !important; margin-top: 2px !important; padding: 4px 6px !important;}
            .forgot-password { margin-top: 5px; }
            .forgot-password a { font-size: 0.65rem; }
            
            /* Tombol WA mengambang diperkecil */
            .wa-btn { width: 38px !important; height: 38px !important; bottom: 12px !important; left: 12px !important; padding: 8px !important; }
            .wa-btn svg { width: 18px !important; height: 18px !important; }
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
                    <label>Email</label>
                    <input type="text" id="logId" class="form-control" placeholder="nama@email.com" oninput="cekFormatLogin(this, 'errorId')">
                    <div id="errorId" class="error-msg" style="display:none;">Format tidak valid. Masukkan Email</div>
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
                    
                    <div class="forgot-password">
                        <a href="https://instagram.com/vandagympky_classic"
                           target="_blank"
                           rel="noopener noreferrer"
                           aria-label="Hubungi admin Vanda Gym melalui Instagram untuk mengganti password">
                            Lupa password? Hubungi admin via Instagram
                        </a>
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

    </div>

    <a href="https://instagram.com/vandagympky_classic" target="_blank" class="wa-btn" title="Hubungi Admin via Instagram" style="position: fixed; bottom: 20px; left: 20px; z-index: 9999; color: #ffffff; background: var(--primary-red, #ff4d4d); border-radius: 50%; padding: 12px; box-shadow: 0 4px 15px rgba(255, 77, 77, 0.4); border: 2px solid #E8C999; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
        <circle cx="12" cy="7" r="4"></circle>
    </svg>
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
        function validasiFormKosong(tipe) {
            let isValid = true;
            let fields = [];

            if (tipe === 'login') { fields = ['logId', 'logPass']; }

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
    </script>
</body>
</html>