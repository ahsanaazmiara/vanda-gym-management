<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Vanda Gym Classic</title>
    <style>
        :root {
            --bg-dark: #000000;
            --primary-red: #8E1616;
            --accent-gold: #E8C999;
            --text-light: #F8EEDF;
            --input-bg: #111111;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: var(--bg-dark); 
            color: var(--text-light); 
            display: flex; justify-content: center; align-items: center;
            min-height: 100vh; padding: 20px;
        }

        .form-container {
            background-color: #0a0a0a;
            border: 1px solid #333; border-top: 4px solid var(--primary-red);
            border-radius: 8px; padding: 30px; 
            width: 100%; max-width: 450px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.8);
            position: relative;
        }

        .nav-top { margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; }
        .btn-back-square { 
            width: 44px; height: 44px; 
            background-color: #1a1a1a; border: 1px solid #333; 
            color: var(--accent-gold); border-radius: 4px;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; font-weight: bold; font-size: 1.2rem;
            transition: 0.3s;
        }
        .btn-back-square:hover { background-color: var(--primary-red); color: white; border-color: var(--primary-red); }

        .form-header { text-align: center; margin-bottom: 20px; }
        .form-header h2 { color: var(--accent-gold); text-transform: uppercase; font-size: 1.5rem; letter-spacing: 1px; margin-bottom: 5px;}
        .form-header p { color: #888; font-size: 0.9rem; }

        .form-group { margin-bottom: 15px; position: relative; text-align: left; }
        .form-group label { display: block; margin-bottom: 8px; color: #ccc; font-weight: 600; font-size: 0.85rem;}
        
        .form-control {
            width: 100%; padding: 12px 15px; min-height: 44px; 
            background-color: var(--input-bg); border: 1px solid #333;
            border-radius: 4px; color: white; font-size: 0.95rem; transition: 0.3s;
        }
        .form-control:focus { outline: none; border-color: var(--accent-gold); }
        .form-control.invalid { border-color: var(--primary-red); }

        .error-msg { color: #ff4d4d; font-size: 0.75rem; margin-top: 5px; display: none; }
        
        #loginErrorBox, #resetErrorBox {
            background: rgba(142, 22, 22, 0.1); padding: 12px; border-radius: 4px; 
            border: 1px dashed var(--primary-red); color: #ff4d4d; font-size: 0.85rem; 
            margin-bottom: 20px; display: none; text-align: center;
        }

        .btn-submit {
            width: 100%; background-color: var(--primary-red); color: white;
            border: none; min-height: 48px; font-size: 1rem; font-weight: bold;
            border-radius: 4px; cursor: pointer; text-transform: uppercase; margin-top: 10px;
            transition: 0.3s;
        }
        .btn-submit:hover { background-color: #a81a1a; transform: translateY(-2px); }

        .info-box {
            background: rgba(232, 201, 153, 0.05); border-left: 3px solid var(--accent-gold); 
            padding: 12px 15px; margin-top: 20px; font-size: 0.8rem; color: #aaa; 
            line-height: 1.5; border-radius: 4px; text-align: left;
        }
        .info-box a { color: var(--accent-gold); text-decoration: none; font-weight: bold; }
        .info-box a:hover { text-decoration: underline; }

        .login-footer { 
            text-align: center; margin-top: 25px; padding-top: 15px; 
            border-top: 1px solid #222; display: flex; flex-direction: column; gap: 10px;
        }
        .login-footer div { display: flex; flex-wrap: wrap; justify-content: center; align-items: center; gap: 8px; font-size: 0.85rem;}
        .login-footer a { 
            color: var(--accent-gold); text-decoration: none; font-weight: bold; 
            border: 1px solid var(--accent-gold); padding: 8px 15px; border-radius: 4px;
            display: inline-flex; align-items: center; justify-content: center; min-height: 38px;
            transition: 0.3s;
        }
        .login-footer a:hover { background: var(--accent-gold); color: #000; }

        /* Khusus untuk Lupa Password */
        .icon-lock { font-size: 3rem; margin-bottom: 10px; text-align: center; }
        .success-msg {
            display: none; background: rgba(40, 167, 69, 0.1); border: 1px dashed #28a745;
            color: #28a745; padding: 15px; border-radius: 4px; margin-bottom: 20px; font-size: 0.9rem; text-align: center; line-height: 1.5;
        }
        .text-link { color: #888; font-size: 0.85rem; text-decoration: none; transition: 0.3s; cursor: pointer; }
        .text-link:hover { color: var(--accent-gold); }
        
        .btn-simulasi {
            background: transparent; border: 1px solid var(--success-green); color: var(--success-green);
            padding: 8px 15px; border-radius: 4px; font-size: 0.85rem; cursor: pointer; margin-top: 10px; transition: 0.3s; font-weight: bold;
        }
        .btn-simulasi:hover { background: var(--success-green); color: #fff; }
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

            <div id="loginErrorBox"></div>
            <div id="loginSuccessBox" class="success-msg" style="border-color: var(--accent-gold); color: var(--accent-gold); background: rgba(232, 201, 153, 0.1);"></div>

            <form id="formLogin" onsubmit="simulasiLogin(event)">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="logUser" class="form-control" required placeholder="Minimal 6 karakter" oninput="cekUsername(this)">
                    <div id="errorUser" class="error-msg">Minimal 6 karakter.</div>
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

                <button type="submit" class="btn-submit">Masuk</button>

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
                <p>Masukkan alamat email yang terdaftar. Kami akan mengirimkan tautan untuk mengatur ulang password.</p>
            </div>

            <div id="pesanSuksesReset" class="success-msg"></div>

            <form id="formReset" onsubmit="kirimLinkReset(event)">
                <div class="form-group">
                    <label>Alamat Email Terdaftar</label>
                    <input type="email" id="resetEmail" class="form-control" placeholder="Masukkan email Anda" required>
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

            <form id="formNewPass" onsubmit="simulasiBuatPasswordBaru(event)">
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
                btnBack.href = "index.php";
                btnBack.onclick = null;
                btnBack.title = "Kembali ke Beranda";
                
                // Reset form lupa password
                document.getElementById('formReset').style.display = 'block';
                document.getElementById('pesanSuksesReset').style.display = 'none';
                document.getElementById('btnReset').innerText = "Kirim Tautan Reset";
                document.getElementById('btnReset').disabled = false;
                document.getElementById('resetEmail').value = "";
            } else if (mode === 'lupa') {
                btnBack.href = "javascript:void(0)";
                btnBack.onclick = function(e) { e.preventDefault(); toggleMode('login'); };
                btnBack.title = "Kembali ke Login";
                document.getElementById('loginSuccessBox').style.display = 'none';
            } else if (mode === 'buat_password') {
                // Simulasi pengguna mengklik link dari email, back button kembali ke form login
                btnBack.href = "javascript:void(0)";
                btnBack.onclick = function(e) { e.preventDefault(); toggleMode('login'); };
                btnBack.title = "Batal dan Kembali Login";
                
                // Bersihkan field
                document.getElementById('newPass').value = "";
                document.getElementById('confirmPass').value = "";
                document.getElementById('resetErrorBox').style.display = 'none';
            }
        }

        function kirimLinkReset(e) {
            e.preventDefault();
            const btn = document.getElementById('btnReset');
            const form = document.getElementById('formReset');
            const pesan = document.getElementById('pesanSuksesReset');
            const email = document.getElementById('resetEmail').value;

            btn.innerText = "Mengirim...";
            btn.disabled = true;

            setTimeout(() => {
                form.style.display = 'none'; 
                pesan.style.display = 'block'; 
                pesan.innerHTML = `Tautan reset password telah dikirim ke <strong>${email}</strong>! Silakan periksa email Anda.<br><br>
                <button type="button" class="btn-simulasi" onclick="toggleMode('buat_password')">[SIMULASI] Klik Link di Email</button>`;
            }, 1500);
        }

        // --- Fungsi Validasi Password Baru ---
        function cekKonfirmasiPassword() {
            const pass1 = document.getElementById('newPass').value;
            const pass2 = document.getElementById('confirmPass').value;
            const error = document.getElementById('errorConfirmPass');
            const input2 = document.getElementById('confirmPass');

            if (pass2.length > 0 && pass1 !== pass2) {
                error.style.display = 'block';
                input2.classList.add('invalid');
            } else {
                error.style.display = 'none';
                input2.classList.remove('invalid');
            }
        }

        function simulasiBuatPasswordBaru(e) {
            e.preventDefault();
            const pass1 = document.getElementById('newPass').value;
            const pass2 = document.getElementById('confirmPass').value;
            const errorBox = document.getElementById('resetErrorBox');
            const btn = document.getElementById('btnSimpanPass');

            const regex = /^(?=.*[0-9])(?=.*[a-zA-Z])([a-zA-Z0-9]+)$/;
            
            errorBox.style.display = 'none';

            if (pass1.length < 6 || !regex.test(pass1)) {
                errorBox.innerHTML = "❌ Password baru harus mengandung huruf dan angka (minimal 6 karakter).";
                errorBox.style.display = 'block';
                return;
            }

            if (pass1 !== pass2) {
                errorBox.innerHTML = "❌ Konfirmasi password tidak cocok!";
                errorBox.style.display = 'block';
                return;
            }

            btn.innerText = "Menyimpan...";
            btn.disabled = true;

            setTimeout(() => {
                // Berhasil ubah, kembali ke halaman login utama dengan pesan sukses
                toggleMode('login');
                const successBox = document.getElementById('loginSuccessBox');
                successBox.innerHTML = "✅ Password berhasil diperbarui! Silakan login dengan password baru Anda.";
                successBox.style.display = 'block';
                
                btn.innerText = "Simpan Password";
                btn.disabled = false;
            }, 1500);
        }
        // -------------------------------------

        function cekUsername(input) {
            const error = document.getElementById('errorUser');
            if (input.value.toLowerCase() === 'admin') {
                error.style.display = 'none';
                input.classList.remove('invalid');
                return;
            }
            if (input.value.length < 6 && input.value.length > 0) {
                error.style.display = 'block';
                input.classList.add('invalid');
            } else {
                error.style.display = 'none';
                input.classList.remove('invalid');
            }
        }

        function cekPassword(input, errorId) {
            const error = document.getElementById(errorId);
            const regex = /^(?=.*[0-9])(?=.*[a-zA-Z])([a-zA-Z0-9]+)$/;
            
            if (input.value.toLowerCase() === 'admin') {
                error.style.display = 'none';
                input.classList.remove('invalid');
                return;
            }

            if (!regex.test(input.value) && input.value.length > 0) {
                error.style.display = 'block';
                input.classList.add('invalid');
            } else {
                error.style.display = 'none';
                input.classList.remove('invalid');
            }
            
            // Trigger konfirmasi ulang jika field pertama diubah
            if (errorId === 'errorNewPass' && document.getElementById('confirmPass').value.length > 0) {
                cekKonfirmasiPassword();
            }
        }

        function toggleVisibility(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const eyeIcon = document.getElementById(iconId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML = `
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                    <line x1="1" y1="1" x2="23" y2="23"></line>
                `;
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = `
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                `;
            }
        }

        function simulasiLogin(e) {
            e.preventDefault();
            const user = document.getElementById('logUser').value.trim();
            const pass = document.getElementById('logPass').value.trim();
            const errorBox = document.getElementById('loginErrorBox');
            const successBox = document.getElementById('loginSuccessBox');

            errorBox.style.display = 'none';
            successBox.style.display = 'none'; // Sembunyikan pesan reset berhasil jika ada

            if (user.toLowerCase() === 'admin' && pass.toLowerCase() === 'admin') {
                prosesMasuk('admin_dasbor.php');
                return;
            }

            const adaAngka = /\d/.test(pass);
            const adaHuruf = /[a-zA-Z]/.test(pass);

            if (user.length < 6 || !adaAngka || !adaHuruf) {
                errorBox.innerHTML = "❌ Username/Password tidak valid. Pastikan sesuai format.";
                errorBox.style.display = 'block';
                return;
            }

            // SIMULASI LOGIN
            if (user !== 'ahsana123') {
                errorBox.innerHTML = "❌ Akun tidak ditemukan. Silakan cek kembali username Anda.";
                errorBox.style.display = 'block';
                return;
            }

            if (pass !== 'password123') {
                errorBox.innerHTML = "❌ Password yang Anda masukkan salah.";
                errorBox.style.display = 'block';
                return;
            }

            prosesMasuk('member_dasbor.php');
        }

        function prosesMasuk(targetHalaman) {
            const btn = document.querySelector('#formLoginUtama .btn-submit');
            const originalText = btn.innerText;
            btn.innerText = "Memeriksa...";
            btn.style.opacity = "0.7";

            setTimeout(() => {
                window.location.href = targetHalaman; 
            }, 800);
        }
    </script>
</body>
</html>