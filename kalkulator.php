<?php
session_start();

// Ambil sumber halaman untuk menentukan navigasi mana yang ditampilkan
$source = $_GET['source'] ?? '';
$is_member_view = ($source === 'dasbor');

// Jika dari dasbor, ambil data member untuk lonceng & status
$peringatan_merah  = false;
$sedang_perpanjang = false;
$status_member     = 'belum_daftar';

if ($is_member_view && isset($_SESSION['id_user'])) {
    require_once 'includes/koneksi.php';
    $uid = (int)$_SESSION['id_user'];

    $q_m = mysqli_query($koneksi, "SELECT tgl_berakhir, status FROM membership WHERE id_user=$uid AND status='aktif' ORDER BY id_membership DESC LIMIT 1");
    $d_m = mysqli_fetch_assoc($q_m);
    if ($d_m) {
        $status_member = 'aktif';
        $sisa = max(0, round((strtotime($d_m['tgl_berakhir']) - time()) / 86400));
        if ($sisa <= 7) $peringatan_merah = true;
    }
    $q_p = mysqli_query($koneksi, "SELECT id_membership FROM membership WHERE id_user=$uid AND status='pending' AND jenis_pengajuan='perpanjang' LIMIT 1");
    if ($q_p && mysqli_num_rows($q_p) > 0) {
        $sedang_perpanjang = true;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalkulator Gizi - Vanda Gym Classic</title>
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
            min-height: 100vh;
            padding: 95px 30px 40px 30px;
            position: relative;
        }

        /* =========================================
           SEMBUNYIKAN BOTTOM NAV DI PC
           ========================================= */
        .bottom-nav-mobile { display: none !important; }

        /* =========================================
           HEADER STICKY
           ========================================= */
        header {
            position: fixed;
            top: 0; left: 0;
            width: 100%;
            display: flex;
            align-items: center;
            padding: 6px 30px;
            box-sizing: border-box;
            z-index: 1001;
            border-bottom: 2px solid var(--primary-red);
            background-color: rgba(10, 10, 10, 0.98);
            height: 70px;
        }

        header .logo img { height: 50px; object-fit: contain; }

        /* HEADER RIGHT (lonceng member + hamburger) */
        .header-right {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-left: auto;
        }

        /* Lonceng — hanya tampil untuk mode member */
        .bell-icon {
            position: relative; color: #888;
            display: flex; align-items: center; justify-content: center;
            width: 40px; height: 40px; border-radius: 4px;
            transition: 0.3s; text-decoration: none;
        }
        .bell-icon:hover { color: var(--text-light); }
        .bell-icon.active { color: var(--text-light); animation: ring 2s infinite ease-in-out; }
        .bell-badge {
            position: absolute; top: 4px; right: 4px;
            background: var(--primary-red); color: white;
            font-size: 10px; font-weight: bold;
            width: 14px; height: 14px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
        }
        @keyframes ring {
            0%  { transform: rotate(0); }   10% { transform: rotate(15deg); }
            20% { transform: rotate(-10deg); } 30% { transform: rotate(5deg); }
            40% { transform: rotate(-5deg); }  50% { transform: rotate(0); }
            100%{ transform: rotate(0); }
        }

        /* =========================================
           HAMBURGER TOGGLE
           ========================================= */
        header .menu-toggle {
            display: flex; flex-direction: column;
            justify-content: center; align-items: center;
            width: 42px; height: 42px;
            background-color: #1a1a1a; border: 1px solid #333;
            border-radius: 10px; cursor: pointer; z-index: 1001; padding: 0;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }
        header .menu-toggle:hover { border-color: var(--accent-gold); }
        header .menu-toggle .bar {
            display: block; width: 20px; height: 2px;
            background-color: #E8C999; border-radius: 2px;
            transition: all 0.3s ease-in-out; margin: 2px 0;
        }
        header .menu-toggle.active .bar:nth-child(1) { transform: translateY(6px) rotate(45deg); }
        header .menu-toggle.active .bar:nth-child(2) { opacity: 0; }
        header .menu-toggle.active .bar:nth-child(3) { transform: translateY(-6px) rotate(-45deg); }

        /* =========================================
           DROPDOWN NAV MENU
           ========================================= */
        #nav-menu {
            display: none;
            position: fixed;
            top: 70px; right: 20px; left: auto;
            width: 220px; box-sizing: border-box;
            background-color: #1a1a1a; padding: 12px;
            border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.8);
            border: 1px solid #333; flex-direction: column; z-index: 1000;
        }
        #nav-menu.active {
            display: flex;
            animation: slideDownFade 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }
        @keyframes slideDownFade {
            from { opacity: 0; transform: translateY(-15px) scale(0.95); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Link umum di dalam dropdown */
        #nav-menu a.menu-link {
            display: flex; align-items: center; justify-content: flex-start; gap: 10px;
            width: 100%; box-sizing: border-box; color: #ccc; text-decoration: none;
            font-weight: 600; padding: 8px 10px; border-radius: 6px;
            transition: all 0.3s ease; font-size: 0.85rem;
            text-transform: none; letter-spacing: normal; margin-left: 0; min-height: auto;
        }
        #nav-menu a.menu-link:hover { background-color: rgba(232,201,153,0.1); color: var(--accent-gold); }
        #nav-menu a.menu-link.active-link { color: var(--accent-gold); background-color: rgba(232,201,153,0.07); }
        #nav-menu a.menu-link.locked-link { opacity: 0.45; cursor: not-allowed; pointer-events: none; }

        .menu-divider { height: 1px; background-color: #333; margin: 4px 0; width: 100%; }

        /* Tombol Login & Daftar (tampilan publik) */
        .nav-actions { display: flex; flex-direction: column; width: 100%; gap: 8px; margin-top: 4px; }
        .nav-actions .nav-login {
            display: block; box-sizing: border-box; padding: 8px 15px;
            border: 1px solid #555; border-radius: 6px; color: #fff;
            text-decoration: none; font-weight: bold; text-align: center;
            transition: all 0.3s ease; font-size: 0.85rem;
        }
        .nav-actions .nav-login:hover { border-color: #fff; background-color: rgba(255,255,255,0.1); }
        .nav-actions .btn-daftar {
            display: block; box-sizing: border-box; padding: 10px 15px;
            border: 1px solid var(--accent-gold); background-color: var(--accent-gold);
            color: #000; border-radius: 6px; font-weight: bold; cursor: pointer;
            text-align: center; transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(232,201,153,0.3); font-size: 0.85rem;
        }
        .nav-actions .btn-daftar:hover { background-color: transparent; color: var(--accent-gold); }

        /* Tombol Keluar (tampilan member) */
        .nav-actions-dasbor { display: flex; flex-direction: column; width: 100%; gap: 8px; margin-top: 4px; }
        .nav-actions-dasbor .btn-keluar-menu {
            display: block; box-sizing: border-box; padding: 9px 15px;
            border: 1px solid var(--primary-red); background-color: transparent;
            color: var(--primary-red); border-radius: 6px; font-weight: bold;
            cursor: pointer; text-align: center; transition: all 0.3s ease;
            font-size: 0.85rem; text-decoration: none;
        }
        .nav-actions-dasbor .btn-keluar-menu:hover { background-color: var(--primary-red); color: white; }

        /* =========================================
           KONTEN KALKULATOR
           ========================================= */
        .calc-container {
            background-color: #0a0a0a;
            border: 1px solid #333; border-top: 4px solid var(--primary-red);
            border-radius: 8px; padding: 30px; width: 100%; max-width: 650px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.8); margin: auto;
        }

        .nav-top {  }
        .btn-back-square {
            width: 44px; height: 44px;
            background-color: #1a1a1a; border: 1px solid #333;
            color: var(--accent-gold); border-radius: 4px;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; font-weight: bold; font-size: 1.2rem; transition: 0.3s;
        }
        .btn-back-square:hover { background-color: var(--primary-red); color: white; border-color: var(--primary-red); }

        .form-header { text-align: center; margin-bottom: 25px; }
        .form-header h2 { color: var(--text-light); text-transform: uppercase; }
        .form-header span { color: var(--accent-gold); }
        .form-header p { color: #888; font-size: 0.9rem; margin-top: 5px; line-height: 1.4; }

        .form-group { margin-bottom: 15px; position: relative; }
        .form-group label { display: flex; align-items: center; margin-bottom: 8px; color: #ccc; font-weight: 600; font-size: 0.9rem; }

        .form-control {
            width: 100%; padding: 10px 15px; min-height: 44px;
            background-color: var(--input-bg); border: 1px solid #333;
            border-radius: 4px; color: white; font-size: 1rem;
        }
        .form-control:focus { outline: none; border-color: var(--accent-gold); }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }

        .btn-submit {
            width: 100%; background-color: var(--primary-red); color: white;
            border: none; min-height: 48px; font-size: 1.1rem; font-weight: bold;
            border-radius: 4px; cursor: pointer; text-transform: uppercase; margin-top: 10px; transition: 0.3s;
        }
        .btn-submit:hover { background-color: #a81a1a; }

        .help-icon {
            display: inline-flex; align-items: center; justify-content: center;
            width: 18px; height: 18px; border-radius: 50%;
            background-color: #333; color: var(--accent-gold);
            font-size: 0.75rem; font-weight: bold; cursor: pointer;
            margin-left: 8px; transition: 0.2s; border: 1px solid var(--accent-gold);
        }
        .help-icon:hover { background-color: var(--accent-gold); color: #000; }

        .warning-box {
            display: none; background: rgba(255,193,7,0.1);
            border: 1px solid #ffc107; color: #ffc107;
            padding: 12px; border-radius: 4px; font-size: 0.85rem;
            margin-bottom: 20px; line-height: 1.4;
        }

        .modal-overlay {
            display: none; position: fixed; top: 0; left: 0;
            width: 100%; height: 100%; background: rgba(0,0,0,0.7);
            z-index: 2000; justify-content: center; align-items: center; padding: 20px;
        }
        .modal-content {
            background: #111; border: 1px solid var(--accent-gold);
            border-radius: 8px; padding: 25px; max-width: 400px;
            text-align: center; animation: fadeIn 0.3s; position: relative;
        }
        .modal-title { color: var(--accent-gold); font-size: 1.2rem; margin-bottom: 10px; text-transform: uppercase; }
        .modal-text  { color: #ccc; font-size: 0.9rem; line-height: 1.5; margin-bottom: 20px; }
        .btn-close { background: var(--primary-red); color: white; border: none; padding: 8px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; }

        .result-box {
            margin-top: 25px; padding: 20px; border-radius: 8px;
            background: #111; border: 1px dashed var(--accent-gold); display: none;
            text-align: center; animation: fadeIn 0.5s;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

        .result-title { font-size: 1rem; color: var(--text-light); font-weight: bold; margin-bottom: 5px; }
        .result-value { font-size: 2.5rem; font-weight: bold; color: var(--accent-gold); margin-bottom: 5px; line-height: 1; }
        .result-desc  { font-size: 0.85rem; color: #aaa; margin-bottom: 20px; line-height: 1.5; }

        .macro-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px; text-align: left; }
        .macro-item  { background: #0a0a0a; padding: 15px; border-radius: 4px; border: 1px solid #222; }
        .macro-label  { color: var(--accent-gold); font-size: 0.9rem; font-weight: bold; display: flex; align-items: center; margin-bottom: 5px; }
        .macro-number { color: var(--text-light); font-weight: bold; font-size: 1.4rem; display: block; margin-bottom: 5px; }
        .macro-note   { font-size: 0.75rem; color: #777; display: block; }

        /* =========================================
           TOMBOL CS KIRI (hanya PC)
           ========================================= */
        .wa-btn {
            position: fixed; bottom: 20px; left: 20px; width: 55px; height: 55px;
            background-color: var(--primary-red); color: white; border-radius: 50%;
            display: flex; justify-content: center; align-items: center;
            box-shadow: 0 4px 15px rgba(255,77,77,0.4); z-index: 9000;
            text-decoration: none; transition: 0.3s;
            border: 2px solid var(--accent-gold);
        }
        .wa-btn:hover { transform: scale(1.1); }

        /* =========================================
           OFFLINE DETECTOR
           ========================================= */
        .connection-error-box { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.85); display: none; justify-content: center; align-items: center; z-index: 999999; padding: 20px; }
        .error-card-center { background-color: #0f0a0a; border: 1px solid #ff4d4d; border-top: 4px solid #ff4d4d; border-radius: 8px; padding: 30px 25px; max-width: 400px; width: 100%; text-align: center; box-shadow: 0 10px 30px rgba(255,77,77,0.15); }
        .btn-retry { background-color: #25D366; color: white; border: none; padding: 10px 15px; border-radius: 4px; font-weight: bold; cursor: pointer; margin-top: 15px; width: 100%; transition: 0.3s; }
        .btn-retry:hover { background-color: #1ebe57; transform: scale(1.02); }

        /* =========================================
           MOBILE RESPONSIVE
           ========================================= */
        @media screen and (max-width: 768px) {
            body { padding: 85px 25px 85px 25px; }

            /* Header mobile */
            header { padding: 10px 20px; }
            header .menu-toggle { width: 36px; height: 36px; }
            header .menu-toggle .bar { width: 16px; }

            #nav-menu { top: 55px; right: 10px; left: auto; width: 195px; box-sizing: border-box; padding: 10px; }
            #nav-menu a.menu-link { font-size: 0.75rem; padding: 5px 6px; white-space: nowrap; }
            .nav-actions .nav-login, .nav-actions .btn-daftar { padding: 6px 8px; font-size: 0.75rem; }
            .nav-actions .btn-daftar { width: 100%; }
            .nav-actions-dasbor .btn-keluar-menu { font-size: 0.75rem; padding: 7px 8px; }

            /* Sembunyikan tombol CS di mobile */
            .wa-btn { display: none !important; }

            /* Kalkulator */
            .calc-container { padding: 15px 12px; }
            .nav-top { margin-bottom: 10px; }
            .btn-back-square { width: 32px; height: 32px; font-size: 1rem; }
            .form-header { margin-bottom: 15px; }
            .form-header h2 { font-size: 1.15rem; margin-bottom: 2px; }
            .form-header p { font-size: 0.75rem; margin-top: 0; }

            .grid-2 { grid-template-columns: 1fr 1fr; gap: 8px; }
            .form-group { margin-bottom: 10px; }
            .form-group label { font-size: 0.75rem; margin-bottom: 4px; }
            .form-control { padding: 6px 10px; min-height: 34px; font-size: 0.8rem; }
            .btn-submit { font-size: 0.9rem; min-height: 38px; margin-top: 5px; }

            .result-box { margin-top: 15px; padding: 12px; }
            .result-title { font-size: 0.85rem; margin-bottom: 2px; }
            .result-value { font-size: 1.8rem; margin-bottom: 2px; }
            .result-value span { font-size: 0.9rem !important; }
            .result-desc { font-size: 0.75rem; margin-bottom: 12px; line-height: 1.3; }

            .macro-grid { grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 8px; }
            .macro-item { padding: 10px; }
            .macro-label { font-size: 0.75rem; margin-bottom: 2px; }
            .macro-number { font-size: 1.1rem; margin-bottom: 2px; }
            .macro-note { font-size: 0.7rem; }

            /* ============================================
               BOTTOM NAV MOBILE
               ============================================ */
            .bottom-nav-mobile {
                display: flex !important; position: fixed !important; bottom: 0 !important; left: 0 !important;
                width: 100vw !important; height: 70px !important; background-color: #0a0a0a !important;
                border-top: 1px solid #333 !important; justify-content: space-around !important; align-items: center !important;
                z-index: 2147483647 !important; box-shadow: 0 -5px 15px rgba(0,0,0,0.9) !important;
            }
            .bottom-nav-mobile .nav-item {
                display: flex !important; flex-direction: column !important; align-items: center !important;
                justify-content: center !important; color: #ccc !important; text-decoration: none !important;
                font-size: 10px !important; background: transparent !important; border: none !important;
                flex: 1 !important; gap: 4px !important; cursor: pointer !important; padding: 5px 0 !important; transition: 0.3s;
            }
            .bottom-nav-mobile .nav-item:hover,
            .bottom-nav-mobile .nav-item:active { color: var(--accent-gold, #E8C999) !important; }
            .bottom-nav-mobile .nav-item svg {
                width: 22px !important; height: 22px !important; stroke: currentColor !important;
                fill: none !important; stroke-width: 2 !important; stroke-linecap: round !important; stroke-linejoin: round !important;
            }
            .bottom-nav-mobile .nav-item.highlight { color: var(--accent-gold, #E8C999) !important; font-weight: bold !important; }
            .bottom-nav-mobile .nav-item.highlight svg { stroke: var(--accent-gold, #E8C999) !important; fill: none !important; }
            .bottom-nav-mobile .nav-item.locked-nav { opacity: 0.4; cursor: not-allowed; }

            /* Tombol Daftar Menonjol Tengah (versi publik) */
            .nav-daftar-special { position: relative !important; top: -15px !important; z-index: 10 !important; }
            .nav-daftar-special .special-bg {
                background: linear-gradient(135deg, var(--accent-gold, #E8C999), #c59b58) !important;
                width: 50px !important; height: 50px !important; border-radius: 50% !important;
                display: flex !important; align-items: center !important; justify-content: center !important;
                box-shadow: 0 5px 15px rgba(232,201,153,0.4) !important; margin-bottom: 5px !important;
                border: 4px solid #0a0a0a !important; transition: transform 0.3s !important;
            }
            .nav-daftar-special .special-bg svg { stroke: #000 !important; }
            .nav-daftar-special:hover .special-bg,
            .nav-daftar-special:active .special-bg { transform: scale(1.1) !important; }
        }

        @media (max-width: 480px) {
            .calc-container { padding: 12px 10px; }
            .form-control { font-size: 0.75rem; padding: 5px 8px; }
        }
    </style>
</head>
<body>

    <!-- ============================================================
         HEADER — dua varian tergantung $is_member_view
         ============================================================ -->
    <header>
        <div class="logo">
            <img src="assets/logo.png" alt="Vanda Gym Classic Logo">
        </div>

        <?php if ($is_member_view): ?>
            <!-- ===== HEADER MEMBER (seperti member_dasbor.php) ===== -->
            <div class="header-right">
                <!-- Lonceng selalu di luar hamburger -->
                <a href="perpanjang.php" class="bell-icon <?= ($peringatan_merah && !$sedang_perpanjang) ? 'active' : '' ?>" title="Tagihan Perpanjangan Membership">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <?php if ($status_member === 'aktif' && $peringatan_merah && !$sedang_perpanjang): ?>
                        <span class="bell-badge">!</span>
                    <?php endif; ?>
                </a>
                <button class="menu-toggle" id="mobile-menu" aria-label="Toggle Menu">
                    <span class="bar"></span><span class="bar"></span><span class="bar"></span>
                </button>
            </div>

            <nav id="nav-menu">
                <a href="member_dasbor.php" class="menu-link">
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
                <a href="chatbot_member.php" class="menu-link <?= ($status_member !== 'aktif') ? 'locked-link' : '' ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"></rect><circle cx="12" cy="5" r="2"></circle><path d="M12 7v4"></path><line x1="8" y1="16" x2="8.01" y2="16"></line><line x1="16" y1="16" x2="16.01" y2="16"></line></svg>
                    Chatbot AI
                </a>
                <a href="kalkulator.php?source=dasbor" class="menu-link active-link">
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

        <?php else: ?>
            <!-- ===== HEADER PUBLIK (seperti index.php / galeri_gym.php) ===== -->
            <button class="menu-toggle" id="mobile-menu" aria-label="Toggle Menu" style="margin-left:auto;">
                <span class="bar"></span><span class="bar"></span><span class="bar"></span>
            </button>

            <nav id="nav-menu">
                <a href="index.php" class="menu-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    Beranda
                </a>
                <a href="galeri_gym.php" class="menu-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><path d="M21 15l-5-5L5 21"></path></svg>
                    Galeri Gym
                </a>
                <a href="kalkulator.php" class="menu-link active-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="8" y1="6" x2="16" y2="6"></line><line x1="16" y1="14" x2="16.01" y2="14"></line><line x1="12" y1="14" x2="12.01" y2="14"></line><line x1="8" y1="14" x2="8.01" y2="14"></line></svg>
                    Kalkulator Gizi
                </a>
                <a href="cek_status.php" class="menu-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    Cek Status Daftar
                </a>
                <a href="index.php#hubungi-kami" class="menu-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    Hubungi Kami
                </a>
                <div class="menu-divider"></div>
                <div class="nav-actions">
                    <a href="login.php" class="nav-login">Login Akun</a>
                    <button class="btn-daftar" onclick="window.location.href='daftar.php'">Daftar Member</button>
                </div>
            </nav>
        <?php endif; ?>
    </header>

    <!-- ============================================================
         KONTEN KALKULATOR
         ============================================================ -->
    <div class="calc-container">
        <div class="nav-top">
            <a href="<?= $is_member_view ? 'member_dasbor.php' : 'index.php' ?>" class="btn-back-square" title="Kembali">←</a>
        </div>

        <div class="form-header">
            <h2>Kalkulator <span>Gizi</span></h2>
            <p>Ketahui target kalori & protein harianmu.</p>
        </div>

        <div id="warningBox" class="warning-box"></div>

        <form id="formKalkulator" onsubmit="hitungGizi(event)">
            <div class="grid-2">
                <div class="form-group">
                    <label>Jenis Kelamin</label>
                    <select id="kalGender" class="form-control" required>
                        <option value="" disabled selected>-- Pilih --</option>
                        <option value="laki">Laki-laki</option>
                        <option value="perempuan">Perempuan</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Usia (Thn)</label>
                    <input type="number" id="kalUsia" class="form-control" required placeholder="Cth: 22">
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Berat (kg)</label>
                    <input type="number" id="kalBb" class="form-control" required step="0.1" placeholder="Cth: 65">
                </div>
                <div class="form-group">
                    <label>Tinggi (cm)</label>
                    <input type="number" id="kalTb" class="form-control" required step="0.1" placeholder="Cth: 170">
                </div>
            </div>

            <div class="form-group">
                <label>Aktivitas Fisik <span class="help-icon" onclick="showHelp('tdee')">?</span></label>
                <select id="kalAktivitas" class="form-control" required>
                    <option value="" disabled selected>-- Pilih Rutinitas --</option>
                    <option value="1.2">Jarang Gerak (Minim olahraga)</option>
                    <option value="1.375">Ringan (Olahraga 1-3 hari/mgg)</option>
                    <option value="1.55">Sedang (Olahraga 3-5 hari/mgg)</option>
                    <option value="1.725">Berat (Olahraga 6-7 hari/mgg)</option>
                    <option value="1.9">Sangat Berat (Pekerja fisik berat)</option>
                </select>
            </div>

            <div class="form-group">
                <label>Tujuan Anda?</label>
                <select id="kalTarget" class="form-control" required>
                    <option value="" disabled selected>-- Pilih Target Badan --</option>
                    <option value="weight_loss">Turun Berat Badan (Cutting)</option>
                    <option value="maintenance">Jaga Berat Badan (Maintenance)</option>
                    <option value="weight_gain">Naik Berat Badan (Bulking)</option>
                </select>
            </div>

            <button type="submit" class="btn-submit">Hitung Kebutuhan</button>
        </form>

        <div id="hasilGizi" class="result-box">
            <div class="result-title">Target Makan Harian Kamu:</div>
            <div class="result-value" id="resKalori">0 <span style="font-size:1.2rem;color:#888;">Kkal</span></div>
            <div class="result-desc" id="resTargetDesc">Angka ini adalah panduan porsi makan yang harus kamu tuju.</div>

            <div class="macro-grid">
                <div class="macro-item">
                    <span class="macro-label">TDEE <span class="help-icon" onclick="showHelp('tdee')">?</span></span>
                    <span class="macro-number" id="resTdee">0 Kkal</span>
                    <span class="macro-note">Total Energi</span>
                </div>
                <div class="macro-item">
                    <span class="macro-label">BMR <span class="help-icon" onclick="showHelp('bmr')">?</span></span>
                    <span class="macro-number" id="resBmr">0 Kkal</span>
                    <span class="macro-note">Kalori Minimal</span>
                </div>
            </div>

            <div class="macro-grid" style="grid-template-columns:1fr;margin-top:8px;">
                <div class="macro-item" style="text-align:center;">
                    <span class="macro-label" style="justify-content:center;">Target Protein <span class="help-icon" onclick="showHelp('protein')">?</span></span>
                    <span class="macro-number" id="resProtein">0g</span>
                    <span class="macro-note">Asupan Otot Harian</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Info -->
    <div id="infoModal" class="modal-overlay">
        <div class="modal-content">
            <h3 id="modalTitle" class="modal-title">Judul</h3>
            <p  id="modalText"  class="modal-text">Teks penjelasan akan muncul di sini.</p>
            <button class="btn-close" onclick="closeHelp()">Paham!</button>
        </div>
    </div>

    <!-- Offline Detector -->
    <div id="boxErrorKoneksi" class="connection-error-box">
        <div class="error-card-center">
            <div style="width:50px;height:50px;background:#221111;border:2px solid #ff4d4d;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 15px auto;">
                <span style="color:#ff4d4d;font-size:1.6rem;font-weight:bold;">!</span>
            </div>
            <h3 style="color:#ff4d4d;font-size:1.2rem;font-weight:bold;margin-bottom:8px;">Koneksi Terputus!</h3>
            <p style="color:#ccc;font-size:0.85rem;line-height:1.5;">Perangkat Anda kehilangan koneksi internet.</p>
            <button class="btn-retry" onclick="cobaLagiKoneksi()">🔄 Coba Lagi</button>
            <button type="button" style="background:transparent;border:none;color:#555;margin-top:12px;cursor:pointer;font-size:0.8rem;" onclick="document.getElementById('boxErrorKoneksi').style.display='none'">Tutup Peringatan</button>
        </div>
    </div>

    <!-- Tombol CS (hanya PC, tersembunyi di mobile) -->
    <a href="https://instagram.com/vandagympky_classic" target="_blank" class="wa-btn" title="Hubungi CS via Instagram">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
        </svg>
    </a>

    <!-- ============================================================
         BOTTOM NAV MOBILE — dua varian
         ============================================================ -->
    <?php if ($is_member_view): ?>
    <!-- Bottom nav MEMBER -->
    <div class="bottom-nav-mobile">
        <a href="member_dasbor.php" class="nav-item">
            <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span>Dasbor</span>
        </a>
        <a href="kalkulator.php?source=dasbor" class="nav-item highlight">
            <svg viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="8" y1="6" x2="16" y2="6"></line><line x1="16" y1="14" x2="16.01" y2="14"></line><line x1="12" y1="14" x2="12.01" y2="14"></line><line x1="8" y1="14" x2="8.01" y2="14"></line></svg>
            <span>Gizi</span>
        </a>
        <a href="galeri_member.php" class="nav-item">
            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
            <span>Galeri</span>
        </a>
        <a href="chatbot_member.php" class="nav-item <?= ($status_member !== 'aktif') ? 'locked-nav' : '' ?>" <?= ($status_member !== 'aktif') ? 'onclick="event.preventDefault();alert(\'Terkunci!\')"' : '' ?>>
            <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="10" rx="2"></rect><circle cx="12" cy="5" r="2"></circle><path d="M12 7v4"></path></svg>
            <span>AI Bot</span>
        </a>
        <a href="profil_member.php" class="nav-item">
            <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            <span>Profil</span>
        </a>
    </div>

    <?php else: ?>
    <!-- Bottom nav PUBLIK -->
    <div class="bottom-nav-mobile">
        <a href="index.php" class="nav-item" onclick="window.scrollTo(0,0);">
            <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span>Beranda</span>
        </a>
        <a href="index.php#jadwal" class="nav-item">
            <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            <span>Jadwal</span>
        </a>
        <a href="daftar.php" class="nav-item nav-daftar-special">
            <div class="special-bg">
                <svg viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
            </div>
            <span style="color:var(--accent-gold);font-weight:bold;">Daftar</span>
        </a>
        <a href="galeri_gym.php" class="nav-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><path d="M21 15l-5-5L5 21"></path></svg>
            <span>Galeri</span>
        </a>
        <a href="kalkulator.php" class="nav-item highlight">
            <svg viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="8" y1="6" x2="16" y2="6"></line><line x1="16" y1="14" x2="16.01" y2="14"></line><line x1="12" y1="14" x2="12.01" y2="14"></line><line x1="8" y1="14" x2="8.01" y2="14"></line><line x1="16" y1="18" x2="16.01" y2="18"></line><line x1="12" y1="18" x2="12.01" y2="18"></line><line x1="8" y1="18" x2="8.01" y2="18"></line></svg>
            <span>Gizi</span>
        </a>
    </div>
    <?php endif; ?>

    <script>
        // ============ HAMBURGER ============
        const menuToggle = document.getElementById('mobile-menu');
        const navMenu    = document.getElementById('nav-menu');

        menuToggle.addEventListener('click', () => {
            menuToggle.classList.toggle('active');
            navMenu.classList.toggle('active');
        });
        document.addEventListener('click', function(event) {
            const isClickInside = navMenu.contains(event.target) || menuToggle.contains(event.target);
            if (!isClickInside && navMenu.classList.contains('active')) {
                navMenu.classList.remove('active');
                menuToggle.classList.remove('active');
            }
        });

        // ============ MODAL INFO ============
        function showHelp(tipe) {
            const modal = document.getElementById('infoModal');
            const title = document.getElementById('modalTitle');
            const text  = document.getElementById('modalText');
            if (tipe === 'bmr') {
                title.innerText = 'Apa itu BMR?';
                text.innerHTML  = '<strong>Basal Metabolic Rate (BMR)</strong> adalah kalori minimal agar organ tubuhmu tetap berfungsi normal meskipun kamu rebahan seharian. Jangan pernah makan di bawah angka ini agar metabolisme tidak rusak!';
            } else if (tipe === 'tdee') {
                title.innerText = 'Apa itu TDEE?';
                text.innerHTML  = '<strong>Total Daily Energy Expenditure (TDEE)</strong> adalah total energi yang dibakar tubuhmu dalam 24 jam, sudah mempertimbangkan aktivitas harianmu.';
            } else if (tipe === 'protein') {
                title.innerText = 'Target Protein';
                text.innerHTML  = 'Asupan protein disarankan antara <strong>1,6 - 2,2 gram per kg berat badan</strong> untuk memelihara dan membangun massa otot yang optimal.';
            }
            modal.style.display = 'flex';
        }
        function closeHelp() { document.getElementById('infoModal').style.display = 'none'; }

        // ============ HITUNG GIZI ============
        function hitungGizi(e) {
            e.preventDefault();
            const gender    = document.getElementById('kalGender').value;
            const usia      = parseInt(document.getElementById('kalUsia').value);
            const bb        = parseFloat(document.getElementById('kalBb').value);
            const tb        = parseFloat(document.getElementById('kalTb').value);
            const aktivitas = parseFloat(document.getElementById('kalAktivitas').value);
            const target    = document.getElementById('kalTarget').value;

            let warningMsg = [];
            if (usia > 100 || usia < 10)  warningMsg.push("Usia kurang wajar.");
            if (bb > 250 || bb < 20)      warningMsg.push("Berat badan kurang wajar.");
            if (tb > 250 || tb < 80)      warningMsg.push("Tinggi badan kurang wajar.");

            const warnBox = document.getElementById('warningBox');
            if (warningMsg.length > 0) {
                warnBox.innerHTML = `<strong>⚠️ Peringatan:</strong> ${warningMsg.join(" ")} <br><em>Perhitungan tetap dilanjutkan.</em>`;
                warnBox.style.display = 'block';
            } else {
                warnBox.style.display = 'none';
            }

            let bmr = (10 * bb) + (6.25 * tb) - (5 * usia);
            bmr += (gender === 'laki') ? 5 : -161;
            const tdee = bmr * aktivitas;

            let kaloriFinal = tdee;
            let targetDesc  = "";
            if (target === 'weight_loss') {
                kaloriFinal -= 500;
                targetDesc = "Kamu menargetkan <strong>Penurunan Berat Badan</strong>. Makanlah lebih sedikit dari TDEE agar tubuh membakar lemak.";
            } else if (target === 'weight_gain') {
                kaloriFinal += 500;
                targetDesc = "Kamu menargetkan <strong>Kenaikan Berat Badan</strong>. Makanlah lebih banyak dari TDEE agar otot bisa tumbuh.";
            } else {
                targetDesc = "Kamu menargetkan <strong>Menjaga Berat Badan</strong>. Porsi ini pas untuk mempertahankan bentuk badanmu saat ini.";
            }

            const proteinMin = Math.round(bb * 1.6);
            const proteinMax = Math.round(bb * 2.2);

            document.getElementById('hasilGizi').style.display   = 'block';
            document.getElementById('resKalori').innerHTML        = Math.round(kaloriFinal).toLocaleString('id-ID') + ' <span style="font-size:1.2rem;color:#888;">Kkal</span>';
            document.getElementById('resTargetDesc').innerHTML    = targetDesc;
            document.getElementById('resTdee').innerText          = Math.round(tdee).toLocaleString('id-ID') + ' Kkal';
            document.getElementById('resBmr').innerText           = Math.round(bmr).toLocaleString('id-ID') + ' Kkal';
            document.getElementById('resProtein').innerText       = proteinMin + ' - ' + proteinMax + ' g';

            setTimeout(() => {
                document.getElementById('hasilGizi').scrollIntoView({ behavior: 'smooth', block: 'end' });
            }, 100);
        }

        // ============ OFFLINE DETECTOR ============
        window.addEventListener('offline', () => document.getElementById('boxErrorKoneksi').style.display = 'flex');
        window.addEventListener('online',  () => document.getElementById('boxErrorKoneksi').style.display = 'none');
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