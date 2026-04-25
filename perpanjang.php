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

        /* Status Box */
        .status-box {
            background: rgba(232, 201, 153, 0.05); border: 1px dashed var(--accent-gold);
            padding: 15px; border-radius: 6px; margin-bottom: 25px; text-align: center;
        }
        .status-box h4 { color: var(--accent-gold); margin-bottom: 5px; font-size: 0.95rem;}
        .status-badge { 
            display: inline-block; padding: 4px 10px; border-radius: 4px; 
            font-size: 0.8rem; font-weight: bold; margin-top: 5px;
        }
        .badge-expired { background: rgba(142, 22, 22, 0.2); color: #ff4d4d; border: 1px solid #ff4d4d; }
        .badge-pending { background: rgba(255, 193, 7, 0.2); color: #ffc107; border: 1px solid #ffc107; display: none; }

        .form-group { margin-bottom: 15px; text-align: left; }
        .form-group label { display: block; margin-bottom: 8px; color: #ccc; font-weight: 600; font-size: 0.85rem; }
        
        .form-control {
            width: 100%; padding: 12px 15px; background-color: var(--input-bg); 
            border: 1px solid #333; border-radius: 4px; color: white; font-size: 0.95rem; transition: 0.3s;
        }
        .form-control:focus { outline: none; border-color: var(--accent-gold); }
        .form-control[readonly] { color: #888; cursor: not-allowed; }
        input[type="date"] { color-scheme: dark; }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }

        /* Pilihan Metode Pembayaran */
        .payment-methods { display: flex; gap: 15px; margin-bottom: 20px; }
        .pay-method {
            flex: 1; border: 1px solid #333; border-radius: 6px; padding: 15px 10px;
            text-align: center; cursor: pointer; transition: 0.3s; background: #151515;
            position: relative;
        }
        .pay-method input { position: absolute; opacity: 0; cursor: pointer; }
        .pay-method span { font-weight: bold; color: #888; display: block; font-size: 0.9rem;}
        
        .pay-method.active { border-color: var(--accent-gold); background: rgba(232, 201, 153, 0.1); }
        .pay-method.active span { color: var(--accent-gold); }

        /* Blok Detail Pembayaran */
        .pay-details { 
            background: #111; border: 1px solid #222; padding: 20px; 
            border-radius: 6px; margin-bottom: 20px; display: none;
        }
        
        .qris-box { text-align: center; }
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
            <span style="color: #444; font-size: 0.8rem;">ID: VGYM-202604</span>
        </div>

        <div class="form-header">
            <h2>Perpanjang <span>Membership</span></h2>
            <p>Aktifkan kembali masa berlaku gym Anda</p>
        </div>

        <div class="status-box">
            <h4>Status Saat Ini</h4>
            <div id="badgeExpired" class="status-badge badge-expired">Kedaluwarsa (Berakhir 20 Apr 2026)</div>
            <div id="badgePending" class="status-badge badge-pending">⏳ Menunggu Verifikasi Pembayaran</div>
        </div>

        <form id="formPerpanjang" onsubmit="bukaModalKonfirmasi(event)">
            
            <div class="section-divider">Data Member & Paket</div>
            
            <div class="form-group">
                <label>Nama Member</label>
                <input type="text" id="namaMember" class="form-control" value="Ahsana Azmiara Ahmadiham" readonly>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Pilih Paket Perpanjangan</label>
                    <select id="paketPilih" class="form-control" style="cursor: pointer; appearance: none;" required onchange="updateTotalHarga()">
                        <option value="" disabled selected>-- Pilih Paket --</option>
                        <option value="175000" data-nama="1 Bulan Gym">1 Bulan Gym</option>
                        <option value="350000" data-nama="2 Bulan Gym">2 Bulan Gym</option>
                        <option value="525000" data-nama="3 Bulan Gym">3 Bulan Gym</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tanggal Mulai</label>
                    <input type="date" id="tglMulai" class="form-control" required>
                </div>
            </div>

            <div class="section-divider">Metode Pembayaran</div>

            <div class="form-group">
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
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=Pembayaran+Perpanjang+Vanda+Gym" alt="QRIS Vanda Gym">
                    <h3 style="color: var(--accent-gold); margin-top: 10px;" id="totalBayarQris">Rp 0</h3>
                </div>

                <div class="file-upload-wrapper">
                    <label style="font-size: 0.8rem; color: #888; margin-bottom: 5px; display: block;">Upload Bukti Transfer (Wajib)</label>
                    <div class="btn-upload">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/></svg>
                        <span id="namaFile">Pilih Gambar / Screenshot...</span>
                    </div>
                    <input type="file" id="buktiTransfer" accept="image/*" required onchange="tampilkanNamaFile(this)">
                </div>
            </div>

            <div id="detailTunai" class="pay-details">
                <div style="text-align: center;">
                    <svg viewBox="0 0 24 24" width="40" height="40" fill="var(--accent-gold)" style="margin-bottom: 10px;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.41 16.09V20h-2.67v-1.93c-1.71-.36-3.16-1.46-3.27-3.4h1.96c.1 1.05.82 1.87 2.65 1.87 1.96 0 2.4-.98 2.4-1.59 0-.83-.44-1.61-2.67-2.14-2.48-.6-4.18-1.62-4.18-3.67 0-1.72 1.39-2.84 3.11-3.21V4h2.67v1.95c1.86.45 2.79 1.86 2.85 3.39H14.3c-.05-1.11-.64-1.87-2.22-1.87-1.5 0-2.4.68-2.4 1.64 0 .84.65 1.39 2.67 1.91 2.95.73 4.18 1.9 4.18 3.91-.01 1.83-1.38 2.83-3.12 3.16z"/></svg>
                    <p style="font-size: 0.9rem; color: #ccc; line-height: 1.5;">
                        Anda memilih pembayaran Tunai.<br>
                        Total Tagihan: <strong style="color: var(--accent-gold);" id="totalBayarTunai">Rp 0</strong>
                    </p>
                    <p style="font-size: 0.8rem; color: #888; margin-top: 10px; background: #0a0a0a; padding: 10px; border-radius: 4px; border: 1px solid #333;">
                        Silakan klik <strong>"Cek Draf Pembayaran"</strong> lalu konfirmasi untuk menyimpan data perpanjangan.
                    </p>
                </div>
            </div>

            <button type="submit" class="btn-submit">Cek Draf Pembayaran</button>
        </form>
    </div>

    <div class="modal-overlay" id="modalOverlay">
        <div class="modal-box" id="modalContent"></div>
    </div>

    <script>
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
                namaFileEl.innerText = "Pilih Gambar / Screenshot...";
                namaFileEl.style.color = "var(--accent-gold)";
            }
        }

        function bukaModalKonfirmasi(e) {
            e.preventDefault();
            
            const namaMember = document.getElementById('namaMember').value;
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
                    <div class="draf-item"><span style="color:#888;">Nama Member:</span> <span style="text-align:right; font-weight:bold; color:white;">${namaMember}</span></div>
                    <div class="draf-item"><span style="color:#888;">Paket Latihan:</span> <span style="text-align:right;">${namaPaket} <br> Mulai: ${tglMulai}</span></div>
                    <div class="draf-item"><span style="color:#888;">Metode Bayar:</span> <span style="text-align:right;">${metode.toUpperCase()}</span></div>
                    <div class="draf-item" style="border-top:1px dashed #333; margin-top:5px; padding-top:15px;">
                        <span style="color:var(--text-light); font-weight:bold; font-size:1.1rem;">Total Tagihan:</span> 
                        <span style="color:var(--accent-gold); font-weight:bold; font-size:1.2rem;">${hargaPaket}</span>
                    </div>
                </div>
                <p style="font-size:0.8rem; color:#888; margin-bottom:15px; text-align:center;">Pastikan paket dan tanggal mulai Anda sudah benar.</p>
                <button class="btn-submit" style="margin-top:0;" onclick="kirimFinalPembayaran('${metode}', '${namaMember}')">Kirim Perpanjangan</button>
                <button onclick="document.getElementById('modalOverlay').style.display='none'" style="background:transparent; border:1px solid #333; border-radius:4px; color:#888; width:100%; margin-top:10px; cursor:pointer; min-height:44px; transition:0.3s;" onmouseover="this.style.background='#1a1a1a'" onmouseout="this.style.background='transparent'">Kembali Edit</button>
            `;
        }

        function kirimFinalPembayaran(metode, namaMember) {
            const modal = document.getElementById('modalOverlay');
            const content = document.getElementById('modalContent');
            const form = document.getElementById('formPerpanjang');

            // --- AMBIL DATA UNTUK DISIMPAN DI LOCALSTORAGE ---
            const selectPaket = document.getElementById('paketPilih');
            const namaPaket = selectPaket.options[selectPaket.selectedIndex].getAttribute('data-nama');
            const hargaPaket = "Rp " + parseInt(selectPaket.value).toLocaleString('id-ID');
            const tglMulai = document.getElementById('tglMulai').value;

            // Simpan ke memori browser
            localStorage.setItem('vanda_renew_paket', namaPaket);
            localStorage.setItem('vanda_renew_harga', hargaPaket);
            localStorage.setItem('vanda_renew_tglMulai', tglMulai);
            // --------------------------------------------------
            
            content.innerHTML = `<div style="text-align:center;"><p style="font-weight:bold; color:var(--accent-gold);">Sedang menyimpan data perpanjangan...</p></div>`;

            setTimeout(() => {
                form.style.display = 'none';
                modal.style.display = 'none';

                document.getElementById('badgeExpired').style.display = 'none';
                document.getElementById('badgePending').style.display = 'inline-block';

                let pesanStatus = "";
                let instruksi = "";
                let tombolWa = "";

                if (metode === 'tunai') {
                    pesanStatus = `<strong style="color: #ffc107;">Menunggu Pembayaran</strong>`;
                    instruksi = `Silakan datang ke resepsionis Vanda Gym untuk menyerahkan pembayaran tunai. Masa aktif akan diperbarui setelah pembayaran diterima.`;
                } else {
                    pesanStatus = `<strong style="color: #ffc107;">Sedang Diproses</strong>`;
                    instruksi = `Admin sedang memverifikasi bukti transfer Anda. Masa aktif gym Anda akan otomatis diperbarui setelah divalidasi.`;
                    
                    const pesanWa = encodeURIComponent(`Halo Admin Vanda Gym, saya atas nama *${namaMember}* baru saja melakukan perpanjangan member. Mohon bantuannya untuk diverifikasi. Terima kasih.`);
                    const linkWa = `https://wa.me/6282148556601?text=${pesanWa}`;
                    
                    tombolWa = `
                    <a href="${linkWa}" target="_blank" style="display: flex; align-items: center; justify-content: center; background-color: #25D366; color: white; text-decoration: none; padding: 10px; border-radius: 4px; font-weight: bold; margin-top: 15px; min-height: 44px; transition: 0.3s; font-size: 0.9rem;">
                        📞 Konfirmasi ke WhatsApp CS
                    </a>`;
                }

                const divSukses = document.createElement('div');
                divSukses.innerHTML = `
                    <div style="background:#050505; padding:20px; border:1px solid #222; border-radius:8px; margin-top:20px;">
                        <h3 style="color:var(--accent-gold); text-align:center; font-size:1.4rem; margin-bottom:10px;">Perpanjangan Berhasil!</h3>
                        <p style="margin:10px 0; text-align:center; font-size:0.95rem;">Status: ${pesanStatus}</p>
                        <div style="background:#111; padding:15px; border:1px solid #333; border-radius:4px; font-size:0.85rem; line-height:1.6; text-align:left; margin-top:15px;">
                            <strong style="color:white;">Langkah Selanjutnya:</strong><br>
                            <span style="color:#aaa;">${instruksi}</span>
                            ${tombolWa}
                        </div>
                        <button class="btn-submit" style="margin-top:20px;" onclick="window.location.href='cek_status_perpanjang.php'">Cek Status Perpanjangan</button>
                        <button onclick="window.location.href='member_dasbor.php'" style="background:transparent; border:none; color:#888; width:100%; margin-top:5px; cursor:pointer; min-height:44px;">Kembali ke Dasbor</button>
                    </div>
                `;
                document.querySelector('.status-box').insertAdjacentElement('afterend', divSukses);

            }, 1500);
        }
    </script>
</body>
</html>