<?php
session_start();
require 'includes/koneksi.php'; 

// Ambil Data Pengaturan Web dari Database
$q_pengaturan = mysqli_query($koneksi, "SELECT * FROM pengaturan_web WHERE id=1");
$web_data = mysqli_fetch_assoc($q_pengaturan);

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

// Decode Jadwal Senam (JSON ke Array)
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

// Format Link WhatsApp
$wa_db = $web_data['wa_cs'] ?? '082148556601';
$wa_link = "62" . substr(preg_replace('/[^0-9]/', '', $wa_db), 1);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vanda Gym Classic Palangkaraya</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* =========================================
           CSS HERO BACKGROUND SLIDER
           ========================================= */
        .hero {
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 85vh; 
        }

        .hero-bg-slider {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: 1;
        }

        .hero-bg-slider img {
            width: 100%; height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0; left: 0;
            opacity: 0;
            animation: fadeSlide 15s infinite;
        }

        .hero-bg-slider img:nth-child(1) { animation-delay: 0s; }
        .hero-bg-slider img:nth-child(2) { animation-delay: 5s; }
        .hero-bg-slider img:nth-child(3) { animation-delay: 10s; }

        @keyframes fadeSlide {
            0% { opacity: 0; transform: scale(1); }
            10% { opacity: 1; }
            33% { opacity: 1; transform: scale(1.05); }
            43% { opacity: 0; transform: scale(1.08); }
            100% { opacity: 0; }
        }

        .hero-overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.7); 
            z-index: 2;
        }

        .hero-content {
            position: relative;
            z-index: 3;
            text-align: center;
            padding: 0 20px;
        }

        .hero-content h1 {
            color: #ffffff;
            font-size: 3rem;
            margin-bottom: 15px;
            text-shadow: 2px 2px 10px rgba(0,0,0,0.6);
            animation: slideUpFade 1s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
            opacity: 0;
            transform: translateY(30px);
        }

        .hero-content p {
            color: #e0e0e0;
            max-width: 600px;
            margin: 0 auto 30px auto;
            line-height: 1.6;
            font-size: 1.1rem;
            animation: slideUpFade 1s cubic-bezier(0.175, 0.885, 0.32, 1.275) 0.2s forwards;
            opacity: 0;
            transform: translateY(30px);
        }

        .hero-content .btn-primary {
            animation: slideUpFade 1s cubic-bezier(0.175, 0.885, 0.32, 1.275) 0.4s forwards;
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.3s ease;
        }
        
        .hero-content .btn-primary:hover {
            transform: scale(1.05) translateY(-3px);
            box-shadow: 0 10px 20px rgba(232, 201, 153, 0.4);
        }

        @keyframes slideUpFade {
            to { opacity: 1; transform: translateY(0); }
        }

        /* =========================================
           TAMBAHAN ANIMASI GEMES & ESTETIK
           ========================================= */
        .reveal {
            opacity: 0;
            transform: translateY(40px);
            transition: all 0.8s cubic-bezier(0.5, 0, 0, 1);
        }
        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }

        .card, .benefit-card {
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.4s ease;
        }
        .card:hover, .benefit-card:hover {
            transform: translateY(-12px);
            box-shadow: 0 15px 30px rgba(232, 201, 153, 0.15) !important;
            border-color: var(--accent-gold);
        }

        @keyframes floatGemes {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
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

        @keyframes popupBouncy {
            0% { opacity: 0; transform: scale(0.5) translateY(50px); }
            60% { opacity: 1; transform: scale(1.05) translateY(-10px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }
        .chatbot-window.show-chat {
            display: flex !important;
            flex-direction: column;
            animation: popupBouncy 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }

        .chat-body {
    height: 270px;
}

        @keyframes jelly {
            0%, 100% { transform: scale(1, 1); }
            25% { transform: scale(0.8, 1.2); }
            50% { transform: scale(1.2, 0.8); }
            75% { transform: scale(0.95, 1.05); }
        }
        .bottom-nav-mobile .nav-item:active svg {
            animation: jelly 0.5s ease;
        }

        /* CSS KOTAK OFFLINE DETECTOR */
        .connection-error-box {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(0, 0, 0, 0.85); display: none; 
            justify-content: center; align-items: center; z-index: 999999; padding: 20px;
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
        .btn-retry:hover { background-color: #1ebe57; transform: scale(1.02); }

        /* Sembunyikan menu navigasi bawah di PC secara default */
        .bottom-nav-mobile { display: none !important; }

        /* =========================================
           NAVIGASI HAMBURGER (UNTUK PC & MOBILE)
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
    margin-left: auto;
        }
header .menu-toggle:hover {
            border-color: var(--accent-gold);
        }

        header .menu-toggle .bar {
            display: block;
            width: 20px;
            height: 2px;
            background-color: #E8C999;
            border-radius: 2px;
            transition: all 0.3s ease-in-out;
        }

        header .menu-toggle.active .bar:nth-child(1) { transform: translateY(6px) rotate(45deg); }
        header .menu-toggle.active .bar:nth-child(2) { opacity: 0; }
        header .menu-toggle.active .bar:nth-child(3) { transform: translateY(-6px) rotate(-45deg); }

        /* Styling Dropdown Hamburger Menu */
        #nav-menu {
            display: none; 
            position: absolute;
            top: 70px; 
            right: 20px;
            left: auto;
            width: 210px;        /* lebar fix */
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
            to { opacity: 1; transform: translateY(0) scale(1); }
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
            font-size: 0.85rem;
        }

        #nav-menu a.menu-link:hover {
            background-color: rgba(232, 201, 153, 0.1);
            color: var(--accent-gold);
        }

        #nav-menu .menu-divider {
            height: 1px;
            background-color: #333;
            margin: 4px 0;
            width: 100%;
        }

        /* =========================================
           STYLING TOMBOL NAVIGASI DROPDOWN (LOGIN & DAFTAR)
           ========================================= */
        .nav-actions {
            display: flex;
            flex-direction: column;
            width: 100%;
            gap: 8px;
            margin-top: 4px;
        }
        
        .nav-actions .nav-login {
            display: block;
            box-sizing: border-box;
            padding: 8px 15px;
            border: 1px solid #555; 
            border-radius: 6px;
            color: #fff;
            text-decoration: none;
            font-weight: bold;
            text-align: center;
            transition: all 0.3s ease;
            font-size: 0.85rem;
        }
        
        .nav-actions .nav-login:hover {
            border-color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .nav-actions .btn-daftar {
            display: block;
            box-sizing: border-box;
            padding: 10px 15px;
            border: 1px solid var(--accent-gold, #E8C999);
            background-color: var(--accent-gold, #E8C999);
            color: #000;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(232, 201, 153, 0.3);
            font-size: 0.85rem;
        }
        
        .nav-actions .btn-daftar:hover {
            background-color: transparent;
            color: var(--accent-gold, #E8C999);
            box-shadow: 0 6px 15px rgba(232, 201, 153, 0.5);
        }

        #nav-menu a.menu-link {
            text-transform: none;
            letter-spacing: normal;
            margin-left: 0;
            min-height: auto;
        }

        #nav-menu .nav-actions .nav-login {
            text-transform: none;
            letter-spacing: normal;
            margin-left: 0;
            min-height: auto;
        }

        #nav-menu .nav-actions .btn-daftar {
            margin-left: 0;
            min-height: auto;
            min-width: auto;
        }

        /* =========================================
           CSS KHUSUS LAYOUT MOBILE 
           ========================================= */
        @media screen and (max-width: 768px) {
           
            body { padding-bottom: 85px !important; }
            
            /* Sembunyikan tombol WA khusus di Mobile */
            .wa-btn { display: none !important; }

             .chat-body {
    height: 235px;
    width: 100%;
    font-size: 11px;
}



.chat-header {
    background-color: var(--primary-red);
    padding: 10px;
    font-size: 15px;
}

.btn-qr {
    background-color: transparent;
    border: 1px solid var(--accent-gold);
    color: var(--accent-gold);
    padding: 6px;
    border-radius: 20px;
    cursor: pointer;
    font-size: 0.6rem;
}

            /* perkecil tombol hamburger */
            header .menu-toggle {
                width: 36px;
                height: 36px;
            }
            header .menu-toggle .bar {
                width: 16px;
            }

            /* dropdown lebih kecil & nempel pojok kanan atas */
            #nav-menu {
        top: 55px;
        right: 10px;
        left: auto;
        width: 185px;
        box-sizing: border-box;
        padding: 10px;
    }
            
            #nav-menu a.menu-link {
                font-size: 0.75rem;
                padding: 5px 6px;
                white-space: nowrap;
            }
            
            .nav-actions .nav-login {
                padding: 6px 8px;
                font-size: 0.75rem;
            }
            .nav-actions .btn-daftar {
                width: 100%;
                padding: 6px 8px;
                font-size: 0.75rem;
            }

            .hero { padding: 100px 20px !important; min-height: auto !important; }
            .hero-content h1 { font-size: 1.8rem !important; line-height: 1.2 !important; margin-bottom: 10px !important; }
            .hero-content p { font-size: 0.85rem !important; margin-bottom: 20px !important; line-height: 1.4 !important; }
            .hero-content .btn-primary { padding: 10px 20px !important; font-size: 0.9rem !important; }

            section { padding: 40px 15px !important; }
            .section-title { font-size: 1.4rem !important; margin-bottom: 20px !important; }

            .grid-3 { 
                display: flex !important; flex-direction: column !important; align-items: center !important; gap: 15px !important; 
            }
            .card, .benefit-card { 
                width: 100% !important; max-width: 330px !important; margin: 0 auto !important; padding: 20px 15px !important; box-sizing: border-box !important;
            }
            .card h3, .benefit-card h3 { font-size: 1.1rem !important; margin-bottom: 5px !important; }
            .card .price { font-size: 1.1rem !important; margin: 5px 0 10px 0 !important; }
            .card p, .benefit-card p { font-size: 0.8rem !important; line-height: 1.4 !important; margin-bottom: 10px !important; }
            .benefit-icon { margin-bottom: 10px !important; }
            .benefit-icon svg { width: 30px !important; height: 30px !important; }
            
            .schedule-box { padding: 15px !important; max-width: 350px !important; margin: 0 auto 15px auto !important; }
            .schedule-header { font-size: 1rem !important; padding: 8px 10px !important; }
            .schedule-row { padding: 8px 0 !important; }

            /* Bottom Navigation */
            .bottom-nav-mobile {
                display: flex !important; position: fixed !important; bottom: 0 !important; left: 0 !important; width: 100vw !important; height: 70px !important;
                background-color: #0a0a0a !important; border-top: 1px solid #333 !important; justify-content: space-around !important; align-items: center !important; z-index: 2147483647 !important; box-shadow: 0 -5px 15px rgba(0,0,0,0.9) !important;
            }

            .bottom-nav-mobile .nav-item {
                display: flex !important; flex-direction: column !important; align-items: center !important; justify-content: center !important; color: #ccc !important; text-decoration: none !important; font-size: 10px !important; background: transparent !important; border: none !important; flex: 1 !important; gap: 4px !important; cursor: pointer !important; padding: 5px 0 !important; transition: 0.3s;
            }

            .bottom-nav-mobile .nav-item:hover, .bottom-nav-mobile .nav-item:active { color: var(--accent-gold, #E8C999) !important; }
            .bottom-nav-mobile .nav-item svg { width: 22px !important; height: 22px !important; stroke: currentColor; fill: none !important; stroke-width: 2 !important; stroke-linecap: round !important; stroke-linejoin: round !important; transition: stroke 0.3s; }

            /* Tombol Daftar Menonjol Tengah */
            .nav-daftar-special {
                position: relative !important;
                top: -15px !important; 
                z-index: 10 !important;
            }

            .special-bg {
                background: linear-gradient(135deg, var(--accent-gold, #E8C999), #c59b58) !important;
                width: 50px !important;
                height: 50px !important;
                border-radius: 50% !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                box-shadow: 0 5px 15px rgba(232, 201, 153, 0.4) !important;
                margin-bottom: 5px !important;
                border: 4px solid #0a0a0a !important;
                transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) !important;
            }

            .nav-daftar-special:hover .special-bg,
            .nav-daftar-special:active .special-bg {
                transform: scale(1.1) !important;
                box-shadow: 0 8px 20px rgba(232, 201, 153, 0.6) !important;
            }

            .special-bg svg {
                width: 24px !important;
                height: 24px !important;
                stroke: #000 !important; 
            }

            .chatbot-btn { bottom: 85px !important; width: 50px !important; height: 50px !important; right: 15px !important; }
            .chatbot-btn svg { width: 24px !important; height: 24px !important; }
            
            .chatbot-window { 
                bottom: 145px !important; right: 15px !important; left: auto !important; width: 85vw !important; max-width: 320px !important; transform: none !important; 
            }
        }
    </style>
</head>
<body>

    <header>
        <div class="logo">
            <img src="assets/logo.png" alt="Vanda Gym Classic Logo">
        </div>
        
        <button class="menu-toggle" id="mobile-menu" aria-label="Toggle Menu">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </button>

        <nav id="nav-menu">
            <a href="#jadwal" class="menu-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                Jadwal
            </a>
            <a href="galeri_gym.php" class="menu-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><path d="M21 15l-5-5L5 21"></path></svg>
                Galeri Gym
            </a>
            <a href="kalkulator.php" class="menu-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-2 2 2 2-2 2 2 2-2 2 2 2-2 2 2V2z"></path><line x1="16" y1="8" x2="16" y2="8.01"></line><line x1="12" y1="8" x2="12" y2="8.01"></line><line x1="8" y1="8" x2="8" y2="8.01"></line><line x1="16" y1="12" x2="16" y2="12.01"></line><line x1="12" y1="12" x2="12" y2="12.01"></line><line x1="8" y1="12" x2="8" y2="12.01"></line><line x1="16" y1="16" x2="16" y2="16.01"></line><line x1="12" y1="16" x2="12" y2="16.01"></line><line x1="8" y1="16" x2="8" y2="16.01"></line></svg>
                Kalkulator Gizi
            </a>
            <a href="cek_status.php" class="menu-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                Cek Status Daftar
            </a>
            <a href="#hubungi-kami" class="menu-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                Hubungi Kami
            </a>

            <div class="menu-divider"></div>

            <div class="nav-actions">
                <a href="login.php" class="nav-login">Login Akun</a>
                <button class="btn-daftar" onclick="window.location.href='daftar.php'">Daftar Member</button>
            </div>
        </nav>
    </header>

    <?php if (($web_data['pengumuman_aktif'] ?? '') === 'aktif'): ?>
    <div class="announcement-banner" id="infoBanner">
        <span class="announcement-badge">Info Terkini</span>
        <span class="announcement-text"><?= htmlspecialchars($web_data['teks_pengumuman'] ?? '') ?></span>
    </div>
    <?php endif; ?>

    <section id="beranda" class="hero">
        <div class="hero-bg-slider">
            <img src="assets/foto-gym-1.jpeg" alt="Fasilitas Beban Vanda Gym">
            <img src="assets/foto-gym-2.jpeg" alt="Mesin Gym Lengkap">
            <img src="assets/foto-gym-3.jpeg" alt="Area Angkat Beban">
        </div>
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>Bentuk Karakter,<br>Bangun Kekuatan</h1>
            <p>Rasakan atmosfer bodybuilding yang autentik. Komunitas lokal aktif dan raih bentuk tubuh idealmu bersama Vanda Gym Palangkaraya.</p>
            <a href="#paket" class="btn-primary">Lihat Paket Membership</a>
        </div>
    </section>

    <section id="paket" class="reveal">
        <h2 class="section-title">Pilihan Membership</h2>
        <div class="grid-3">
            <div class="card">
                <h3>1x Visit Gym</h3>
                <div class="price">Rp <?= number_format($web_data['harga_harian'] ?? 25000, 0, ',', '.') ?></div>
                <p style="color: #aaa; margin-bottom: 15px;">Akses harian penuh ke seluruh fasilitas beban dan kardio.</p>
                <div class="highlight-text">Tidak perlu daftar online. Silakan langsung datang bayar di resepsionis.</div>
            </div>
            
            <div class="card" style="border-top-color: var(--accent-gold); box-shadow: 0 0 20px rgba(232, 201, 153, 0.1);">
                <h3 style="color: #fff;">Gym Bulanan</h3>
                <div class="price" style="color: var(--accent-gold);">Rp <?= number_format($web_data['harga_bulanan'] ?? 175000, 0, ',', '.') ?> / Bulan</div>
                <p style="color: #aaa; margin-bottom: 20px;">Akses gym tanpa batas dan dapatkan semua keuntungan sistem online.</p>
                <button class="btn-action solid" onclick="window.location.href='daftar.php'">Daftar Sekarang</button>
            </div>

            <div class="card">
                <h3>Kelas Senam</h3>
                <div class="price">Rp <?= number_format($web_data['harga_senam'] ?? 25000, 0, ',', '.') ?><span>/datang</span></div>
                <p style="color: #aaa; margin-bottom: 15px;">Bergabunglah dengan kelas Zumba, Pilates, atau BL+ bersama instruktur ahli.</p>
                <div class="highlight-text">Tidak perlu daftar online. Silakan langsung datang bayar di resepsionis.</div>
            </div>
        </div>
    </section>

    <section id="jadwal" class="reveal" style="background-color: #0a0a0a;">
        <h2 class="section-title">Jadwal Operasional & Kelas</h2>
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

    <section id="benefit" class="reveal" style="background-color: #050505; border-top: 1px solid #1a1a1a; border-bottom: 1px solid #1a1a1a;">
        <h2 class="section-title">Keuntungan Member Online</h2>
        <div class="grid-3">
            <div class="benefit-card">
                <div class="benefit-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--accent-gold)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="10" rx="2"></rect>
                        <circle cx="12" cy="5" r="2"></circle>
                        <path d="M12 7v4"></path>
                        <line x1="8" y1="16" x2="8.01" y2="16"></line>
                        <line x1="16" y1="16" x2="16.01" y2="16"></line>
                    </svg>
                </div>
                <h3>Akses Chatbot AI</h3>
                <p>Member mendapatkan AI khusus untuk info nutrisi dasar dan tips kebugaran harian.</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--accent-gold)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
                        <line x1="12" y1="18" x2="12.01" y2="18"></line>
                    </svg>
                </div>
                <h3>Kelola Masa Aktif</h3>
                <p>Miliki dasbor pribadi untuk pantau status membership, terima notifikasi, dan perpanjang online.</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--accent-gold)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                </div>
                <h3>Riwayat Instan</h3>
                <p>Dapatkan bukti pembayaran digital langsung di dashboard dan akses riwayat transaksi kapan saja.</p>
            </div>
        </div>
    </section>

    <section id="galeri" class="reveal">
        <h2 class="section-title">Fasilitas Gym Kami</h2>
        <p style="text-align: center; color: #888; margin-bottom: 25px; font-size: 0.85rem;">Geser untuk melihat beberapa fasilitas alat beban dan area gym.</p>
        
        <div class="gallery-slider">
            <img src="assets/foto-gym-1.jpeg" alt="Area Gym Utama Mesin Beban" class="gallery-item">
            <img src="assets/foto-gym-2.jpeg" alt="Fasilitas Mesin Gym Lengkap" class="gallery-item">
            <img src="assets/foto-gym-3.jpeg" alt="Area Angkat Beban Bebas (Free Weight)" class="gallery-item">
            <img src="assets/foto-gym-4.jpeg" alt="Rak Dumbbell Lengkap" class="gallery-item">
            <img src="assets/foto-gym-7.jpeg" alt="Pintu Masuk Vanda Gym Classic" class="gallery-item">
            <img src="assets/foto-gym-5.jpeg" alt="Area Parkir Mobil Luas" class="gallery-item">
            <img src="assets/foto-gym-6.jpeg" alt="Lobby Gym" class="gallery-item">
        </div>

        <div style="text-align: center; margin-top: 40px;">
            <a href="galeri_gym.php" class="btn-primary" style="padding: 10px 30px; font-size: 0.9rem;">Lihat Galeri & Tutorial Lengkap</a>
        </div>
    </section>

    <footer id="hubungi-kami" class="reveal">
        <div class="footer-container">
            <div class="footer-info">
                <h3>Vanda Gym Classic</h3>
                <p>Membentuk Karakter, Membangun Kekuatan.</p>
                
                <div style="margin-top: 20px;">
                    <p style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent-gold)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        Jl. Kapten Pierre Tendean No.17, Palangka Raya
                    </p>
                    
                    <p style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent-gold)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                        <span>CS / Pendaftaran: <a href="<?= htmlspecialchars($web_data['ig'] ?? 'https://instagram.com/vandagympky_classic') ?>" target="_blank" style="color: #aaa; font-weight: normal; text-decoration: none;">@vandagympky_classic</a></span>
                    </p>

                    <p style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent-gold)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
                            <line x1="12" y1="18" x2="12.01" y2="18"></line>
                        </svg>
                        Info Kelas Senam: 0821-xxxx-xxxx
                    </p>
                    <p style="display: flex; align-items: center; gap: 10px; margin-top: 15px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent-gold)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                            <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                        </svg>
                        <span style="color: #aaa;">Instagram: <a href="<?= htmlspecialchars($web_data['ig'] ?? 'https://instagram.com/vandagympky_classic') ?>" target="_blank" style="color: #aaa; font-weight: normal; text-decoration: none;">@vandagympky_classic</a></span>
                    </p>
                </div>
            </div>
            
            <div class="footer-map">
                <iframe src="https://maps.google.com/maps?q=Vanda%20Gym%20Palangkaraya&t=&z=15&ie=UTF8&iwloc=&output=embed" width="100%" height="220" style="border:0; border-radius: 8px;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>

        <div class="footer-bottom">
            © 2026 Vanda Gym Classic Room. Ahsana Azmiara
        </div>
    </footer>

    <div id="boxErrorKoneksi" class="connection-error-box">
        <div class="error-card-center">
            <div style="width: 50px; height: 50px; background: #221111; border: 2px solid #ff4d4d; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto;">
                <span style="color: #ff4d4d; font-size: 1.6rem; font-weight: bold;">!</span>
            </div>
            <h3 style="color:#ff4d4d; font-size:1.2rem; font-weight:bold; margin-bottom: 8px;">Koneksi Terputus!</h3>
            <p style="color:#ccc; font-size:0.85rem; line-height:1.5;">Perangkat Anda kehilangan koneksi internet. Silakan periksa jaringan Wi-Fi atau Data Seluler Anda.</p>
            <button class="btn-retry" onclick="cobaLagiKoneksi()">🔄 Coba Lagi</button>
            <button type="button" style="background: transparent; border: none; color: #555; margin-top: 12px; cursor: pointer; font-size: 0.8rem;" onclick="document.getElementById('boxErrorKoneksi').style.display='none'">Tutup Peringatan</button>
        </div>
    </div>

    <a href="https://instagram.com/vandagympky_classic" target="_blank" class="wa-btn" title="Hubungi CS via Instagram" style="position: fixed; bottom: 20px; left: 20px; z-index: 9999; color: #ffffff; background: var(--primary-red, #ff4d4d); border-radius: 50%; padding: 12px; box-shadow: 0 4px 15px rgba(255, 77, 77, 0.4); border: 2px solid #E8C999; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
        <circle cx="12" cy="7" r="4"></circle>
    </svg>
</a>

    <button class="chatbot-btn" onclick="toggleChat()" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="10" rx="2"></rect>
            <circle cx="12" cy="5" r="2"></circle>
            <path d="M12 7v4"></path>
            <line x1="8" y1="16" x2="8.01" y2="16"></line>
            <line x1="16" y1="16" x2="16.01" y2="16"></line>
        </svg>
    </button>
    
    <div class="chatbot-window" id="chatWindow" style="display: none;">
        <div class="chat-header">
            <span style="display: flex; align-items: center; gap: 8px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="10" rx="2"></rect>
                    <circle cx="12" cy="5" r="2"></circle>
                    <path d="M12 7v4"></path>
                    <line x1="8" y1="16" x2="8.01" y2="16"></line>
                    <line x1="16" y1="16" x2="16.01" y2="16"></line>
                </svg>
                 ChatBot Vanda Gym
            </span>
            <button class="close-chat" onclick="toggleChat()" title="Tutup Chat">×</button>
        </div>
        <div class="chat-body clearfix" id="chatBody">
            <div class="chat-msg">
                Halo Brosis! 👋 Selamat datang di Vanda Gym Classic. Aku siap bantu jawab pertanyaan seputar gym kita nih. Yuk, pilih topik yang mau ditanyakan di bawah! 💪
            </div>
        </div>
        <div class="chat-footer-menu">
            <div class="quick-replies">
                <button class="btn-qr" onclick="kirimFaq('Apa saja peraturan gym?', 'Sip! Biar latihan makin nyaman, ini beberapa *rules* kita ya:<br><br>1️⃣ Jangan lupa bawa handuk sendiri (biar higienis bro!).<br>2️⃣ Rapikan & kembalikan alat (dumbell/plat) ke tempat semula setelah dipakai.<br>3️⃣ Area gym bebas asap rokok & vape ya.<br>4️⃣ Jaga kebersihan bareng-bareng.<br><br>Gampang kan? <i>Let\'s go!</i> 🔥')">📜 Peraturan</button>
                <button class="btn-qr" onclick="kirimFaq('Bagaimana cara batalkan pendaftaran?', 'Gampang banget! Kalau status pendaftaranmu masih <strong>Menunggu Verifikasi</strong>, kamu cukup lakukan langkah ini:<br><br>1️⃣ Masukkan emailmu di kolom pencarian pada <a href=\'cek_status.php\' style=\'color:var(--accent-gold); font-weight:bold;\'>halaman Cek Status</a>.<br>2️⃣ Klik tombol <strong>Cari Data</strong>.<br>3️⃣ Nanti bakal muncul tombol <strong>Batalkan Pendaftaran</strong> warna merah di bawah keterangan status.<br><br>Klik aja tombol itu, dan data pengajuanmu bakal otomatis terhapus dari sistem deh. 🗑️✨<br><br><a href=\'cek_status.php\' style=\'display:inline-block; padding:8px 15px; background-color:var(--accent-gold); color:#000; text-decoration:none; border-radius:4px; font-weight:bold; font-size:0.8rem; margin-top:10px;\'>➡️ Ke Halaman Cek Status</a>')">❌ Batal Daftar</button>
                <button class="btn-qr" onclick="kirimFaq('Bagaimana cara menghubungi CS?', 'Butuh bantuan lebih lanjut? Tenang, langsung aja chat admin kita via DM Instagram ya.<br><br>Klik link di bawah ini untuk kirim pesan langsung:<br><strong><a href=\'https://ig.me/m/vandagympky_classic\' target=\'_blank\' style=\'color:var(--accent-gold); text-decoration:none;\'>DM Vanda Gym di Instagram 📩</a></strong>')">💬 Hubungi CS</button>
               
                <button class="btn-qr" onclick="kirimFaq('Di mana lokasi Vanda Gym?', 'Vanda Gym Classic berlokasi strategis di <strong>Jl. Kapten Pierre Tendean No.17, Palangka Raya</strong>. 📍<br><br>Cek aja peta Google Maps di bagian paling bawah halaman ini kalau takut nyasar. Ditunggu kedatangannya ya! 🏋️‍♂️')">📍 Info Lokasi</button>
                
                <button class="btn-qr" onclick="kirimFaq('Berapa harga paket membership?', 'Wah, tertarik join ya? Mantap! 🤩<br><br>Untuk Gym Bulanan harganya cuma <strong>Rp <?= number_format($web_data['harga_bulanan'] ?? 175000, 0, ',', '.') ?></strong> (Udah bebas akses!).<br>Kalau mau nyoba dulu, bisa pilih 1x Visit (<strong>Rp <?= number_format($web_data['harga_harian'] ?? 25000, 0, ',', '.') ?></strong>).<br>Ada juga Kelas Senam seharga <strong>Rp <?= number_format($web_data['harga_senam'] ?? 25000, 0, ',', '.') ?>/datang</strong>.<br><br>Langsung aja klik Daftar di menu bawah biar gampang! 🚀')">💰 Harga Membership</button>
                
            </div>
        </div>
    </div>

    <div class="bottom-nav-mobile">
        <a href="index.php" class="nav-item highlight" onclick="window.scrollTo(0,0);">
            <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span>Beranda</span>
        </a>
        <a href="#jadwal" class="nav-item">
            <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            <span>Jadwal</span>
        </a>
        
        <a href="daftar.php" class="nav-item nav-daftar-special">
            <div class="special-bg">
                <svg viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
            </div>
            <span style="color: var(--accent-gold); font-weight: bold;">Daftar</span>
        </a>

        <a href="galeri_gym.php" class="nav-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><path d="M21 15l-5-5L5 21"></path></svg>
            <span>Galeri</span>
        </a>

        <a href="kalkulator.php" class="nav-item">
            <svg viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="8" y1="6" x2="16" y2="6"></line><line x1="16" y1="14" x2="16.01" y2="14"></line><line x1="12" y1="14" x2="12.01" y2="14"></line><line x1="8" y1="14" x2="8.01" y2="14"></line><line x1="16" y1="18" x2="16.01" y2="18"></line><line x1="12" y1="18" x2="12.01" y2="18"></line><line x1="8" y1="18" x2="8.01" y2="18"></line></svg>
            <span>Gizi</span>
        </a>
    </div>

    <script>
        // SCRIPT NAVIGASI DESKTOP & MOBILE HAMBURGER
        const menuToggle = document.getElementById('mobile-menu');
        const navMenu = document.getElementById('nav-menu');

        menuToggle.addEventListener('click', () => {
            menuToggle.classList.toggle('active');
            navMenu.classList.toggle('active');
        });

        // Tutup menu dropdown jika klik di luar
        document.addEventListener('click', function(event) {
            const isClickInside = navMenu.contains(event.target) || menuToggle.contains(event.target);
            if (!isClickInside && navMenu.classList.contains('active')) {
                navMenu.classList.remove('active');
                menuToggle.classList.remove('active');
            }
        });

        // SCRIPT CHATBOT
        function toggleChat() {
            const chat = document.getElementById("chatWindow");
            if (chat.classList.contains("show-chat")) {
                chat.classList.remove("show-chat");
                setTimeout(() => chat.style.display = "none", 300);
            } else {
                chat.style.display = "block";
                setTimeout(() => chat.classList.add("show-chat"), 10);
            }
        }

        function kirimFaq(pertanyaan, jawaban) {
            const body = document.getElementById("chatBody");
            body.innerHTML += '<div class="chat-msg user">' + pertanyaan + '</div><div class="clearfix"></div>';
            body.scrollTop = body.scrollHeight;

            setTimeout(function() {
                body.innerHTML += '<div class="chat-msg" style="border-left: 3px solid var(--accent-gold);">' + jawaban + '</div><div class="clearfix"></div>';
                body.scrollTop = body.scrollHeight;
            }, 600);
        }

        // SCROLL REVEAL ANIMATION
        document.addEventListener('DOMContentLoaded', function() {
            const reveals = document.querySelectorAll('.reveal');
            const revealOptions = { threshold: 0.15, rootMargin: "0px 0px -50px 0px" };

            const revealOnScroll = new IntersectionObserver(function(entries, observer) {
                entries.forEach(entry => {
                    if (!entry.isIntersecting) return;
                    entry.target.classList.add('active');
                    observer.unobserve(entry.target);
                });
            }, revealOptions);

            reveals.forEach(reveal => { revealOnScroll.observe(reveal); });
        });

        // OFFLINE DETECTOR
        window.addEventListener('offline', () => document.getElementById('boxErrorKoneksi').style.display = 'flex');
        window.addEventListener('online', () => document.getElementById('boxErrorKoneksi').style.display = 'none');
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