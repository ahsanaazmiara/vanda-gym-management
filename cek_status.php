<?php
require 'includes/koneksi.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // --- AKSI 1: BATALKAN PENDAFTARAN PENDING ---
    if ($_POST['action'] == 'batalkan_pendaftaran') {
        $email = mysqli_real_escape_string($koneksi, $_POST['email']);
        
        $q_user = mysqli_query($koneksi, "SELECT id_user, role FROM users WHERE email = '$email' LIMIT 1");
        if (mysqli_num_rows($q_user) > 0) {
            $user_row = mysqli_fetch_assoc($q_user);
            $id_user = $user_row['id_user'];
            $role_user = $user_row['role'];
            
            // 1. Hapus pengajuan membership yang statusnya 'pending'
            $q_batal = mysqli_query($koneksi, "DELETE FROM membership WHERE id_user = '$id_user' AND status = 'pending' AND jenis_pengajuan = 'daftar'");
            
            if ($q_batal && mysqli_affected_rows($koneksi) > 0) {
                // 2. Jika dia adalah calon_member murni (belum pernah aktif) dan tidak punya riwayat lain
                // Maka lebih baik kita hapus juga akun sementaranya di tabel users agar bisa daftar ulang
                $q_sisa_riwayat = mysqli_query($koneksi, "SELECT id_membership FROM membership WHERE id_user = '$id_user'");
                if (mysqli_num_rows($q_sisa_riwayat) == 0 && $role_user == 'calon_member') {
                    mysqli_query($koneksi, "DELETE FROM users WHERE id_user = '$id_user'");
                }

                echo json_encode(['status' => 'success', 'message' => 'Pendaftaran berhasil dibatalkan. Anda dapat mendaftar ulang kapan saja.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal membatalkan. Status pengajuan mungkin sudah disetujui atau ditolak admin.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan.']);
        }
        exit;
    }

    // --- AKSI 2: CEK STATUS ---
    if ($_POST['action'] == 'cek_status') {
        $email = mysqli_real_escape_string($koneksi, $_POST['email']);

        $query_user = mysqli_query($koneksi, "SELECT id_user FROM users WHERE email = '$email' LIMIT 1");
        
        if (mysqli_num_rows($query_user) > 0) {
            $user_row = mysqli_fetch_assoc($query_user);
            $id_user = $user_row['id_user'];
            
            // Cek apakah dia punya riwayat membership
            $query_history = "SELECT jenis_pengajuan, paket_bulan, total_harga, tgl_mulai, tgl_berakhir, status, alasan_tolak 
                              FROM membership WHERE id_user = '$id_user' ORDER BY id_membership DESC";
            $res_history = mysqli_query($koneksi, $query_history);
            
            // PERBAIKAN: Jika user ada tapi TIDAK punya riwayat membership sama sekali (misal batal), anggap tidak ditemukan
            if (mysqli_num_rows($res_history) == 0) {
                echo json_encode(['status_code' => 'tidak_ditemukan']);
                exit;
            }

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
}

// Ambil Pengaturan Web untuk nomor WA
$q_pengaturan = mysqli_query($koneksi, "SELECT wa_cs FROM pengaturan_web WHERE id=1");
$web_data = mysqli_fetch_assoc($q_pengaturan);
$wa_db = $web_data['wa_cs'] ?? '082148556601';
$wa_link = "62" . substr(preg_replace('/[^0-9]/', '', $wa_db), 1);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Status Pendaftaran - Vanda Gym Classic</title>
    <style>
        :root { 
            --bg-dark: #000000; --primary-red: #dc3545; --accent-gold: #E8C999; 
            --text-light: #F8EEDF; --input-bg: #111111; --success-green: #28a745;
            --warning-yellow: #ffc107;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--bg-dark); color: var(--text-light); display: flex; justify-content: center; align-items: flex-start; min-height: 100vh; padding: 40px 150px; }
        
        .status-container { background-color: #0a0a0a; border: 1px solid #333; border-top: 4px solid var(--accent-gold); border-radius: 8px; padding: 30px; width: 100%; max-width: 800px; box-shadow: 0 10px 30px rgba(0,0,0,0.8); position: relative; margin-bottom: 80px; }
        
        .nav-top { margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; }
        .btn-back-square { width: 44px; height: 44px; background-color: #1a1a1a; border: 1px solid #333; color: var(--accent-gold); border-radius: 4px; display: flex; align-items: center; justify-content: center; text-decoration: none; font-weight: bold; font-size: 1.2rem; transition: 0.3s; }
        .btn-back-square:hover { background-color: var(--primary-red); color: white; border-color: var(--primary-red); }
        
        .form-header { text-align: center; margin-bottom: 25px; border-bottom: 1px solid #222; padding-bottom: 20px;}
        .form-header h2 { color: var(--text-light); text-transform: uppercase; letter-spacing: 1px; font-size: 1.5rem; margin-bottom: 5px;}
        .form-header p { color: #888; font-size: 0.9rem; }

        .form-group { margin: 25px 0; text-align: left; position: relative; }
        .form-group label { display: block; margin-bottom: 8px; color: #ccc; font-weight: 600; font-size: 0.85rem;}
        
        .form-control {
            width: 100%; padding: 10px 15px; min-height: 44px;
            background-color: var(--input-bg); border: 1px solid #333;
            border-radius: 4px; color: white; font-size: 1rem; transition: 0.3s;
        }
        .form-control:focus { outline: none; border-color: var(--accent-gold); }
        .form-control.invalid { border-color: var(--primary-red); }

        .error-msg { color: var(--primary-red); font-size: 0.85rem; margin-top: 5px; display: none; }

        .btn-search {
            width: 100%; background-color: var(--success-green); color: white;
            border: none; min-height: 48px; font-size: 1rem; font-weight: bold;
            border-radius: 4px; cursor: pointer; text-transform: uppercase; transition: 0.3s; margin-bottom: 15px;
        }
        .btn-search:hover { background-color: #218838; }
        
        /* HASIL PENCARIAN & TABEL RIWAYAT */
        .result-box { display: none; margin-top: 20px; animation: fadeIn 0.5s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

        .table-container { overflow-x: auto; margin-top: 15px; border-radius: 4px; border: 1px solid #222; }
        .table-riwayat { width: 100%; border-collapse: collapse; font-size: 0.85rem; text-align: left; }
        .table-riwayat th { background-color: #151515; color: var(--accent-gold); padding: 12px; border-bottom: 1px solid #333; white-space: nowrap; text-transform: uppercase; }
        .table-riwayat td { padding: 12px; border-bottom: 1px solid #222; color: #ccc; white-space: nowrap; vertical-align: middle; }
        .table-riwayat tr:last-child td { border-bottom: none; }
        .table-riwayat tr:hover { background-color: #111; }

        .btn-small-gold { display: inline-flex; justify-content: center; align-items: center; background: transparent; border: 1px solid var(--accent-gold); color: var(--accent-gold); font-size: 0.75rem; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-weight: bold; transition: 0.3s; margin-top: 10px; text-decoration: none;}
        .btn-small-gold:hover { background: var(--accent-gold); color: #000; }

        .detail-info { background-color: #151515; border: 1px dashed #333; border-radius: 4px; padding: 15px; margin: 15px 0; font-size: 0.85rem; color: #ccc; }
        .detail-row { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .detail-row:last-child { margin-bottom: 0; padding-top: 8px; border-top: 1px solid #333; }
        .detail-row span:last-child { font-weight: bold; color: var(--text-light); text-align: right; }

        /* MODAL E-RECEIPT */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); display: none; justify-content: center; align-items: center; z-index: 2000; padding: 20px; overflow-y: auto; }
        .receipt-card { background: #fff; color: #000; width: 100%; max-width: 350px; padding: 25px 20px; border-radius: 8px; font-family: 'Courier New', Courier, monospace; position: relative; box-shadow: 0 0 20px rgba(232, 201, 153, 0.2); }
        .close-modal { position: absolute; top: -15px; right: -15px; background: var(--primary-red); color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; font-weight: bold; font-family: sans-serif; box-shadow: 0 2px 5px rgba(0,0,0,0.5); }
        .receipt-header { text-align: center; border-bottom: 2px dashed #000; padding-bottom: 15px; margin-bottom: 15px; }
        .receipt-header h3 { margin: 0; font-size: 1.2rem; font-family: sans-serif; font-weight: 900;}
        .receipt-header p { margin: 5px 0 0; font-size: 0.75rem; color: #555;}
        .receipt-body p { margin: 5px 0; font-size: 0.85rem; display: flex; justify-content: space-between; }
        .receipt-footer { text-align: center; border-top: 2px dashed #000; padding-top: 15px; margin-top: 15px; }
        .btn-download { display: flex; align-items: center; justify-content: center; gap: 8px; background-color: #000; color: #fff; border: none; padding: 12px; width: 100%; margin-top: 20px; font-weight: bold; border-radius: 4px; cursor: pointer; font-family: sans-serif; transition: 0.3s; }
        .btn-download:hover { background-color: #333; }
        @media print { body * { visibility: hidden; } .modal-overlay { position: absolute; left: 0; top: 0; padding: 0; background: transparent; } .receipt-card, .receipt-card * { visibility: visible; } .receipt-card { box-shadow: none; max-width: 100%; padding: 0; margin: 0; } .no-print, .close-modal { display: none !important; } }
        
        /* ERROR BOX */
        .connection-error-box { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.85); display: none; justify-content: center; align-items: center; z-index: 3000; padding: 20px; }
        .error-card-center { background-color: #0f0a0a; border: 1px solid var(--primary-red); border-top: 4px solid var(--primary-red); border-radius: 8px; padding: 30px 25px; max-width: 400px; width: 100%; text-align: center; box-shadow: 0 10px 30px rgba(220, 53, 69, 0.15); }
        .btn-retry { background-color: var(--success-green); color: white; border: none; padding: 10px 15px; border-radius: 4px; font-weight: bold; cursor: pointer; margin-top: 15px; width: 100%; transition: 0.3s; }
        .btn-retry:hover { background-color: #218838; }

        /* TOMBOL WA MELAYANG */
        .wa-btn { position: fixed; bottom: 30px; left: 30px; background-color: #25D366; color: white; border-radius: 50%; width: 55px; height: 55px; display: flex; justify-content: center; align-items: center; box-shadow: 0 4px 15px rgba(0,0,0,0.6); z-index: 1000; transition: 0.3s; text-decoration: none; }
        .wa-btn:hover { transform: scale(1.1); background-color: #1ebe57; }
        .wa-btn svg { width: 30px; height: 30px; }

        @media (max-width: 768px) {
            body { padding: 40px 45px 75px; }
            .status-container { padding: 20px 15px; margin-bottom: 20px; }
            .wa-btn { bottom: 20px !important; left: 15px; width: 44px; height: 44px; }
            .wa-btn svg { width: 24px; height: 24px; }
            
            .nav-top { margin-bottom: 15px; }
            .btn-back-square { width: 35px; height: 35px; font-size: 1rem; }
            .form-header { margin-bottom: 15px; padding-bottom: 15px; }
            .form-header h2 { font-size: 1.25rem; }
            .form-header p { font-size: 0.8rem; }
            
            .form-group { margin: 15px 0; }
            .form-group label { font-size: 0.8rem; margin-bottom: 5px; }
            .form-control { padding: 8px 12px; min-height: 38px; font-size: 0.85rem; }
            .btn-search { min-height: 40px; font-size: 0.9rem; margin-bottom: 10px; }
            
            .result-box { padding: 15px; margin-top: 15px; }
            #resNama { font-size: 0.95rem !important; }
            .detail-info { padding: 10px; font-size: 0.8rem; margin: 10px 0; }
            h4 { font-size: 0.85rem !important; margin-bottom: 10px !important; }
            #resPesan { font-size: 0.8rem !important; }
            
            .table-riwayat { font-size: 0.75rem; }
            .table-riwayat th, .table-riwayat td { padding: 8px; }
            .btn-small-gold { font-size: 0.7rem; padding: 6px 10px; }
        }
    </style>
</head>
<body>

    <div class="status-container">
        <div class="nav-top">
            <a href="index.php" class="btn-back-square" title="Kembali">←</a>
            <span style="color: #666; font-size: 0.75rem; font-weight: bold; text-transform: uppercase;">Pusat Tagihan</span>
        </div>
        
        <div class="form-header">
            <h2>Cek <span style="color: var(--accent-gold);">Status</span></h2>
            <p>Masukkan Email Anda untuk melihat status aktivasi membership.</p>
        </div>

        <div class="form-group">
            <label>Email Pendaftaran</label>
            <input type="email" id="cekEmail" class="form-control" placeholder="nama@email.com" oninput="cekFormatEmail(this)">
            <div id="errorEmail" class="error-msg">Format email tidak valid.</div>
        </div>

        <button class="btn-search" id="btnCariTeks" onclick="cariStatus()">Cari Data</button>

        <div id="hasilCek" class="result-box">
            <div style="border-bottom: 1px solid #333; padding-bottom: 15px; margin-bottom: 15px;">
                <span style="color: #888; font-size: 0.85rem; display: block; margin-bottom: 4px;">Pencarian Email:</span>
                <div id="resNama" style="font-weight: bold; color: var(--text-light); font-size: 1.1rem;">-</div>
            </div>
            
            <h4 style="color: var(--accent-gold); margin-bottom: 10px; text-transform: uppercase; font-size: 0.95rem;">Informasi Status</h4>
            <div id="resStatus"></div>
            <div id="resPesan" style="margin-top: 10px; font-size: 0.85rem; color: #ccc; line-height: 1.4;"></div>
            
            <div style="margin-top: 30px; border-top: 1px solid #222; padding-top: 20px;">
                <h4 style="color: var(--accent-gold); margin-bottom: 15px; text-transform: uppercase; font-size: 0.95rem;">Riwayat Transaksi</h4>
                <div class="table-container">
                    <table class="table-riwayat">
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
    </div>

    <div class="modal-overlay" id="receiptModal">
        <div class="receipt-card">
            <div class="close-modal no-print" onclick="tutupBukti()">X</div>
            <div class="receipt-header">
                <h3>VANDA GYM CLASSIC</h3>
                <p>E-RECEIPT REGISTRASI MEMBER</p>
                <p>Palangka Raya, Kalimantan Tengah</p>
            </div>
            <div class="receipt-body" id="receiptData"></div>
            <div class="receipt-footer">
                <h3 style="margin:0; font-family:sans-serif; letter-spacing:1px;">LUNAS</h3>
                <p style="font-size:0.7rem; color:#666; margin-top:5px;">Terima kasih. Simpan bukti ini sebagai referensi Anda.</p>
            </div>
            <button class="btn-download no-print" onclick="window.print()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Simpan sebagai PDF
            </button>
        </div>
    </div>

    <div id="boxErrorKoneksi" class="connection-error-box">
        <div class="error-card-center">
            <div style="width: 50px; height: 50px; background: #221111; border: 2px solid var(--primary-red); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto;">
                <span style="color: var(--primary-red); font-size: 1.6rem; font-weight: bold;">!</span>
            </div>
            <h3 style="color:var(--primary-red); font-size:1.2rem; font-weight:bold; margin-bottom: 8px;">Koneksi Server Gagal</h3>
            <p style="color:#ccc; font-size:0.85rem; line-height:1.5;">Sistem gagal memuat data pendaftaran dari database. Pastikan modul server telah dinyalakan dan koneksi internet stabil.</p>
            <button class="btn-retry" onclick="cariStatus()">🔄 Coba Lagi</button>
            <button type="button" style="background: transparent; border: none; color: #555; margin-top: 12px; cursor: pointer; font-size: 0.8rem;" onclick="document.getElementById('boxErrorKoneksi').style.display='none'">Tutup</button>
        </div>
    </div>

    <a href="https://instagram.com/vandagympky_classic" target="_blank" class="wa-btn" title="Hubungi CS via Instagram" style="position: fixed; bottom: 20px; left: 20px; z-index: 9999; color: #ffffff; background: var(--primary-red, #ff4d4d); border-radius: 50%; padding: 12px; box-shadow: 0 4px 15px rgba(255, 77, 77, 0.4); border: 2px solid #E8C999; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
        <circle cx="12" cy="7" r="4"></circle>
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

        // --- FUNGSI BARU: BATALKAN PENDAFTARAN PENDING ---
        function batalkanPendaftaran(email) {
            if(confirm("Apakah Anda yakin ingin membatalkan pendaftaran ini? Data pengajuan akan dihapus.")) {
                const btnTeks = document.getElementById('btnCariTeks');
                const aslinya = btnTeks.innerText;
                btnTeks.innerText = "Membatalkan...";
                btnTeks.disabled = true;
                
                const formData = new FormData();
                formData.append('action', 'batalkan_pendaftaran');
                formData.append('email', email);

                fetch('cek_status.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        alert(data.message);
                        cariStatus(); // Refresh data layar tanpa me-reload halaman full
                    } else {
                        alert(data.message);
                        btnTeks.innerText = aslinya;
                        btnTeks.disabled = false;
                    }
                })
                .catch(err => {
                    alert("Koneksi gagal. Silakan coba lagi.");
                    btnTeks.innerText = aslinya;
                    btnTeks.disabled = false;
                });
            }
        }

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
            btnTeks.innerText = "Mencari Data...";
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
                btnTeks.innerText = "Cari Data";
                btnTeks.disabled = false;
                resultBox.style.display = 'block';
                resNama.innerText = email;

                if (data.status_code === 'tidak_ditemukan') {
                    resStatus.innerHTML = '<span style="color:var(--primary-red); font-weight:bold;">Tidak Terdaftar</span>';
                    resPesan.innerHTML = `<strong style="color: var(--primary-red); display:block; margin-top:5px;">Email Tidak Ditemukan!</strong>Sistem tidak menemukan akun dengan email tersebut.`;
                    tabelBody.innerHTML = `<tr><td colspan="5" style="color:#888; text-align:center;">Tidak ada data riwayat.</td></tr>`;
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
                        let clr = (row.status === 'aktif') ? 'color:var(--success-green);' : (row.status === 'ditolak' ? 'color:var(--primary-red);' : 'color:var(--warning-yellow);');
                        tabelBody.innerHTML += `<tr>
                            <td>${row.jenis}</td>
                            <td>${row.paket}</td>
                            <td>${row.harga}</td>
                            <td>${row.mulai}</td>
                            <td style="${clr} font-weight:bold;">${row.status.toUpperCase()}</td>
                        </tr>`;
                    });
                }

                if (statusDb === 'aktif') {
                    const noTrx = "REG-" + Math.floor(Math.random() * 99999);
                    document.getElementById('receiptData').innerHTML = `
                        <p><span>No. Trx</span> <span>${noTrx}</span></p>
                        <p><span>Tgl Bayar</span> <span>${tglMulai}</span></p>
                        <p><span>Email</span> <span>${emailDb}</span></p>
                        <hr style="border:1px dashed #000; margin:10px 0;">
                        <p><span>Paket</span> <span>${namaPaket}</span></p>
                        <p><span>Mulai Berlaku</span> <span>${tglMulai}</span></p>
                        <p><span>Berakhir Pada</span> <span>${tglBerakhir}</span></p>
                        <hr style="border:1px dashed #000; margin:10px 0;">
                        <p style="font-weight:bold; font-size:1rem;"><span>TOTAL</span> <span>${harga}</span></p>
                    `;
                    resStatus.innerHTML = '<span style="color:var(--success-green); font-weight:bold;">Aktif Terverifikasi</span>';
                    resPesan.innerHTML = `
                        <div class="detail-info">
                            <div class="detail-row"><span>Paket Latihan:</span><span>${namaPaket}</span></div>
                            <div class="detail-row"><span>Berlaku Mulai:</span><span>${tglMulai}</span></div>
                            <div class="detail-row"><span>Berakhir Pada:</span><span style="color: var(--primary-red);">${tglBerakhir}</span></div>
                        </div>
                        <button class="btn-small-gold" style="width:100%; font-size:0.85rem;" onclick="bukaBukti()">🧾 Download E-Receipt</button>
                    `;
                } else if (statusDb === 'ditolak') {
                    resStatus.innerHTML = '<span style="color:var(--primary-red); font-weight:bold;">Pendaftaran Ditolak</span>';
                    const alasanTolak = data.alasan_tolak ? data.alasan_tolak : 'Bukti transfer tidak valid.';
                    resPesan.innerHTML = `<strong style="color: var(--primary-red); display:block; margin-top:10px;">Verifikasi Gagal!</strong>Alasan Penolakan: <em>"${alasanTolak}"</em>`;
                } else {
                    resStatus.innerHTML = '<span style="color:var(--warning-yellow); font-weight:bold;">Menunggu Verifikasi</span>';
                    // --- TOMBOL BATAL DITAMBAHKAN DI SINI ---
                    resPesan.innerHTML = `
                        <strong style="display:block; margin-top:10px;">Tahap Verifikasi:</strong>Admin Vanda Gym sedang meninjau berkas pendaftaran Anda.
                        <button class="btn-search" style="background-color: var(--primary-red); margin-top: 15px; margin-bottom: 0;" onclick="batalkanPendaftaran('${emailDb}')">Batalkan Pendaftaran</button>
                    `;
                }
            })
            .catch(error => {
                btnTeks.innerText = "Cari Data";
                btnTeks.disabled = false;
                errorKoneksiBox.style.display = 'flex';
            });
        }
    </script>
</body>
</html>