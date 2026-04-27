<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Status Perpanjangan - Vanda Gym Classic</title>
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

        .status-container {
            background-color: #0a0a0a;
            border: 1px solid #333; border-top: 4px solid var(--primary-red);
            border-radius: 8px; padding: 30px; width: 100%; max-width: 500px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.8);
            text-align: center;
            position: relative; 
        }

        .btn-back-square { 
            width: 44px; height: 44px; 
            background-color: #1a1a1a; border: 1px solid #333; 
            color: var(--accent-gold); border-radius: 4px;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; font-weight: bold; font-size: 1.2rem;
            margin-bottom: 20px;
            transition: 0.3s;
        }
        .btn-back-square:hover { background-color: var(--primary-red); color: white; border-color: var(--primary-red); }

        .form-group { margin: 25px 0; text-align: left; position: relative; }
        .form-group label { display: block; margin-bottom: 8px; color: #ccc; font-weight: 600; font-size: 0.85rem;}
        
        .form-control {
            width: 100%; padding: 10px 15px; min-height: 44px;
            background-color: var(--input-bg); border: 1px solid #333;
            border-radius: 4px; color: white; font-size: 1rem;
            transition: 0.3s;
        }
        .form-control:focus { outline: none; border-color: var(--accent-gold); }
        .form-control.invalid { border-color: var(--primary-red); }

        .error-msg { color: #ff4d4d; font-size: 0.85rem; margin-top: 5px; display: none; }

        .btn-search {
            width: 100%; background-color: var(--primary-red); color: white;
            border: none; min-height: 48px; font-size: 1rem; font-weight: bold;
            border-radius: 4px; cursor: pointer; text-transform: uppercase;
            transition: 0.3s;
        }
        .btn-search:hover { background-color: #a81a1a; }

        /* Result Box Style */
        .result-box {
            margin-top: 30px; padding: 20px; border-radius: 4px;
            background: #111; border: 1px solid #222; display: none;
            text-align: left;
        }
        .status-badge {
            display: inline-block; padding: 5px 12px; border-radius: 20px;
            font-size: 0.85rem; font-weight: bold; margin-top: 10px;
        }
        .status-pending { background: #856404; color: #fff; }
        .status-active { background: #155724; color: #fff; }
        .status-rejected { background: #721c24; color: #fff; }

        /* Detail Info Style */
        .detail-info {
            background-color: #1a1a1a; border: 1px dashed #333; border-radius: 4px;
            padding: 15px; margin: 15px 0; font-size: 0.85rem; color: #ccc;
        }
        .detail-row { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .detail-row:last-child { margin-bottom: 0; padding-top: 8px; border-top: 1px solid #333; }
        .detail-row span:last-child { font-weight: bold; color: var(--text-light); text-align: right; }

        .btn-outline-gold {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            background: transparent; border: 1px solid var(--accent-gold);
            color: var(--accent-gold); text-decoration: none; padding: 8px 15px;
            border-radius: 4px; font-weight: bold; font-size: 0.85rem;
            transition: 0.3s; margin-top: 10px; cursor: pointer; width: 100%;
        }
        .btn-outline-gold:hover { background: var(--accent-gold); color: #000; }

        /* Tombol WA (Floating) */
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

        /* ================= MODAL & E-RECEIPT STYLE ================= */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.9); display: none; justify-content: center; 
            align-items: center; z-index: 2000; padding: 20px; overflow-y: auto;
        }
        .receipt-card {
            background: #fff; color: #000; width: 100%; max-width: 350px;
            padding: 25px 20px; border-radius: 8px; font-family: 'Courier New', Courier, monospace;
            position: relative; box-shadow: 0 0 20px rgba(232, 201, 153, 0.2);
        }
        .close-modal {
            position: absolute; top: -15px; right: -15px;
            background: var(--primary-red); color: white; width: 30px; height: 30px;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-weight: bold; font-family: sans-serif; box-shadow: 0 2px 5px rgba(0,0,0,0.5);
        }
        .receipt-header { text-align: center; border-bottom: 2px dashed #000; padding-bottom: 15px; margin-bottom: 15px; }
        .receipt-header h3 { margin: 0; font-size: 1.2rem; font-family: sans-serif; font-weight: 900;}
        .receipt-header p { margin: 5px 0 0; font-size: 0.75rem; color: #555;}
        .receipt-body p { margin: 5px 0; font-size: 0.85rem; display: flex; justify-content: space-between; }
        .receipt-footer { text-align: center; border-top: 2px dashed #000; padding-top: 15px; margin-top: 15px; }
        
        .btn-download {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            background-color: #000; color: #fff; border: none; padding: 12px; width: 100%;
            margin-top: 20px; font-weight: bold; border-radius: 4px; cursor: pointer;
            font-family: sans-serif; transition: 0.3s;
        }
        .btn-download:hover { background-color: #333; }

        /* ================= CSS KHUSUS CETAK (PRINT) ================= */
        @media print {
            body * { visibility: hidden; } 
            .modal-overlay { position: absolute; left: 0; top: 0; padding: 0; background: transparent; }
            .receipt-card, .receipt-card * { visibility: visible; } 
            .receipt-card { box-shadow: none; max-width: 100%; padding: 0; }
            .no-print, .close-modal { display: none !important; } 
        }
    </style>
</head>
<body>

    <div class="status-container">
        <a href="member_dasbor.php" class="btn-back-square" title="Kembali ke Dasbor">←</a>
        
        <h2 style="color: var(--accent-gold); text-transform: uppercase; font-size: 1.4rem;">Status Perpanjangan</h2>
        <p style="color: #888; font-size: 0.9rem; margin-top: 10px;">
            Masukkan email Anda untuk melihat status verifikasi perpanjangan membership.
        </p>

        <div class="form-group">
            <label>Email Member</label>
            <input type="email" id="cekEmail" class="form-control" placeholder="nama@email.com" oninput="cekFormatEmail(this)">
            <div id="errorEmail" class="error-msg">Format email tidak valid.</div>
        </div>

        <button class="btn-search" onclick="cariStatus()">Cek Status</button>

        <div id="hasilCek" class="result-box">
            <div style="border-bottom: 1px solid #333; padding-bottom: 10px; margin-bottom: 10px;">
                <span style="color: #888; font-size: 0.85rem;">Email Terkait:</span>
                <div id="resNama" style="font-weight: bold; color: var(--text-light);">-</div>
            </div>
            
            <span style="color: #888; font-size: 0.85rem;">Status Pembayaran:</span>
            <div id="resStatus"></div>
            
            <div id="resPesan" style="margin-top: 15px; font-size: 0.85rem; color: #ccc; line-height: 1.4;"></div>
        </div>
    </div>

    <div class="modal-overlay" id="receiptModal">
        <div class="receipt-card">
            <div class="close-modal no-print" onclick="tutupBukti()">X</div>
            
            <div class="receipt-header">
                <h3>VANDA GYM CLASSIC</h3>
                <p>E-RECEIPT PERPANJANGAN MEMBER</p>
                <p>Palangka Raya, Kalimantan Tengah</p>
            </div>
            
            <div class="receipt-body" id="receiptData">
                </div>
            
            <div class="receipt-footer">
                <h3 style="margin:0; font-family:sans-serif;">STATUS: LUNAS</h3>
                <p style="font-size:0.7rem; color:#666; margin-top:5px;">Terima kasih. Masa aktif Anda telah diperbarui. Simpan bukti ini sebagai referensi.</p>
            </div>

            <button class="btn-download no-print" onclick="window.print()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Simpan sebagai PDF
            </button>
        </div>
    </div>

    <a href="https://wa.me/6282148556601?text=Halo%20Admin%20Vanda%20Gym,%20saya%20butuh%20bantuan%20terkait%20informasi%20perpanjangan%20member." target="_blank" class="wa-btn" title="Hubungi CS via WhatsApp">
        <svg viewBox="0 0 24 24">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/>
        </svg>
    </a>

    <script>
        function cekFormatEmail(input) {
            const error = document.getElementById('errorEmail');
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!regex.test(input.value) && input.value.length > 0) {
                error.style.display = 'block';
                input.classList.add('invalid');
            } else {
                error.style.display = 'none';
                input.classList.remove('invalid');
            }
        }

        function bukaBukti() { document.getElementById('receiptModal').style.display = 'flex'; }
        function tutupBukti() { document.getElementById('receiptModal').style.display = 'none'; }

        function cariStatus() {
            const email = document.getElementById('cekEmail').value.trim();
            const resultBox = document.getElementById('hasilCek');
            const inputElement = document.getElementById('cekEmail');
            const errorElement = document.getElementById('errorEmail');
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (!email) {
                alert("Silakan masukkan email terlebih dahulu.");
                return;
            }

            if (!regex.test(email)) {
                errorElement.style.display = 'block';
                inputElement.classList.add('invalid');
                return;
            }

            resultBox.style.display = 'block';
            const resNama = document.getElementById('resNama');
            const resStatus = document.getElementById('resStatus');
            const resPesan = document.getElementById('resPesan');

            resNama.innerText = email;

            // ==========================================
            // SIMULASI CEK KE DATABASE PERPANJANGAN
            // ==========================================
            
            // 1. Kondisi Jika AKTIF (Ketik email yang mengandung kata "aktif")
            if (email.toLowerCase().includes('aktif')) {
                
                // === BACA DATA DARI LOCALSTORAGE (Hasil Input perpanjang.php) ===
                const savedPaket = localStorage.getItem('vanda_renew_paket') || "1 Bulan Gym";
                const savedHarga = localStorage.getItem('vanda_renew_harga') || "Rp 175.000";
                const savedTglMulai = localStorage.getItem('vanda_renew_tglMulai') || "2026-04-25";
                
                // Format Tanggal Mulai jadi teks cantik (Misal: 25 Apr 2026)
                const formatTglCetak = (tglString, tambahBulan = 0) => {
                    let d = new Date(tglString);
                    if (tambahBulan > 0) d.setMonth(d.getMonth() + tambahBulan);
                    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
                };

                let tglMulaiFormat = formatTglCetak(savedTglMulai);
                
                // Otomatis tambah bulan kedaluwarsa sesuai paket
                let durasiBulan = 1;
                if (savedPaket.includes('2')) durasiBulan = 2;
                if (savedPaket.includes('3')) durasiBulan = 3;
                let tglAkhirFormat = formatTglCetak(savedTglMulai, durasiBulan);

                const noTrx = "RENEW-" + Math.floor(Math.random() * 99999);
                // ================================================================

                // Render Data Dinamis ke Struk E-Receipt
                document.getElementById('receiptData').innerHTML = `
                    <p><span>No. Trx</span> <span>${noTrx}</span></p>
                    <p><span>Tgl Bayar</span> <span>${formatTglCetak(new Date())}</span></p>
                    <p><span>Email</span> <span>${email}</span></p>
                    <hr style="border:1px dashed #000; margin:10px 0;">
                    <p><span>Paket</span> <span>${savedPaket}</span></p>
                    <p><span>Mulai Berlaku</span> <span>${tglMulaiFormat}</span></p>
                    <p><span>Berakhir Pada</span> <span>${tglAkhirFormat}</span></p>
                    <hr style="border:1px dashed #000; margin:10px 0;">
                    <p style="font-weight:bold; font-size:1rem;"><span>TOTAL</span> <span>${savedHarga}</span></p>
                `;
                
                resStatus.innerHTML = '<span class="status-badge status-active">Perpanjangan Aktif</span>';
                resPesan.innerHTML = `
                    <div class="detail-info">
                        <div class="detail-row"><span>Paket:</span><span>${savedPaket}</span></div>
                        <div class="detail-row"><span>Mulai:</span><span>${tglMulaiFormat}</span></div>
                        <div class="detail-row"><span>Berakhir:</span><span style="color: var(--primary-red);">${tglAkhirFormat}</span></div>
                    </div>
                    
                    <button class="btn-outline-gold" onclick="bukaBukti()">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        Download E-Receipt
                    </button>

                    <a href="member_dasbor.php" style="display: flex; align-items: center; justify-content: center; gap: 8px; background-color: var(--accent-gold); color: #000; text-decoration: none; padding: 10px; border-radius: 4px; font-weight: bold; margin-top: 15px; min-height: 44px; transition: 0.3s; font-size: 0.9rem;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                        Masuk ke Dasbor
                    </a>
                `;

            // 2. Kondisi Jika DITOLAK
            } else if (email.toLowerCase().includes('tolak') || email.toLowerCase().includes('gagal')) {
                
                resStatus.innerHTML = '<span class="status-badge status-rejected">Perpanjangan Ditolak</span>';
                
                const pesanWaTolak = encodeURIComponent(`Halo Admin Vanda Gym, pengajuan perpanjangan member saya dengan email *${email}* berstatus ditolak. Boleh mohon info alasannya?`);
                const linkWaTolak = `https://wa.me/6282148556601?text=${pesanWaTolak}`;

                resPesan.innerHTML = `
                    <strong style="color: #ff4d4d; display:block; margin-top:10px;">Verifikasi Gagal!</strong>
                    Pembayaran perpanjangan Anda tidak dapat diverifikasi (kemungkinan karena bukti bayar buram atau nominal tidak sesuai).
                    
                    <a href="${linkWaTolak}" target="_blank" style="display: flex; align-items: center; justify-content: center; gap: 8px; background-color: #8E1616; color: white; border: 1px solid #ff4d4d; text-decoration: none; padding: 10px; border-radius: 4px; font-weight: bold; margin-top: 15px; min-height: 44px; transition: 0.3s; font-size: 0.9rem;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                        Tanya Alasan ke CS
                    </a>
                    <a href="perpanjang.php" style="display: flex; align-items: center; justify-content: center; gap: 8px; background: transparent; color: #888; text-decoration: none; padding: 10px; border-radius: 4px; font-weight: bold; margin-top: 10px; min-height: 44px; transition: 0.3s; font-size: 0.9rem; border: 1px solid #333;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                        Ajukan Ulang Perpanjangan
                    </a>
                `;

            // 3. Kondisi DEFAULT (Menunggu / Pending)
            } else {
                resStatus.innerHTML = '<span class="status-badge status-pending">Menunggu Verifikasi</span>';
                
                const pesanWa = encodeURIComponent(`Halo Admin Vanda Gym, saya ingin mengkonfirmasi pembayaran perpanjangan member saya dengan email *${email}*. Apakah sudah diverifikasi? Terima kasih.`);
                const linkWa = `https://wa.me/6282148556601?text=${pesanWa}`;

                resPesan.innerHTML = `
                    <strong style="display:block; margin-top:10px; color:var(--text-light);">Informasi:</strong>
                    Admin sedang memverifikasi bukti transfer perpanjangan Anda. Masa aktif Anda akan otomatis diperbarui setelah pembayaran divalidasi.
                    
                    <a href="${linkWa}" target="_blank" style="display: flex; align-items: center; justify-content: center; gap: 8px; background-color: #25D366; color: white; text-decoration: none; padding: 10px; border-radius: 4px; font-weight: bold; margin-top: 15px; min-height: 44px; transition: 0.3s; font-size: 0.9rem;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                        Konfirmasi ke WhatsApp CS
                    </a>
                `;
            }
        }
    </script>
</body>
</html>