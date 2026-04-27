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

        .form-header { text-align: center; margin-bottom: 25px; }
        .form-header h2 { color: var(--text-light); text-transform: uppercase; font-size: 1.5rem; letter-spacing: 1px; margin-bottom: 5px;}
        .form-header p { color: #888; font-size: 0.9rem; }

        .section-divider { 
            border-bottom: 1px solid #222; margin: 25px 0 15px; 
            padding-bottom: 8px; color: var(--accent-gold); font-weight: bold; text-transform: uppercase; font-size: 0.9rem;
        }

        .form-group { margin-bottom: 15px; text-align: left; position: relative;}
        .form-group label { display: block; margin-bottom: 8px; color: #ccc; font-weight: 600; font-size: 0.85rem; }
        
        .form-control {
            width: 100%; padding: 12px 15px; background-color: var(--input-bg); 
            border: 1px solid #333; border-radius: 4px; color: white; font-size: 0.95rem; transition: 0.3s;
        }
        .form-control:focus { outline: none; border-color: var(--accent-gold); }
        .form-control.invalid { border-color: var(--primary-red); }
        input[type="date"] { color-scheme: dark; }

        .error-msg { color: #ff4d4d; font-size: 0.75rem; margin-top: 5px; display: none; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }

        /* Status Nominal Tagihan Box */
        .nominal-box {
            background: rgba(232, 201, 153, 0.05); border: 1px dashed var(--accent-gold);
            padding: 15px; border-radius: 6px; margin-top: 15px; margin-bottom: 15px; text-align: center;
            display: none; flex-direction: column; align-items: center;
        }
        .nominal-box span:first-child { color: #ccc; font-size: 0.85rem; margin-bottom: 5px;}
        .nominal-box span:last-child { color: var(--accent-gold); font-weight: bold; font-size: 1.4rem; }

        /* Pilihan Metode Pembayaran (Radio Buttons) */
        .payment-methods { display: flex; gap: 15px; margin-bottom: 20px; }
        .pay-method {
            flex: 1; border: 1px solid #333; border-radius: 6px; padding: 15px 10px;
            text-align: center; cursor: pointer; transition: 0.3s; background: #151515;
            position: relative;
        }
        .pay-method input { position: absolute; opacity: 0; cursor: pointer; }
        .pay-method span { font-weight: bold; color: #888; display: block; font-size: 0.9rem;}
        
        /* State Aktif Metode Pembayaran */
        .pay-method.active { border-color: var(--accent-gold); background: rgba(232, 201, 153, 0.1); }
        .pay-method.active span { color: var(--accent-gold); }

        /* Blok Detail Pembayaran */
        .pay-details { 
            background: #111; border: 1px solid #222; padding: 20px; 
            border-radius: 6px; margin-bottom: 20px; display: none;
        }
        
        .qris-box { text-align: center; }
        .qris-box img { max-width: 150px; border-radius: 8px; margin: 10px 0; border: 2px solid white; background: #fff;}
        
        /* Tombol Upload Kustom */
        .file-upload-wrapper { position: relative; margin-top: 15px; text-align: left; }
        .file-upload-wrapper input[type="file"] {
            position: absolute; left: 0; top: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer;
        }
        .btn-upload {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            background: #1a1a1a; border: 1px dashed var(--accent-gold); color: var(--accent-gold);
            padding: 12px; border-radius: 4px; width: 100%; font-size: 0.9rem; transition: 0.3s;
        }
        .file-upload-wrapper:hover .btn-upload { background: #222; }

        .btn-submit {
            width: 100%; background-color: var(--primary-red); color: white;
            border: none; min-height: 48px; font-size: 1rem; font-weight: bold;
            border-radius: 4px; cursor: pointer; text-transform: uppercase; margin-top: 10px;
            transition: 0.3s;
        }
        .btn-submit:hover { background-color: #a81a1a; transform: translateY(-2px); }

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

        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.95); display: none; justify-content: center; 
            align-items: center; z-index: 1000; padding: 20px; overflow-y: auto;
        }
        .modal-box {
            background: #111; border: 1px solid var(--accent-gold);
            padding: 25px; border-radius: 8px; width: 100%; max-width: 450px;
        }
        .draf-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #222; font-size: 0.9rem;}
    </style>
</head>
<body>

    <div class="form-container">
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
                <input type="text" id="regNama" class="form-control" required placeholder="Contoh: Ahsana Azmiara">
            </div>
            
            <div class="grid-2">
                <div class="form-group">
                    <label>Nomor WhatsApp</label>
                    <input type="text" id="regHp" class="form-control" required oninput="validasiAngka(this)" placeholder="0812xxxx">
                    <div id="errorHp" class="error-msg">Wajib angka saja.</div>
                </div>
                <div class="form-group">
                    <label>Alamat Email</label>
                    <input type="email" id="regEmail" class="form-control" required placeholder="nama@email.com">
                </div>
            </div>

            <div class="section-divider">2. Keamanan Akun</div>
            <div class="form-group">
                <label>Password Akun</label>
                <div style="position: relative;">
                    <input type="password" id="regPass" class="form-control" required placeholder="Kombinasi angka & huruf" oninput="cekPassword(this)" style="padding-right: 50px;">
                    <span id="togglePassword" onclick="toggleVisibility()" style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); cursor: pointer; min-height: 44px; min-width: 44px; display: flex; align-items: center; justify-content: center; z-index: 10;">
                        <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </span>
                </div>
                <div id="errorPass" class="error-msg">Gunakan minimal satu huruf dan satu angka.</div>
                <p style="font-size: 0.8rem; color: #888; margin-top: 8px;">Gunakan email Anda sebagai identitas login nantinya.</p>
            </div>

            <div class="section-divider">3. Paket Latihan & Pembayaran</div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Pilih Durasi</label>
                    <select id="regPaket" class="form-control" onchange="updateNominal()" required style="cursor: pointer;">
                        <option value="" disabled selected>-- Pilih Paket --</option>
                        <option value="175000" data-nama="1 Bulan Gym">1 Bulan Gym</option>
                        <option value="350000" data-nama="2 Bulan Gym">2 Bulan Gym</option>
                        <option value="525000" data-nama="3 Bulan Gym">3 Bulan Gym</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tanggal Mulai</label>
                    <input type="date" id="regTgl" class="form-control" required>
                </div>
            </div>

            <div class="nominal-box" id="boxNominal">
                <span>Total Tagihan Pendaftaran:</span>
                <span id="textNominal">Rp 0</span>
            </div>

            <div class="form-group">
                <label>Metode Pembayaran</label>
                <div class="payment-methods">
                    <label class="pay-method active" id="labelQris">
                        <input type="radio" name="metodeBayar" value="qris" checked onchange="ubahMetode()">
                        <span>📱 Transfer / QRIS</span>
                    </label>
                    <label class="pay-method" id="labelTunai">
                        <input type="radio" name="metodeBayar" value="tunai" onchange="ubahMetode()">
                        <span>💵 Bayar Tunai (Kasir)</span>
                    </label>
                </div>
            </div>

            <div id="detailQris" class="pay-details" style="display: block;">
                <div class="qris-box">
                    <p style="font-size: 0.85rem; color: #ccc;">Scan QR Code di bawah atau transfer ke:<br><strong>BCA 123-456-789 a.n Vanda Gym</strong></p>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=Pembayaran+Member+Baru+Vanda+Gym" alt="QRIS Vanda Gym">
                </div>
                <div class="file-upload-wrapper">
                    <label style="font-size: 0.8rem; color: #888; margin-bottom: 5px; display: block;">Upload Bukti Transfer (Wajib)</label>
                    <div class="btn-upload">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/></svg>
                        <span id="namaFile">Pilih Gambar / Screenshot...</span>
                    </div>
                    <input type="file" id="regBukti" accept="image/*" required onchange="tampilkanNamaFile(this)">
                </div>
            </div>

            <div id="detailTunai" class="pay-details">
                <div style="text-align: center;">
                    <svg viewBox="0 0 24 24" width="40" height="40" fill="var(--accent-gold)" style="margin-bottom: 10px;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.41 16.09V20h-2.67v-1.93c-1.71-.36-3.16-1.46-3.27-3.4h1.96c.1 1.05.82 1.87 2.65 1.87 1.96 0 2.4-.98 2.4-1.59 0-.83-.44-1.61-2.67-2.14-2.48-.6-4.18-1.62-4.18-3.67 0-1.72 1.39-2.84 3.11-3.21V4h2.67v1.95c1.86.45 2.79 1.86 2.85 3.39H14.3c-.05-1.11-.64-1.87-2.22-1.87-1.5 0-2.4.68-2.4 1.64 0 .84.65 1.39 2.67 1.91 2.95.73 4.18 1.9 4.18 3.91-.01 1.83-1.38 2.83-3.12 3.16z"/></svg>
                    <p style="font-size: 0.8rem; color: #888; margin-top: 5px; background: #0a0a0a; padding: 10px; border-radius: 4px; border: 1px solid #333;">
                        <strong>Silahkan kirim pendaftaran</strong> dan datang ke resepsionis untuk melakukan pembayaran agar akun aktif.
                    </p>
                </div>
            </div>

            <button type="submit" class="btn-submit">Kirim Pendaftaran</button>

            <div class="login-footer">
                <div>
                    <span style="color: #888;">Menunggu verifikasi Admin?</span>
                    <a href="cek_status.php">Cek Status</a>
                </div>
                <div>
                    <span style="color: #888;">Sudah memiliki akun?</span>
                    <a href="login.php">Login Sekarang</a>
                </div>
            </div>
        </form>
    </div>

    <div class="modal-overlay" id="modalOverlay">
        <div class="modal-box" id="modalContent"></div>
    </div>

    <script>
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

        function toggleVisibility() {
            const passwordInput = document.getElementById('regPass');
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

        function updateNominal() {
            const paket = document.getElementById('regPaket');
            const boxNominal = document.getElementById('boxNominal');
            const textNominal = document.getElementById('textNominal');
            
            if (paket.value) {
                boxNominal.style.display = 'flex';
                textNominal.innerText = "Rp " + parseInt(paket.value).toLocaleString('id-ID');
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

            document.getElementById('regBukti').required = isQris;
        }

        function tampilkanNamaFile(input) {
            const namaFileEl = document.getElementById('namaFile');
            if (input.files && input.files[0]) {
                namaFileEl.innerText = input.files[0].name;
                namaFileEl.style.color = "white";
            } else {
                namaFileEl.innerText = "Pilih Gambar / Screenshot...";
                namaFileEl.style.color = "var(--accent-gold)";
            }
        }

        function validasiDanBukaDraf(e) {
            e.preventDefault();
            
            const namaLengkap = document.getElementById('regNama').value;
            const noHp = document.getElementById('regHp').value;
            const email = document.getElementById('regEmail').value;
            const pass = document.getElementById('regPass').value;
            const tglMulai = document.getElementById('regTgl').value;
            const metode = document.querySelector('input[name="metodeBayar"]:checked').value;
            
            const regex = /^(?=.*[0-9])(?=.*[a-zA-Z])([a-zA-Z0-9]+)$/;

            if (!regex.test(pass)) {
                alert("Harap perbaiki kesalahan (tulisan merah) pada formulir sebelum melanjutkan.");
                return;
            }

            const selectPaket = document.getElementById('regPaket');
            const namaPaket = selectPaket.options[selectPaket.selectedIndex].getAttribute('data-nama');
            const hargaPaket = "Rp " + parseInt(selectPaket.value).toLocaleString('id-ID');

            const modal = document.getElementById('modalOverlay');
            const content = document.getElementById('modalContent');
            modal.style.display = 'flex';
            
            content.innerHTML = `
                <h3 style="color:var(--accent-gold); border-bottom:1px solid #333; padding-bottom:10px; text-align:center; font-size:1.3rem;">Konfirmasi Data</h3>
                <div style="margin:15px 0; font-size: 0.9rem; color:#ccc;">
                    <div class="draf-item"><span style="color:#888;">Nama:</span> <span style="text-align:right;">${namaLengkap}</span></div>
                    <div class="draf-item"><span style="color:#888;">Nomor WA:</span> <span style="text-align:right;">${noHp}</span></div>
                    <div class="draf-item"><span style="color:#888;">Login via:</span> <span style="text-align:right; font-weight:bold; color:white;">${email}</span></div>
                    <div class="draf-item"><span style="color:#888;">Paket Latihan:</span> <span style="text-align:right;">${namaPaket} <br> Mulai: ${tglMulai}</span></div>
                    <div class="draf-item"><span style="color:#888;">Metode:</span> <span style="text-align:right;">${metode.toUpperCase()}</span></div>
                    <div class="draf-item" style="border-top:1px dashed #333; margin-top:5px; padding-top:15px;">
                        <span style="color:var(--text-light); font-weight:bold; font-size:1.1rem;">Total Tagihan:</span> 
                        <span style="color:var(--accent-gold); font-weight:bold; font-size:1.2rem;">${hargaPaket}</span>
                    </div>
                </div>
                <p style="font-size:0.8rem; color:#888; margin-bottom:15px; text-align:center;">Pastikan data pendaftaran Anda sudah benar.</p>
                <button class="btn-submit" style="margin-top:0;" onclick="kirimFinal('${metode}', '${email}')">Kirim Pendaftaran</button>
                <button onclick="document.getElementById('modalOverlay').style.display='none'" style="background:transparent; border:1px solid #333; border-radius:4px; color:#888; width:100%; margin-top:10px; cursor:pointer; min-height:44px; transition:0.3s;" onmouseover="this.style.background='#1a1a1a'" onmouseout="this.style.background='transparent'">Kembali Edit</button>
            `;
        }

        function kirimFinal(metode, email) {
            const content = document.getElementById('modalContent');
            
            // --- AMBIL DATA UNTUK DISIMPAN DI LOCALSTORAGE ---
            const selectPaket = document.getElementById('regPaket');
            const namaPaket = selectPaket.options[selectPaket.selectedIndex].getAttribute('data-nama');
            const hargaPaket = "Rp " + parseInt(selectPaket.value).toLocaleString('id-ID');
            const tglMulai = document.getElementById('regTgl').value;

            localStorage.setItem('vanda_daftar_paket', namaPaket);
            localStorage.setItem('vanda_daftar_harga', hargaPaket);
            localStorage.setItem('vanda_daftar_tglMulai', tglMulai);
            // --------------------------------------------------

            content.innerHTML = `<div style="text-align:center;"><p style="font-weight:bold; color:var(--accent-gold);">Sedang menyimpan data pendaftaran...</p></div>`;

            setTimeout(() => {
                let pesanStatus = "";
                let instruksi = "";
                let tombolWa = "";

                if (metode === 'tunai') {
                    pesanStatus = `<strong style="color: #ffc107;">Menunggu Pembayaran</strong>`;
                    instruksi = `Silakan datang ke resepsionis Vanda Gym untuk melakukan pembayaran tunai. Akun Anda akan diaktifkan setelah pembayaran diselesaikan.`;
                } else {
                    pesanStatus = `<strong style="color: #ffc107;">Sedang Diproses</strong>`;
                    instruksi = `Admin sedang memverifikasi bukti pembayaran Anda. Jika sudah aktif, Anda bisa login menggunakan email yang didaftarkan.`;
                    
                    const pesanWa = encodeURIComponent(`Halo Admin Vanda Gym, saya baru saja melakukan pendaftaran member baru dengan email *${email}*. Tolong dicek ya. Terima kasih.`);
                    const linkWa = `https://wa.me/6282148556601?text=${pesanWa}`;
                    
                    tombolWa = `
                    <a href="${linkWa}" target="_blank" style="display: flex; align-items: center; justify-content: center; background-color: #25D366; color: white; text-decoration: none; padding: 10px; border-radius: 4px; font-weight: bold; margin-top: 15px; min-height: 44px; transition: 0.3s; font-size: 0.9rem;">
                        📞 Konfirmasi ke WhatsApp CS
                    </a>`;
                }

                content.innerHTML = `
                    <h3 style="color:var(--accent-gold); text-align:center; font-size:1.4rem;">Pendaftaran Berhasil!</h3>
                    <p style="margin:10px 0; text-align:center; font-size:0.95rem;">Status: ${pesanStatus}</p>
                    <div style="background:#050505; padding:15px; border:1px solid #222; border-radius:4px; font-size:0.85rem; line-height:1.6; text-align:left;">
                        <strong style="color:white;">Langkah Selanjutnya:</strong><br>
                        <span style="color:#aaa;">${instruksi}</span>
                        ${tombolWa}
                    </div>
                    <button class="btn-submit" style="margin-top:20px;" onclick="window.location.href='cek_status.php'">Cek Status Pendaftaran</button>
                    <button onclick="window.location.href='index.php'" style="background:transparent; border:none; color:#888; width:100%; margin-top:5px; cursor:pointer; min-height:44px;">Kembali ke Beranda</button>
                `;
            }, 1500);
        }
    </script>
</body>
</html>