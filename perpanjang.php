<?php
session_start();
require 'includes/koneksi.php';

// 1. PROTEKSI: Cek Login
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'member') {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// =========================================================
// BLOK PHP: HANDLING AJAX SUBMISSION (PERPANJANG & BATAL)
// =========================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // --- AKSI: BATALKAN TRANSAKSI PENDING ---
    if ($_POST['action'] == 'batalkan_pending') {
        $q_batal = mysqli_query($koneksi, "DELETE FROM membership WHERE id_user = $id_user AND status = 'pending'");
        if ($q_batal) {
            echo json_encode(['status' => 'success', 'message' => 'Transaksi pending berhasil dibatalkan.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal membatalkan transaksi.']);
        }
        exit;
    }

    // --- AKSI: PERPANJANG MEMBERSHIP ---
    if ($_POST['action'] == 'perpanjang') {
        $paket     = (int) $_POST['paketHarga']; 
        $tgl_mulai = $_POST['tglMulaiInput'];
        $metode    = $_POST['metodeBayar'];
        
        // Tentukan durasi bulan berdasarkan kelipatan harga base
        $q_web_harga = mysqli_query($koneksi, "SELECT harga_bulanan FROM pengaturan_web WHERE id=1");
        $web_harga = mysqli_fetch_assoc($q_web_harga);
        $harga_base = $web_harga['harga_bulanan'] ?? 175000;
        
        $durasi = round($paket / $harga_base);
        if($durasi < 1) $durasi = 1;

        $tgl_berakhir = date('Y-m-d', strtotime($tgl_mulai . " + $durasi months"));

        // Proses Upload Gambar (Hanya ke uploads/)
        $nama_file_bukti = NULL;
        if ($metode == 'qris' && isset($_FILES['buktiFile']['name']) && $_FILES['buktiFile']['name'] != '') {
            $ext = pathinfo($_FILES['buktiFile']['name'], PATHINFO_EXTENSION);
            $nama_bersih = str_replace(' ', '_', preg_replace('/[^A-Za-z0-9 ]/', '', $_SESSION['nama']));
            $nama_file_bukti = "Bukti_Perpanjang_" . $nama_bersih . "_" . date('dmy_His') . "." . $ext;
            
            if(!move_uploaded_file($_FILES['buktiFile']['tmp_name'], 'uploads/' . $nama_file_bukti)) {
                echo json_encode(['status' => 'error', 'message' => 'Gagal mengupload bukti transfer.']);
                exit;
            }
        }

        // Simpan ke tabel membership sebagai pengajuan baru (Status Pending)
        $query = "INSERT INTO membership (id_user, jenis_pengajuan, paket_bulan, total_harga, tgl_mulai, tgl_berakhir, metode_bayar, bukti_bayar, status) 
                  VALUES ($id_user, 'perpanjang', $durasi, $paket, '$tgl_mulai', '$tgl_berakhir', '$metode', '$nama_file_bukti', 'pending')";
        
        if (mysqli_query($koneksi, $query)) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data ke database.']);
        }
        exit;
    }
}

// =========================================================
// AMBIL DATA MEMBER, STATUS AKTIF, & INFO PAKET
// =========================================================
$q_user = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = $id_user");
$user = mysqli_fetch_assoc($q_user);

// Cek Pengajuan Pending
$q_pending = mysqli_query($koneksi, "SELECT * FROM membership WHERE id_user = $id_user AND status = 'pending' LIMIT 1");
$ada_pending = mysqli_num_rows($q_pending) > 0;

// Ambil Status Aktif Terakhir (Prioritaskan yang statusnya Aktif)
$q_member = mysqli_query($koneksi, "
    SELECT * FROM membership 
    WHERE id_user = $id_user AND status != 'pending' 
    ORDER BY 
        CASE WHEN status = 'aktif' THEN 1 ELSE 2 END,
        id_membership DESC 
    LIMIT 1
");
$m_data = mysqli_fetch_assoc($q_member);

$tgl_akhir_db = $m_data['tgl_berakhir'] ?? date('Y-m-d', strtotime('-1 day'));
$paket_terakhir = $m_data['total_harga'] ?? '';

// Ambil Harga dari Pengaturan Web
$q_web = mysqli_query($koneksi, "SELECT harga_bulanan, wa_cs FROM pengaturan_web WHERE id=1");
$web = mysqli_fetch_assoc($q_web);
$harga_base = $web['harga_bulanan'] ?? 175000;

$wa_db = $web['wa_cs'] ?? '082148556601';
$wa_link = "62" . substr(preg_replace('/[^0-9]/', '', $wa_db), 1);

// Cek Status Member Keseluruhan untuk Menu Bawah
$status_member = $m_data['status'] ?? 'belum_daftar';

// Fungsi Format Tanggal Indonesia
function formatTglIndo($tanggal) {
    if (!$tanggal) return '-';
    $bulanIndo = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
    $waktu = strtotime($tanggal);
    return date('d', $waktu) . ' ' . $bulanIndo[date('m', $waktu)] . ' ' . date('Y', $waktu);
}


// PERBAIKAN SISA HARI: SINKRONISASI DENGAN DASBOR
// ---------------------------------------------------------
// 1. Set zona waktu ke WIB agar hari sinkron dengan waktu lokal
date_default_timezone_set('Asia/Jakarta');

$hari_ini = date('Y-m-d');
$is_expired = true; // Set default kedaluwarsa
$sisa_hari = 0;

if ($tgl_akhir_db) {
    $tglAkhirObj = new DateTime($tgl_akhir_db);
    $tglAkhirObj->setTime(0, 0, 0); // Pastikan perbandingan tepat di jam 00:00:00
    $hariIniObj = new DateTime('today'); // Mengambil hari ini jam 00:00:00

    if ($tglAkhirObj < $hariIniObj) {
        $is_expired = true;
    } else {
        $is_expired = false;
        $selisih = $hariIniObj->diff($tglAkhirObj);
        $sisa_hari = $selisih->days;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perpanjang Member - Vanda Gym Classic</title>
    <style>
        :root { 
            --bg-dark: #000000; --primary-red: #dc3545; --accent-gold: #E8C999; 
            --text-light: #F8EEDF; --input-bg: #111111; --success-green: #28a745; 
            --warning-yellow: #ffc107;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--bg-dark); color: var(--text-light); display: flex; justify-content: center; align-items: flex-start; min-height: 100vh; padding: 40px 20px; }
        
        .pay-container { background-color: #0a0a0a; border: 1px solid #333; border-top: 4px solid var(--accent-gold); border-radius: 8px; padding: 30px; width: 100%; max-width: 650px; box-shadow: 0 10px 30px rgba(0,0,0,0.8); position: relative; margin-bottom: 80px; }
        
        .nav-top { margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; }
        .btn-back-square { width: 40px; height: 40px; background-color: #1a1a1a; border: 1px solid #333; color: var(--accent-gold); border-radius: 4px; display: flex; align-items: center; justify-content: center; text-decoration: none; font-weight: bold; font-size: 1.2rem; transition: 0.3s; }
        .btn-back-square:hover { background-color: var(--primary-red); color: white; border-color: var(--primary-red); }
        
        .form-header { text-align: center; margin-bottom: 25px; }
        .form-header h2 { color: var(--text-light); text-transform: uppercase; font-size: 1.4rem; letter-spacing: 1px; margin-bottom: 5px;}
        .form-header h2 span { color: var(--accent-gold); }
        .form-header p { color: #888; font-size: 0.85rem; }
        
        .section-divider { border-bottom: 1px solid #222; margin: 25px 0 15px; padding-bottom: 8px; color: var(--accent-gold); font-weight: bold; text-transform: uppercase; font-size: 0.9rem; display: flex; justify-content: space-between; align-items: center;}
        
        /* ALERT BOXES */
        .alert-box { padding: 12px 15px; border-radius: 4px; margin-bottom: 20px; font-size: 0.85rem; line-height: 1.4; }
        .alert-error { background: #1a0f0f; border: 1px solid #331a1a; color: #ff6b6b; display: none; }
        .alert-warning { background: #1f1b10; border: 1px solid #332b1a; color: var(--warning-yellow); }
        .alert-info { background: #111; border: 1px solid #222; color: #ccc; text-align: center; }

        .status-badge { display: inline-block; padding: 6px 15px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; letter-spacing: 0.5px; }
        .badge-expired { background: var(--primary-red); color: white; }
        .badge-active { background: var(--success-green); color: white; }
        .badge-pending { background: var(--warning-yellow); color: black; }

        .form-group { margin-bottom: 15px; text-align: left; }
        .form-group label { display: block; margin-bottom: 6px; color: #ccc; font-weight: 600; font-size: 0.8rem; }
        .form-control { width: 100%; padding: 10px 12px; background-color: var(--input-bg); border: 1px solid #333; border-radius: 4px; color: white; font-size: 0.9rem; transition: 0.3s; }
        .form-control:focus { outline: none; border-color: var(--accent-gold); }
        .form-control.invalid-field { border-color: var(--primary-red) !important; background-color: #221111 !important; }
        .form-control[readonly] { color: #888; cursor: not-allowed; background-color: #050505; border-color: #222;}
        input[type="date"] { color-scheme: dark; cursor: pointer; }
        
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        
        .payment-methods { display: flex; gap: 10px; margin-bottom: 15px; }
        .pay-method { flex: 1; border: 1px solid #333; border-radius: 4px; padding: 12px 10px; text-align: center; cursor: pointer; transition: 0.3s; background: #151515; position: relative; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .pay-method input { position: absolute; opacity: 0; cursor: pointer; }
        .pay-method span { font-weight: bold; color: #888; font-size: 0.85rem;}
        .pay-method.active { border-color: var(--accent-gold); background: rgba(232, 201, 153, 0.1); }
        .pay-method.active span { color: var(--accent-gold); }
        
        .pay-details { background: #111; border: 1px solid #222; padding: 20px; border-radius: 4px; margin-bottom: 20px; display: none; text-align: center; }
        .qris-box img { max-width: 150px; border-radius: 8px; margin: 10px 0; border: 2px solid white; background: #fff; padding: 5px; }
        .file-upload-wrapper { position: relative; margin-top: 15px; text-align: left; }
        .file-upload-wrapper input[type="file"] { position: absolute; left: 0; top: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
        .btn-upload { display: flex; align-items: center; justify-content: center; gap: 10px; background: #1a1a1a; border: 1px dashed var(--accent-gold); color: var(--accent-gold); padding: 10px; border-radius: 4px; width: 100%; font-size: 0.85rem; transition: 0.3s; }
        
        /* TOMBOL AKSI */
        .btn-action { width: 100%; border: none; min-height: 44px; font-size: 0.9rem; font-weight: bold; border-radius: 4px; cursor: pointer; text-transform: uppercase; transition: 0.3s; display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 15px; }
        .btn-success { background-color: var(--success-green); color: white; }
        .btn-success:hover { background-color: #218838; }
        .btn-success:disabled { background-color: #1e5c2b; color: #888; cursor: not-allowed; }
        .btn-danger { background-color: var(--primary-red); color: white; }
        .btn-danger:hover { background-color: #b01c1c; }
        .btn-outline { background-color: transparent; border: 1px solid #444; color: #aaa; margin-top: 10px; text-decoration: none;}
        .btn-outline:hover { border-color: var(--primary-red); color: var(--primary-red); }
        .btn-small-gold { background: transparent; border: 1px solid var(--accent-gold); color: var(--accent-gold); font-size: 0.7rem; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-small-gold:hover { background: var(--accent-gold); color: #000; }

        /* CHECKBOX KONFIRMASI (Di Modal) */
        .checkbox-container { display: flex; align-items: flex-start; gap: 10px; margin: 20px 0; background: #151515; padding: 12px; border-radius: 4px; border: 1px solid #333; text-align: left; }
        .checkbox-container input { margin-top: 3px; cursor: pointer; width: 16px; height: 16px; accent-color: var(--success-green); }
        .checkbox-container label { font-size: 0.8rem; color: #ccc; cursor: pointer; line-height: 1.4; }

        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); display: none; justify-content: center; align-items: center; z-index: 1000; padding: 20px; }
        .modal-box { background: #111; border: 1px solid var(--accent-gold); padding: 25px; border-radius: 8px; width: 100%; max-width: 400px; }
        .draf-item { display: flex; justify-content: space-between; margin-bottom: 8px; }
        
        .wa-btn { position: fixed; bottom: 30px; left: 30px; width: 55px; height: 55px; background-color: #25D366; color: white; border-radius: 50%; display: flex; justify-content: center; align-items: center; box-shadow: 0 4px 10px rgba(0,0,0,0.5); z-index: 1000; text-decoration: none; transition: 0.3s; }
        .wa-btn:hover { transform: scale(1.1); background-color: #1ebe57; }
        .wa-btn svg { width: 30px; height: 30px; }

        .bottom-nav-mobile { display: none !important; }

        /* ====================================================
           OPTIMASI TAMPILAN MOBILE (LAYAR KECIL)
           ==================================================== */
        @media (max-width: 768px) {
            body { 
                padding: 15px 25px; 
                align-items: flex-start;
            }
            .pay-container { 
                padding: 20px 15px; 
                margin-bottom: 80px; /* Tambah margin bawah agar tidak tertutup nav bottom */
                max-width: 400px; 
            }
            
            /* Navigasi & Header */
            .nav-top { margin-bottom: 15px; }
            .btn-back-square { width: 35px; height: 35px; font-size: 1rem; }
            .form-header { margin-bottom: 20px; }
            .form-header h2 { font-size: 1.0rem; margin-bottom: 3px; }
            .form-header p { font-size: 0.65rem; }
            
            /* Section Divider */
            .section-divider { margin: 20px 0 10px; padding-bottom: 5px; font-size: 0.7rem; }
            
            /* Box Info & Alert */
            .alert-box { padding: 12px; font-size: 0.7rem; margin-bottom: 15px; }
            .status-badge { font-size: 0.6rem; padding: 4px 12px; }
            
            /* Form Input */
            .grid-2 { grid-template-columns: 1fr; gap: 10px; }
            .form-group { margin-bottom: 12px; }
            .form-group label { font-size: 0.65rem; margin-bottom: 4px; }
            .form-control { padding: 8px 10px; font-size: 0.75rem; min-height: 38px; }
            
            /* Box Nominal & Pembayaran */
            #boxNominal { padding: 10px; margin-bottom: 12px; }
            #boxNominal span { font-size: 0.7rem; }
            #textNominal { font-size: 0.9rem !important; }
            
            .payment-methods { gap: 8px; margin-bottom: 12px; }
            .pay-method { padding: 10px 8px; }
            .pay-method span { font-size: 0.65rem; }
            
            .pay-details { padding: 15px; margin-bottom: 15px; }
            .btn-upload { padding: 8px; font-size: 0.7rem; }
            
            /* Tombol Aksi */
            .btn-action { min-height: 40px; font-size: 0.75rem; margin-top: 10px; }
            
            /* Modal / Draf */
            .modal-box { padding: 20px 15px; }
            .draf-item { font-size: 0.7rem; }
            .checkbox-container { padding: 10px; margin: 15px 0; }
            .checkbox-container label { font-size: 0.65rem; }

            /* Tombol WA & Navigasi Bawah */
            .wa-btn { bottom: 85px !important; left: 15px !important; width: 45px; height: 45px; }
            .wa-btn svg { width: 24px; height: 24px; }

            .bottom-nav-mobile {
                display: flex !important;
                position: fixed !important;
                bottom: 0 !important;
                left: 0 !important;
                width: 100vw !important;
                height: 70px !important;
                background-color: #0a0a0a !important;
                border-top: 1px solid #333 !important;
                justify-content: space-around !important;
                align-items: center !important;
                z-index: 2147483647 !important;
                box-shadow: 0 -5px 15px rgba(0,0,0,0.9) !important;
            }

            .bottom-nav-mobile .nav-item {
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center !important;
                color: #ccc !important;
                text-decoration: none !important;
                font-size: 10px !important;
                background: transparent !important;
                border: none !important;
                flex: 1 !important;
                gap: 4px !important;
                cursor: pointer !important;
                padding: 5px 0 !important;
                transition: 0.3s;
            }

            .bottom-nav-mobile .nav-item:hover, 
            .bottom-nav-mobile .nav-item:active {
                color: var(--accent-gold, #E8C999) !important;
            }

            .bottom-nav-mobile .nav-item svg {
                width: 22px !important;
                height: 22px !important;
                stroke: currentColor !important;
                fill: none !important;
                stroke-width: 2 !important;
                stroke-linecap: round !important;
                stroke-linejoin: round !important;
            }
        }
    </style>
</head>
<body>

    <a href="https://instagram.com/vandagympky_classic" target="_blank" class="wa-btn" title="Hubungi CS via Instagram" style="position: fixed; bottom: 20px; left: 20px; z-index: 9999; color: #ffffff; background: var(--primary-red, #ff4d4d); border-radius: 50%; padding: 12px; box-shadow: 0 4px 15px rgba(255, 77, 77, 0.4); border: 2px solid #E8C999; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
        <circle cx="12" cy="7" r="4"></circle>
    </svg>
</a>

    <div class="pay-container">
        <div class="nav-top">
            <a href="member_dasbor.php" class="btn-back-square" title="Kembali ke Dasbor">←</a>
        </div>

        <div class="form-header">
            <h2>Perpanjang <span>Membership</span></h2>
            <p>Aktifkan kembali masa berlaku gym Anda</p>
        </div>

        <div id="errorBox" class="alert-box alert-error"></div>

        <?php if ($ada_pending): ?>
    <div class="alert-box alert-info" style="border-color: var(--warning-yellow);">
        <div class="status-badge badge-pending" style="margin-bottom: 10px;">MENUNGGU VERIFIKASI</div>
        <h3 style="color: white; margin-bottom: 5px;">Transaksi Sedang Diproses</h3>
        <p>Anda memiliki pengajuan perpanjangan yang belum diverifikasi oleh Admin. Sistem mencegah pembayaran ganda.</p>
        
        <div style="background: #0a0a0a; padding: 15px; border-radius: 4px; margin-top: 15px; border: 1px solid #222;">
            <span style="color: #ccc; font-size: 0.75rem; display: block; margin-bottom: 15px; line-height: 1.4;">
                <strong>Agar tagihan lebih cepat diproses</strong>, silakan konfirmasi pembayaran Anda ke Admin melalui DM Instagram dengan menekan tombol di bawah ini:
            </span>
            
            <?php 
            $pesanIgPending = "Halo Admin Vanda Gym, saya memiliki tagihan perpanjangan member yang masih pending. Tolong dibantu cek ya. Terima kasih.";
            if (isset($email)) {
                $pesanIgPending = "Halo Admin Vanda Gym, saya memiliki tagihan perpanjangan member yang masih pending dengan email " . $email . ". Tolong dibantu cek ya. Terima kasih.";
            }
            ?>
            
            <div style="text-align: center;">
                <button onclick="salinDanBukaIG('<?= htmlspecialchars($pesanIgPending, ENT_QUOTES) ?>')" style="background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); color: white; font-size: 0.85rem; padding: 10px 20px; border-radius: 4px; border: none; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; margin: 0 auto; transition: 0.2s;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;">
                        <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                        <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                        <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                    </svg>
                    Konfirmasi via DM IG
                </button>
                <p style="font-size:0.65rem; color:#777; text-align:center; margin-top:6px; margin-bottom:0;">(Teks disalin otomatis, cukup tekan 'Paste/Tempel' di IG)</p>
            </div>
        </div>
        <p style="margin-top: 15px; font-size: 0.8rem; color: #888;">Ingin mengganti paket atau metode bayar?</p>
    </div>
    
    <button class="btn-action btn-danger" onclick="batalkanPending()">Batalkan Transaksi Ini</button>
    <a href="member_dasbor.php" class="btn-action btn-outline">Kembali ke Dasbor</a>
            
            
        <?php else: ?>
            <div class="alert-box alert-info">
                <h4 style="color: #ccc; margin-bottom: 8px;">Status Membership Terakhir</h4>
                <div class="status-badge <?= $is_expired ? 'badge-expired' : 'badge-active' ?>">
                    <?= $is_expired ? 'KADALUWARSA' : 'AKTIF' ?>
                </div>
                <div style="color: #ccc; font-size: 0.85rem; margin-top: 8px;">
                    Berlaku hingga: <strong style="color: white;"><?= formatTglIndo($tgl_akhir_db) ?></strong>
                </div>
            </div>

            <?php if (!$is_expired && $sisa_hari > 14): ?>
                <div class="alert-box alert-warning">
                    <strong>Pemberitahuan:</strong> Masa aktif Anda masih tersisa <?= $sisa_hari ?> hari. Mengajukan perpanjangan sekarang akan otomatis menambahkan durasi dari tanggal berakhir saat ini.
                </div>
            <?php endif; ?>

            <form id="formPerpanjang" onsubmit="validasiDanBukaDraf(event)">
                <div class="section-divider">
                    <span>Data Profil (Otomatis)</span>
                </div>
                
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" id="regNama" class="form-control" value="<?= htmlspecialchars($user['nama_lengkap']) ?>" readonly>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Email Pendaftaran</label>
                        <input type="email" id="regEmail" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Nomor Telepon / WA</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['no_wa']) ?>" readonly>
                    </div>
                </div>

                <div class="section-divider">
                    <span>Pilihan Paket Baru</span>
                    <?php if ($paket_terakhir != ''): ?>
                        <button type="button" class="btn-small-gold" onclick="ulangiPaket(<?= $paket_terakhir ?>)">Perpanjang Lagi</button>
                    <?php endif; ?>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Pilih Paket Durasi *</label>
                        <select id="paketPilih" name="paketPilih" class="form-control" required onchange="updateTotalHarga()">
                            <option value="" disabled selected>-- Pilih Paket --</option>
                            <option value="<?= $harga_base ?>" data-nama="1 Bulan Gym">1 Bulan Gym (Rp <?= number_format($harga_base,0,',','.') ?>)</option>
                            <option value="<?= $harga_base * 2 ?>" data-nama="2 Bulan Gym">2 Bulan Gym (Rp <?= number_format($harga_base*2,0,',','.') ?>)</option>
                            <option value="<?= $harga_base * 3 ?>" data-nama="3 Bulan Gym">3 Bulan Gym (Rp <?= number_format($harga_base*3,0,',','.') ?>)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tanggal Berlaku Baru</label>
                        <input type="date" id="tglMulai" name="tglMulai" class="form-control" required 
                               value="<?= ($is_expired) ? $hari_ini : $tgl_akhir_db ?>" 
                               min="<?= ($is_expired) ? $hari_ini : $tgl_akhir_db ?>">
                    </div>
                </div>
                
                <div id="boxNominal" style="display: none; justify-content: space-between; align-items: center; background: rgba(232, 201, 153, 0.1); border: 1px dashed var(--accent-gold); padding: 12px; border-radius: 4px; margin-bottom: 15px;">
                    <span style="font-size: 0.85rem; color: var(--text-light);">Total Tagihan:</span>
                    <span id="textNominal" style="font-size: 1.1rem; font-weight: bold; color: var(--accent-gold);">Rp 0</span>
                </div>

                <div class="section-divider">Metode Pembayaran</div>

                <div class="form-group">
                    <div class="payment-methods">
                        <label class="pay-method active" id="labelQris">
                            <input type="radio" name="metodeBayar" value="qris" checked onchange="ubahMetode()">
                            <span>📱 QRIS / Transfer</span>
                        </label>
                        <label class="pay-method" id="labelTunai">
                            <input type="radio" name="metodeBayar" value="tunai" onchange="ubahMetode()">
                            <span>💵 Tunai (Kasir)</span>
                        </label>
                    </div>
                </div>

                <div id="detailQris" class="pay-details" style="display: block;">
                    <p style="font-size: 0.85rem; color: #ccc;">Transfer ke: <strong>BCA 123-456-789 (Vanda Gym)</strong></p>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=Pembayaran+Perpanjang+Vanda+Gym" alt="QRIS">
                    
                    <div class="file-upload-wrapper">
                        <div class="btn-upload">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/></svg>
                            <span id="namaFile">Upload Bukti Transfer *</span>
                        </div>
                        <input type="file" id="buktiFile" name="buktiFile" accept="image/*" onchange="tampilkanNamaFile(this)">
                    </div>
                </div>

                <div id="detailTunai" class="pay-details">
                    <p style="font-size: 0.8rem; color: #888;">
                        <strong>Kirim draf pengajuan ini,</strong> lalu bayar langsung secara tunai ke Resepsionis untuk verifikasi aktifasi.
                    </p>
                </div>

                <button type="submit" class="btn-action btn-success">Lanjut Konfirmasi</button>
                <a href="member_dasbor.php" class="btn-action btn-outline">Batal & Kembali</a>
            </form>
        <?php endif; ?>

        <div style="margin-top: 30px; border-top: 1px solid #222; padding-top: 20px;">
            <a href="cek_status_perpanjang.php" class="btn-action btn-outline" style="border-color: #444; color: var(--accent-gold); font-size: 0.85rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                Cek Status & Riwayat Transaksi
            </a>
        </div>
    </div>

    <div class="bottom-nav-mobile">
        <a href="member_dasbor.php" class="nav-item">
            <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span>Dasbor</span>
        </a>
        <a href="kalkulator.php?source=dasbor" class="nav-item">
            <svg viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="8" y1="6" x2="16" y2="6"></line><line x1="16" y1="14" x2="16.01" y2="14"></line><line x1="12" y1="14" x2="12.01" y2="14"></line><line x1="8" y1="14" x2="8.01" y2="14"></line></svg>
            <span>Gizi</span>
        </a>
        <a href="galeri_member.php" class="nav-item <?= ($status_member !== 'aktif') ? 'locked' : '' ?>" <?= ($status_member !== 'aktif') ? 'onclick="event.preventDefault(); alert(\'Terkunci!\')"' : '' ?>>
            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
            <span>Galeri</span>
        </a>
        <a href="chatbot_member.php" class="nav-item <?= ($status_member !== 'aktif') ? 'locked' : '' ?>" <?= ($status_member !== 'aktif') ? 'onclick="event.preventDefault(); alert(\'Terkunci!\')"' : '' ?>>
            <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="10" rx="2"></rect><circle cx="12" cy="5" r="2"></circle><path d="M12 7v4"></path></svg>
            <span>AI Bot</span>
        </a>
        <a href="profil_member.php" class="nav-item">
            <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            <span>Profil</span>
        </a>
    </div>

    <div class="modal-overlay" id="modalOverlay">
        <div class="modal-box" id="modalContent"></div>
    </div>

    <script>
        function updateTotalHarga() {
            const select = document.getElementById('paketPilih');
            const boxNominal = document.getElementById('boxNominal');
            const textNominal = document.getElementById('textNominal');
            
            if(select.value) {
                boxNominal.style.display = 'flex';
                textNominal.innerText = "Rp " + parseInt(select.value).toLocaleString('id-ID');
            } else {
                boxNominal.style.display = 'none';
            }
        }

        function ulangiPaket(hargaTerakhir) {
            const select = document.getElementById('paketPilih');
            for(let i=0; i<select.options.length; i++) {
                if(select.options[i].value == hargaTerakhir) {
                    select.selectedIndex = i;
                    updateTotalHarga();
                    break;
                }
            }
        }

        function ubahMetode() {
            const isQris = document.querySelector('input[name="metodeBayar"]:checked').value === 'qris';
            document.getElementById('labelQris').classList.toggle('active', isQris);
            document.getElementById('labelTunai').classList.toggle('active', !isQris);
            document.getElementById('detailQris').style.display = isQris ? 'block' : 'none';
            document.getElementById('detailTunai').style.display = isQris ? 'none' : 'block';
        }

        function tampilkanNamaFile(input) {
            const namaFileEl = document.getElementById('namaFile');
            if (input.files && input.files[0]) {
                namaFileEl.innerText = input.files[0].name;
                namaFileEl.style.color = "var(--text-light)";
                document.querySelector('.btn-upload').style.borderColor = "var(--accent-gold)";
            } else {
                namaFileEl.innerText = "Upload Bukti Transfer *";
                namaFileEl.style.color = "var(--accent-gold)";
            }
        }

        function tampilkanError(pesan) {
            const errBox = document.getElementById('errorBox');
            errBox.innerHTML = `<strong>⚠️ Peringatan:</strong> ${pesan}`;
            errBox.style.display = 'block';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function toggleTombolBayar(checkbox) {
            document.getElementById('btnFinalBayar').disabled = !checkbox.checked;
        }

        // FUNGSI BARU: VALIDASI DAN BUKA DRAF (MODAL)
        function validasiDanBukaDraf(e) {
            e.preventDefault();
            document.getElementById('errorBox').style.display = 'none';

            const paket = document.getElementById('paketPilih').value;
            if(!paket) {
                tampilkanError("Silakan pilih paket durasi gym terlebih dahulu.");
                return;
            }

            const tglMulai = document.getElementById('tglMulai').value;
            const metode = document.querySelector('input[name="metodeBayar"]:checked').value;
            const fileBukti = document.getElementById('buktiFile').files[0];
            
            if (metode === 'qris') {
                if (!fileBukti) {
                    tampilkanError("Anda memilih metode QRIS. Harap unggah foto bukti transfer.");
                    document.querySelector('.btn-upload').style.borderColor = "var(--primary-red)";
                    return;
                }
                if (fileBukti.size > 5 * 1024 * 1024) {
                    tampilkanError("Ukuran foto bukti transfer maksimal 5MB.");
                    return;
                }
            }

            // Ambil data untuk Draf
            const selectPaket = document.getElementById('paketPilih');
            const namaPaket = selectPaket.options[selectPaket.selectedIndex].getAttribute('data-nama');
            const hargaPaket = "Rp " + parseInt(selectPaket.value).toLocaleString('id-ID');
            const namaLengkap = document.getElementById('regNama').value;
            const emailUser = document.getElementById('regEmail').value;

            const modal = document.getElementById('modalOverlay');
            const content = document.getElementById('modalContent');
            modal.style.display = 'flex';
            
            content.innerHTML = `
                <h3 style="color:var(--text-light); text-transform:uppercase; text-align:center; font-size:1.0rem; letter-spacing:1px; margin-bottom:5px;">Konfirmasi Perpanjangan</h3>
                <div style="margin:20px 0; font-size: 0.75rem; color:#ccc;">
                    <div class="draf-item"><span style="color:#888;">Nama:</span> <span style="text-align:right; color:white;">${namaLengkap}</span></div>
                    <div class="draf-item"><span style="color:#888;">Paket Latihan:</span> <span style="text-align:right; color:white;">${namaPaket} <br> Berlaku: ${tglMulai}</span></div>
                    <div class="draf-item"><span style="color:#888;">Metode:</span> <span style="text-align:right; color:white; text-transform: uppercase;">${metode}</span></div>
                    <div class="draf-item" style="border-top:1px dashed #333; margin-top:10px; padding-top:15px;">
                        <span style="color:var(--text-light); font-weight:bold;">Total Tagihan:</span> 
                        <span style="color:var(--accent-gold); font-weight:bold; font-size:1.0rem;">${hargaPaket}</span>
                    </div>
                </div>
                
                <div class="checkbox-container">
                    <input type="checkbox" id="chkYakin" onchange="toggleTombolBayar(this)">
                    <label for="chkYakin">Saya yakin data paket dan bukti pembayaran yang saya masukkan sudah benar dan sesuai.</label>
                </div>
                
                <button id="btnFinalBayar" class="btn-action btn-success" style="margin-top:0;" onclick="kirimFinal('${metode}', '${emailUser}')" disabled>Kirim Perpanjangan</button>
                <button type="button" class="btn-action btn-outline" onclick="document.getElementById('modalOverlay').style.display='none'">Batal & Edit</button>
            `;
        }

        // FUNGSI SUBMIT FINAL KE BACKEND
        // FUNGSI SUBMIT FINAL KE BACKEND (VERSI PERPANJANG + IG)
// FUNGSI SUBMIT FINAL KE BACKEND (VERSI PERPANJANG + IG)
function kirimFinal(metode, email) {
    const content = document.getElementById('modalContent');
    
    content.innerHTML = `<div style="text-align:center;"><p style="font-weight:bold; font-size:0.8rem; color:var(--accent-gold);">Menyimpan data...</p><p style="color:#888; font-size:0.8rem; margin-top:10px;">Mohon tunggu sebentar.</p></div>`;

    const formData = new FormData();
    formData.append('action', 'perpanjang');
    formData.append('paketHarga', document.getElementById('paketPilih').value);
    formData.append('tglMulaiInput', document.getElementById('tglMulai').value);
    formData.append('metodeBayar', document.querySelector('input[name="metodeBayar"]:checked').value);
    
    if(document.getElementById('buktiFile') && document.getElementById('buktiFile').files[0]) {
        formData.append('buktiFile', document.getElementById('buktiFile').files[0]);
    }

    fetch('perpanjang.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            let pesanStatus = (metode === 'tunai') ? `<strong style="color: var(--warning-yellow);">Menunggu Pembayaran</strong>` : `<strong style="color: var(--warning-yellow);">Sedang Diproses</strong>`;
            
            // --- BAGIAN INSTRUKSI YANG DIUBAH ---
            let instruksi = (metode === 'tunai') 
                ? `Silakan datang ke resepsionis Vanda Gym untuk melakukan pembayaran tunai.` 
                : `Bukti pembayaran Anda telah tersimpan. <br><br><span style="color: var(--accent-gold);">Agar tagihan lebih cepat diproses, silakan konfirmasi pembayaran Anda ke Admin melalui DM Instagram dengan menekan tombol di bawah ini.</span>`;
            
            let tombolIg = "";
            if (metode !== 'tunai') {
                const pesanIg = `Halo Admin Vanda Gym, saya baru saja mengajukan perpanjangan member dengan email ${email}. Tolong dicek ya. Terima kasih.`;
                
                tombolIg = `
                <button onclick="salinDanBukaIG('${pesanIg}')" class="btn-action" style="background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); color: white; text-decoration: none; font-size: 0.8rem; margin-top: 15px; border: none; width: 100%; cursor: pointer;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 5px;">
                        <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                        <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                        <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                    </svg>
                    Konfirmasi via DM IG
                </button>
                <p style="font-size:0.55rem; color:#888; text-align:center; margin-top:5px;">(Teks akan disalin otomatis, cukup tekan 'Paste/Tempel' di IG)</p>`;
            }

            content.innerHTML = `
                <h3 style="color:var(--success-green); text-align:center; font-size:1.1rem; text-transform:uppercase;">Berhasil!</h3>
                <p style="margin:5px 0 15px 0; text-align:center; font-size:0.75rem; color:#ccc;">Status: ${pesanStatus}</p>
                <div style="background:#151515; padding:15px; border:1px solid #333; border-radius:4px; font-size:0.8rem; line-height:1.5;">
                    <strong style="color:white; display:block; margin-bottom:5px;">Langkah Selanjutnya:</strong>
                    <span style="color:#aaa;">${instruksi}</span>
                    ${tombolIg}
                </div>
                <button class="btn-action btn-success" onclick="window.location.href='cek_status_perpanjang.php'">Cek Status Transaksi</button>
            `;
        } else { 
            document.getElementById('modalOverlay').style.display = 'none';
            tampilkanError(data.message); 
        }
    })
    .catch(err => {
        content.innerHTML = `
            <div style="text-align:center; padding: 5px;">
                <h3 style="color:var(--primary-red); font-weight:bold; margin-bottom:10px; font-size:1.0rem; text-transform:uppercase;">Koneksi Gagal!</h3>
                <p style="font-size:0.7rem; color:#ccc; margin-bottom:20px; line-height:1.5;">Sistem gagal terhubung ke server. Periksa koneksi internet Anda.</p>
                <button class="btn-action btn-success" onclick="kirimFinal('${metode}', '${email}')">🔄 Coba Lagi</button>
                <button class="btn-action btn-outline" onclick="document.getElementById('modalOverlay').style.display='none'">Batal</button>
            </div>`;
    });
}

// Fungsi helper salin pesan otomatis & buka Instagram
function salinDanBukaIG(pesan) {
    navigator.clipboard.writeText(pesan).then(() => {
        alert("Pesan otomatis telah disalin (Copied)! ✅\n\nSilakan klik 'Paste' (Tempel) di kolom pesan Instagram Vanda Gym.");
        window.open("https://ig.me/m/vandagympky_classic", "_blank");
    }).catch(err => {
        window.open("https://ig.me/m/vandagympky_classic", "_blank");
    });
}

        function batalkanPending() {
            if(confirm("Apakah Anda yakin ingin membatalkan transaksi pengajuan ini?")) {
                document.getElementById('modalOverlay').style.display = 'flex';
                document.getElementById('modalContent').innerHTML = `<h3 style="color:var(--accent-gold); text-align:center;">Membatalkan...</h3>`;
                
                const formData = new FormData();
                formData.append('action', 'batalkan_pending');
                
                fetch('perpanjang.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        window.location.reload();
                    } else {
                        document.getElementById('modalOverlay').style.display = 'none';
                        alert(data.message);
                    }
                });
            }
        }
    </script>
</body>
</html>