<?php
session_start();
require 'includes/koneksi.php';

// Ambil Data Pengaturan Web dari Database untuk nomor WA CS
$q_pengaturan = mysqli_query($koneksi, "SELECT wa_cs FROM pengaturan_web WHERE id=1");
$web_data = mysqli_fetch_assoc($q_pengaturan);
$wa_db = $web_data['wa_cs'] ?? '082148556601';
$wa_link = "62" . substr(preg_replace('/[^0-9]/', '', $wa_db), 1);

// Ambil semua data galeri dari database
$q_galeri = mysqli_query($koneksi, "SELECT * FROM galeri_gym ORDER BY id_media DESC");

// Kelompokkan data berdasarkan kategori
$kategori_media = [
    'alat' => [],
    'upper' => [],
    'lower' => []
];

while ($row = mysqli_fetch_assoc($q_galeri)) {
    $kat = $row['kategori'];
    if (array_key_exists($kat, $kategori_media)) {
        $kategori_media[$kat][] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galeri & Tutorial - Vanda Gym Classic</title>
    <style>
        :root {
            --bg-dark: #000000; --primary-red: #8E1616; --accent-gold: #E8C999;
            --text-light: #F8EEDF; --card-bg: #111111;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background-color: var(--bg-dark); color: var(--text-light); display: flex; flex-direction: column; align-items: center; min-height: 100vh; padding: 100px 20px 40px 20px; position: relative; }

        /* =========================================
           NAVIGASI ATAS & HAMBURGER MENU
           ========================================= */
        header {
            position: absolute;
            top: 0; left: 0;
            width: 100%;
            display: flex;
            align-items: center;
            padding: 6px 62px;
            box-sizing: border-box;
            z-index: 1001;
            border-bottom: 2px solid var(--primary-red);
        }

        header .logo img {
            height: 50px;
            object-fit: contain;
        }

        header .menu-toggle {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 8px;
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

        header .menu-toggle.active .bar:nth-child(1) { transform: translateY(12px) rotate(45deg); }
        header .menu-toggle.active .bar:nth-child(2) { opacity: 0; }
        header .menu-toggle.active .bar:nth-child(3) { transform: translateY(-8px) rotate(-45deg); }

        #nav-menu {
            display: none; 
            position: absolute;
            top: 70px; 
            right: 20px;
            left: auto;
            width: 210px;
            box-sizing: border-box;
            background-color: #1a1a1a;
            padding: 12px;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.8);
            border: 1px solid #333;
            flex-direction: column;
            gap: 4px;
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
            display: flex; align-items: center; justify-content: flex-start; gap: 10px;
            width: 100%; box-sizing: border-box; color: #ccc; text-decoration: none;
            font-weight: 600; padding: 8px 10px; border-radius: 6px;
            transition: all 0.3s ease; font-size: 0.85rem;
        }

        #nav-menu a.menu-link:hover {
            background-color: rgba(232, 201, 153, 0.1);
            color: var(--accent-gold);
        }

        #nav-menu .menu-divider {
            height: 1px; background-color: #333; margin: 4px 0; width: 100%;
        }

        .nav-actions { display: flex; flex-direction: column; width: 100%; gap: 8px; margin-top: 4px; }
        
        .nav-actions .nav-login {
            display: block; box-sizing: border-box; padding: 8px 15px; border: 1px solid #555; 
            border-radius: 6px; color: #fff; text-decoration: none; font-weight: bold;
            text-align: center; transition: all 0.3s ease; font-size: 0.85rem;
        }
        
        .nav-actions .nav-login:hover { border-color: #fff; background-color: rgba(255, 255, 255, 0.1); }

        .nav-actions .btn-daftar {
            display: block; box-sizing: border-box; padding: 10px 15px;
            border: 1px solid var(--accent-gold); background-color: var(--accent-gold);
            color: #000; border-radius: 6px; font-weight: bold; cursor: pointer;
            text-align: center; transition: all 0.3s ease; box-shadow: 0 4px 10px rgba(232, 201, 153, 0.3);
            font-size: 0.85rem;
        }
        
        .nav-actions .btn-daftar:hover {
            background-color: transparent; color: var(--accent-gold);
            box-shadow: 0 6px 15px rgba(232, 201, 153, 0.5);
        }

        .galeri-container { background-color: #0a0a0a; border: 1px solid #333; border-top: 4px solid var(--primary-red); border-radius: 8px; padding: 30px; width: 100%; max-width: 1000px; box-shadow: 0 10px 30px rgba(0,0,0,0.8); }

        .nav-top { margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; }
        .btn-back-square { width: 40px; height: 40px; background-color: #1a1a1a; border: 1px solid #333; color: var(--accent-gold); border-radius: 4px; display: flex; align-items: center; justify-content: center; text-decoration: none; font-weight: bold; font-size: 1.2rem; transition: 0.3s; }
        .btn-back-square:hover { background-color: var(--primary-red); color: white; border-color: var(--primary-red); }

        .form-header { text-align: center; margin-bottom: 25px; }
        .form-header h2 { color: var(--text-light); text-transform: uppercase; letter-spacing: 1px; font-size: 1.5rem; margin-bottom: 5px; }
        .form-header p { color: #888; font-size: 0.9rem; }

        .search-box { width: 100%; position: relative; margin-bottom: 20px; }
        .search-box input { width: 100%; padding: 12px 15px 12px 40px; background: #151515; border: 1px solid #333; border-radius: 6px; color: white; outline: none; transition: 0.3s; font-size: 0.95rem; }
        .search-box input:focus { border-color: var(--accent-gold); }
        .search-box svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; fill: #666; }

        /* FILTER KATEGORI */
        .category-filter { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; margin-bottom: 30px; }
        .filter-btn { background: #111; color: #888; border: 1px solid #333; padding: 10px 20px; border-radius: 30px; cursor: pointer; transition: 0.3s; font-weight: bold; font-size: 0.9rem; }
        .filter-btn:hover { border-color: var(--accent-gold); color: var(--text-light); }
        .filter-btn.active { background: var(--accent-gold); color: #000; border-color: var(--accent-gold); }

        /* KATEGORI & GRID */
        .category-section { margin-bottom: 35px; }
        .category-title { color: var(--accent-gold); font-size: 1.1rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; border-bottom: 1px solid #222; padding-bottom: 8px; }
        
        .horizontal-scroll { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 15px; 
            padding-bottom: 5px;
        }

        .gallery-item { 
            width: 100%;
            position: relative; 
            border-radius: 8px; 
            overflow: hidden; 
            background-color: var(--card-bg); 
            border: 1px solid #222; 
            cursor: pointer; 
            aspect-ratio: 4/3; 
            transition: 0.3s; 
        }
        .gallery-item:hover { border-color: var(--accent-gold); transform: translateY(-3px); box-shadow: 0 5px 15px rgba(232, 201, 153, 0.15); }
        .gallery-item img, .gallery-item video { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; pointer-events: none; }
        .gallery-item:hover img, .gallery-item:hover video { transform: scale(1.05); }

        .item-info { position: absolute; bottom: 0; left: 0; width: 100%; background: linear-gradient(transparent, rgba(0,0,0,0.95)); padding: 25px 12px 10px; display: flex; flex-direction: column; gap: 3px; }
        .item-title { font-size: 0.9rem; font-weight: bold; color: white; text-shadow: 1px 1px 2px black; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .play-icon { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 40px; height: 40px; background: rgba(142, 22, 22, 0.8); border-radius: 50%; display: flex; justify-content: center; align-items: center; border: 2px solid var(--text-light); transition: 0.3s; z-index: 2;}
        .gallery-item:hover .play-icon { background: var(--primary-red); transform: translate(-50%, -50%) scale(1.1); }
        .play-icon svg { width: 18px; height: 18px; fill: white; margin-left: 2px; }

        .empty-state { text-align: center; padding: 20px; color: #666; font-style: italic; background: #111; border: 1px dashed #333; border-radius: 8px; width: 100%; display: none; }

        /* TOMBOL LIHAT SELENGKAPNYA */
        .btn-show-more {
            background: #111; color: var(--accent-gold); border: 1px solid #333;
            padding: 8px 20px; border-radius: 30px; cursor: pointer; transition: 0.3s;
            font-weight: bold; font-size: 0.85rem; display: block; margin: 20px auto 0;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        .btn-show-more:hover { background: #1a1a1a; border-color: var(--accent-gold); color: #fff; }

        /* MODAL LIGHTBOX */
        .lightbox { display: none; position: fixed; z-index: 9999; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.92); justify-content: center; align-items: center; padding: 20px; }
        .lightbox.show { display: flex; }
        
        .lightbox-inner { display: flex; flex-direction: row; background: #0a0a0a; border: 1px solid #333; border-radius: 12px; overflow: hidden; width: 100%; max-width: 900px; height: 75vh; position: relative; box-shadow: 0 0 30px rgba(0,0,0,0.8); }
        
        .lightbox-media-area { flex: 1.5; background: #000; display: flex; justify-content: center; align-items: center; border-right: 1px solid #222; overflow: hidden; position: relative; }
        .lightbox-content { width: 100%; height: 100%; object-fit: contain; }

        .lightbox-text-container { flex: 1; padding: 25px; display: flex; flex-direction: column; overflow-y: auto; background: #111; }
        .lightbox-text-container::-webkit-scrollbar { width: 5px; }
        .lightbox-text-container::-webkit-scrollbar-thumb { background: #444; border-radius: 3px; }

        .lightbox-title { color: var(--accent-gold); margin-top: 0; font-size: 1.2rem; font-weight: bold; text-transform: uppercase; margin-bottom: 15px; border-bottom: 1px dashed #333; padding-bottom: 12px; }
        .lightbox-caption { color: #ddd; font-size: 0.9rem; line-height: 1.6; white-space: pre-wrap; word-wrap: break-word; }

        .lightbox-close { position: absolute; top: 10px; right: 15px; color: white; font-size: 30px; font-weight: bold; cursor: pointer; transition: 0.3s; z-index: 10000; text-shadow: 0 0 5px black; }
        .lightbox-close:hover { color: var(--primary-red); transform: scale(1.1); }

        /* Sembunyikan navigasi bawah di desktop */
        .bottom-nav-mobile { display: none !important; }

        /* =========================================
           TOMBOL WA & CHATBOT MENGAMBANG
           ========================================= */
        .wa-btn {
            position: fixed; bottom: 30px; left: 30px; width: 55px; height: 55px;
            background-color: #25D366; color: white; border-radius: 50%;
            display: flex; justify-content: center; align-items: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.5); z-index: 9000; transition: 0.3s; text-decoration: none;
        }
        .wa-btn:hover { transform: scale(1.1); background-color: #1ebe57; color: white; }

        .chatbot-btn {
            position: fixed; bottom: 30px; right: 30px; width: 55px; height: 55px;
            background-color: var(--primary-red); color: white; border-radius: 50%; border: none;
            display: flex; justify-content: center; align-items: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.5); z-index: 9000; cursor: pointer; transition: 0.3s;
        }
        .chatbot-btn:hover { transform: scale(1.1); background-color: #b01c1c; }

        .chatbot-window {
            position: fixed; bottom: 95px; right: 30px; width: 330px; max-height: 450px;
            background-color: #0a0a0a; border: 1px solid #333; border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.8); display: none; flex-direction: column; z-index: 9000;
            overflow: hidden;
        }
        .chat-header {
            background-color: var(--primary-red); color: white; padding: 12px 15px;
            font-weight: bold; display: flex; justify-content: space-between; align-items: center;
            font-size: 0.95rem; border-bottom: 1px solid #333;
        }
        .close-chat {
            background: none; border: none; color: white; font-size: 1.5rem; line-height: 1;
            cursor: pointer; transition: 0.3s;
        }
        .close-chat:hover { color: var(--accent-gold); }
        .chat-body {
            padding: 15px; height: 260px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; background-color: #111;
        }
        .chat-body::-webkit-scrollbar { width: 4px; }
        .chat-body::-webkit-scrollbar-thumb { background: #444; border-radius: 2px; }
        
        .chat-msg {
            background-color: #1a1a1a; color: #fff; padding: 10px 14px; border-radius: 8px;
            font-size: 0.85rem; line-height: 1.5; max-width: 85%; align-self: flex-start;
            border-bottom-left-radius: 0;
        }
        .chat-msg.user {
            background-color: var(--accent-gold); color: #000; align-self: flex-end;
            border-bottom-left-radius: 8px; border-bottom-right-radius: 0; font-weight: bold;
        }
        .chat-footer-menu {
            padding: 15px; background-color: #0a0a0a; border-top: 1px solid #222;
        }
        
        /* Slider horizontal untuk Quick Replies Chatbot (Desktop & Mobile) */
        .quick-replies { 
            display: flex; 
            flex-wrap: nowrap; /* Paksa tombol berjejer menyamping */
            overflow-x: auto; /* Aktifkan scroll horizontal */
            overflow-y: hidden;
            gap: 8px; /* Jarak antar tombol sedikit diperlebar di PC */
            padding-bottom: 8px; /* Ruang untuk scrollbar */
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin; /* Firefox */
            scrollbar-color: var(--accent-gold) transparent;
        }

        /* Styling Scrollbar Webkit (Chrome, Edge, Safari) */
        .quick-replies::-webkit-scrollbar { height: 6px; }
        .quick-replies::-webkit-scrollbar-track { background: #1a1a1a; border-radius: 4px; }
        .quick-replies::-webkit-scrollbar-thumb { background: var(--accent-gold); border-radius: 4px; }
        
        .btn-qr {
            background-color: #1a1a1a; color: var(--accent-gold); border: 1px solid #333;
            padding: 8px 12px; border-radius: 20px; font-size: 0.8rem; cursor: pointer; transition: 0.3s; text-align: left;
            flex: 0 0 auto; /* Mencegah tombol menyusut */
            white-space: nowrap; /* Mencegah teks turun ke bawah */
        }
        .btn-qr:hover { background-color: var(--accent-gold); color: #000; border-color: var(--accent-gold); }
        /* =========================================
           KOTAK OFFLINE DETECTOR
           ========================================= */
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
        
        /* RESPONSIVE UNTUK HP */
        @media (max-width: 768px) {
            body { padding: 85px 25px 85px 25px; } /* 85px atas untuk header, 85px bawah untuk nav */

            /* Penyesuaian Header Mobile */
            header { padding: 7px 20px; }
            header .menu-toggle { width: 36px; height: 36px; gap: 4px; }
            header .menu-toggle .bar { width: 16px; }

            #nav-menu { 
                top: 55px; gap: 3px; right: 10px; left: auto;
                width: 185px; box-sizing: border-box; padding: 10px;
            }
            #nav-menu a.menu-link { font-size: 0.75rem; padding: 5px 6px; white-space: nowrap; }
            .nav-actions .nav-login, .nav-actions .btn-daftar { padding: 6px 8px; font-size: 0.75rem; }
            .nav-actions .btn-daftar { width: 100%; }

            header .menu-toggle.active .bar:nth-child(1) { transform: translateY(8px) rotate(45deg); }
            header .menu-toggle.active .bar:nth-child(2) { opacity: 0; }
            header .menu-toggle.active .bar:nth-child(3) { transform: translateY(-4px) rotate(-45deg); }

            .galeri-container { padding: 10px; }
            .btn-back-square { width: 32px; height: 32px; font-size: 1rem; }
            .form-header h2 { font-size: 1.05rem; }
            .form-header p { font-size: 0.7rem; }
            
            .search-box input { padding: 8px 12px 8px 30px; font-size: 0.8rem; }
            .search-box svg { width: 16px; height: 16px; }
            
            .category-filter { gap: 5px; margin-bottom: 14px; }
            .filter-btn { padding: 6px 10px; font-size: 0.7rem; }

            .category-title { font-size: 0.8rem; margin-bottom: 8px; padding-bottom: 6px; }
            
            .horizontal-scroll { grid-template-columns: repeat(3, 1fr); gap: 6px; }
            .gallery-item { border-radius: 6px; }
            .item-info { padding: 10px 6px 6px; }
            .item-title { font-size: 0.6rem; }
            .play-icon { width: 24px; height: 24px; }
            .play-icon svg { width: 12px; height: 12px; }

            .lightbox { padding: 10px; }
            .lightbox-inner { flex-direction: row; height: 60vh; border-radius: 8px; }
            .lightbox-media-area { flex: 1.2; border-right: 1px solid #222; }
            .lightbox-text-container { flex: 1; padding: 12px; }
            .lightbox-title { font-size: 0.9rem; margin-bottom: 8px; padding-bottom: 8px; }
            .lightbox-caption { font-size: 0.75rem; line-height: 1.4; }
            .lightbox-close { top: 5px; right: 10px; font-size: 24px; }

            /* Tombol DM IG disembunyikan total di mode mobile */
            .wa-btn { display: none !important; }
            .chatbot-btn { bottom: 85px !important; right: 15px !important; width: 45px !important; height: 45px !important; }
            .chatbot-btn svg { width: 22px !important; height: 22px !important; }
            
            .chatbot-window { bottom: 140px !important; right: 15px !important; left: 15px !important; width: auto !important; max-height: 60vh !important; }

            /* Perkecil tampilan Chatbot untuk mode mobile */
.chatbot-btn { width: 42px !important; height: 42px !important; }
.chatbot-btn svg { width: 20px !important; height: 20px !important; }

.chatbot-window {
    right: 15px !important;
    left: auto !important;
    width: 85vw !important;
    max-width: 270px !important;
}

.chat-header { padding: 8px 10px; font-size: 0.8rem; }
.chat-header svg { width: 14px; height: 14px; }
.close-chat { font-size: 1.2rem; }

.chat-body { padding: 10px; height: 200px; gap: 6px; }
.chat-msg { padding: 7px 10px; font-size: 0.6rem; line-height: 1.4; max-width: 70%; }

.chat-footer-menu { padding: 10px; }
/* Slider horizontal untuk Quick Replies Chatbot */
.quick-replies {
    display: flex;
    flex-wrap: nowrap;
    overflow-x: auto;
    overflow-y: hidden;
    gap: 6px;
    padding-bottom: 6px;
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    scrollbar-color: var(--accent-gold) transparent;
}

.quick-replies::-webkit-scrollbar {
    height: 4px;
}
.quick-replies::-webkit-scrollbar-track {
    background: transparent;
}
.quick-replies::-webkit-scrollbar-thumb {
    background: var(--accent-gold);
    border-radius: 4px;
}

.btn-qr {
    flex: 0 0 auto;
    white-space: nowrap;
    font-size: 9px;
}

            /* =========================================
               NAVIGASI BAWAH MOBILE (STANDAR)
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
            <a href="index.php" class="menu-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
        <polyline points="9 22 9 12 15 12 15 22"></polyline>
    </svg>Beranda
            </a>
            <a href="galeri_gym.php" class="menu-link" style="color: var(--accent-gold);">
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
    </header>

    <div class="galeri-container">
        <div class="nav-top">
            <a href="index.php" class="btn-back-square" title="Kembali">←</a>
            <span style="color: #666; font-size: 0.75rem; font-weight: bold; text-transform: uppercase;">Galeri Vanda Gym</span>
        </div>

        <div class="form-header">
            <h2>Galeri <span style="color:var(--accent-gold)">&</span> Tutorial</h2>
            <p>Kenali fasilitas alat & pelajari posisi otot yang benar</p>
        </div>

        <div class="search-box">
            <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
            <input type="text" id="searchInput" placeholder="Cari alat atau gerakan..." onkeyup="jalankanFilter()">
        </div>

        <div class="category-filter">
            <button class="filter-btn active" onclick="pilihKategori('semua', this)">Semua Kategori</button>
            <button class="filter-btn" onclick="pilihKategori('alat', this)">Alat Gym</button>
            <button class="filter-btn" onclick="pilihKategori('upper', this)">Upper Body</button>
            <button class="filter-btn" onclick="pilihKategori('lower', this)">Lower Body</button>
        </div>

        <div class="category-section" id="sec-alat">
            <h3 class="category-title">Fasilitas & Alat Gym</h3>
            <?php if(empty($kategori_media['alat'])): ?>
                <div class="empty-state" style="display:block;">Belum ada data alat.</div>
            <?php else: ?>
                <div class="horizontal-scroll">
                    <?php foreach($kategori_media['alat'] as $m): renderGalleryItem($m); endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="category-section" id="sec-upper">
            <h3 class="category-title">Tutorial Upper Body</h3>
            <?php if(empty($kategori_media['upper'])): ?>
                <div class="empty-state" style="display:block;">Belum ada tutorial upper body.</div>
            <?php else: ?>
                <div class="horizontal-scroll">
                    <?php foreach($kategori_media['upper'] as $m): renderGalleryItem($m); endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="category-section" id="sec-lower">
            <h3 class="category-title">Tutorial Lower Body</h3>
            <?php if(empty($kategori_media['lower'])): ?>
                <div class="empty-state" style="display:block;">Belum ada tutorial lower body.</div>
            <?php else: ?>
                <div class="horizontal-scroll">
                    <?php foreach($kategori_media['lower'] as $m): renderGalleryItem($m); endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <div id="mediaLightbox" class="lightbox" onclick="tutupMedia(event)">
        <div class="lightbox-inner" id="lightboxInner">
            <span class="lightbox-close" title="Tutup" onclick="tutupLewatTombol()">&times;</span>
            
            <div class="lightbox-media-area" id="lightboxContainer"></div>
            
            <div class="lightbox-text-container" id="lightboxTextContainer">
                <div id="lightboxTitle" class="lightbox-title">Judul Media</div>
                <div id="lightboxCaption" class="lightbox-caption"></div>
            </div>
        </div>
    </div>

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

    <button class="chatbot-btn" onclick="toggleChat()" title="Tanya Asisten Galeri">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="10" rx="2"></rect>
            <circle cx="12" cy="5" r="2"></circle>
            <path d="M12 7v4"></path>
            <line x1="8" y1="16" x2="8.01" y2="16"></line>
            <line x1="16" y1="16" x2="16.01" y2="16"></line>
        </svg>
    </button>
    
    <div class="chatbot-window" id="chatWindow">
        <div class="chat-header">
            <span style="display: flex; align-items: center; gap: 8px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="10" rx="2"></rect>
                    <circle cx="12" cy="5" r="2"></circle>
                    <path d="M12 7v4"></path>
                    <line x1="8" y1="16" x2="8.01" y2="16"></line>
                    <line x1="16" y1="16" x2="16.01" y2="16"></line>
                </svg>
                Asisten Galeri Gym
            </span>
            <button class="close-chat" onclick="toggleChat()" title="Tutup Chat">×</button>
        </div>
        <div class="chat-body" id="chatBody">
            <div class="chat-msg">
                Halo! 👋 Selamat datang di halaman Galeri & Tutorial Vanda Gym.<br><br>Pasti bingung ya mau lihat bagian mana dulu? Yuk, pilih info yang kamu butuhkan di bawah ini! 💪
            </div>
        </div>
        <div class="chat-footer-menu">
            <div class="quick-replies">
                <button class="btn-qr" onclick="kirimFaq('Bagaimana cara pakai alat gym?', 'Gampang banget! Kamu bisa cari alat yang pengen kamu pakai di kotak pencarian atas, atau klik tombol filter kategori <b>Alat Gym</b>.<br><br>Klik foto/videonya untuk melihat detail dan fungsinya ya! 🏋️‍♂️')">🏋️ Cara Pakai Alat</button>
                
                <button class="btn-qr" onclick="kirimFaq('Apa bedanya Upper & Lower Body?', 'Biar jadwal latihanmu terstruktur, tutorialnya kita bagi dua nih:<br><br>🔹 <b>Upper Body:</b> Untuk melatih otot atas (Dada, Punggung, Bahu, Tangan).<br>🔹 <b>Lower Body:</b> Untuk melatih kaki (Paha, Betis, Bokong).<br><br>Sesuaikan sama jadwal harianmu ya! 🔥')">🦾 Upper vs Lower Body</button>
                
                <button class="btn-qr" onclick="kirimFaq('Keterangan target otot di mana?', 'Coba deh kamu klik salah satu video tutorial di layar! Nanti videonya akan membesar, nah keterangan target otot dan cara ambil nafas yang benar ada di bagian teks sebelah kanannya. 💡')">🎯 Keterangan Target Otot</button>
                
                <button class="btn-qr" onclick="kirimFaq('Bisa minta diajarin langsung?', 'Tentu dong! Kalau kamu masih ragu sama <i>form</i> (posisi tubuh) alat tertentu, jangan segan buat panggil instruktur/admin yang lagi jaga di Gym ya.<br><br>Atau mau tanya-tanya CS via DM Instagram sekarang? Klik aja tombol di pojok kiri bawah layar atau klik link di bawah ini untuk kirim pesan langsung:<br><strong><a href=\'https://ig.me/m/vandagympky_classic\' target=\'_blank\' style=\'color:var(--accent-gold); text-decoration:none;\'>DM Vanda Gym di Instagram 📩</a></strong>')">🗣️ Minta Bimbingan Langsung</button>
            </div>
        </div>
    </div>

    <div class="bottom-nav-mobile">
        <a href="index.php" class="nav-item" onclick="window.scrollTo(0,0);">
            <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span>Beranda</span>
        </a>
        <!-- Ubah dari href="#jadwal" menjadi href="index.php#jadwal" -->
<a href="index.php#jadwal" class="nav-item">
    <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
    <span>Jadwal</span>
</a>
        
        
        <a href="daftar.php" class="nav-item nav-daftar-special">
            <div class="special-bg">
                <svg viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
            </div>
            <span>Daftar</span>
        </a>
        <!-- Tombol Galeri Baru -->
        <a href="galeri_gym.php" class="nav-item highlight">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><path d="M21 15l-5-5L5 21"></path></svg>
            <span>Galeri</span>
        </a>

        <a href="kalkulator.php" class="nav-item">
            <svg viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="8" y1="6" x2="16" y2="6"></line><line x1="16" y1="14" x2="16.01" y2="14"></line><line x1="12" y1="14" x2="12.01" y2="14"></line><line x1="8" y1="14" x2="8.01" y2="14"></line><line x1="16" y1="18" x2="16.01" y2="18"></line><line x1="12" y1="18" x2="12.01" y2="18"></line><line x1="8" y1="18" x2="8.01" y2="18"></line></svg>
            <span>Gizi</span>
        </a>
    </div>

    <?php 
    function renderGalleryItem($m) { 
    ?>
        <div class="gallery-item" 
             data-judul="<?= strtolower(htmlspecialchars($m['judul'])) ?>"
             data-judul-asli="<?= htmlspecialchars($m['judul']) ?>"
             data-path="<?= $m['file_path'] ?>"
             data-tipe="<?= $m['tipe_media'] ?>"
             data-caption="<?= htmlspecialchars($m['caption'] ?? '') ?>"
             onclick="bukaMedia(this)">
             
            <?php if($m['tipe_media'] == 'video'): ?>
                <video src="<?= $m['file_path'] ?>#t=0.1" preload="metadata" muted playsinline></video>
                <div class="play-icon"><svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></div>
            <?php else: ?>
                <img src="<?= $m['file_path'] ?>" loading="lazy" alt="<?= htmlspecialchars($m['judul']) ?>">
            <?php endif; ?>
            
            <div class="item-info">
                <span class="item-title" title="<?= htmlspecialchars($m['judul']) ?>"><?= htmlspecialchars($m['judul']) ?></span>
            </div>
        </div>
    <?php } ?>

    <script>
        // SCRIPT NAVIGASI HAMBURGER MENU
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

        // SCRIPT OFFLINE DETECTOR
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

        const maxItems = 8; 
        let filterKategoriSaatIni = 'semua'; 

        document.addEventListener('DOMContentLoaded', () => {
            initShowMore(); 
        });

        // ===============================================
        // FUNGSI CHATBOT
        // ===============================================
        function toggleChat() {
            const chat = document.getElementById("chatWindow");
            chat.style.display = (chat.style.display === "flex") ? "none" : "flex";
        }

        function kirimFaq(pertanyaan, jawaban) {
            const body = document.getElementById("chatBody");

            // Tampilkan Pesan User
            body.innerHTML += '<div class="chat-msg user">' + pertanyaan + '</div>';
            body.scrollTop = body.scrollHeight;

            // Tunda sedikit seolah-olah Bot sedang mengetik
            setTimeout(function() {
                body.innerHTML += '<div class="chat-msg" style="border-left: 3px solid var(--accent-gold);">' + jawaban + '</div>';
                body.scrollTop = body.scrollHeight;
            }, 600);
        }

        // ===============================================
        // FUNGSI FILTER KATEGORI & SHOW MORE
        // ===============================================
        function pilihKategori(kat, btnElement) {
            filterKategoriSaatIni = kat;
            
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            btnElement.classList.add('active');

            jalankanFilter(); 
        }

        function jalankanFilter() {
            const searchText = document.getElementById('searchInput').value.toLowerCase();
            const sections = document.querySelectorAll('.category-section');
            const isSearching = searchText.trim() !== '';
            
            sections.forEach(section => {
                const sectionId = section.id;
                const isMatchCategory = (filterKategoriSaatIni === 'semua' || sectionId === 'sec-' + filterKategoriSaatIni);
                
                if (!isMatchCategory) {
                    section.style.display = 'none';
                    return; 
                }
                
                section.style.display = 'block'; 

                if (isSearching) {
                    let hasVisibleItems = false;
                    const items = section.querySelectorAll('.gallery-item');
                    
                    const btnMore = section.querySelector('.btn-show-more');
                    if (btnMore) btnMore.style.display = 'none';
                    
                    items.forEach(item => {
                        const judul = item.getAttribute('data-judul');
                        if (judul.includes(searchText)) {
                            item.style.display = 'block';
                            hasVisibleItems = true;
                        } else {
                            item.style.display = 'none';
                        }
                    });
                    
                    const emptyState = section.querySelector('.empty-state');
                    const scrollArea = section.querySelector('.horizontal-scroll');
                    
                    if (scrollArea) {
                        if (hasVisibleItems) {
                            scrollArea.style.display = 'grid'; 
                            if(emptyState) emptyState.style.display = 'none';
                        } else {
                            scrollArea.style.display = 'none';
                            if(emptyState) {
                                emptyState.innerText = "Tidak ditemukan pencarian di kategori ini.";
                                emptyState.style.display = 'block';
                            }
                        }
                    }
                }
            });

            if (!isSearching) {
                initShowMore();
                
                sections.forEach(section => {
                    const emptyState = section.querySelector('.empty-state');
                    const scrollArea = section.querySelector('.horizontal-scroll');
                    const items = section.querySelectorAll('.gallery-item');
                    
                    if (items.length === 0 && emptyState) {
                        emptyState.innerText = "Belum ada data di kategori ini.";
                        emptyState.style.display = 'block';
                        if (scrollArea) scrollArea.style.display = 'none';
                    } else if (items.length > 0 && emptyState) {
                        emptyState.style.display = 'none';
                        if (scrollArea) scrollArea.style.display = 'grid';
                    }
                });
            }
        }

        function initShowMore() {
            const sections = document.querySelectorAll('.category-section');
            
            sections.forEach(section => {
                const items = section.querySelectorAll('.gallery-item');
                
                const oldBtn = section.querySelector('.btn-show-more');
                if (oldBtn) oldBtn.remove();

                if (items.length > maxItems) {
                    items.forEach((item, index) => {
                        if (index >= maxItems) {
                            item.style.display = 'none';
                        } else {
                            item.style.display = 'block';
                        }
                    });

                    const btn = document.createElement('button');
                    btn.className = 'btn-show-more';
                    btn.innerText = 'Lihat Selengkapnya ▼';
                    
                    let isExpanded = false; 
                    
                    btn.onclick = () => {
                        isExpanded = !isExpanded;
                        
                        items.forEach((item, index) => {
                            if (index >= maxItems) {
                                item.style.display = isExpanded ? 'block' : 'none';
                            }
                        });

                        if (isExpanded) {
                            btn.innerText = 'Lebih Sedikit ▲';
                        } else {
                            btn.innerText = 'Lihat Selengkapnya ▼';
                            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    };
                    
                    section.appendChild(btn);
                } else {
                    items.forEach(item => item.style.display = 'block');
                }
            });
        }

        // ===============================================
        // FUNGSI LIGHTBOX
        // ===============================================
        function bukaMedia(element) {
            const path = element.getAttribute('data-path');
            const tipe = element.getAttribute('data-tipe');
            const judul = element.getAttribute('data-judul-asli');
            const caption = element.getAttribute('data-caption');

            const container = document.getElementById('lightboxContainer');
            const textContainer = document.getElementById('lightboxTextContainer');
            
            document.getElementById('lightboxTitle').innerText = judul;
            
            const captionBox = document.getElementById('lightboxCaption');
            if(caption && caption.trim() !== '') {
                captionBox.innerText = caption;
                textContainer.style.display = 'flex';
            } else {
                captionBox.innerText = 'Belum ada keterangan target otot atau posisi gerakan.';
                textContainer.style.display = 'flex'; 
            }
            
            if(tipe === 'video') {
                container.innerHTML = `<video src="${path}" class="lightbox-content" controls autoplay muted playsinline></video>`;
            } else {
                container.innerHTML = `<img src="${path}" class="lightbox-content">`;
            }
            
            document.getElementById('mediaLightbox').classList.add('show');
        }

        function tutupMedia(e) {
            const modal = document.getElementById('mediaLightbox');
            // Mencegah modal tertutup jika pengguna mengklik area teks atau media
            if (e.target.id === 'mediaLightbox') {
                tutupProses(modal);
            }
        }

        function tutupLewatTombol() {
            tutupProses(document.getElementById('mediaLightbox'));
        }

        function tutupProses(modal) {
            modal.classList.remove('show');
            document.getElementById('lightboxContainer').innerHTML = '';
        }
    </script>
</body>
</html>