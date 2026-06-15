<?php
session_start();
require 'includes/koneksi.php';

// 1. KEAMANAN: Cek apakah user sudah login
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'member') {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// =========================================================
// BLOK PHP: HANDLING AJAX UPDATE NOTIFIKASI
// =========================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_notif') {
    header('Content-Type: application/json');
    $pref = mysqli_real_escape_string($koneksi, $_POST['prefNotif']);
    
    // Reset semua jadi 0 dulu, baru set yang dipilih jadi 1
    mysqli_query($koneksi, "UPDATE users SET notif_wa=0, notif_email=0, notif_dash=0 WHERE id_user=$id_user");
    
    $column = "notif_" . $pref; 
    $q = mysqli_query($koneksi, "UPDATE users SET $column=1 WHERE id_user=$id_user");
    
    echo json_encode(['status' => $q ? 'success' : 'error']);
    exit;
}

// =========================================================
// 2. AMBIL DATA MEMBER & STATUS
// =========================================================
$query = "
    SELECT u.*, m.paket_bulan, m.tgl_mulai, m.tgl_berakhir, m.status, m.metode_bayar 
    FROM users u 
    LEFT JOIN membership m ON u.id_user = m.id_user 
    WHERE u.id_user = $id_user 
    ORDER BY 
        CASE WHEN m.status = 'aktif' THEN 1 ELSE 2 END,
        m.id_membership DESC 
    LIMIT 1
";
$result = mysqli_query($koneksi, $query);
$data = mysqli_fetch_assoc($result);

// Cek status pending perpanjangan
$cek_pending = mysqli_query($koneksi, "SELECT status FROM membership WHERE id_user = $id_user AND status = 'pending' AND jenis_pengajuan = 'perpanjang' LIMIT 1");
$sedang_perpanjang = (mysqli_num_rows($cek_pending) > 0);

// =========================================================
// 3. AMBIL DATA PENGATURAN WEB (UNTUK JAM & FOOTER)
// =========================================================
$q_web = mysqli_query($koneksi, "SELECT * FROM pengaturan_web WHERE id=1");
$web_data = mysqli_fetch_assoc($q_web);

// Decode Jam Operasional (JSON ke Array)
$jam = isset($web_data['jam_operasional']) && !empty($web_data['jam_operasional']) ? json_decode($web_data['jam_operasional'], true) : [];
if (empty($jam)) {
    $jam = [
        'sjPagi'  => ['libur' => false, 'buka' => '06:00', 'tutup' => '10:30'],
        'sjSiang' => ['libur' => false, 'buka' => '14:15', 'tutup' => '19:45'],
        'sbPagi'  => ['libur' => false, 'buka' => '06:00', 'tutup' => '10:30'],
        'sbSiang' => ['libur' => false, 'buka' => '14:15', 'tutup' => '19:00'],
        'mgPagi'  => ['libur' => true,  'buka' => '',      'tutup' => ''],
        'mgSiang' => ['libur' => false, 'buka' => '14:15', 'tutup' => '19:00']
    ];
}

// Decode Jadwal Senam
$js_data = isset($web_data['jadwal_senam']) && !empty($web_data['jadwal_senam']) ? json_decode($web_data['jadwal_senam'], true) : [];
if (empty($js_data)) {
    $js_data = [
        'sr' => ['libur' => false, 'buka' => '16.15', 'tutup' => '17.15', 'ket' => 'BL+'],
        'sk' => ['libur' => false, 'buka' => '16.00', 'tutup' => '17.00', 'ket' => 'Zumba'],
        'sb' => ['libur' => false, 'buka' => '08.00', 'tutup' => '09.00', 'ket' => 'BL+'],
        'mg' => ['libur' => false, 'buka' => '15.30', 'tutup' => '16.30', 'ket' => 'Pilates']
    ];
}

function renderJam($jam_array, $sesi_key) {
    $libur = $jam_array[$sesi_key]['libur'] ?? false;
    $buka  = $jam_array[$sesi_key]['buka'] ?? '';
    $tutup = $jam_array[$sesi_key]['tutup'] ?? '';
    
    if ($libur === true || $libur === 'true') {
        return '<span class="schedule-time" style="display:block; margin-bottom:2px; color: var(--primary-red); font-weight:bold;">Libur / Tutup</span>';
    } else {
        return '<span class="schedule-time" style="display:block; margin-bottom:2px;">'.$buka.' - '.$tutup.' WIB</span>';
    }
}

// Format Nomor WA
$wa_raw = preg_replace('/[^0-9]/', '', $web_data['wa_cs'] ?? '082148556601');
$wa_link = (substr($wa_raw, 0, 1) == '0') ? '62' . substr($wa_raw, 1) : $wa_raw;

// 4. SIAPKAN VARIABEL MEMBER
$nama_lengkap  = $data['nama_lengkap'] ?? 'Member Vanda';
$email_user    = $data['email'] ?? '';
$status_member = $data['status'] ?? 'belum_daftar'; 
$paket         = $data['paket_bulan'] ? $data['paket_bulan'] . ' Bulan Gym' : 'Belum Ada Paket';
$tgl_mulai_raw = $data['tgl_mulai'];
$tgl_akhir_raw = $data['tgl_berakhir'];

$bulanIndo = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];

if ($tgl_mulai_raw) {
    $tm = strtotime($tgl_mulai_raw);
    $tgl_mulai = date('d', $tm) . ' ' . $bulanIndo[date('m', $tm)] . ' ' . date('Y', $tm);
} else { $tgl_mulai = '-'; }

if ($tgl_akhir_raw) {
    $ta = strtotime($tgl_akhir_raw);
    $tgl_berakhir = date('d', $ta) . ' ' . $bulanIndo[date('m', $ta)] . ' ' . date('Y', $ta);
} else { $tgl_berakhir = '-'; }

// 5. HITUNG SISA HARI & LOGIKA PERINGATAN
$sisa_hari = 0;
$peringatan_merah = false;

if ($status_member === 'aktif' && $tgl_akhir_raw) {
    $sekarang = time();
    $batas_waktu = strtotime($tgl_akhir_raw);
    $selisih = $batas_waktu - $sekarang;
    $sisa_hari = max(0, round($selisih / (60 * 60 * 24)));
    
    if ($sisa_hari <= 0 && $tgl_akhir_raw < date('Y-m-d')) {
        $status_member = 'kadaluarsa';
        mysqli_query($koneksi, "UPDATE membership SET status='kadaluarsa' WHERE id_user=$id_user AND status='aktif'");
    } elseif ($sisa_hari <= 7) {
        $peringatan_merah = true;
    }
}

// 6. LOGIKA SIMULASI MASA AKTIF SISA 7 HARI
if (isset($_GET['simulasi']) && $_GET['simulasi'] == '7') {
    $status_member = 'aktif';
    $sisa_hari = 7;
    $peringatan_merah = true;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dasbor Member - Vanda Gym Classic</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* =========================================
           KOTAK OFFLINE DETECTOR
           ========================================= */
        .connection-error-box { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0, 0, 0, 0.85); display: none; justify-content: center; align-items: center; z-index: 999999; padding: 20px; }
        .error-card-center { background-color: #0f0a0a; border: 1px solid #ff4d4d; border-top: 4px solid #ff4d4d; border-radius: 8px; padding: 30px 25px; max-width: 400px; width: 100%; text-align: center; box-shadow: 0 10px 30px rgba(255, 77, 77, 0.15); }
        .btn-retry { background-color: #25D366; color: white; border: none; padding: 10px 15px; border-radius: 4px; font-weight: bold; cursor: pointer; margin-top: 15px; width: 100%; transition: 0.3s; }
        .btn-retry:hover { background-color: #1ebe57; }

        /* =========================================
           HEADER, LONCENG & PROFIL DI KANAN
           ========================================= */
        .header-right { display: flex; align-items: center; gap: 15px; }
        .bell-icon { position: relative; color: #888; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 4px; transition: 0.3s; text-decoration: none; }
        .bell-icon:hover { color: var(--text-light); }
        .bell-icon.active { color: var(--text-light); animation: ring 2s infinite ease-in-out; }
        .bell-badge { position: absolute; top: 4px; right: 4px; background: var(--primary-red); color: white; font-size: 10px; font-weight: bold; width: 14px; height: 14px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        
        @keyframes ring { 0% {transform: rotate(0);} 10% {transform: rotate(15deg);} 20% {transform: rotate(-10deg);} 30% {transform: rotate(5deg);} 40% {transform: rotate(-5deg);} 50% {transform: rotate(0);} 100% {transform: rotate(0);} }

        .profile-icon-header { display: flex; align-items: center; justify-content: center; color: var(--accent-gold); width: 40px; height: 40px; transition: 0.3s; border: 1px solid transparent; }
        .profile-icon-header:hover { transform: scale(1.1); color: var(--text-light); }
        nav .profile-icon { display: none; }

        @media (min-width: 769px) {
            header { display: flex; justify-content: space-between; align-items: center; }
            nav#nav-menu { flex: 1; display: flex; justify-content: flex-end; padding-right: 20px; }
            .header-right { margin-left: 0; border-left: 1px solid #333; padding-left: 15px; }
            .bottom-nav-mobile { display: none !important; }
        }

        /* =========================================
           DASBOR KONTEN (DATA MEMBER)
           ========================================= */
        .dashboard-container { max-width: 900px; margin: 40px auto; padding: 0 20px; width: 100%; min-height: 60vh; }
        
        .dash-card { background: #111; border: 1px solid #222; border-top: 4px solid var(--accent-gold); border-radius: 8px; padding: 30px; margin-bottom: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .dash-card.card-danger { border-top-color: var(--primary-red); }
        
        .profile-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 1px dashed #333; padding-bottom: 20px; }
        .user-info h2 { color: var(--text-light); font-size: 1.6rem; margin-bottom: 5px; }
        .user-info p { color: #888; font-size: 0.95rem; }
        .status-badge { background: rgba(37, 211, 102, 0.1); color: var(--success-green); border: 1px solid var(--success-green); padding: 6px 20px; border-radius: 30px; font-weight: bold; font-size: 0.9rem; letter-spacing: 1px; }
        .status-badge.danger { background: rgba(255, 77, 77, 0.1); color: var(--primary-red); border-color: var(--primary-red); }

        .membership-details { display: flex; flex-direction: column; gap: 15px; }
        .detail-item { display: flex; justify-content: space-between; align-items: center; padding-bottom: 12px; border-bottom: 1px dashed #222; }
        .detail-item:last-child { border-bottom: none; padding-bottom: 0; }
        .detail-item span { color: #888; font-size: 0.95rem; }
        .detail-item strong { color: var(--text-light); font-size: 1.05rem; text-align: right; }
        .danger-text { color: var(--primary-red) !important; }

        /* =========================================
           AKSI CEPAT
           ========================================= */
        .action-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px; margin-bottom: 40px; }
        .action-btn { background: #111; border: 1px solid #222; border-radius: 8px; padding: 20px; display: flex; align-items: center; gap: 15px; text-decoration: none; transition: 0.3s; cursor: pointer; }
        .action-btn:hover { border-color: var(--accent-gold); transform: translateY(-3px); box-shadow: 0 5px 15px rgba(232, 201, 153, 0.1); }
        .action-btn.danger-border { border-color: var(--primary-red); }
        .action-btn.locked { opacity: 0.5; filter: grayscale(100%); cursor: not-allowed; }
        .action-icon { color: var(--accent-gold); display: flex; align-items: center; }
        .action-btn.danger-border .action-icon { color: var(--primary-red); }
        .action-btn h3 { color: var(--text-light); font-size: 1.1rem; margin-bottom: 3px; }
        .action-btn p { color: #888; font-size: 0.85rem; margin-bottom: 0; }

        /* =========================================
           MOBILE RESPONSIVE (UKURAN DIPERKECIL)
           ========================================= */
        .bottom-nav-mobile { display: none !important; }

        @media screen and (max-width: 768px) {
            body { padding-bottom: 85px !important; }
            header #nav-menu, header .menu-toggle { display: none !important; }
            
            .dashboard-container { padding: 10px; margin: 15px auto; }
            
            .alert-box { flex-direction: column; text-align: center; gap: 8px; padding: 10px 12px; font-size: 0.85rem; margin-bottom: 15px; }
            .alert-box a { width: 100%; text-align: center; padding: 8px 10px; font-size: 0.85rem; }
            
            .dash-card { padding: 15px 12px; margin-bottom: 15px; border-radius: 6px; }
            .profile-header { flex-direction: column; align-items: center; text-align: center; gap: 8px; margin-bottom: 12px; padding-bottom: 12px; }
            .user-info h2 { font-size: 1.25rem; margin-bottom: 2px;}
            .user-info p { font-size: 0.8rem; }
            .status-badge { font-size: 0.75rem; padding: 4px 12px; }

            .membership-details { gap: 10px; }
            .detail-item { flex-direction: row; padding-bottom: 8px; }
            .detail-item span { font-size: 0.8rem; }
            .detail-item strong { font-size: 0.9rem; } 

            /* Styling Khusus Tombol Simulasi HP */
            .dash-card > div:last-child { margin-top: 15px !important; padding-top: 10px !important; text-align: center !important; }
            .dash-card > div:last-child a { font-size: 0.75rem !important; padding: 5px 10px !important; }

            /* Jadwal HP */
            #jadwal { padding: 10px 0; margin: 15px 0; }
            .section-title { font-size: 1.2rem !important; margin-bottom: 15px !important; padding-bottom: 8px !important; }
            .schedule-container { grid-template-columns: 1fr; gap: 12px; }
            .schedule-box { padding: 12px !important; margin: 0 auto; box-sizing: border-box; border-radius: 6px; }
            .schedule-header { font-size: 0.85rem !important; padding: 6px 8px !important; margin-bottom: 10px !important; }
            .schedule-row { padding: 8px 0 !important; }
            .schedule-day { font-size: 0.8rem !important; }
            .schedule-time { font-size: 0.75rem !important; }

            /* Aksi Cepat HP */
            .action-grid { grid-template-columns: 1fr; gap: 10px; margin-bottom: 20px; }
            .action-btn { padding: 12px 15px; gap: 12px; border-radius: 6px; }
            .action-btn h3 { font-size: 0.95rem; margin-bottom: 2px; }
            .action-btn p { font-size: 0.75rem; }
            .action-icon svg { width: 32px; height: 32px; }
            
            /* =========================================
   NAVIGASI BAWAH MOBILE (STANDAR KEDUA HALAMAN)
   ========================================= */
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

/* Menu Aktif / Highlight */
.bottom-nav-mobile .nav-item.highlight {
    color: var(--accent-gold, #E8C999) !important;
    font-weight: bold !important;
}
.bottom-nav-mobile .nav-item.highlight svg {
    stroke: var(--accent-gold, #E8C999) !important;
    fill: none !important; 
}

            /* Tombol Melayang */
            .wa-btn, .chatbot-btn { bottom: 85px !important; width: 45px !important; height: 45px !important; }
            .wa-btn { left: 15px !important; } .chatbot-btn { right: 15px !important; }
            .wa-btn svg, .chatbot-btn svg { width: 22px !important; height: 22px !important; }
        }

        @media (max-width: 480px) {
            .wa-btn, .chatbot-btn { width: 40px !important; height: 40px !important; }
            .wa-btn svg, .chatbot-btn svg { width: 20px !important; height: 20px !important; }
        }
    </style>
</head>
<body>

    <header>
        <div class="logo">
            <img src="assets/logo.png" alt="Logo Vanda Gym">
        </div>

        <nav id="nav-menu">
            <a href="member_dasbor.php" class="nav-link active">Dasbor</a>
            <a href="chatbot_member.php" class="nav-link <?= ($status_member !== 'aktif') ? 'locked' : '' ?>">Chatbot AI</a>
            <a href="kalkulator.php?source=dasbor" class="nav-link">Kalkulator Gizi</a>
            <a href="galeri_member.php" class="nav-link <?= ($status_member !== 'aktif') ? 'locked' : '' ?>">Galeri Gym</a>
            <button class="btn-logout" onclick="window.location.href='index.php'" style="margin-left: 15px; padding: 8px 20px; background:var(--primary-red); color:white; border:none; border-radius:4px; cursor:pointer; font-weight:bold;">Keluar</button>
        </nav>

        <div class="header-right">
            <a href="perpanjang.php" class="bell-icon <?= ($peringatan_merah && !$sedang_perpanjang) ? 'active' : '' ?>" title="Tagihan Perpanjangan Membership">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                <?php if ($status_member === 'aktif' && $peringatan_merah && !$sedang_perpanjang): ?>
                    <span class="bell-badge">!</span>
                <?php endif; ?>
            </a>

            <a href="profil_member.php" class="profile-icon-header" title="Profil Saya">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            </a>

            <button class="menu-toggle" id="mobile-menu" aria-label="Toggle Menu">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>
        </div>
    </header>

   <?php if (($web_data['pengumuman_aktif'] ?? '') === 'aktif'): ?>
    <div class="announcement-banner">
        <span class="announcement-badge">Info Terkini</span>
        <span class="announcement-text"><?= htmlspecialchars($web_data['teks_pengumuman']) ?></span>
    </div>
    <?php endif; ?>

    <div class="dashboard-container">
        
        <?php if ($sedang_perpanjang && $status_member === 'aktif'): ?>
            <div class="alert-box info" style="margin-bottom: 20px;">
                <div style="margin-bottom: 12px;"><strong>Info:</strong> Permintaan <strong>Perpanjangan</strong> Anda sedang diverifikasi oleh Admin. Masa aktif Anda saat ini masih berjalan.</div>
                <a href="cek_status_perpanjang.php" style="background-color: var(--accent-gold); color: #000; font-weight: bold; padding: 6px 15px; font-size: 0.85rem; text-decoration: none; display: inline-block; border-radius: 4px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); transition: 0.3s;">Cek Status & Riwayat</a>
            </div>
        <?php elseif ($status_member === 'aktif'): ?>
            <div class="alert-box <?= $peringatan_merah ? 'danger' : '' ?>" style="margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                    <?php if($peringatan_merah): ?>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    <?php endif; ?>
                    <div><strong><?= $peringatan_merah ? 'Peringatan:' : 'Info:' ?></strong> Masa aktif membership Anda tersisa <strong style="font-size:1.2rem;"><?= $sisa_hari ?> Hari</strong> lagi.</div>
                </div>
                
                <?php if ($peringatan_merah): ?>
                    <a href="perpanjang.php" class="btn-primary" style="background-color: var(--primary-red); color: white; border: none; font-weight:bold; padding: 6px 12px; font-size: 0.85rem; display: inline-block;">Perpanjang Sekarang</a>
                <?php else: ?>
                    <a href="perpanjang.php" class="btn-primary" style="min-height: 35px; padding: 5px 15px; font-size: 0.9rem; display: inline-block;">Perpanjang</a>
                <?php endif; ?>
            </div>
        <?php elseif ($status_member === 'kadaluarsa'): ?>
            <div class="alert-box danger" style="margin-bottom: 20px;">
                <div style="margin-bottom: 10px;"><strong>Perhatian:</strong> Masa aktif membership Anda telah <strong>KEDALUWARSA</strong>.</div>
                <a href="perpanjang.php" class="btn-primary" style="background-color: var(--primary-red); color: white; border: none; font-weight:bold; padding: 6px 12px; font-size: 0.85rem; display: inline-block;">Perpanjang Sekarang</a>
            </div>
        <?php endif; ?>

        <div class="dash-card <?= ($status_member !== 'aktif') ? 'card-danger' : '' ?>">
            <div class="profile-header">
                <div class="user-info">
                    <h2><?= htmlspecialchars($nama_lengkap) ?></h2>
                    <p><?= htmlspecialchars($email_user) ?></p>
                </div>
                <div class="status-badge <?= ($status_member !== 'aktif') ? 'danger' : '' ?>">
                    <?= strtoupper($status_member) ?>
                </div>
            </div>
        
        

            <div class="membership-details">
                <div class="detail-item"><span>Paket Saat Ini</span><strong><?= $paket ?></strong></div>
                <div class="detail-item"><span>Status Pembayaran</span><strong style="color: <?= ($status_member === 'aktif') ? 'var(--success-green)' : 'var(--primary-red)' ?>;"><?= ($status_member === 'aktif') ? 'LUNAS' : 'MENUNGGU' ?></strong></div>
                <div class="detail-item"><span>Tanggal Mulai</span><strong><?= $tgl_mulai ?></strong></div>
                <div class="detail-item"><span>Tanggal Berakhir</span><strong class="<?= ($status_member !== 'aktif') ? 'danger-text' : '' ?>"><?= $tgl_berakhir ?></strong></div>
            </div>

            <div style="margin-top: 25px; border-top: 1px solid #222; padding-top: 15px; text-align: right;">
                <span style="font-size: 0.8rem; color: #666; margin-right: 10px;">Fitur Uji Coba:</span>
                <a href="?simulasi=7" style="background: transparent; border: 1px solid #444; color: #aaa; padding: 6px 12px; font-size: 0.8rem; text-decoration: none; border-radius: 4px; transition: 0.3s;">Tampilkan Sisa 7 Hari</a>
                <?php if (isset($_GET['simulasi'])): ?>
                    <a href="member_dasbor.php" style="background: var(--primary-red); border: none; color: white; padding: 6px 12px; font-size: 0.8rem; text-decoration: none; border-radius: 4px; margin-left: 5px;">Reset</a>
                <?php endif; ?>
            </div>
        </div>

        <section id="jadwal" style="background-color: transparent;">
            <h2 class="section-title" style="font-size: 1.4rem; text-align: left; color: var(--accent-gold); border-bottom: 1px solid #333; padding-bottom: 10px; margin-bottom: 20px;">Jadwal Operasional & Kelas</h2>
            <div class="schedule-container">
                <div class="schedule-box">
                    <div class="schedule-header">Jam Operasional Gym</div>
                    <div class="schedule-body">
                        <div class="schedule-row">
                            <span class="schedule-day">Senin - Jumat</span>
                            <div style="text-align: right;">
                                <?= renderJam($jam, 'sjPagi') ?>
                                <?= renderJam($jam, 'sjSiang') ?>
                            </div>
                        </div>
                        <div class="schedule-row">
                            <span class="schedule-day">Sabtu</span>
                            <div style="text-align: right;">
                                <?= renderJam($jam, 'sbPagi') ?>
                                <?= renderJam($jam, 'sbSiang') ?>
                            </div>
                        </div>
                        <div class="schedule-row">
                            <span class="schedule-day">Minggu</span>
                            <div style="text-align: right;">
                                <?= renderJam($jam, 'mgPagi') ?>
                                <?= renderJam($jam, 'mgSiang') ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="schedule-box">
                    <div class="schedule-header gold">Jadwal Kelas Senam</div>
                    <div class="schedule-body">
                        <?php
                        $labels = ['sr' => 'Senin & Rabu', 'sk' => 'Selasa & Kamis', 'sb' => 'Sabtu', 'mg' => 'Minggu'];
                        foreach($labels as $k => $v):
                            $l = $js_data[$k]['libur'] ?? false;
                            $b = $js_data[$k]['buka'] ?? '';
                            $t = $js_data[$k]['tutup'] ?? '';
                            $ket = $js_data[$k]['ket'] ?? '';
                        ?>
                        <div class="schedule-row">
                            <span class="schedule-day"><?= $v ?></span>
                            <span class="schedule-time" style="text-align: right;">
                                <?php if($l === true || $l === 'true'): ?>
                                    <span style="color:var(--primary-red); font-weight:bold;">Libur / Tutup</span>
                                <?php else: ?>
                                    <?= $b ?> - <?= $t ?> (<?= $ket ?>)
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <div style="margin-top: 20px; margin-bottom: 20px;">
            <h3 style="color: var(--accent-gold); border-bottom: 1px solid #333; padding-bottom: 10px; margin-bottom: 20px; font-size: 1.15rem; text-transform: uppercase; letter-spacing: 1px;">Aksi Cepat</h3>
            <div class="action-grid">
                <a href="kalkulator.php?source=dasbor" class="action-btn">
                    <div class="action-icon"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="8" y1="6" x2="16" y2="6"></line><line x1="8" y1="14" x2="8.01" y2="14"></line><line x1="12" y1="14" x2="12.01" y2="14"></line><line x1="16" y1="14" x2="16.01" y2="14"></line></svg></div>
                    <div>
                        <h3>Kalkulator Gizi</h3>
                        <p>Hitung kebutuhan kalori harian.</p>
                    </div>
                </a>
                
                <a href="perpanjang.php" class="action-btn <?= ($status_member !== 'aktif' || $sedang_perpanjang) ? 'danger-border' : '' ?>">
                    <div class="action-icon"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg></div>
                    <div>
                        <h3 style="<?= ($status_member !== 'aktif' || $sedang_perpanjang) ? 'color:var(--primary-red);' : '' ?>">Tagihan Member</h3>
                        <p><?= $sedang_perpanjang ? 'Pembayaran sedang diproses.' : 'Perpanjang masa aktif gym.' ?></p>
                    </div>
                </a>

                <a href="chatbot_member.php" class="action-btn <?= ($status_member !== 'aktif') ? 'locked' : '' ?>" <?= ($status_member !== 'aktif') ? 'onclick="event.preventDefault(); alert(\'Fitur AI terkunci. Silakan perpanjang membership Anda.\')"' : '' ?>>
                    <div class="action-icon"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"></rect><circle cx="12" cy="5" r="2"></circle><path d="M12 7v4"></path></svg></div>
                    <div>
                        <h3>Chatbot AI</h3>
                        <p>Tanya tips latihan dan nutrisi.</p>
                    </div>
                </a>

                <a href="galeri_member.php" class="action-btn <?= ($status_member !== 'aktif') ? 'locked' : '' ?>" <?= ($status_member !== 'aktif') ? 'onclick="event.preventDefault(); alert(\'Galeri terkunci. Silakan perpanjang membership Anda.\')"' : '' ?>>
                    <div class="action-icon"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg></div>
                    <div>
                        <h3>Galeri & Tutorial</h3>
                        <p>Video posisi latihan alat gym.</p>
                    </div>
                </a>
            </div>
        </div>

    </div> <footer>
        <div class="footer-container">
            <div class="footer-info">
                <h3>Vanda Gym Classic</h3>
                <p>Membentuk Karakter, Membangun Kekuatan.</p>
                <div style="margin-top: 20px;">
                    <p style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent-gold)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        <?= htmlspecialchars($web_data['alamat'] ?? 'Jl. Kapten Pierre Tendean No.17, Palangka Raya') ?>
                    </p>
                    <p style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent-gold)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                        <span>CS / Pendaftaran: <?= htmlspecialchars($web_data['wa_cs'] ?? '0821-4855-6601') ?></span>
                    </p>
                    <p style="display: flex; align-items: center; gap: 10px; margin-top: 15px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent-gold)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
                        <span style="color: #aaa;">Instagram: <a href="<?= htmlspecialchars($web_data['ig'] ?? 'https://instagram.com/vandagympky_classic') ?>" target="_blank" style="color: #aaa; font-weight: normal; text-decoration: none;">@vandagympky_classic</a></span>
                    </p>
                </div>
            </div>
            <div class="footer-map">
                <iframe src="https://maps.google.com/maps?q=Vanda%20Gym%20Palangkaraya&t=&z=15&ie=UTF8&iwloc=&output=embed" width="100%" height="220" style="border:0; border-radius: 8px;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
        <div class="footer-bottom">
            © 2026 Vanda Gym Classic Room.
        </div>
    </footer>

    <div class="bottom-nav-mobile">
    <a href="member_dasbor.php" class="nav-item highlight">
        <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
        <span>Dasbor</span>
    </a>
    <a href="kalkulator.php?source=dasbor" class="nav-item">
        <svg viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="8" y1="6" x2="16" y2="6"></line><line x1="16" y1="14" x2="16.01" y2="14"></line><line x1="12" y1="14" x2="12.01" y2="14"></line><line x1="8" y1="14" x2="8.01" y2="14"></line></svg>
        <span>Gizi</span>
    </a>
    <a href="galeri_member.php" class="nav-item <?= ($status_member !== 'aktif') ? 'locked' : '' ?>" <?= ($status_member !== 'aktif') ? 'onclick="event.preventDefault(); alert(\'Terkunci!\')"' : '' ?>>
        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
        <span>Tutorial</span>
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

    <a href="https://instagram.com/vandagympky_classic" target="_blank" class="wa-btn" title="Hubungi CS via Instagram" style="position: fixed; bottom: 20px; left: 20px; z-index: 9999; color: #ffffff; background: var(--primary-red, #ff4d4d); border-radius: 50%; padding: 12px; box-shadow: 0 4px 15px rgba(255, 77, 77, 0.4); border: 2px solid #E8C999; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
        <circle cx="12" cy="7" r="4"></circle>
    </svg>
</a>

    <a href="chatbot_member.php" class="chatbot-btn <?= ($status_member !== 'aktif') ? 'locked' : '' ?>" <?= ($status_member !== 'aktif') ? 'onclick="event.preventDefault(); alert(\'Fitur AI terkunci.\')"' : '' ?> title="Chatbot Vanda AI">
        <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="10" rx="2"></rect>
            <circle cx="12" cy="5" r="2"></circle>
            <path d="M12 7v4"></path>
            <line x1="8" y1="16" x2="8.01" y2="16"></line>
            <line x1="16" y1="16" x2="16.01" y2="16"></line>
        </svg>
    </a>

    <div id="boxErrorKoneksi" class="connection-error-box">
        <div class="error-card-center">
            <div style="width: 50px; height: 50px; background: #221111; border: 2px solid #ff4d4d; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto;">
                <span style="color: #ff4d4d; font-size: 1.6rem; font-weight: bold;">!</span>
            </div>
            <h3 style="color:#ff4d4d; font-size:1.2rem; font-weight:bold; margin-bottom: 8px;">Koneksi Terputus!</h3>
            <p style="color:#ccc; font-size:0.85rem; line-height:1.5;">Perangkat Anda kehilangan koneksi internet. Silakan periksa jaringan Anda.</p>
            <button class="btn-retry" onclick="cobaLagiKoneksi()">🔄 Coba Lagi</button>
            <button type="button" style="background: transparent; border: none; color: #555; margin-top: 12px; cursor: pointer; font-size: 0.8rem;" onclick="document.getElementById('boxErrorKoneksi').style.display='none'">Tutup</button>
        </div>
    </div>

    <script>
    const menuToggle = document.getElementById('mobile-menu');
    const navMenu = document.getElementById('nav-menu');

    if(menuToggle && navMenu) {
        menuToggle.addEventListener('click', () => {
            menuToggle.classList.toggle('active');
            navMenu.classList.toggle('active');
        });
    }

    // Menutup menu jika area luar diklik
    document.addEventListener('click', (e) => {
        if (!navMenu.contains(e.target) && !menuToggle.contains(e.target)) {
            menuToggle.classList.remove('active');
            navMenu.classList.remove('active');
        }
    });

    // Offline Detector
    window.addEventListener('offline', function() {
        document.getElementById('boxErrorKoneksi').style.display = 'flex';
    });
    window.addEventListener('online', function() {
        document.getElementById('boxErrorKoneksi').style.display = 'none';
    });
    function cobaLagiKoneksi() {
        if(navigator.onLine) {
            document.getElementById('boxErrorKoneksi').style.display = 'none';
            window.location.reload(); 
        } else {
            alert("Koneksi masih terputus! Silakan periksa jaringan Wi-Fi atau Data Seluler Anda.");
        }
    }
    </script>
</body>
</html>