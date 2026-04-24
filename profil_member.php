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
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: var(--bg-dark); 
            color: var(--text-light); 
            display: flex; justify-content: center; align-items: center;
            min-height: 100vh; padding: 40px 20px;
        }

        .profil-container {
            background-color: #0a0a0a;
            border: 1px solid #333; border-top: 4px solid var(--primary-red);
            border-radius: 8px; padding: 30px;
            width: 100%; max-width: 600px;
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

        .section-header { 
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid #222; padding-bottom: 10px; margin: 25px 0 15px;
        }
        .section-header h3 { color: var(--accent-gold); text-transform: uppercase; font-size: 1rem; }

        .form-group { margin-bottom: 15px; text-align: left; }
        .form-group label { display: block; margin-bottom: 8px; color: #ccc; font-weight: 600; font-size: 0.85rem; }
        
        .form-control {
            width: 100%; padding: 12px 15px; min-height: 44px;
            background-color: var(--input-bg); border: 1px solid #333;
            border-radius: 4px; color: white; font-size: 0.95rem; transition: 0.3s;
        }
        .form-control:focus { outline: none; border-color: var(--accent-gold); }
        .form-control:disabled { background-color: #050505; color: #666; cursor: not-allowed; border-color: #222; }
        .form-control.invalid { border-color: var(--primary-red); }

        .error-msg { color: #ff4d4d; font-size: 0.75rem; margin-top: 5px; display: none; }

        .btn-action {
            padding: 8px 15px; border-radius: 4px; font-weight: bold; cursor: pointer; transition: 0.3s; font-size: 0.85rem; border: 1px solid var(--accent-gold); background: transparent; color: var(--accent-gold);
        }
        .btn-action:hover { background: var(--accent-gold); color: #000; }
        .btn-save { background-color: var(--primary-red); color: white; border: none; width: 100%; min-height: 48px; text-transform: uppercase; margin-top: 10px; display: none; cursor: pointer; font-weight: bold; border-radius: 4px; transition: 0.3s;}
        .btn-save:hover { background-color: #a81a1a; }
        .btn-cancel { background: transparent; color: #888; border: none; width: 100%; margin-top: 5px; cursor: pointer; display: none; font-size: 0.85rem; }

        .toast {
            position: fixed; top: 20px; right: 20px; background: #28a745;
            color: white; padding: 15px 25px; border-radius: 4px; font-weight: bold;
            display: none; z-index: 1000; box-shadow: 0 5px 15px rgba(0,0,0,0.5);
        }

        .eye-toggle {
            position: absolute; right: 5px; top: 50%; transform: translateY(-50%); 
            cursor: pointer; min-height: 44px; min-width: 44px; 
            display: flex; align-items: center; justify-content: center; z-index: 10;
        }

        .text-link { color: #888; font-size: 0.85rem; text-decoration: none; transition: 0.3s; cursor: pointer; }
        .text-link:hover { color: var(--accent-gold); }
        .icon-lock { font-size: 3rem; margin-bottom: 10px; text-align: center; }
        .success-msg {
            display: none; background: rgba(40, 167, 69, 0.1); border: 1px dashed #28a745;
            color: #28a745; padding: 15px; border-radius: 4px; margin-bottom: 20px; font-size: 0.9rem; text-align: center;
        }
    </style>
</head>
<body>

    <div id="toastNotif" class="toast">Data berhasil disimpan!</div>

    <div class="profil-container">
        <div class="nav-top">
            <a href="member_dasbor.php" id="btnBackTop" class="btn-back-square" title="Kembali ke Dasbor">←</a>
            <span style="color: #444; font-size: 0.8rem;">ID Member: VGYM-202604</span>
        </div>

        <div id="blokProfilUtama">
            <h2 style="text-align:center; color:var(--text-light); text-transform:uppercase; letter-spacing:1px;">Pengaturan Akun</h2>

            <div class="section-header">
                <h3>Data Pribadi</h3>
                <button type="button" class="btn-action" id="btnEditProfil" onclick="toggleEdit('profil')">Edit Profil</button>
            </div>

            <form id="formProfil" onsubmit="handleSimpan(event, 'profil')">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" id="profNama" class="form-control" value="Ahsana Azmiara Ahmadiham" disabled required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Nomor WhatsApp</label>
                        <input type="text" id="profHp" class="form-control" value="082148556601" disabled required oninput="validasiAngka(this)">
                        <div id="errorHp" class="error-msg">Wajib angka saja.</div>
                    </div>
                    <div class="form-group">
                        <label>Alamat Email</label>
                        <input type="email" id="profEmail" class="form-control" value="ahsana@email.com" disabled required>
                    </div>
                </div>

                <button type="submit" id="saveProfil" class="btn-save">Simpan Perubahan</button>
                <button type="button" id="cancelProfil" class="btn-cancel" onclick="toggleEdit('profil')">Batal</button>
            </form>

            <div class="section-header">
                <h3>Keamanan Akun</h3>
                <button type="button" class="btn-action" id="btnEditKeamanan" onclick="toggleEdit('keamanan')">Ubah Akun</button>
            </div>

            <form id="formKeamanan" onsubmit="handleSimpan(event, 'keamanan')">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="profUser" class="form-control" value="ahsana123" disabled required oninput="cekUsername(this)">
                    <div id="errorUser" class="error-msg">Minimal 6 karakter.</div>
                </div>

                <div id="groupPassDummy">
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" class="form-control" value="passwordpalsu" disabled>
                    </div>
                </div>

                <div id="groupEditPass" style="display: none;">
                    <div class="form-group" style="position: relative;">
                        <label>Password Lama</label>
                        <input type="password" id="profPassLama" class="form-control" placeholder="Masukkan password saat ini" disabled required oninput="cekPassword(this, 'errorPassLama')" style="padding-right: 50px;">
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
                        <input type="password" id="profPass" class="form-control" placeholder="Min. 6 karakter (Huruf & Angka)" disabled required oninput="cekPassword(this, 'errorPassBaru')" style="padding-right: 50px;">
                        <span class="eye-toggle" onclick="toggleVisibility('profPass', 'eyeIconBaru')">
                            <svg id="eyeIconBaru" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </span>
                        <div id="errorPassBaru" class="error-msg">Gunakan huruf & angka (min 6).</div>
                    </div>
                </div>

                <button type="submit" id="saveKeamanan" class="btn-save">Simpan Keamanan</button>
                <button type="button" id="cancelKeamanan" class="btn-cancel" onclick="toggleEdit('keamanan')">Batal</button>
            </form>
        </div>

        <div id="blokResetPassword" style="display: none; padding-top: 10px;">
            <div class="icon-lock">🔐</div>
            <div style="text-align: center; margin-bottom: 25px;">
                <h2 style="color: var(--accent-gold); text-transform: uppercase; font-size: 1.4rem; margin-bottom: 5px;">Lupa Password?</h2>
                <p style="color: #888; font-size: 0.9rem; line-height: 1.4;">Kami akan mengirimkan tautan untuk mengatur ulang password ke email Anda.</p>
            </div>

            <div id="pesanSuksesReset" class="success-msg"></div>

            <form id="formResetPass" onsubmit="kirimLinkReset(event)">
                <div class="form-group">
                    <label>Alamat Email Terdaftar</label>
                    <input type="email" id="resetEmailProf" class="form-control" placeholder="Masukkan email Anda" required>
                </div>
                <button type="submit" id="btnKirimReset" class="btn-save" style="display: block; margin-top: 20px;">Kirim Tautan Reset</button>
            </form>
        </div>

    </div>

    <script>
        function toggleResetForm(tampilkanLupaPass, e) {
            if (e) e.preventDefault(); // Mencegah link pindah halaman
            
            const btnBack = document.getElementById('btnBackTop');

            document.getElementById('blokProfilUtama').style.display = tampilkanLupaPass ? 'none' : 'block';
            document.getElementById('blokResetPassword').style.display = tampilkanLupaPass ? 'block' : 'none';
            
            if (tampilkanLupaPass) {
                document.getElementById('pesanSuksesReset').style.display = 'none';
                document.getElementById('formResetPass').style.display = 'block';
                document.getElementById('btnKirimReset').innerText = "Kirim Tautan Reset";
                document.getElementById('btnKirimReset').disabled = false;
                document.getElementById('resetEmailProf').value = ""; // Mengosongkan isian email agar diketik manual

                // Tombol Back Kiri Atas kembali ke Pengaturan Akun
                btnBack.onclick = function(e) { 
                    e.preventDefault(); 
                    toggleResetForm(false); 
                };
                btnBack.title = "Kembali ke Pengaturan";
            } else {
                // Tombol Back Kiri Atas kembali ke Dasbor
                btnBack.onclick = null;
                btnBack.href = "member_dasbor.php";
                btnBack.title = "Kembali ke Dasbor";
            }
        }

        function kirimLinkReset(e) {
            e.preventDefault();
            const btn = document.getElementById('btnKirimReset');
            const form = document.getElementById('formResetPass');
            const pesan = document.getElementById('pesanSuksesReset');
            const email = document.getElementById('resetEmailProf').value;

            btn.innerText = "Mengirim...";
            btn.disabled = true;

            setTimeout(() => {
                form.style.display = 'none'; 
                pesan.style.display = 'block'; 
                pesan.innerHTML = `Tautan reset password telah dikirim ke <strong>${email}</strong>! Silakan periksa kotak masuk Anda.`;
            }, 1500);
        }

        function toggleEdit(tipe) {
            const isProfil = (tipe === 'profil');
            
            if (isProfil) {
                const ids = ['profNama', 'profHp', 'profEmail', 'btnEditProfil', 'saveProfil', 'cancelProfil'];
                const elements = ids.map(id => document.getElementById(id));
                const isDisabled = elements[0].disabled;

                for(let i=0; i < 3; i++) elements[i].disabled = !isDisabled;
                
                elements[3].style.display = isDisabled ? 'none' : 'block'; 
                elements[4].style.display = isDisabled ? 'block' : 'none'; 
                elements[5].style.display = isDisabled ? 'block' : 'none'; 
                if (isDisabled) elements[0].focus();

            } else {
                const ids = ['profUser', 'profPassLama', 'profPass', 'btnEditKeamanan', 'saveKeamanan', 'cancelKeamanan'];
                const elements = ids.map(id => document.getElementById(id));
                const isDisabled = elements[0].disabled;

                elements[0].disabled = !isDisabled; 
                elements[1].disabled = !isDisabled; 
                elements[2].disabled = !isDisabled; 

                document.getElementById('groupPassDummy').style.display = isDisabled ? 'none' : 'block';
                document.getElementById('groupEditPass').style.display = isDisabled ? 'block' : 'none';

                elements[3].style.display = isDisabled ? 'none' : 'block'; 
                elements[4].style.display = isDisabled ? 'block' : 'none'; 
                elements[5].style.display = isDisabled ? 'block' : 'none'; 
                
                if (isDisabled) {
                    elements[1].value = '';
                    elements[2].value = '';
                    elements[1].focus(); 
                }
            }
        }

        function validasiAngka(input) {
            const error = document.getElementById('errorHp');
            if (/\D/g.test(input.value)) {
                error.style.display = 'block';
                input.classList.add('invalid');
                input.value = input.value.replace(/\D/g, ''); 
            } else {
                error.style.display = 'none';
                input.classList.remove('invalid');
            }
        }

        function cekUsername(input) {
            const error = document.getElementById('errorUser');
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
            const regex = /^(?=.*[0-9])(?=.*[a-zA-Z])([a-zA-Z0-9]{6,})$/;
            if (!regex.test(input.value) && input.value.length > 0) {
                error.style.display = 'block';
                input.classList.add('invalid');
            } else {
                error.style.display = 'none';
                input.classList.remove('invalid');
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

        function handleSimpan(e, tipe) {
            e.preventDefault();
            const form = e.target;
            
            if (form.querySelector('.invalid')) {
                alert("Harap perbaiki kesalahan input.");
                return;
            }

            if (tipe === 'keamanan') {
                const passLama = document.getElementById('profPassLama').value;
                if (passLama !== "password123") {
                    alert("❌ Password Lama salah! Gagal memperbarui keamanan akun.");
                    document.getElementById('profPassLama').classList.add('invalid');
                    return;
                }
            }

            const btn = form.querySelector('.btn-save');
            btn.innerText = "Menyimpan...";
            btn.disabled = true;

            setTimeout(() => {
                showToast("Data berhasil disimpan!");
                btn.innerText = "Simpan Perubahan";
                btn.disabled = false;
                toggleEdit(tipe);
            }, 1000);
        }

        function showToast(pesan) {
            const toast = document.getElementById('toastNotif');
            toast.innerText = pesan;
            toast.style.display = 'block';
            setTimeout(() => { toast.style.display = 'none'; }, 3000);
        }
    </script>
</body>
</html>