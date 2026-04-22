<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Member - Vanda Gym Classic</title>
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

        .form-container {
            background-color: #0a0a0a;
            border: 1px solid #333; border-top: 4px solid var(--primary-red);
            border-radius: 8px; padding: 30px; width: 100%; max-width: 650px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.8);
        }

        /* Tombol Kembali 44x44px */
        .nav-top { margin-bottom: 20px; }
        .btn-back-square { 
            width: 44px; height: 44px; 
            background-color: #1a1a1a; border: 1px solid #333; 
            color: var(--accent-gold); border-radius: 4px;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; font-weight: bold; font-size: 1.2rem;
            transition: 0.3s;
        }
        .btn-back-square:hover { background-color: var(--primary-red); color: white; border-color: var(--primary-red); }

        .form-header { text-align: center; margin-bottom: 25px; }
        .form-header h2 { color: var(--accent-gold); text-transform: uppercase; }

        .form-group { margin-bottom: 15px; position: relative; }
        .form-group label { display: block; margin-bottom: 8px; color: #ccc; font-weight: 600; }
        
        .form-control {
            width: 100%; padding: 10px 15px; min-height: 44px;
            background-color: var(--input-bg); border: 1px solid #333;
            border-radius: 4px; color: white; font-size: 1rem;
        }
        .form-control:focus { outline: none; border-color: var(--accent-gold); }
        .form-control.invalid { border-color: var(--primary-red); }

        input[type="date"] { color-scheme: dark; }

        .error-msg { color: #ff4d4d; font-size: 0.8rem; margin-top: 5px; display: none; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }

        .section-divider { 
            border-bottom: 1px solid #222; margin: 25px 0 20px; 
            padding-bottom: 5px; color: var(--accent-gold); font-weight: bold; text-transform: uppercase; font-size: 0.85rem;
        }

        .payment-box {
            background: #151515; padding: 20px; border-radius: 4px;
            border-left: 3px solid var(--accent-gold); margin-top: 10px; display: none;
        }

        .btn-submit {
            width: 100%; background-color: var(--primary-red); color: white;
            border: none; min-height: 48px; font-size: 1.1rem; font-weight: bold;
            border-radius: 4px; cursor: pointer; text-transform: uppercase; margin-top: 20px;
        }

        .login-footer { 
            text-align: center; margin-top: 30px; padding-top: 20px; 
            border-top: 1px solid #222; 
        }
        .login-footer a { 
            color: var(--accent-gold); text-decoration: none; font-weight: bold; 
            border: 1px solid var(--accent-gold); padding: 8px 15px; border-radius: 4px;
            margin-left: 10px; display: inline-flex; align-items: center; min-height: 44px;
        }

        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.95); display: none; justify-content: center; 
            align-items: center; z-index: 1000; padding: 20px;
        }
        .modal-box {
            background: #111; border: 1px solid var(--accent-gold);
            padding: 30px; border-radius: 8px; width: 100%; max-width: 500px;
        }
        .draf-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #222; }
    </style>
</head>
<body>

    <div class="form-container">
        <div class="nav-top">
            <a href="index.php" class="btn-back-square" title="Kembali">←</a>
        </div>

        <div class="form-header">
            <h2>Pendaftaran Member</h2>
        </div>

        <form id="formPendaftaran" onsubmit="validasiDanBukaDraf(event)">
            <div class="section-divider">Data Pribadi</div>
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" id="regNama" class="form-control" required placeholder="Contoh: Ahsana Azmiara">
            </div>
            
            <div class="grid-2">
                <div class="form-group">
                    <label>Nomor WhatsApp</label>
                    <input type="text" id="regHp" class="form-control" required oninput="validasiAngka(this)" placeholder="Contoh: 08123456789">
                    <div id="errorHp" class="error-msg">Nomor HP wajib berupa angka saja.</div>
                </div>
                <div class="form-group">
                    <label>Alamat Email</label>
                    <input type="email" id="regEmail" class="form-control" required placeholder="Contoh: nama@email.com">
                </div>
            </div>

            <div class="section-divider">Keamanan Akun</div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="regUser" class="form-control" required placeholder="Minimal 6 karakter" oninput="cekUsername(this)">
                    <div id="errorUser" class="error-msg">Username minimal harus 6 karakter.</div>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" id="regPass" class="form-control" required placeholder="Gunakan angka & huruf" oninput="cekPassword(this)">
                    <div id="errorPass" class="error-msg">Password harus mengandung kombinasi huruf dan angka.</div>
                </div>
            </div>

            <div class="section-divider">Rencana Latihan</div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Pilih Paket</label>
                    <select id="regPaket" class="form-control" required>
                        <option value="" disabled selected>-- Pilih Jenis Paket --</option>
                        <option value="1 Bulan Gym (Rp 175.000)">1 Bulan Gym</option>
                        <option value="1x Visit (Rp 25.000)">1x Visit</option>
                        <option value="Kelas Senam (Rp 25.000)">Kelas Senam</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tanggal Mulai</label>
                    <input type="date" id="regTgl" class="form-control" required>
                </div>
            </div>

            <div class="form-group">
                <label>Metode Pembayaran</label>
                <select id="regMetode" class="form-control" onchange="toggleQris()" required>
                    <option value="" disabled selected>-- Pilih Metode Bayar --</option>
                    <option value="tunai">Bayar Tunai di Resepsionis</option>
                    <option value="qris">Bayar via QRIS (Transfer)</option>
                </select>
            </div>

            <div class="payment-box" id="boxQris">
                <p style="text-align:center; margin-bottom:10px; font-size:0.9rem;">Scan QRIS Vanda Gym:</p>
                <img src="https://upload.wikimedia.org/wikipedia/commons/d/d0/QR_code_for_mobile_English_Wikipedia.svg" style="width:150px; display:block; margin:0 auto; background:#fff; padding:5px; border-radius:4px;">
                <div class="form-group" style="margin-top:15px;">
                    <label>Unggah Bukti Bayar</label>
                    <input type="file" id="regBukti" class="form-control" accept="image/*">
                </div>
            </div>

            <button type="submit" class="btn-submit">Cek Draf Pendaftaran</button>

            <div class="login-footer">
                <span>Sudah memiliki akun?</span>
                <a href="login.php">Login Sekarang</a>
            </div>
        </form>
    </div>

    <div class="modal-overlay" id="modalOverlay">
        <div class="modal-box" id="modalContent"></div>
    </div>

    <script>
        // Validasi Nomor HP
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

        // Validasi Username (Min 6 Karakter)
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

        // Validasi Password (Kombinasi Huruf & Angka)
        function cekPassword(input) {
            const error = document.getElementById('errorPass');
            const regex = /^(?=.*[0-9])(?=.*[a-zA-Z])([a-zA-Z0-9]+)$/;
            if (!regex.test(input.value) && input.value.length > 0) {
                error.style.display = 'block';
                input.classList.add('invalid');
            } else {
                error.style.display = 'none';
                input.classList.remove('invalid');
            }
        }

        function toggleQris() {
            const box = document.getElementById('boxQris');
            box.style.display = document.getElementById('regMetode').value === 'qris' ? 'block' : 'none';
        }

        function validasiDanBukaDraf(e) {
            e.preventDefault();
            const user = document.getElementById('regUser').value;
            const pass = document.getElementById('regPass').value;
            const regex = /^(?=.*[0-9])(?=.*[a-zA-Z])([a-zA-Z0-9]+)$/;

            // Final check sebelum buka draf
            if (user.length < 6 || !regex.test(pass)) {
                alert("Harap perbaiki kesalahan pada formulir sebelum melanjutkan.");
                return;
            }

            const modal = document.getElementById('modalOverlay');
            const content = document.getElementById('modalContent');
            modal.style.display = 'flex';
            content.innerHTML = `
                <h3 style="color:var(--accent-gold); border-bottom:1px solid #333; padding-bottom:10px; text-align:center;">Konfirmasi Data</h3>
                <div style="margin:20px 0;">
                    <div class="draf-item"><span>Nama:</span> <span>${document.getElementById('regNama').value}</span></div>
                    <div class="draf-item"><span>Username:</span> <span>${user}</span></div>
                    <div class="draf-item"><span>Paket:</span> <span>${document.getElementById('regPaket').value}</span></div>
                    <div class="draf-item"><span>Metode:</span> <span>${document.getElementById('regMetode').value.toUpperCase()}</span></div>
                </div>
                <p style="font-size:0.85rem; color:#888; margin-bottom:20px;">Pastikan semua informasi sudah benar sebelum dikirim ke sistem.</p>
                <button class="btn-submit" onclick="kirimFinal()">Kirim Pendaftaran</button>
                <button onclick="document.getElementById('modalOverlay').style.display='none'" style="background:transparent; border:none; color:#888; width:100%; margin-top:10px; cursor:pointer; min-height:44px;">Edit Kembali</button>
            `;
        }

        function kirimFinal() {
            const content = document.getElementById('modalContent');
            content.innerHTML = `<div style="text-align:center;"><p>Mengirim data pendaftaran...</p></div>`;

            setTimeout(() => {
                content.innerHTML = `
                    <h3 style="color:var(--accent-gold); text-align:center;">Pendaftaran Berhasil!</h3>
                    <p style="margin:20px 0; text-align:center;">Status: <strong>Sedang Diproses</strong>.</p>
                    <div style="background:#000; padding:15px; border:1px solid #222; border-radius:4px; font-size:0.9rem; line-height:1.5;">
                        <strong>Cara Cek Status:</strong><br>
                        Admin akan memverifikasi data Anda. Jika sudah aktif, Anda bisa login menggunakan username yang didaftarkan. Silakan coba login secara berkala atau hubungi CS.
                    </div>
                    <button class="btn-submit" onclick="window.location.href='index.php'">Tutup</button>
                `;
            }, 1500);
        }
    </script>
</body>
</html>