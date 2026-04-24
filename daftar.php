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
            min-height: 100vh; padding: 20px;
        }

        .form-container {
            background-color: #0a0a0a;
            border: 1px solid #333; border-top: 4px solid var(--primary-red);
            border-radius: 8px; padding: 20px 25px;
            width: 100%; max-width: 600px;
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

        .form-group { margin-bottom: 10px; position: relative; }
        .form-group label { display: block; margin-bottom: 5px; color: #ccc; font-weight: 600; font-size: 0.9rem;}
        
        .form-control {
            width: 100%; padding: 8px 15px; min-height: 44px;
            background-color: var(--input-bg); border: 1px solid #333;
            border-radius: 4px; color: white; font-size: 0.95rem;
        }
        .form-control:focus { outline: none; border-color: var(--accent-gold); }
        .form-control.invalid { border-color: var(--primary-red); }

        input[type="date"] { color-scheme: dark; }

        .error-msg { color: #ff4d4d; font-size: 0.8rem; margin-top: 3px; display: none; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

        .section-divider { 
            border-bottom: 1px solid #222; margin: 15px 0 10px; 
            padding-bottom: 5px; color: var(--accent-gold); font-weight: bold; text-transform: uppercase; font-size: 0.8rem;
        }

        .nominal-box {
            background: #1a1a1a; padding: 10px 15px; border-radius: 4px;
            border-left: 3px solid var(--accent-gold); margin-top: 5px;
            display: none; justify-content: space-between; align-items: center;
        }

        .payment-box {
            background: #151515; padding: 15px; border-radius: 4px;
            border-left: 3px solid var(--accent-gold); margin-top: 10px; display: none;
        }

        .btn-submit {
            width: 100%; background-color: var(--primary-red); color: white;
            border: none; min-height: 44px; font-size: 1rem; font-weight: bold;
            border-radius: 4px; cursor: pointer; text-transform: uppercase; margin-top: 15px;
        }

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

        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.95); display: none; justify-content: center; 
            align-items: center; z-index: 1000; padding: 20px; overflow-y: auto;
        }
        .modal-box {
            background: #111; border: 1px solid var(--accent-gold);
            padding: 25px; border-radius: 8px; width: 100%; max-width: 400px;
        }
        .draf-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #222; font-size: 0.9rem;}
    </style>
</head>
<body>

    <div class="form-container">
        <div class="nav-top">
            <a href="index.php" class="btn-back-square" title="Kembali">←</a>
        </div>

        <div class="form-header">
            <h2>Daftar Membership</h2>
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
                    <div id="errorHp" class="error-msg">Wajib angka saja.</div>
                </div>
                <div class="form-group">
                    <label>Alamat Email</label>
                    <input type="email" id="regEmail" class="form-control" required placeholder="nama@email.com">
                </div>
            </div>

            <div class="section-divider">Keamanan Akun</div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="regUser" class="form-control" required placeholder="Minimal 6 karakter" oninput="cekUsername(this)">
                    <div id="errorUser" class="error-msg">Minimal 6 karakter.</div>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div style="position: relative;">
                        <input type="password" id="regPass" class="form-control" required 
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
            </div>

            <div class="section-divider">Paket Latihan</div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Pilih Durasi</label>
                    <select id="regPaket" class="form-control" onchange="updateNominal()" required>
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
                <span style="color: #ccc; font-weight: bold;">Total Tagihan:</span>
                <span id="textNominal" style="color: var(--accent-gold); font-weight: bold; font-size: 1.1rem;">Rp 0</span>
            </div>

            <div class="form-group" style="margin-top: 10px;">
                <label>Metode Pembayaran</label>
                <select id="regMetode" class="form-control" onchange="toggleQris()" required>
                    <option value="" disabled selected>-- Pilih Metode Bayar --</option>
                    <option value="tunai">Bayar Tunai di Resepsionis</option>
                    <option value="qris">Bayar via QRIS (Transfer)</option>
                </select>
            </div>

            <div class="payment-box" id="boxQris">
                <p style="text-align:center; margin-bottom:5px; font-size:0.85rem;">Scan QRIS Vanda Gym:</p>
                <img src="https://upload.wikimedia.org/wikipedia/commons/d/d0/QR_code_for_mobile_English_Wikipedia.svg" style="width:120px; display:block; margin:0 auto; background:#fff; padding:5px; border-radius:4px;">
                <div class="form-group" style="margin-top:10px;">
                    <label>Unggah Bukti Bayar</label>
                    <input type="file" id="regBukti" class="form-control" accept="image/*">
                </div>
            </div>

            <button type="submit" class="btn-submit">Cek Draf Pendaftaran</button>

            <div class="login-footer">
                <div>
                    <span style="color: #888;">Menunggu verifikasi Admin?</span>
                    <a href="cek_status.php">Cek Status</a>
                </div>
                <div>
                    <span style="color: #888;">Sudah memiliki akun?</span>
                    <a href="login.php">Login</a>
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

        function toggleQris() {
            const box = document.getElementById('boxQris');
            box.style.display = document.getElementById('regMetode').value === 'qris' ? 'block' : 'none';
        }

        function validasiDanBukaDraf(e) {
            e.preventDefault();
            
            // Ambil Data
            const namaLengkap = document.getElementById('regNama').value;
            const noHp = document.getElementById('regHp').value;
            const email = document.getElementById('regEmail').value;
            const user = document.getElementById('regUser').value;
            const pass = document.getElementById('regPass').value;
            const tglMulai = document.getElementById('regTgl').value;
            const metode = document.getElementById('regMetode').value;
            
            const regex = /^(?=.*[0-9])(?=.*[a-zA-Z])([a-zA-Z0-9]+)$/;

            if (user.length < 6 || !regex.test(pass)) {
                alert("Harap perbaiki kesalahan pada formulir sebelum melanjutkan.");
                return;
            }

            // Ambil nama paket dan harga dari dropdown
            const selectPaket = document.getElementById('regPaket');
            const namaPaket = selectPaket.options[selectPaket.selectedIndex].getAttribute('data-nama');
            const hargaPaket = "Rp " + parseInt(selectPaket.value).toLocaleString('id-ID');

            const modal = document.getElementById('modalOverlay');
            const content = document.getElementById('modalContent');
            modal.style.display = 'flex';
            
            // Mengirim parameter metode dan user ke kirimFinal()
            content.innerHTML = `
                <h3 style="color:var(--accent-gold); border-bottom:1px solid #333; padding-bottom:10px; text-align:center;">Konfirmasi Data</h3>
                <div style="margin:10px 0; font-size: 0.85rem;">
                    <div class="draf-item"><span style="color:#888;">Nama:</span> <span style="text-align:right;">${namaLengkap}</span></div>
                    <div class="draf-item"><span style="color:#888;">Kontak:</span> <span style="text-align:right;">${noHp} <br> ${email}</span></div>
                    <div class="draf-item"><span style="color:#888;">Username:</span> <span style="text-align:right;">${user}</span></div>
                    <div class="draf-item"><span style="color:#888;">Paket Latihan:</span> <span style="text-align:right;">${namaPaket} <br> Mulai: ${tglMulai}</span></div>
                    <div class="draf-item"><span style="color:#888;">Metode:</span> <span style="text-align:right;">${metode.toUpperCase()}</span></div>
                    <div class="draf-item" style="border-top:1px dashed #333; margin-top:5px; padding-top:10px;">
                        <span style="color:var(--text-light); font-weight:bold; font-size:1rem;">Total Tagihan:</span> 
                        <span style="color:var(--accent-gold); font-weight:bold; font-size:1rem;">${hargaPaket}</span>
                    </div>
                </div>
                <p style="font-size:0.75rem; color:#888; margin-bottom:10px; text-align:center;">Pastikan data Anda sudah benar.</p>
                <button class="btn-submit" style="margin-top:0;" onclick="kirimFinal('${metode}', '${user}')">Kirim Pendaftaran</button>
                <button onclick="document.getElementById('modalOverlay').style.display='none'" style="background:transparent; border:none; color:#888; width:100%; margin-top:5px; cursor:pointer; min-height:44px;">Edit Kembali</button>
            `;
        }

        // Fungsi kirimFinal menerima metode bayar dan username
        function kirimFinal(metode, user) {
            const content = document.getElementById('modalContent');
            content.innerHTML = `<div style="text-align:center;"><p>Mengirim data pendaftaran...</p></div>`;

            setTimeout(() => {
                let pesanStatus = "";
                let instruksi = "";
                let tombolWa = "";

                if (metode === 'tunai') {
                    pesanStatus = `<strong style="color: #ffc107;">Menunggu Pembayaran</strong>`;
                    instruksi = `Silakan datang ke resepsionis Vanda Gym untuk melakukan pembayaran tunai. Akun Anda akan diaktifkan setelah pembayaran diselesaikan di tempat.`;
                } else {
                    pesanStatus = `<strong style="color: #ffc107;">Sedang Diproses</strong>`;
                    instruksi = `Admin sedang memverifikasi data Anda. Jika sudah aktif, Anda bisa login menggunakan username yang didaftarkan.`;
                    
                    // Membuat pesan dan link WhatsApp otomatis
                    const pesanWa = encodeURIComponent(`Halo Admin Vanda Gym, saya baru saja melakukan pendaftaran member baru saya dengan username *${user}*. Tolong dicek ya. Terima kasih.`);
                    const linkWa = `https://wa.me/6282148556601?text=${pesanWa}`;
                    
                    tombolWa = `
                    <a href="${linkWa}" target="_blank" style="display: flex; align-items: center; justify-content: center; background-color: #25D366; color: white; text-decoration: none; padding: 10px; border-radius: 4px; font-weight: bold; margin-top: 15px; min-height: 44px; transition: 0.3s; font-size: 0.9rem;">
                        📞 Konfirmasi ke WhatsApp CS
                    </a>`;
                }

                content.innerHTML = `
                    <h3 style="color:var(--accent-gold); text-align:center;">Pendaftaran Berhasil!</h3>
                    <p style="margin:10px 0; text-align:center;">Status: ${pesanStatus}</p>
                    <div style="background:#000; padding:15px; border:1px solid #222; border-radius:4px; font-size:0.85rem; line-height:1.5;">
                        <strong>Langkah Selanjutnya:</strong><br>
                        ${instruksi}
                        ${tombolWa}
                    </div>
                    <button class="btn-submit" onclick="window.location.href='cek_status.php'">Cek Status Pendaftaran</button>
                    <button onclick="window.location.href='index.php'" style="background:transparent; border:none; color:#888; width:100%; margin-top:5px; cursor:pointer; min-height:44px;">Kembali ke Beranda</button>
                `;
            }, 1500);
        }
    </script>
</body>
</html>