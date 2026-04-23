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
            background-color: var(--bg-dark); 
        }

        .form-container {
            background-color: #0a0a0a;
            border: 1px solid #333; border-top: 4px solid var(--primary-red);
            border-radius: 8px; padding: 20px 25px; 
            width: 100%; max-width: 450px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.8);
        }

        .nav-top { margin-bottom: 10px; }
        .btn-back-square { 
            width: 44px; height: 44px; 
            background-color: #1a1a1a; border: 1px solid #333; 
            color: var(--accent-gold); border-radius: 4px;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; font-weight: bold; font-size: 1.2rem;
            transition: 0.3s;
        }
        .btn-back-square:hover { background-color: var(--primary-red); color: white; border-color: var(--primary-red); }

        .form-header { text-align: center; margin-bottom: 15px; }
        .form-header h2 { color: var(--accent-gold); text-transform: uppercase; font-size: 1.4rem;}
        .form-header p { color: #888; font-size: 0.9rem; margin-top: 5px; }

        .form-group { margin-bottom: 10px; position: relative; }
        .form-group label { display: block; margin-bottom: 5px; color: #ccc; font-weight: 600; font-size: 0.9rem;}
        
        .form-control {
            width: 100%; padding: 8px 15px; min-height: 44px; 
            background-color: var(--input-bg); border: 1px solid #333;
            border-radius: 4px; color: white; font-size: 0.95rem;
        }
        .form-control:focus { outline: none; border-color: var(--accent-gold); }
        .form-control.invalid { border-color: var(--primary-red); }

        .error-msg { color: #ff4d4d; font-size: 0.8rem; margin-top: 3px; display: none; }
        
        #loginErrorBox {
            background: rgba(142, 22, 22, 0.1); padding: 10px; border-radius: 4px; 
            border: 1px dashed var(--primary-red); color: #ff4d4d; font-size: 0.85rem; 
            margin-bottom: 15px; display: none; text-align: center;
        }

        .btn-submit {
            width: 100%; background-color: var(--primary-red); color: white;
            border: none; min-height: 44px; font-size: 1rem; font-weight: bold;
            border-radius: 4px; cursor: pointer; text-transform: uppercase; margin-top: 15px;
            transition: 0.3s;
        }
        .btn-submit:hover { background-color: #a81a1a; }

        /* Box Informasi Menunggu Verifikasi */
        .info-box {
            background: rgba(232, 201, 153, 0.05); border-left: 3px solid var(--accent-gold); 
            padding: 12px 15px; margin-top: 15px; font-size: 0.85rem; color: #aaa; 
            line-height: 1.5; border-radius: 4px;
        }
        .info-box a { color: var(--accent-gold); text-decoration: none; font-weight: bold; }
        .info-box a:hover { text-decoration: underline; }

        .login-footer { 
            text-align: center; margin-top: 20px; padding-top: 15px; 
            border-top: 1px solid #222; display: flex; flex-direction: column; gap: 10px;
        }
        .login-footer div { display: flex; flex-wrap: wrap; justify-content: center; align-items: center; gap: 8px; font-size: 0.85rem;}
        .login-footer a { 
            color: var(--accent-gold); text-decoration: none; font-weight: bold; 
            border: 1px solid var(--accent-gold); padding: 5px 15px; border-radius: 4px;
            display: inline-flex; align-items: center; justify-content: center; min-height: 38px;
            transition: 0.3s;
        }
        .login-footer a:hover { background: var(--accent-gold); color: #000; }
    </style>
</head>
<body>

    <div class="form-container">
        <div class="nav-top">
            <a href="index.php" class="btn-back-square" title="Kembali">←</a>
        </div>

        <div class="form-header">
            <h2>Login Sistem</h2>
            <p>Silakan masuk ke akun Vanda Gym Anda</p>
        </div>

        <div id="loginErrorBox"></div>

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
                           placeholder="Angka & huruf" oninput="cekPassword(this)" 
                           style="padding-right: 50px;">
                    
                    <span id="togglePassword" onclick="toggleVisibility()" 
                          style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); 
                                 cursor: pointer; min-height: 44px; min-width: 44px; 
                                 display: flex; align-items: center; justify-content: center; z-index: 10;">
                        
                        <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" 
                             viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" 
                             stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </span>
                </div>
                <div id="errorPass" class="error-msg">Gunakan huruf & angka.</div>
            </div>

            <button type="submit" class="btn-submit">Masuk</button>

            <div class="info-box">
                <strong>Catatan:</strong> Jika Anda baru saja mendaftar, akun hanya bisa digunakan untuk login <em>setelah</em> diverifikasi oleh Admin. <br>
                Cek <a href="cek_status.php">Status Pendaftaran Anda di sini</a>.
            </div>

            <div class="login-footer">
                <div>
                    <span style="color: #888;">Belum menjadi member?</span>
                    <a href="daftar.php">Daftar Sekarang</a>
                </div>
                <div>
                    <span style="color: #888;">Lupa Password?</span>
                    <a href="https://wa.me/6282148556601?text=Halo%20Admin%20Vanda%20Gym,%20saya%20lupa%20password%20akun%20saya.%20Mohon%20bantuannya." target="_blank" style="border-color: #555; color: #ccc;">Hubungi CS</a>
                </div>
            </div>
        </form>
    </div>

    <script>
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

        function cekPassword(input) {
            const error = document.getElementById('errorPass');
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
        }

        function toggleVisibility() {
            const passwordInput = document.getElementById('logPass');
            const eyeIcon = document.getElementById('eyeIcon');
            
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
            const userField = document.getElementById('logUser');
            const passField = document.getElementById('logPass');

            errorBox.style.display = 'none';

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

            // Simulasi Sukses Member
            prosesMasuk('member_dasbor.php');
        }

        function prosesMasuk(targetHalaman) {
            const btn = document.querySelector('.btn-submit');
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