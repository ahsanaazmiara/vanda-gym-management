<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perpanjang Member - Vanda Gym Classic</title>
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
            display: flex; justify-content: center; align-items: center;
            min-height: 100vh; padding: 40px 20px;
        }

        .pay-container {
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

        /* Status Box Mengikuti Dasbor */
        .status-box {
            background: rgba(232, 201, 153, 0.05); border: 1px dashed var(--accent-gold);
            padding: 15px; border-radius: 6px; margin-bottom: 25px; text-align: center;
        }
        .status-box h4 { color: #ccc; margin-bottom: 8px; font-size: 0.85rem;}
        .status-badge { 
            display: inline-block; padding: 6px 15px; border-radius: 20px; 
            font-size: 0.85rem; font-weight: bold; letter-spacing: 0.5px;
        }
        .badge-expired { background: var(--primary-red); color: white; }
        .badge-active { background: var(--success-green); color: white; }

        .form-group { margin-bottom: 15px; text-align: left; }
        .form-group label { display: block; margin-bottom: 8px; color: #ccc; font-weight: 600; font-size: 0.85rem; }
        
        .form-control {
            width: 100%; padding: 12px 15px; background-color: var(--input-bg); 
            border: 1px solid #333; border-radius: 4px; color: white; font-size: 0.95rem; transition: 0.3s;
        }
        .form-control:focus { outline: none; border-color: var(--accent-gold); }
        .form-control[readonly] { color: #888; cursor: not-allowed; }
        input[type="date"] { color-scheme: dark; cursor: pointer; }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }

        /* Pilihan Metode Pembayaran */
        .payment-methods { display: flex; gap: 15px; margin-bottom: 20px; }
        .pay-method {
            flex: 1; border: 1px solid #333; border-radius: 6px; padding: 15px 10px;
            text-align: center; cursor: pointer; transition: 0.3s; background: #151515;
            position: relative; display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .pay-method input { position: absolute; opacity: 0; cursor: pointer; }
        .pay-method span { font-weight: bold; color: #888; display: block; font-size: 0.9rem;}
        
        .pay-method.active { border-color: var(--accent-gold); background: rgba(232, 201, 153, 0.1); }
        .pay-method.active span { color: var(--accent-gold); }

        /* Blok Detail Pembayaran */
        .pay-details { 
            background: #111; border: 1px solid #222; padding: 20px; 
            border-radius: 6px; margin-bottom: 20px; display: none; text-align: center;
        }
        
        .qris-box img { max-width: 150px; border-radius: 8px; margin: 10px 0; border: 2px solid white; background: #fff; padding: 5px; }
        
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
            border-radius: 4px; cursor: pointer; text-transform: uppercase; transition: 0.3s; margin-top: 10px;
        }
        .btn-submit:hover { background-color: #a81a1a; transform: translateY(-2px); }
        
        .action-link {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            text-decoration: none; padding: 10px; border-radius: 4px; font-weight: bold; 
            margin-top: 15px; min-height: 44px; transition: 0.3s; font-size: 0.9rem;
        }
        .action-link.wa { background-color: #25D366; color: white; }
        .action-link.wa:hover { background-color: #1ebe57; }
        .action-link.outline { background: transparent; color: #888; border: 1px solid #333; margin-top: 10px; }
        .action-link.outline:hover { background: #1a1a1a; color: white; }

        /* Footer Login (Link Cek Status) */
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

        /* Modal Draf Konfirmasi */
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

    <div class="pay-container">
        <div class="nav-top">
            <a href="member_dasbor.php" class="btn-back-square" title="Kembali ke Dasbor">←</a>
        </div>

        <div class="form-header">
            <h2>Perpanjang <span>Membership</span></h2>
            <p>Aktifkan kembali masa berlaku gym Anda</p>
        </div>

        <div class="status-box" id="statusBoxContainer">
            <h4>Status Membership Saat Ini</h4>
            <div id="badgeStatus" class="status-badge">Memuat...</div>
            <div id="tglBerakhirTeks" style="color: #ccc; font-size: 0.85rem; margin-top: 8px;"></div>
        </div>

        <form id="formPerpanjang" onsubmit="bukaModalKonfirmasi(event)">
            
            <div class="section-divider">Data Member & Paket</div>
            
            <div class="form-group">
                <label>Nama Member</label>
                <input type="text" id="namaMember" class="form-control" value="Ahsana Azmiara Ahmadiham" readonly>
            </div>
            <div class="form-group">
                <label>Email Pendaftaran</label>
                <input type="email" id="emailMember" class="form-control" value="ahsana@email.com" readonly>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Pilih Paket</label>
                    <select id="paketPilih" class="form-control" style="cursor: pointer; appearance: none;" required onchange="updateTotalHarga()">
                        <option value="" disabled selected>-- Pilih Paket --</option>
                        <option value="175000" data-nama="1 Bulan Gym">1 Bulan Gym</option>
                        <option value="350000" data-nama="2 Bulan Gym">2 Bulan Gym</option>
                        <option value="525000" data-nama="3 Bulan Gym">3 Bulan Gym</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tanggal Mulai Baru</label>
                    <input type="date" id="tglMulai" class="form-control" required title="Tanggal tidak bisa mundur dari masa aktif">
                </div>
            </div>

            <div class="section-divider">Pembayaran</div>

            <div class="form-group">
                <div class="payment-methods">
                    <label class="pay-method active" id="labelQris">
                        <input type="radio" name="metodeBayar" value="qris" checked onchange="ubahMetode()">
                        <span>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
                            QRIS / Transfer
                        </span>
                    </label>
                    <label class="pay-method" id="labelTunai">
                        <input type="radio" name="metodeBayar" value="tunai" onchange="ubahMetode()">
                        <span>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><rect x="2" y="6" width="20" height="12" rx="2"></rect><circle cx="12" cy="12" r="2"></circle><path d="M6 12h.01M18 12h.01"></path></svg>
                            Tunai (Kasir)
                        </span>
                    </label>
                </div>
            </div>

            <div id="detailQris" class="pay-details" style="display: block;">
                <p style="font-size: 0.85rem; color: #ccc;">Transfer ke: <strong>BCA 123-456-789 (Vanda Gym)</strong></p>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=Pembayaran+Perpanjang+Vanda+Gym" alt="QRIS Vanda Gym">
                <h3 style="color: var(--accent-gold); margin-top: 10px;" id="totalBayarQris">Rp 0</h3>

                <div class="file-upload-wrapper">
                    <div class="btn-upload">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/></svg>
                        <span id="namaFile">Upload Bukti Transfer</span>
                    </div>
                    <input type="file" id="buktiTransfer" accept="image/*" required onchange="tampilkanNamaFile(this)">
                </div>
            </div>

            <div id="detailTunai" class="pay-details">
                <svg viewBox="0 0 24 24" width="40" height="40" fill="var(--accent-gold)" style="margin-bottom: 10px;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.41 16.09V20h-2.67v-1.93c-1.71-.36-3.16-1.46-3.27-3.4h1.96c.1 1.05.82 1.87 2.65 1.87 1.96 0 2.4-.98 2.4-1.59 0-.83-.44-1.61-2.67-2.14-2.48-.6-4.18-1.62-4.18-3.67 0-1.72 1.39-2.84 3.11-3.21V4h2.67v1.95c1.86.45 2.79 1.86 2.85 3.39H14.3c-.05-1.11-.64-1.87-2.22-1.87-1.5 0-2.4.68-2.4 1.64 0 .84.65 1.39 2.67 1.91 2.95.73 4.18 1.9 4.18 3.91-.01 1.83-1.38 2.83-3.12 3.16z"/></svg>
                <p style="font-size: 0.9rem; color: #ccc;">Total Tagihan: <strong style="color: var(--accent-gold);" id="totalBayarTunai">Rp 0</strong></p>
                <p style="font-size: 0.8rem; color: #888; margin-top: 5px;">Kirim draf ini, lalu bayar langsung ke Kasir Resepsionis.</p>
            </div>

            <button type="submit" class="btn-submit">Proses Perpanjangan</button>

            <div class="login-footer">
                <div>
                    <span style="color: #888;">Sudah mengajukan perpanjangan?</span>
                    <a href="cek_status_perpanjang.php">Cek Status</a>
                </div>
            </div>
        </form>
    </div>

    <div class="modal-overlay" id="modalOverlay">
        <div class="modal-box" id="modalContent"></div>
    </div>

    <script>
        // ==========================================
        // INISIALISASI DATA TANGGAL (SIMULASI DB)
        // ==========================================
        window.onload = function() {
            // Simulasi Tanggal Berakhir dari Database (Ganti variabel ini di backend PHP)
            // Misalnya diset ke 25 Mei 2026.
            const tglBerakhirDB = "2026-05-25"; 
            
            const tglInput = document.getElementById('tglMulai');
            const badgeStatus = document.getElementById('badgeStatus');
            const teksBerakhir = document.getElementById('tglBerakhirTeks');
            
            // Format Tanggal (misal: 25 Mei 2026)
            const d = new Date(tglBerakhirDB);
            const formatTgl = d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });

            // Cek Tanggal Hari Ini untuk Status
            const todayObj = new Date();
            const todayStr = todayObj.toISOString().split('T')[0];

            if (tglBerakhirDB < todayStr) {
                // Jika sudah KADALUWARSA
                badgeStatus.className = "status-badge badge-expired";
                badgeStatus.innerText = "KADALUWARSA";
                teksBerakhir.innerHTML = "Masa aktif Anda telah berakhir pada: <strong style='color: white;'>" + formatTgl + "</strong>";
                
                // Jika sudah mati, tanggal mulai perpanjangan minimal adalah HARI INI
                tglInput.min = todayStr;
                tglInput.value = todayStr;
            } else {
                // Jika masih AKTIF
                badgeStatus.className = "status-badge badge-active";
                badgeStatus.innerText = "AKTIF";
                teksBerakhir.innerHTML = "Masa aktif Anda masih berlaku hingga: <strong style='color: white;'>" + formatTgl + "</strong>";
                
                // Kunci form input tanggal: Minimal diisi sama dengan tanggal berakhir database
                tglInput.min = tglBerakhirDB;
                tglInput.value = tglBerakhirDB; 
            }
        }

        function updateTotalHarga() {
            const selectPaket = document.getElementById('paketPilih');
            if (selectPaket.value) {
                const harga = parseInt(selectPaket.value);
                const hargaRupiah = "Rp " + harga.toLocaleString('id-ID');
                document.getElementById('totalBayarQris').innerText = hargaRupiah;
                document.getElementById('totalBayarTunai').innerText = hargaRupiah;
            }
        }

        function ubahMetode() {
            const isQris = document.querySelector('input[name="metodeBayar"]:checked').value === 'qris';
            
            document.getElementById('labelQris').classList.toggle('active', isQris);
            document.getElementById('labelTunai').classList.toggle('active', !isQris);
            document.getElementById('detailQris').style.display = isQris ? 'block' : 'none';
            document.getElementById('detailTunai').style.display = isQris ? 'none' : 'block';
            document.getElementById('buktiTransfer').required = isQris;
        }

        function tampilkanNamaFile(input) {
            const namaFileEl = document.getElementById('namaFile');
            if (input.files && input.files[0]) {
                namaFileEl.innerText = input.files[0].name;
                namaFileEl.style.color = "white";
            } else {
                namaFileEl.innerText = "Upload Bukti Transfer";
                namaFileEl.style.color = "var(--accent-gold)";
            }
        }

        function bukaModalKonfirmasi(e) {
            e.preventDefault();
            
            const emailMember = document.getElementById('emailMember').value;
            const metode = document.querySelector('input[name="metodeBayar"]:checked').value;
            const selectPaket = document.getElementById('paketPilih');
            const tglMulai = document.getElementById('tglMulai').value;
            
            const namaPaket = selectPaket.options[selectPaket.selectedIndex].getAttribute('data-nama');
            const hargaPaket = "Rp " + parseInt(selectPaket.value).toLocaleString('id-ID');

            const modal = document.getElementById('modalOverlay');
            const content = document.getElementById('modalContent');
            modal.style.display = 'flex';
            
            content.innerHTML = `
                <h3 style="color:var(--accent-gold); border-bottom:1px solid #333; padding-bottom:10px; text-align:center; font-size:1.3rem;">Konfirmasi Perpanjangan</h3>
                <div style="margin:15px 0; font-size: 0.9rem; color:#ccc;">
                    <div class="draf-item"><span style="color:#888;">Email:</span> <span style="text-align:right; font-weight:bold; color:white;">${emailMember}</span></div>
                    <div class="draf-item"><span style="color:#888;">Paket Baru:</span> <span style="text-align:right;">${namaPaket}</span></div>
                    <div class="draf-item"><span style="color:#888;">Tgl Mulai Aktif:</span> <span style="text-align:right; color:var(--success-green); font-weight:bold;">${tglMulai}</span></div>
                    <div class="draf-item"><span style="color:#888;">Metode Bayar:</span> <span style="text-align:right;">${metode.toUpperCase()}</span></div>
                    <div class="draf-item" style="border-top:1px dashed #333; margin-top:5px; padding-top:15px;">
                        <span style="color:var(--text-light); font-weight:bold; font-size:1.1rem;">Total Bayar:</span> 
                        <span style="color:var(--accent-gold); font-weight:bold; font-size:1.2rem;">${hargaPaket}</span>
                    </div>
                </div>
                <button class="btn-submit" style="margin-top:10px;" onclick="kirimFinalPembayaran('${metode}', '${emailMember}')">Kirim Pengajuan</button>
                <button onclick="document.getElementById('modalOverlay').style.display='none'" class="action-link outline" style="width: 100%;">Kembali Edit</button>
            `;
        }

        function kirimFinalPembayaran(metode, emailMember) {
            const modal = document.getElementById('modalOverlay');
            const content = document.getElementById('modalContent');
            const form = document.getElementById('formPerpanjang');

            // Simpan ke memori browser untuk simulasi di halaman cek status
            const selectPaket = document.getElementById('paketPilih');
            const namaPaket = selectPaket.options[selectPaket.selectedIndex].getAttribute('data-nama');
            const hargaPaket = "Rp " + parseInt(selectPaket.value).toLocaleString('id-ID');
            const tglMulaiBaru = document.getElementById('tglMulai').value;

            localStorage.setItem('vanda_renew_paket', namaPaket);
            localStorage.setItem('vanda_renew_harga', hargaPaket);
            localStorage.setItem('vanda_renew_tglMulai', tglMulaiBaru);
            
            content.innerHTML = `<div style="text-align:center;"><p style="font-weight:bold; color:var(--accent-gold);">Sedang memproses...</p></div>`;

            setTimeout(() => {
                form.style.display = 'none';
                modal.style.display = 'none';
                document.getElementById('statusBoxContainer').style.display = 'none';

                let pesanStatus = (metode === 'tunai') ? "Menunggu Pembayaran Kasir" : "Sedang Diverifikasi";
                let instruksi = (metode === 'tunai') 
                    ? "Silakan datang ke resepsionis untuk menyelesaikan pembayaran tunai." 
                    : "Admin sedang memeriksa bukti transfer Anda.";
                
                let tombolWa = `
                <a href="https://wa.me/6282148556601?text=Halo%20Admin%20Vanda%20Gym,%20saya%20baru%20mengajukan%20perpanjangan%20dengan%20email%20*${emailMember}*.%20Mohon%20diverifikasi." target="_blank" class="action-link wa">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                    Konfirmasi ke WA Admin
                </a>`;

                const divSukses = document.createElement('div');
                divSukses.innerHTML = `
                    <div style="background:#050505; padding:20px; border:1px solid #222; border-radius:8px;">
                        <h3 style="color:var(--accent-gold); text-align:center; font-size:1.4rem; margin-bottom:10px;">Pengajuan Berhasil!</h3>
                        <p style="margin:10px 0; text-align:center; font-size:0.95rem; color:#ffc107;"><strong>${pesanStatus}</strong></p>
                        <div style="background:#111; padding:15px; border:1px solid #333; border-radius:4px; font-size:0.85rem; line-height:1.6; text-align:left; margin-top:15px;">
                            <span style="color:#ccc;">${instruksi}</span>
                            ${(metode !== 'tunai') ? tombolWa : ''}
                        </div>
                        
                        <a href="cek_status_perpanjang.php" class="btn-submit" style="display:flex; justify-content:center; align-items:center; text-decoration:none; margin-top:20px;">Cek Status Perpanjangan</a>
                        <a href="member_dasbor.php" class="action-link outline">Kembali ke Dasbor</a>
                    </div>
                `;
                document.querySelector('.form-header').insertAdjacentElement('afterend', divSukses);

            }, 1000);
        }
    </script>
</body>
</html>