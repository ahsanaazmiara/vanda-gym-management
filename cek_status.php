<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'cek_status') {
    require 'includes/koneksi.php'; 
    header('Content-Type: application/json');

    $email = mysqli_real_escape_string($koneksi, $_POST['email']);

    $query_user = mysqli_query($koneksi, "SELECT id_user FROM users WHERE email = '$email' LIMIT 1");
    
    if (mysqli_num_rows($query_user) > 0) {
        $user_row = mysqli_fetch_assoc($query_user);
        $id_user = $user_row['id_user'];
        
        $query_history = "SELECT jenis_pengajuan, paket_bulan, total_harga, tgl_mulai, tgl_berakhir, status, alasan_tolak 
                          FROM membership WHERE id_user = '$id_user' ORDER BY id_membership DESC";
        $res_history = mysqli_query($koneksi, $query_history);
        
        $riwayat = [];
        while ($row = mysqli_fetch_assoc($res_history)) {
            $riwayat[] = [
                'jenis' => ucfirst($row['jenis_pengajuan']),
                'paket' => $row['paket_bulan'] . " Bulan",
                'harga' => "Rp " . number_format($row['total_harga'], 0, ',', '.'),
                'mulai' => date('d M Y', strtotime($row['tgl_mulai'])),
                'berakhir' => $row['tgl_berakhir'] ? date('d M Y', strtotime($row['tgl_berakhir'])) : '-',
                'status' => $row['status'],
                'alasan' => $row['alasan_tolak']
            ];
        }

        $query_last = "SELECT m.* FROM membership m WHERE m.id_user = '$id_user' ORDER BY m.id_membership DESC LIMIT 1";
        $res_last = mysqli_query($koneksi, $query_last);
        $last_data = mysqli_fetch_assoc($res_last);

        echo json_encode([
            'status_code' => 'ditemukan',
            'status' => $last_data['status'],
            'email' => $email,
            'namaPaket' => $last_data['paket_bulan'] . " Bulan Gym",
            'harga' => "Rp " . number_format($last_data['total_harga'], 0, ',', '.'),
            'tglMulai' => date('d M Y', strtotime($last_data['tgl_mulai'])),
            'tglBerakhir' => $last_data['tgl_berakhir'] ? date('d M Y', strtotime($last_data['tgl_berakhir'])) : '-',
            'alasan_tolak' => $last_data['alasan_tolak'],
            'riwayat' => $riwayat
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
            border-radius: 8px; padding: 30px; width: 100%; max-width: 600px;
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
            margin-bottom: 20px; transition: 0.3s;
        }
        .btn-back-square:hover { background-color: var(--primary-red); color: white; border-color: var(--primary-red); }

        .form-group { margin: 25px 0; text-align: left; position: relative; }
        .form-group label { display: block; margin-bottom: 8px; color: #ccc; font-weight: 600; font-size: 0.85rem;}
        
        .form-control {
            width: 100%; padding: 10px 15px; min-height: 44px;
            background-color: var(--input-bg); border: 1px solid #333;
            border-radius: 4px; color: white; font-size: 1rem; transition: 0.3s;
        }
        .form-control:focus { outline: none; border-color: var(--accent-gold); }
        .form-control.invalid { border-color: var(--primary-red); }

        .error-msg { color: #ff4d4d; font-size: 0.85rem; margin-top: 5px; display: none; }

        .btn-search {
            width: 100%; background-color: var(--primary-red); color: white;
            border: none; min-height: 48px; font-size: 1rem; font-weight: bold;
            border-radius: 4px; cursor: pointer; text-transform: uppercase; transition: 0.3s;
        }
        .btn-search:hover { background-color: #a81a1a; }

        .result-box {
            margin-top: 30px; padding: 20px; border-radius: 4px;
            background: #111; border: 1px solid #222; display: none; text-align: left;
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

        .history-table {
            width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.85rem; color: #ccc;
        }
        .history-table th, .history-table td {
            border: 1px solid #333; padding: 10px; text-align: center;
        }
        .history-table th { background-color: #161616; color: var(--accent-gold); font-weight: 600; }
        .history-table tr:nth-child(even) { background-color: #0d0d0d; }

        .btn-outline-gold {
            display: inline-flex; align-items: center; justify-content: center;
            background: transparent; border: 1px solid var(--accent-gold);
            color: var(--accent-gold); text-decoration: none; padding: 8px 15px;
            border-radius: 4px; font-weight: bold; font-size: 0.85rem;
            transition: 0.3s; margin-top: 10px; cursor: pointer; width: 100%;
        }
        .btn-outline-gold:hover { background: var(--accent-gold); color: #000; }

        .wa-btn {
            position: fixed; bottom: 30px; left: 30px; background-color: #25D366; color: white; 
            border-radius: 50%; width: 60px; height: 60px; display: flex; justify-content: center; align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.6); z-index: 1000; transition: 0.3s; text-decoration: none;
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
            position: absolute; top: -15px; right: -15px; background: var(--primary-red); color: white; width: 30px; height: 30px;
            border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; font-weight: bold;
        }
        .receipt-header { text-align: center; border-bottom: 2px dashed #000; padding-bottom: 15px; margin-bottom: 15px; }
        .receipt-header h3 { margin: 0; font-size: 1.2rem; font-family: sans-serif; font-weight: 900;}
        .receipt-header p { margin: 5px 0 0; font-size: 0.75rem; color: #555;}
        .receipt-body p { margin: 5px 0; font-size: 0.85rem; display: flex; justify-content: space-between; }
        .receipt-footer { text-align: center; border-top: 2px dashed #000; padding-top: 15px; margin-top: 15px; }
        .btn-download {
            background-color: #000; color: #fff; border: none; padding: 12px; width: 100%;
            margin-top: 20px; font-weight: bold; border-radius: 4px; cursor: pointer; transition: 0.3s;
        }
        .btn-download:hover { background-color: #333; }

        /* KOTAK ERROR HARUS FIXED DAN FLEX AGAR DI TENGAH LAYAR */
        .connection-error-box {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.85); display: none; 
            justify-content: center; align-items: center; z-index: 3000; padding: 20px;
        }
        .error-card-center {
            background-color: #0f0a0a; border: 1px solid #ff4d4d; border-top: 4px solid #ff4d4d;
            border-radius: 8px; padding: 30px 25px; max-width: 400px; width: 100%; text-align: center;
            box-shadow: 0 10px 30px rgba(255, 77, 77, 0.15);
        }
        .btn-retry {
            background-color: #25D366; color: white; border: none; padding: 10px 15px;
            border-radius: 4px; font-weight: bold; cursor: pointer; margin-top: 15px; width: 100%; transition: 0.3s;
        }
        .btn-retry:hover { background-color: #1ebe57; }

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
            
            <div style="margin-top: 25px; border-top: 1px solid #333; padding-top: 15px;">
                <h4 style="color: var(--accent-gold); font-size: 0.9rem; margin-bottom: 10px; text-transform: uppercase;">Riwayat Transaksi</h4>
                <div style="overflow-x: auto;">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Jenis</th>
                                <th>Paket</th>
                                <th>Total</th>
                                <th>Mulai</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="tabelRiwayatBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div> <div class="modal-overlay" id="receiptModal">
        <div class="receipt-card">
            <div class="close-modal no-print" onclick="tutupBukti()">X</div>
            <div class="receipt-header">
                <h3>VANDA GYM CLASSIC</h3>
                <p>E-RECEIPT REGISTRASI MEMBER</p>
                <p>Palangka Raya, Kalimantan Tengah</p>
            </div>
            <div class="receipt-body" id="receiptData"></div>
            <div class="receipt-footer">
                <h3 style="margin:0; font-family:sans-serif;">STATUS: LUNAS</h3>
                <p style="font-size:0.7rem; color:#666; margin-top:5px;">Terima kasih. Simpan bukti ini sebagai referensi pendaftaran Anda.</p>
            </div>
            <button class="btn-download no-print" onclick="window.print()">📥 Simpan sebagai PDF</button>
        </div>
    </div>

    <div id="boxErrorKoneksi" class="connection-error-box">
        <div class="error-card-center">
            <div style="width: 50px; height: 50px; background: #221111; border: 2px solid #ff4d4d; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto;">
                <span style="color: #ff4d4d; font-size: 1.6rem; font-weight: bold;">!</span>
            </div>
            <h3 style="color:#ff4d4d; font-size:1.2rem; font-weight:bold; margin-bottom: 8px;">Koneksi Server Gagal</h3>
            <p style="color:#ccc; font-size:0.85rem; line-height:1.5;">Sistem gagal memuat data pendaftaran dari database. Pastikan modul Apache & MySQL di XAMPP telah dinyalakan.</p>
            <button class="btn-retry" onclick="cariStatus()">🔄 Coba Lagi</button>
            <button type="button" style="background: transparent; border: none; color: #555; margin-top: 12px; cursor: pointer; font-size: 0.8rem;" onclick="document.getElementById('boxErrorKoneksi').style.display='none'">Tutup</button>
        </div>
    </div>

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
            const btnTeks = document.getElementById('btnCariTeks');
            const errorKoneksiBox = document.getElementById('boxErrorKoneksi');
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (!email) { alert("Silakan masukkan email terlebih dahulu."); return; }
            if (!regex.test(email)) {
                errorElement.style.display = 'block';
                inputElement.classList.add('invalid'); return;
            }

            resultBox.style.display = 'none'; 
            errorKoneksiBox.style.display = 'none';
            
            const resNama = document.getElementById('resNama');
            const resStatus = document.getElementById('resStatus');
            const resPesan = document.getElementById('resPesan');
            const tabelBody = document.getElementById('tabelRiwayatBody');
            tabelBody.innerHTML = ""; 

            const aslinya = btnTeks.innerText;
            btnTeks.innerText = "Mencari di Sistem...";
            btnTeks.disabled = true;

            const formData = new FormData();
            formData.append('action', 'cek_status');
            formData.append('email', email);

            fetch('cek_status.php', { method: 'POST', body: formData })
            .then(response => {
                if(!response.ok) throw new Error('Database Error');
                return response.json();
            })
            .then(data => {
                btnTeks.innerText = aslinya;
                btnTeks.disabled = false;
                resultBox.style.display = 'block';
                resNama.innerText = email;

                if (data.status_code === 'tidak_ditemukan') {
                    resStatus.innerHTML = '<span class="status-badge status-rejected">Tidak Terdaftar</span>';
                    resPesan.innerHTML = `<strong style="color: #ff4d4d; display:block; margin-top:10px;">Email Tidak Ditemukan!</strong>Sistem tidak menemukan akun Anda.`;
                    tabelBody.innerHTML = `<tr><td colspan="5" style="color:#888;">Tidak ada data riwayat.</td></tr>`;
                    return;
                }

                const statusDb = data.status;
                const emailDb = data.email;
                const namaPaket = data.namaPaket;
                const harga = data.harga;
                const tglMulai = data.tglMulai;
                const tglBerakhir = data.tglBerakhir;

                if(data.riwayat && data.riwayat.length > 0) {
                    data.riwayat.forEach(row => {
                        let clr = (row.status === 'aktif') ? 'color:#25D366;' : (row.status === 'ditolak' ? 'color:#ff4d4d;' : 'color:#ffc107;');
                        tabelBody.innerHTML += `<tr><td>${row.jenis}</td><td>${row.paket}</td><td>${row.harga}</td><td>${row.mulai}</td><td style="${clr} font-weight:bold;">${row.status.toUpperCase()}</td></tr>`;
                    });
                }

                if (statusDb === 'aktif') {
                    const noTrx = "REG-" + Math.floor(Math.random() * 99999);
                    document.getElementById('receiptData').innerHTML = `<p><span>No. Trx</span> <span>${noTrx}</span></p><p><span>Tgl Bayar</span> <span>${tglMulai}</span></p><p><span>Email</span> <span>${emailDb}</span></p><hr style="border:1px dashed #000; margin:10px 0;"><p><span>Paket</span> <span>${namaPaket}</span></p><p><span>Mulai Berlaku</span> <span>${tglMulai}</span></p><p><span>Berakhir Pada</span> <span>${tglBerakhir}</span></p><hr style="border:1px dashed #000; margin:10px 0;"><p style="font-weight:bold; font-size:1rem;"><span>TOTAL</span> <span>${harga}</span></p>`;
                    resStatus.innerHTML = '<span class="status-badge status-active">Aktif Terverifikasi</span>';
                    resPesan.innerHTML = `<div class="detail-info"><div class="detail-row"><span>Paket:</span><span>${namaPaket}</span></div><div class="detail-row"><span>Mulai:</span><span>${tglMulai}</span></div><div class="detail-row"><span>Berakhir:</span><span style="color: var(--primary-red);">${tglBerakhir}</span></div></div><button class="btn-outline-gold" onclick="bukaBukti()">🧾 Download E-Receipt</button>`;
                } else if (statusDb === 'ditolak') {
                    resStatus.innerHTML = '<span class="status-badge status-rejected">Pendaftaran Ditolak</span>';
                    const alasanTolak = data.alasan_tolak ? data.alasan_tolak : 'Bukti transfer tidak valid.';
                    resPesan.innerHTML = `<strong style="color: #ff4d4d; display:block; margin-top:10px;">Verifikasi Gagal!</strong>Alasan: <em>"${alasanTolak}"</em>`;
                } else {
                    resStatus.innerHTML = '<span class="status-badge status-pending">Menunggu Verifikasi</span>';
                    resPesan.innerHTML = `<strong style="display:block; margin-top:10px;">Tahap Verifikasi:</strong>Admin sedang meninjau berkas Anda.`;
                }
            })
            .catch(error => {
                btnTeks.innerText = aslinya;
                btnTeks.disabled = false;
                errorKoneksiBox.style.display = 'flex'; // MENAMPILKAN MODAL DI TENGAH LAYAR
            });
        }
    </script>
</body>
</html>