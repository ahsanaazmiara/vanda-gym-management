<?php
// Atur masa aktif session menjadi 1 hari (86400 detik)
ini_set('session.gc_maxlifetime', 86400);
session_set_cookie_params(86400);

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

$cek_pending = mysqli_query($koneksi, "SELECT status FROM membership WHERE id_user = $id_user AND status = 'pending' AND jenis_pengajuan = 'perpanjang' LIMIT 1");
$sedang_perpanjang = (mysqli_num_rows($cek_pending) > 0);

// =========================================================
// 3. AMBIL DATA PENGATURAN WEB
// =========================================================
$q_web = mysqli_query($koneksi, "SELECT * FROM pengaturan_web WHERE id=1");
$web_data = mysqli_fetch_assoc($q_web);

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

// =========================================================
// 5. HITUNG SISA HARI & LOGIKA PERINGATAN (FIX DATETIME)
// =========================================================
$sisa_hari = 0;
$peringatan_merah = false;

// Kunci zona waktu agar sinkron
date_default_timezone_set('Asia/Jakarta');

if ($status_member === 'aktif' && $tgl_akhir_raw) {
    $tgl_sekarang = new DateTime('today'); 
    $tgl_akhir = new DateTime($tgl_akhir_raw);
    
    // Pastikan tanggal akhir dihitung pada jam 00:00:00
    $tgl_akhir->setTime(0, 0, 0); 
    
    if ($tgl_akhir < $tgl_sekarang) {
        $sisa_hari = 0;
        $status_member = 'kedaluwarsa';
        mysqli_query($koneksi, "UPDATE membership SET status='kedaluwarsa' WHERE id_user=$id_user AND status='aktif'");
    } else {
        $selisih = $tgl_sekarang->diff($tgl_akhir);
        $sisa_hari = $selisih->days;
        
        if ($sisa_hari <= 7) {
            $peringatan_merah = true;
        }
    }
}

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
        .connection-error-box { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.85); display: none; justify-content: center; align-items: center; z-index: 999999; padding: 20px; }
        .error-card-center { background-color: #0f0a0a; border: 1px solid #ff4d4d; border-top: 4px solid #ff4d4d; border-radius: 8px; padding: 30px 25px; max-width: 400px; width: 100%; text-align: center; box-shadow: 0 10px 30px rgba(255,77,77,0.15); }
        .btn-retry { background-color: #25D366; color: white; border: none; padding: 10px 15px; border-radius: 4px; font-weight: bold; cursor: pointer; margin-top: 15px; width: 100%; transition: 0.3s; }
        .btn-retry:hover { background-color: #1ebe57; }

        /* =========================================
           SEMBUNYIKAN BOTTOM NAV DI PC DEFAULT
           ========================================= */
        .bottom-nav-mobile { display: none !important; }

        /* =========================================
           HEADER & KANAN HEADER (LONCENG + HAMBURGER)
           ========================================= */
        .header-right {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-left: auto;
        }

        .bell-icon {
            position: relative;
            color: #888;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 4px;
            transition: 0.3s;
            text-decoration: none;
        }
        .bell-icon:hover { color: var(--text-light); }
        .bell-icon.active { color: var(--primary-red); animation: ring 2s infinite ease-in-out; }
        .bell-badge { position: absolute; top: 4px; right: 4px; background: var(--primary-red); color: white; font-size: 10px; font-weight: bold; width: 14px; height: 14px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }

        @keyframes ring {
            0%  { transform: rotate(0); }
            10% { transform: rotate(15deg); }
            20% { transform: rotate(-10deg); }
            30% { transform: rotate(5deg); }
            40% { transform: rotate(-5deg); }
            50% { transform: rotate(0); }
            100%{ transform: rotate(0); }
        }

        /* =========================================
           HAMBURGER TOGGLE
           ========================================= */
        header .menu-toggle {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            width: 42px;
            height: 42px;
            background-color: #1a1a1a;
            border: 1px solid #333;
            border-radius: 10px;
            cursor: pointer;
            z-index: 1001;
            padding: 0;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }
        header .menu-toggle:hover { border-color: var(--accent-gold); }
        header .menu-toggle .bar {
            display: block;
            width: 20px;
            height: 2px;
            background-color: #E8C999;
            border-radius: 2px;
            transition: all 0.3s ease-in-out;
            margin: 2px 0;
        }
        header .menu-toggle.active .bar:nth-child(1) { transform: translateY(6px) rotate(45deg); }
        header .menu-toggle.active .bar:nth-child(2) { opacity: 0; }
        header .menu-toggle.active .bar:nth-child(3) { transform: translateY(-6px) rotate(-45deg); }

        /* =========================================
           DROPDOWN NAV MENU
           ========================================= */
        #nav-menu {
            display: none;
            position: absolute;
            top: 70px;
            right: 20px;
            left: auto;
            width: 220px;
            box-sizing: border-box;
            background-color: #1a1a1a;
            padding: 12px;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.8);
            border: 1px solid #333;
            flex-direction: column;
            z-index: 1000;
        }

        #nav-menu.active {
            display: flex;
            animation: slideDownFade 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }

        @keyframes slideDownFade {
            from { opacity: 0; transform: translateY(-15px) scale(0.95); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        #nav-menu a.menu-link {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 10px;
            width: 100%;
            box-sizing: border-box;
            color: #ccc;
            text-decoration: none;
            font-weight: 600;
            padding: 8px 10px;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-size: 0.75rem;
            text-transform: none;
            letter-spacing: normal;
            margin-left: 0;
            min-height: auto;
        }
        #nav-menu a.menu-link:hover {
            background-color: rgba(232, 201, 153, 0.1);
            color: var(--accent-gold);
        }
        #nav-menu a.menu-link.active-link {
            color: var(--accent-gold);
            background-color: rgba(232, 201, 153, 0.07);
        }
        #nav-menu a.menu-link.locked-link {
            opacity: 0.45;
            cursor: not-allowed;
        }

        .menu-divider {
            height: 1px;
            background-color: #333;
            margin: 4px 0;
            width: 100%;
        }

        .nav-actions-dasbor {
            display: flex;
            flex-direction: column;
            width: 100%;
            gap: 8px;
            margin-top: 4px;
        }

        .nav-actions-dasbor .btn-keluar-menu {
            display: block;
            box-sizing: border-box;
            padding: 9px 15px;
            border: 1px solid var(--primary-red);
            background-color: transparent;
            color: var(--primary-red);
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s ease;
            font-size: 0.6rem;
            text-decoration: none;
        }
        .nav-actions-dasbor .btn-keluar-menu:hover {
            background-color: var(--primary-red);
            color: white;
        }

        /* =========================================
           DASBOR KONTEN
           ========================================= */
        .dashboard-container { max-width: 900px; margin: 20px auto; padding: 0 20px; width: 100%; min-height: 60vh; }

        .dash-card { background: #111; border: 1px solid #222; border-top: 4px solid var(--accent-gold); border-radius: 8px; padding: 30px; margin-bottom: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .dash-card.card-danger { border-top-color: var(--primary-red); }

        .profile-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0px; border-bottom: 1px dashed #333; padding-bottom: 10px; }
        .user-info h2 { color: var(--text-light); font-size: 1.5rem; margin-bottom: 5px; }
        .user-info p { color: #888; font-size: 0.75rem; }
        .status-badge { background: rgba(37,211,102,0.1); color: var(--success-green); border: 1px solid var(--success-green); padding: 6px 20px; border-radius: 30px; font-weight: bold; font-size: 0.8rem; letter-spacing: 1px; }
        .status-badge.danger { background: rgba(255,77,77,0.1); color: var(--primary-red); border-color: var(--primary-red); }

        .membership-details { display: flex; flex-direction: column; gap: 15px; }
        .detail-item { display: flex; justify-content: space-between; align-items: center; padding-bottom: 12px; border-bottom: 1px dashed #222; }
        .detail-item:last-child { border-bottom: none; padding-bottom: 0; }
        .detail-item span { color: #888; font-size: 0.75rem; }
        .detail-item strong { color: var(--text-light); font-size: 1.0rem; text-align: right; }
        .danger-text { color: var(--primary-red) !important; }

        /* =========================================
           AKSI CEPAT
           ========================================= */
        .action-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px;    margin: 40px 100px; }
        .action-btn { background: #111; border: 1px solid #222; border-radius: 8px; padding: 20px; display: flex; align-items: center; gap: 15px; text-decoration: none; transition: 0.3s; cursor: pointer; }
        .action-btn:hover { border-color: var(--accent-gold); transform: translateY(-3px); box-shadow: 0 5px 15px rgba(232,201,153,0.1); }
        .action-btn.danger-border { border-color: var(--primary-red); }
        .action-btn.locked { opacity: 0.5; filter: grayscale(100%); cursor: not-allowed; }
        .action-icon { color: var(--accent-gold); display: flex; align-items: center; }
        .action-btn.danger-border .action-icon { color: var(--primary-red); }
        .action-btn h3 { color: var(--text-light); font-size: 1.0rem; margin-bottom: 3px; }
        .action-btn p { color: #888; font-size: 0.75rem; margin-bottom: 0; }

        /* =========================================
           FLOATING BUTTONS
           ========================================= */
        @keyframes floatGemes {
            0%   { transform: translateY(0px); }
            50%  { transform: translateY(-8px); }
            100% { transform: translateY(0px); }
        }
        .chatbot-btn, .wa-btn {
            animation: floatGemes 3s ease-in-out infinite;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .chatbot-btn:hover, .wa-btn:hover {
            transform: scale(1.15) rotate(8deg) !important;
            animation-play-state: paused;
        }

        /* =========================================
           MOBILE RESPONSIVE
           ========================================= */
        @media screen and (max-width: 768px) {
            body { padding-bottom: 85px !important; }
            .wa-btn { display: none !important; }

            header .menu-toggle { width: 36px; height: 36px; }
            header .menu-toggle .bar { width: 16px; }

            #nav-menu { top: 55px; right: 10px; left: auto; width: 195px; box-sizing: border-box; padding: 10px; }
            #nav-menu a.menu-link { font-size: 0.75rem; padding: 5px 6px; white-space: nowrap; }
            .nav-actions-dasbor .btn-keluar-menu { font-size: 0.75rem; padding: 7px 8px; }

            .dashboard-container { padding: 0px 45px; margin: 15px auto; }
            .alert-box { flex-direction: column; text-align: center; gap: 8px; padding: 10px 12px; font-size: 0.75rem; margin-bottom: 15px; }
            .alert-box a { width: 165px; text-align: center; padding: 8px 10px; font-size: 0.85rem; }

            .dash-card { padding: 15px 12px; margin-bottom: 15px; border-radius: 6px; }
            .profile-header { flex-direction: column; align-items: center; text-align: center; gap: 8px; margin-bottom: 12px; padding-bottom: 12px; }
            .user-info h2 { font-size: 1.25rem; margin-bottom: 2px; }
            .user-info p { font-size: 0.8rem; }
            .status-badge { font-size: 0.75rem; padding: 4px 12px; }

            .membership-details { gap: 10px; }
            .detail-item { flex-direction: row; padding-bottom: 8px; }
            .detail-item span { font-size: 0.8rem; }
            .detail-item strong { font-size: 0.9rem; }

            .dash-card > div:last-child { margin-top: 15px !important; padding-top: 10px !important; text-align: center !important; }
            .dash-card > div:last-child a { font-size: 0.75rem !important; padding: 5px 10px !important; }

            #jadwal { padding: 10px 0; margin: 15px 0; }
            .section-title { font-size: 1.2rem !important; margin-bottom: 15px !important; padding-bottom: 8px !important; }
            .schedule-container { grid-template-columns: 1fr; gap: 12px; }
            .schedule-box { padding: 12px !important; margin: 0 auto; box-sizing: border-box; border-radius: 6px; }
            .schedule-header { font-size: 0.85rem !important; padding: 6px 8px !important; margin-bottom: 10px !important; }
            .schedule-row { padding: 8px 0 !important; }
            .schedule-day { font-size: 0.8rem !important; }
            .schedule-time { font-size: 0.75rem !important; }

            .action-grid { grid-template-columns: 1fr 1fr; gap: 10px; margin: 20px 5px }
            .action-btn { padding: 12px 15px; gap: 12px; border-radius: 6px; }
            .action-btn h3 { font-size: 0.95rem; margin-bottom: 2px; }
            .action-btn p { font-size: 0.75rem; }
            .action-icon svg { width: 32px; height: 32px; }

            .chatbot-btn { bottom: 85px !important; width: 45px !important; height: 45px !important; right: 15px !important; }
            .chatbot-btn svg { width: 22px !important; height: 22px !important; }

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
            .bottom-nav-mobile .nav-item:active { color: var(--accent-gold, #E8C999) !important; }

            .bottom-nav-mobile .nav-item svg {
                width: 22px !important;
                height: 22px !important;
                stroke: currentColor !important;
                fill: none !important;
                stroke-width: 2 !important;
                stroke-linecap: round !important;
                stroke-linejoin: round !important;
            }

            .bottom-nav-mobile .nav-item.highlight { color: var(--accent-gold, #E8C999) !important; font-weight: bold !important; }
            .bottom-nav-mobile .nav-item.highlight svg { stroke: var(--accent-gold, #E8C999) !important; }
            .bottom-nav-mobile .nav-item.locked-nav { opacity: 0.4; cursor: not-allowed; }

            @keyframes jelly {
                0%, 100% { transform: scale(1, 1); }
                25% { transform: scale(0.8, 1.2); }
                50% { transform: scale(1.2, 0.8); }
                75% { transform: scale(0.95, 1.05); }
            }
            .bottom-nav-mobile .nav-item:active svg { animation: jelly 0.5s ease; }

            .announcement-banner { flex-direction: row; gap: 8px; padding: 12px; font-size: 13px; }

            .profile-header { flex-direction: row; align-items: center; text-align: center; gap: 8px; margin-bottom: 0px; padding-bottom: 10px; }
        }

        @media (max-width: 480px) {
            .action-grid { grid-template-columns: 1fr; gap: 10px; margin: 20px 50px; }
            .announcement-banner { flex-direction: column; gap: 8px; padding: 12px; font-size: 13px; }
            .chatbot-btn { width: 40px !important; height: 40px !important; }
            .chatbot-btn svg { width: 20px !important; height: 20px !important; }
            .dashboard-container { padding: 0px 35px; margin: 15px auto; }
        }
    </style>
</head>
<body>

    <header>
        <div class="logo">
            <img src="assets/logo.png" alt="Logo Vanda Gym">
        </div>

        <div class="header-right">
            <?php 
                $notif_merah = (($peringatan_merah || $status_member !== 'aktif') && !$sedang_perpanjang); 
            ?>
            <a href="perpanjang.php" class="bell-icon <?= $notif_merah ? 'active' : '' ?>" title="Tagihan Perpanjangan Membership">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <?php if ($notif_merah): ?>
                    <span class="bell-badge">!</span>
                <?php endif; ?>
            </a>

            <button class="menu-toggle" id="mobile-menu" aria-label="Toggle Menu">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>
        </div>

        <nav id="nav-menu">
            <a href="member_dasbor.php" class="menu-link active-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                Dasbor
            </a>
            <a href="https://instagram.com/vandagympky_classic" target="_blank" class="menu-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
                Hubungi Kami
            </a>
            <a href="galeri_member.php" class="menu-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><path d="M21 15l-5-5L5 21"></path></svg>
                Galeri Gym
            </a>
            <a href="chatbot_member.php" class="menu-link <?= ($status_member !== 'aktif') ? 'locked-link' : '' ?>" <?= ($status_member !== 'aktif') ? 'onclick="event.preventDefault(); alert(\'Fitur AI terkunci. Silakan perpanjang membership Anda.\')"' : '' ?>>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"></rect><circle cx="12" cy="5" r="2"></circle><path d="M12 7v4"></path><line x1="8" y1="16" x2="8.01" y2="16"></line><line x1="16" y1="16" x2="16.01" y2="16"></line></svg>
                Chatbot AI
            </a>
            <a href="kalkulator.php?source=dasbor" class="menu-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="8" y1="6" x2="16" y2="6"></line><line x1="16" y1="14" x2="16.01" y2="14"></line><line x1="12" y1="14" x2="12.01" y2="14"></line><line x1="8" y1="14" x2="8.01" y2="14"></line></svg>
                Kalkulator Gizi
            </a>
            <a href="profil_member.php" class="menu-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                Profil Saya
            </a>

            <div class="menu-divider"></div>

            <div class="nav-actions-dasbor">
                <a href="index.php" class="btn-keluar-menu">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline;vertical-align:middle;margin-right:5px;"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    Keluar dari Akun
                </a>
            </div>
        </nav>
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
                <a href="cek_status_perpanjang.php" style="background-color: var(--accent-gold); color: #000; font-weight: bold; padding: 6px 15px; font-size: 0.7rem; text-decoration: none; display: inline-block; border-radius: 4px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); transition: 0.3s;">Cek Status & Riwayat</a>
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
        <?php elseif ($status_member === 'kedaluwarsa'): ?>
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
                    <?= ($status_member === 'aktif') ? 'AKTIF' : 'kedaluwarsa' ?>
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
            <h2 class="section-title" style="font-size: 1.4rem; text-align: center; color: var(--accent-gold); border-bottom: 1px solid #333; padding-bottom: 10px; margin-bottom: 20px;">Jadwal Operasional & Kelas</h2>
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
            <h2 style="color: var(--accent-gold); border-bottom: 1px solid #333; padding-bottom: 10px; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px; text-align: center; font-size: 1.4rem;">Aksi Cepat</h2>
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

                <a href="galeri_member.php" class="action-btn">
                    <div class="action-icon"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><path d="M21 15l-5-5L5 21"></path></svg></div>
                    <div>
                        <h3>Galeri Gym</h3>
                        <p>Foto & video fasilitas dan tutorial alat gym.</p>
                    </div>
                </a>
            </div>
        </div>

    </div>

    <footer>
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
                        <span>CS / Pendaftaran: @vandagympky_classic</span>
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
        <a href="galeri_member.php" class="nav-item">
            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
            <span>Galeri</span>
        </a>
        <a href="chatbot_member.php" class="nav-item <?= ($status_member !== 'aktif') ? 'locked-nav' : '' ?>" <?= ($status_member !== 'aktif') ? 'onclick="event.preventDefault(); alert(\'Terkunci!\')"' : '' ?>>
            <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="10" rx="2"></rect><circle cx="12" cy="5" r="2"></circle><path d="M12 7v4"></path></svg>
            <span>AI Bot</span>
        </a>
        <a href="profil_member.php" class="nav-item">
            <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            <span>Profil</span>
        </a>
    </div>

    <a href="https://instagram.com/vandagympky_classic" target="_blank" class="wa-btn" title="Hubungi CS via Instagram"
       style="position: fixed; bottom: 20px; left: 20px; z-index: 9999; color: #ffffff; background: var(--primary-red, #ff4d4d); border-radius: 50%; padding: 12px; box-shadow: 0 4px 15px rgba(255,77,77,0.4); border: 2px solid #E8C999; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
        </svg>
    </a>

    <a href="chatbot_member.php" class="chatbot-btn <?= ($status_member !== 'aktif') ? 'locked' : '' ?>"
       <?= ($status_member !== 'aktif') ? 'onclick="event.preventDefault(); alert(\'Fitur AI terkunci.\')"' : '' ?>
       title="Chatbot Vanda AI"
       style="position: fixed; bottom: 20px; right: 20px; z-index: 9999; background-color: var(--primary-red); color: white; border: none; border-radius: 50%; width: 60px; height: 60px; display: flex; justify-content: center; align-items: center; box-shadow: 0 4px 15px rgba(0,0,0,0.6); text-decoration: none;">
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
        // HAMBURGER TOGGLE 
        const menuToggle = document.getElementById('mobile-menu');
        const navMenu    = document.getElementById('nav-menu');

        menuToggle.addEventListener('click', () => {
            menuToggle.classList.toggle('active');
            navMenu.classList.toggle('active');
        });

        // Tutup dropdown jika klik di luar
        document.addEventListener('click', function(event) {
            const isClickInside = navMenu.contains(event.target) || menuToggle.contains(event.target);
            if (!isClickInside && navMenu.classList.contains('active')) {
                navMenu.classList.remove('active');
                menuToggle.classList.remove('active');
            }
        });

        // OFFLINE DETECTOR
        window.addEventListener('offline', function() {
            document.getElementById('boxErrorKoneksi').style.display = 'flex';
        });
        window.addEventListener('online', function() {
            document.getElementById('boxErrorKoneksi').style.display = 'none';
        });
        function cobaLagiKoneksi() {
            if (navigator.onLine) {
                document.getElementById('boxErrorKoneksi').style.display = 'none';
                window.location.reload();
            } else {
                alert("Koneksi masih terputus! Silakan periksa jaringan Wi-Fi atau Data Seluler Anda.");
            }
        }
    </script>
</body>
</html>