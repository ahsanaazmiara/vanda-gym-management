<?php
// =========================================================
// BLOK PHP: MENCARI DATA PENDAFTARAN DARI DATABASE MENGGUNAKAN AJAX
// =========================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'cek_status') {
    require 'includes/koneksi.php'; // Pastikan path benar
    header('Content-Type: application/json');

    $email = mysqli_real_escape_string($koneksi, $_POST['email']);

    // Cari di tabel users di-join ke membership untuk dapat status terbarunya
    $query = "SELECT u.nama_lengkap, u.email, m.paket_bulan, m.total_harga, m.tgl_mulai, m.tgl_berakhir, m.status, m.alasan_tolak 
              FROM users u 
              LEFT JOIN membership m ON u.id_user = m.id_user 
              WHERE u.email = '$email' 
              ORDER BY m.id_membership DESC LIMIT 1";

    $result = mysqli_query($koneksi, $query);

    if (mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        
        // Bikin format Rupiah untuk Harga
        $harga = "Rp " . number_format($data['total_harga'], 0, ',', '.');
        
        // Bikin format nama Paket (misal 1 -> 1 Bulan Gym)
        $namaPaket = $data['paket_bulan'] . " Bulan Gym";

        // Bikin format Tanggal Cantik (misal: 25 Apr 2026)
        $tgl_mulai = date('d M Y', strtotime($data['tgl_mulai']));
        $tgl_berakhir = $data['tgl_berakhir'] ? date('d M Y', strtotime($data['tgl_berakhir'])) : '-';

        echo json_encode([
            'status_code' => 'ditemukan',
            'status' => $data['status'], // pending, aktif, atau ditolak
            'email' => $data['email'],
            'namaPaket' => $namaPaket,
            'harga' => $harga,
            'tglMulai' => $tgl_mulai,
            'tglBerakhir' => $tgl_berakhir,
            'alasan_tolak' => $data['alasan_tolak']
        ]);
    } else {
        echo json_encode(['status_code' => 'tidak_ditemukan']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Status Pendaftaran - Vanda Gym Classic</title>
    <style>
        /* [SELURUH CSS KAMU YANG ASLI TIDAK ADA YANG DIUBAH] */
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

        .detail-info {
            background-color: #1a1a1a; border: 1px dashed #333; border-radius: 4px;
            padding: 15px; margin: 15px 0; font-size: 0.85rem; color: #ccc;
        }
        .detail-row { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .detail-row:last-child { margin-bottom: 0; padding-top: 8px; border-top: 1px solid #333; }
        .detail-row span:last-child { font-weight: bold; color: var(--text-light); text-align: right; }

        .btn-outline-gold {
            display: inline-flex; align-items: center; justify-content: center;
            background: transparent; border: 1px solid var(--accent-gold);
            color: var(--accent-gold); text-decoration: none; padding: 8px 15px;
            border-radius: 4px; font-weight: bold; font-size: 0.85rem;
            transition: 0.3s; margin-top: 10px; cursor: pointer; width: 100%;
        }
        .btn-outline-gold:hover { background: var(--accent-gold); color: #000; }

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
            background-color: #000; color: #fff; border: none; padding: 12px; width: 100%;
            margin-top: 20px; font-weight: bold; border-radius: 4px; cursor: pointer;
            font-family: sans-serif; transition: 0.3s;
        }
        .btn-download:hover { background-color: #333; }

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
        <a href="index.php" class="btn-back-square" title="Kembali">←</a>
        
        <h2 style="color: var(--accent-gold); text-transform: uppercase; font-size: 1.4rem;">Cek Status Verifikasi</h2>
        <p style="color: #888; font-size: 0.9rem; margin-top: 10px;">
            Masukkan Email Anda untuk melihat status aktivasi membership.
        </p>

        <div class="form-group">
            <label>Email Pendaftaran</label>
            <input type="email" id="cekEmail" class="form-control" placeholder="nama@email.com" oninput="cekFormatEmail(this)">
            <div id="errorEmail" class="error-msg">Format email tidak valid.</div>
        </div>

        <button class="btn-search" id="btnCariTeks" onclick="cariStatus()">Cari Data</button>

        <div id="hasilCek" class="result-box">
            <div style="border-bottom: 1px solid #333; padding-bottom: 10px; margin-bottom: 10px;">
                <span style="color: #888; font-size: 0.85rem;">Email Pendaftar:</span>
                <div id="resNama" style="font-weight: bold; color: var(--text-light);">-</div>
            </div>
            
            <span style="color: #888; font-size: 0.85rem;">Status Verifikasi:</span>
            <div id="resStatus"></div>
            
            <div id="resPesan" style="margin-top: 15px; font-size: 0.85rem; color: #ccc; line-height: 1.4;"></div>
        </div>
    </div>

    <div class="modal-overlay" id="receiptModal">
        <div class="receipt-card">
            <div class="close-modal no-print" onclick="tutupBukti()">X</div>
            
            <div class="receipt-header">
                <h3>VANDA GYM CLASSIC</h3>
                <p>E-RECEIPT REGISTRASI MEMBER</p>
                <p>Palangka Raya, Kalimantan Tengah</p>
            </div>
            
            <div class="receipt-body" id="receiptData">
                </div>
            
            <div class="receipt-footer">
                <h3 style="margin:0; font-family:sans-serif;">STATUS: LUNAS</h3>
                <p style="font-size:0.7rem; color:#666; margin-top:5px;">Terima kasih. Simpan bukti ini sebagai referensi pendaftaran Anda.</p>
            </div>

            <button class="btn-download no-print" onclick="window.print()">📥 Simpan sebagai PDF</button>
        </div>
    </div>

    <a href="https://wa.me/6282148556601?text=Halo%20Admin%20Vanda%20Gym,%20saya%20butuh%20bantuan%20terkait%20informasi%20pendaftaran%20member." target="_blank" class="wa-btn" title="Hubungi CS via WhatsApp">
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

        // ==========================================
        // FUNGSI INI SEKARANG MENARIK DATA DARI PHP DATABASE (AJAX)
        // ==========================================
        function cariStatus() {
            const email = document.getElementById('cekEmail').value.trim();
            const resultBox = document.getElementById('hasilCek');
            const inputElement = document.getElementById('cekEmail');
            const errorElement = document.getElementById('errorEmail');
            const btnTeks = document.getElementById('btnCariTeks');
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (!email) { alert("Silakan masukkan email terlebih dahulu."); return; }
            if (!regex.test(email)) {
                errorElement.style.display = 'block';
                inputElement.classList.add('invalid'); return;
            }

            resultBox.style.display = 'none'; // Sembunyikan dulu selama loading
            const resNama = document.getElementById('resNama');
            const resStatus = document.getElementById('resStatus');
            const resPesan = document.getElementById('resPesan');

            // Animasi Loading
            const aslinya = btnTeks.innerText;
            btnTeks.innerText = "Mencari di Sistem...";
            btnTeks.disabled = true;

            // Siapkan pengiriman AJAX ke atas file ini
            const formData = new FormData();
            formData.append('action', 'cek_status');
            formData.append('email', email);

            fetch('cek_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btnTeks.innerText = aslinya;
                btnTeks.disabled = false;
                resultBox.style.display = 'block';
                resNama.innerText = email;

                // JIKA DATA TIDAK DITEMUKAN
                if (data.status_code === 'tidak_ditemukan') {
                    resStatus.innerHTML = '<span class="status-badge status-rejected">Tidak Terdaftar</span>';
                    resPesan.innerHTML = `
                        <strong style="color: #ff4d4d; display:block; margin-top:10px;">Email Tidak Ditemukan!</strong>
                        Sistem kami tidak menemukan pendaftaran dengan email tersebut. Pastikan email tidak salah ketik, atau silakan daftar baru.
                        <a href="daftar.php" style="display: flex; align-items: center; justify-content: center; background-color: var(--primary-red); color: white; text-decoration: none; padding: 10px; border-radius: 4px; font-weight: bold; margin-top: 15px; transition: 0.3s; font-size: 0.9rem;">Daftar Sekarang</a>
                    `;
                    return;
                }

                // JIKA DATA DITEMUKAN, CEK STATUSNYA:
                const statusDb = data.status; // pending, aktif, atau ditolak
                const emailDb = data.email;
                const namaPaket = data.namaPaket;
                const harga = data.harga;
                const tglMulai = data.tglMulai;
                const tglBerakhir = data.tglBerakhir;

                // 1. KONDISI JIKA AKTIF
                if (statusDb === 'aktif') {
                    const noTrx = "REG-" + Math.floor(Math.random() * 99999); // Nomor transaksi bebas
                    
                    // Isi E-Receipt
                    document.getElementById('receiptData').innerHTML = `
                        <p><span>No. Trx</span> <span>${noTrx}</span></p>
                        <p><span>Tgl Bayar</span> <span>${tglMulai}</span></p>
                        <p><span>Email</span> <span style="font-size:0.75rem;">${emailDb}</span></p>
                        <hr style="border:1px dashed #000; margin:10px 0;">
                        <p><span>Paket</span> <span>${namaPaket}</span></p>
                        <p><span>Mulai Berlaku</span> <span>${tglMulai}</span></p>
                        <p><span>Berakhir Pada</span> <span>${tglBerakhir}</span></p>
                        <hr style="border:1px dashed #000; margin:10px 0;">
                        <p style="font-weight:bold; font-size:1rem;"><span>TOTAL</span> <span>${harga}</span></p>
                    `;
                    
                    resStatus.innerHTML = '<span class="status-badge status-active">Aktif Terverifikasi</span>';
                    resPesan.innerHTML = `
                        <div class="detail-info">
                            <div class="detail-row"><span>Paket:</span><span>${namaPaket}</span></div>
                            <div class="detail-row"><span>Mulai:</span><span>${tglMulai}</span></div>
                            <div class="detail-row"><span>Berakhir:</span><span style="color: var(--primary-red);">${tglBerakhir}</span></div>
                        </div>
                        <button class="btn-outline-gold" onclick="bukaBukti()">🧾 Download E-Receipt</button>
                        <a href="login.php" style="display: flex; align-items: center; justify-content: center; background-color: var(--accent-gold); color: #000; text-decoration: none; padding: 10px; border-radius: 4px; font-weight: bold; margin-top: 15px; min-height: 44px; transition: 0.3s; font-size: 0.9rem;">
                            🔑 Login ke Dasbor
                        </a>
                    `;
                } 
                
                // 2. KONDISI JIKA DITOLAK
                else if (statusDb === 'ditolak') {
                    resStatus.innerHTML = '<span class="status-badge status-rejected">Pendaftaran Ditolak</span>';
                    const alasanTolak = data.alasan_tolak ? data.alasan_tolak : 'Bukti transfer tidak valid/nominal tidak sesuai.';
                    const pesanWaTolak = encodeURIComponent(`Halo Admin Vanda Gym, pendaftaran member saya dengan email *${emailDb}* berstatus ditolak. Boleh mohon info perbaikannya?`);
                    
                    resPesan.innerHTML = `
                        <strong style="color: #ff4d4d; display:block; margin-top:10px;">Verifikasi Gagal!</strong>
                        Alasan Admin: <em style="color:#aaa;">"${alasanTolak}"</em>
                        
                        <a href="https://wa.me/6282148556601?text=${pesanWaTolak}" target="_blank" style="display: flex; align-items: center; justify-content: center; background-color: #8E1616; color: white; border: 1px solid #ff4d4d; text-decoration: none; padding: 10px; border-radius: 4px; font-weight: bold; margin-top: 15px; min-height: 44px; transition: 0.3s; font-size: 0.9rem;">
                            📞 Tanya Detail ke CS
                        </a>
                        <a href="daftar.php" style="display: flex; align-items: center; justify-content: center; background: transparent; color: #888; text-decoration: none; padding: 10px; border-radius: 4px; font-weight: bold; margin-top: 10px; min-height: 44px; transition: 0.3s; font-size: 0.9rem;">
                            Daftar Ulang
                        </a>
                    `;
                } 
                
                // 3. KONDISI DEFAULT (PENDING)
                else {
                    resStatus.innerHTML = '<span class="status-badge status-pending">Menunggu Verifikasi</span>';
                    const pesanWa = encodeURIComponent(`Halo Admin Vanda Gym, saya ingin mengkonfirmasi pendaftaran member baru saya dengan email *${emailDb}*. Apakah pembayarannya sudah diverifikasi? Terima kasih.`);
                    
                    resPesan.innerHTML = `
                        <strong style="display:block; margin-top:10px; color:var(--text-light);">Tahap Verifikasi:</strong>
                        Admin sedang memverifikasi data dan bukti pembayaran Anda. Jika status sudah berubah aktif, Anda bisa langsung login.
                        
                        <a href="https://wa.me/6282148556601?text=${pesanWa}" target="_blank" style="display: flex; align-items: center; justify-content: center; background-color: #25D366; color: white; text-decoration: none; padding: 10px; border-radius: 4px; font-weight: bold; margin-top: 15px; min-height: 44px; transition: 0.3s; font-size: 0.9rem;">
                            📞 Konfirmasi Pengecekan ke WhatsApp
                        </a>
                    `;
                }
            })
            .catch(error => {
                btnTeks.innerText = aslinya;
                btnTeks.disabled = false;
                alert("Terjadi kesalahan pada server. Pastikan XAMPP/Database menyala.");
            });
        }
    </script>
</body>
</html>