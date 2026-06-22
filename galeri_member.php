<?php
session_start();
require 'includes/koneksi.php';

// Proteksi: Hanya Member yang boleh mengakses halaman ini
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'member') {
    header("Location: login.php");
    exit;
}

$id_user = str_pad($_SESSION['id_user'], 4, '0', STR_PAD_LEFT);

// Cek status membership
$q_member = mysqli_query($koneksi, "SELECT status FROM membership WHERE id_user = {$_SESSION['id_user']} ORDER BY id_membership DESC LIMIT 1");
$d_member = mysqli_fetch_assoc($q_member);
$status_member = $d_member['status'] ?? 'belum_daftar';

// Cek peringatan perpanjangan untuk lonceng
$q_membership_full = mysqli_query($koneksi, "SELECT tgl_berakhir, status FROM membership WHERE id_user = {$_SESSION['id_user']} AND status = 'aktif' ORDER BY id_membership DESC LIMIT 1");
$d_membership_full  = mysqli_fetch_assoc($q_membership_full);
$peringatan_merah   = false;
$sedang_perpanjang  = false;

if ($d_membership_full) {
    $sisa_hari = max(0, round((strtotime($d_membership_full['tgl_berakhir']) - time()) / 86400));
    if ($sisa_hari <= 7) $peringatan_merah = true;
}
$cek_pending = mysqli_query($koneksi, "SELECT id_membership FROM membership WHERE id_user = {$_SESSION['id_user']} AND status = 'pending' AND jenis_pengajuan = 'perpanjang' LIMIT 1");

// Pastikan query berhasil ($cek_pending mengembalikan objek) sebelum menghitung baris
if ($cek_pending && mysqli_num_rows($cek_pending) > 0) {
    $sedang_perpanjang = true;
}

// Ambil semua data galeri dari database
$q_galeri = mysqli_query($koneksi, "SELECT * FROM galeri_gym ORDER BY id_media DESC");
$kategori_media = ['alat' => [], 'upper' => [], 'lower' => []];
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
    <style>:root {
            --bg-dark: #000000; --primary-red: #8E1616; --accent-gold: #E8C999;
            --text-light: #F8EEDF; --card-bg: #111111; --success-green: #28a745;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-light);
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            padding: 100px 20px 40px 20px;
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
            padding: 10px 5%;
            box-sizing: border-box;
            z-index: 1001;
            border-bottom: 2px solid var(--primary-red);
            background-color: rgba(10, 10, 10, 0.98);
            height: 70px;
        }

        header .logo img { height: 50px; object-fit: contain; }

        /* HEADER RIGHT: lonceng + hamburger */
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
            width: 40px; height: 40px;
            border-radius: 4px;
            transition: 0.3s;
            text-decoration: none;
        }
        .bell-icon:hover { color: var(--text-light); }
        .bell-icon.active { color: var(--text-light); animation: ring 2s infinite ease-in-out; }
        .bell-badge {
            position: absolute; top: 4px; right: 4px;
            background: var(--primary-red); color: white;
            font-size: 9px; /* Dikurangi jika awalnya px, walau kamu minta rem, ini saya sesuaikan proporsinya jika perlu, tapi fokus utamanya yang pakai rem di bawah */
            font-weight: bold;
            width: 14px; height: 14px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
        }

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
            width: 42px; height: 42px;
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
            width: 20px; height: 2px;
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
            position: fixed;
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
            display: flex; align-items: center; justify-content: flex-start; gap: 10px;
            width: 100%; box-sizing: border-box;
            color: #ccc; text-decoration: none;
            font-weight: 600; padding: 8px 10px; border-radius: 6px;
            transition: all 0.3s ease; font-size: 0.75rem; /* Awalnya 0.85rem -> 0.75rem */
            text-transform: none; letter-spacing: normal; margin-left: 0; min-height: auto;
        }
        #nav-menu a.menu-link:hover { background-color: rgba(232,201,153,0.1); color: var(--accent-gold); }
        #nav-menu a.menu-link.active-link { color: var(--accent-gold); background-color: rgba(232,201,153,0.07); }
        #nav-menu a.menu-link.locked-link { opacity: 0.45; cursor: not-allowed; pointer-events: none; }

        .menu-divider { height: 1px; background-color: #333; margin: 4px 0; width: 100%; }

        .nav-actions-dasbor { display: flex; flex-direction: column; width: 100%; gap: 8px; margin-top: 4px; }
        .nav-actions-dasbor .btn-keluar-menu {
            display: block; box-sizing: border-box;
            padding: 9px 15px;
            border: 1px solid var(--primary-red);
            background-color: transparent;
            color: var(--primary-red);
            border-radius: 6px; font-weight: bold; cursor: pointer;
            text-align: center; transition: all 0.3s ease; font-size: 0.75rem; /* Awalnya 0.85rem -> 0.75rem */ text-decoration: none;
        }
        .nav-actions-dasbor .btn-keluar-menu:hover { background-color: var(--primary-red); color: white; }

        /* =========================================
           ANNOUNCEMENT BANNER
           ========================================= */
        .announcement-banner {
            width: 100%;
            background-color: #1a1a1a; border-bottom: 1px solid #333;
            color: var(--text-light);
            padding: 12px 20px; text-align: center; font-size: 0.9rem; /* Awalnya 1rem -> 0.9rem */
            display: flex; justify-content: center; align-items: center; gap: 12px;
            position: fixed; top: 70px; left: 0; z-index: 999;
        }
        .announcement-badge {
            background-color: var(--primary-red); color: white;
            padding: 4px 10px; border-radius: 4px; font-weight: bold;
            font-size: 0.7rem; /* Awalnya 0.8rem -> 0.7rem */ text-transform: uppercase;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%   { box-shadow: 0 0 0 0 rgba(142,22,22,0.7); }
            70%  { box-shadow: 0 0 0 8px rgba(142,22,22,0); }
            100% { box-shadow: 0 0 0 0 rgba(142,22,22,0); }
        }

        /* =========================================
           KONTEN GALERI
           ========================================= */
        .galeri-container {
            background-color: #0a0a0a;
            border: 1px solid #333; border-top: 4px solid var(--primary-red);
            border-radius: 8px; padding: 30px;
            width: 100%; max-width: 1000px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.8);
        }

        .nav-top { margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; }
        .btn-back-square {
            width: 40px; height: 40px; background-color: #1a1a1a; border: 1px solid #333;
            color: var(--accent-gold); border-radius: 4px; display: flex; align-items: center;
            justify-content: center; text-decoration: none; font-weight: bold; font-size: 1.1rem; /* Awalnya 1.2rem -> 1.1rem */ transition: 0.3s;
        }
        .btn-back-square:hover { background-color: var(--primary-red); color: white; border-color: var(--primary-red); }

        .form-header { text-align: center; margin-bottom: 25px; }
        .form-header h2 { color: var(--text-light); text-transform: uppercase; letter-spacing: 1px; font-size: 1.4rem; /* Awalnya 1.5rem -> 1.4rem */ margin-bottom: 5px; }
        .form-header p { color: #888; font-size: 0.8rem; /* Awalnya 0.9rem -> 0.8rem */ }

        .search-box { width: 100%; position: relative; margin-bottom: 20px; }
        .search-box input {
            width: 100%; padding: 12px 15px 12px 40px;
            background: #151515; border: 1px solid #333; border-radius: 6px;
            color: white; outline: none; transition: 0.3s; font-size: 0.85rem; /* Awalnya 0.95rem -> 0.85rem */
        }
        .search-box input:focus { border-color: var(--accent-gold); }
        .search-box svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; fill: #666; }

        /* FILTER KATEGORI */
        .category-filter { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; margin-bottom: 30px; }
        .filter-btn { background: #111; color: #888; border: 1px solid #333; padding: 10px 20px; border-radius: 30px; cursor: pointer; transition: 0.3s; font-weight: bold; font-size: 0.8rem; /* Awalnya 0.9rem -> 0.8rem */ }
        .filter-btn:hover { border-color: var(--accent-gold); color: var(--text-light); }
        .filter-btn.active { background: var(--accent-gold); color: #000; border-color: var(--accent-gold); }

        /* GRID MEDIA */
        .category-section { margin-bottom: 35px; }
        .category-title { color: var(--accent-gold); font-size: 1rem; /* Awalnya 1.1rem -> 1rem */ text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; border-bottom: 1px solid #222; padding-bottom: 8px; }

        .horizontal-scroll {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 15px;
            padding-bottom: 5px;
        }

        .gallery-item {
            width: 95%; position: relative; border-radius: 8px; overflow: hidden;
            background-color: var(--card-bg); border: 1px solid #222;
            cursor: pointer; aspect-ratio: 4/3; transition: 0.3s;
        }
        .gallery-item:hover { border-color: var(--accent-gold); transform: translateY(-3px); box-shadow: 0 5px 15px rgba(232,201,153,0.15); }
        .gallery-item img, .gallery-item video { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; pointer-events: none; }
        .gallery-item:hover img, .gallery-item:hover video { transform: scale(1.05); }

        .item-info { position: absolute; bottom: 0; left: 0; width: 100%; background: linear-gradient(transparent, rgba(0,0,0,0.95)); padding: 25px 12px 10px; display: flex; flex-direction: column; gap: 3px; }
        .item-title { font-size: 0.8rem; /* Awalnya 0.9rem -> 0.8rem */ font-weight: bold; color: white; text-shadow: 1px 1px 2px black; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .play-icon { position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%); width: 40px; height: 40px; background: rgba(142,22,22,0.8); border-radius: 50%; display: flex; justify-content: center; align-items: center; border: 2px solid var(--text-light); transition: 0.3s; z-index: 2; }
        .gallery-item:hover .play-icon { background: var(--primary-red); transform: translate(-50%,-50%) scale(1.1); }
        .play-icon svg { width: 18px; height: 18px; fill: white; margin-left: 2px; }

        .empty-state { text-align: center; padding: 20px; color: #666; font-style: italic; background: #111; border: 1px dashed #333; border-radius: 8px; width: 100%; display: none; }

        .btn-show-more {
            background: #111; color: var(--accent-gold); border: 1px solid #333;
            padding: 8px 20px; border-radius: 30px; cursor: pointer; transition: 0.3s;
            font-weight: bold; font-size: 0.75rem; /* Awalnya 0.85rem -> 0.75rem */ display: block; margin: 20px auto 0;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        .btn-show-more:hover { background: #1a1a1a; border-color: var(--accent-gold); color: #fff; }

        /* =========================================
           MODAL LIGHTBOX
           ========================================= */
        .lightbox { display: none; position: fixed; z-index: 9999; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.92); justify-content: center; align-items: center; padding: 20px; }
        .lightbox.show { display: flex; }

        .lightbox-inner { display: flex; flex-direction: row; background: #0a0a0a; border: 1px solid #333; border-radius: 12px; overflow: hidden; width: 100%; max-width: 900px; height: 75vh; position: relative; box-shadow: 0 0 30px rgba(0,0,0,0.8); }

        .lightbox-media-area { flex: 1.5; background: #000; display: flex; justify-content: center; align-items: center; border-right: 1px solid #222; overflow: hidden; position: relative; }
        .lightbox-content { width: 100%; height: 100%; object-fit: contain; }

        .lightbox-text-container { flex: 1; padding: 25px; display: flex; flex-direction: column; overflow-y: auto; background: #111; }
        .lightbox-text-container::-webkit-scrollbar { width: 5px; }
        .lightbox-text-container::-webkit-scrollbar-thumb { background: #444; border-radius: 3px; }

        .lightbox-title { color: var(--accent-gold); margin-top: 0; font-size: 1.1rem; /* Awalnya 1.2rem -> 1.1rem */ font-weight: bold; text-transform: uppercase; margin-bottom: 15px; border-bottom: 1px dashed #333; padding-bottom: 12px; }
        .lightbox-caption { color: #ddd; font-size: 0.8rem; /* Awalnya 0.9rem -> 0.8rem */ line-height: 1.6; white-space: pre-wrap; word-wrap: break-word; }

        .lightbox-close { position: absolute; top: 10px; right: 15px; color: white; font-size: 30px; font-weight: bold; cursor: pointer; transition: 0.3s; z-index: 10000; text-shadow: 0 0 5px black; }
        .lightbox-close:hover { color: var(--primary-red); transform: scale(1.1); }

        /* =========================================
           FLOATING BUTTONS (PC)
           ========================================= */
        @keyframes floatGemes {
            0%   { transform: translateY(0px); }
            50%  { transform: translateY(-8px); }
            100% { transform: translateY(0px); }
        }
        .wa-btn, .chatbot-btn { animation: floatGemes 3s ease-in-out infinite; }
        .wa-btn:hover, .chatbot-btn:hover { animation-play-state: paused; }

        .wa-btn {
            position: fixed; bottom: 30px; left: 30px; width: 55px; height: 55px;
            background: var(--primary-red); color: white; border-radius: 50%;
            display: flex; justify-content: center; align-items: center;
            box-shadow: 0 4px 15px rgba(255,77,77,0.4); z-index: 9000;
            transition: 0.3s; text-decoration: none;
            border: 2px solid var(--accent-gold);
        }
        .wa-btn:hover { transform: scale(1.1) rotate(8deg) !important; }

        .chatbot-btn {
            position: fixed; bottom: 30px; right: 30px; width: 55px; height: 55px;
            background-color: var(--primary-red); color: white; border-radius: 50%; border: none;
            display: flex; justify-content: center; align-items: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.5); z-index: 9000; cursor: pointer; transition: 0.3s;
        }
        .chatbot-btn:hover { transform: scale(1.1) rotate(8deg) !important; }

        /* =========================================
           CHATBOT WINDOW — sama persis galeri_gym.php
           ========================================= */
        .chatbot-window {
            position: fixed; bottom: 95px; right: 30px; width: 330px; max-height: 450px;
            background-color: #0a0a0a; border: 1px solid #333; border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.8); display: none; flex-direction: column; z-index: 9000;
            overflow: hidden;
        }
        .chat-header {
            background-color: var(--primary-red); color: white; padding: 12px 15px;
            font-weight: bold; display: flex; justify-content: space-between; align-items: center;
            font-size: 0.85rem; /* Awalnya 0.95rem -> 0.85rem */ border-bottom: 1px solid #333;
        }
        .close-chat { background: none; border: none; color: white; font-size: 1.5rem; line-height: 1; cursor: pointer; transition: 0.3s; }
        .close-chat:hover { color: var(--accent-gold); }
        .chat-body {
            padding: 15px; height: 260px; overflow-y: auto;
            display: flex; flex-direction: column; gap: 10px; background-color: #111;
        }
        .chat-body::-webkit-scrollbar { width: 4px; }
        .chat-body::-webkit-scrollbar-thumb { background: #444; border-radius: 2px; }

        .chat-msg {
            background-color: #1a1a1a; color: #fff; padding: 10px 14px; border-radius: 8px;
            font-size: 0.75rem; /* Awalnya 0.85rem -> 0.75rem */ line-height: 1.5; max-width: 85%; align-self: flex-start;
            border-bottom-left-radius: 0;
        }
        .chat-msg.user {
            background-color: var(--accent-gold); color: #000; align-self: flex-end;
            border-bottom-left-radius: 8px; border-bottom-right-radius: 0; font-weight: bold;
        }
        .chat-footer-menu { padding: 15px; background-color: #0a0a0a; border-top: 1px solid #222; }

        /* Slider horizontal quick replies — sama seperti galeri_gym.php */
        .quick-replies {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            overflow-y: hidden;
            gap: 8px;
            padding-bottom: 8px;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: var(--accent-gold) transparent;
        }
        .quick-replies::-webkit-scrollbar { height: 6px; }
        .quick-replies::-webkit-scrollbar-track { background: #1a1a1a; border-radius: 4px; }
        .quick-replies::-webkit-scrollbar-thumb { background: var(--accent-gold); border-radius: 4px; }

        .btn-qr {
            background-color: #1a1a1a; color: var(--accent-gold); border: 1px solid #333;
            padding: 8px 12px; border-radius: 20px; font-size: 0.7rem; /* Awalnya 0.8rem -> 0.7rem */ cursor: pointer;
            transition: 0.3s; text-align: left;
            flex: 0 0 auto;
            white-space: nowrap;
        }
        .btn-qr:hover { background-color: var(--accent-gold); color: #000; border-color: var(--accent-gold); }

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
        @media (max-width: 768px) {
            body { padding: 85px 25px 85px 25px; }

            /* Header mobile */
            header { padding: 10px 20px; }
            header .menu-toggle { width: 36px; height: 36px; }
            header .menu-toggle .bar { width: 16px; }

            #nav-menu {
                top: 55px; right: 10px; left: auto;
                width: 195px; box-sizing: border-box; padding: 10px;
            }
            #nav-menu a.menu-link { font-size: 0.75rem; padding: 5px 6px; white-space: nowrap; }
            .nav-actions-dasbor .btn-keluar-menu { font-size: 0.75rem; padding: 7px 8px; }

            .announcement-banner { top: 55px; padding: 8px 12px; font-size: 0.8rem; flex-direction: column; gap: 4px; }

            /* Konten galeri */
            .galeri-container { padding: 12px 10px; }
            .btn-back-square { width: 32px; height: 32px; font-size: 1rem; }
            .form-header h2 { font-size: 1.05rem; }
            .form-header p { font-size: 0.7rem; }

            .search-box input { padding: 8px 12px 8px 32px; font-size: 0.8rem; }
            .search-box svg { width: 16px; height: 16px; }

            .category-filter { gap: 5px; margin-bottom: 14px; }
            .filter-btn { padding: 6px 10px; font-size: 0.7rem; }

            .category-title { font-size: 0.8rem; margin-bottom: 8px; padding-bottom: 6px; }

            /* Grid 3 kolom — sama seperti galeri_gym.php mobile */
            .horizontal-scroll { grid-template-columns: repeat(3, 1fr); gap: 6px; }
            .gallery-item { border-radius: 6px; }
            .item-info { padding: 10px 6px 6px; }
            .item-title { font-size: 0.6rem; }
            .play-icon { width: 24px; height: 24px; }
            .play-icon svg { width: 12px; height: 12px; }

            /* Lightbox mobile */
            .lightbox { padding: 10px; }
            .lightbox-inner { flex-direction: row; height: 60vh; border-radius: 8px; }
            .lightbox-media-area { flex: 1.2; border-right: 1px solid #222; }
            .lightbox-text-container { flex: 1; padding: 12px; }
            .lightbox-title { font-size: 0.9rem; margin-bottom: 8px; padding-bottom: 8px; }
            .lightbox-caption { font-size: 0.75rem; line-height: 1.4; }
            .lightbox-close { top: 5px; right: 10px; font-size: 24px; }

            /* Sembunyikan tombol CS kiri di mobile */
            .wa-btn { display: none !important; }

            /* Chatbot floating mobile */
            .chatbot-btn { bottom: 85px !important; right: 15px !important; width: 42px !important; height: 42px !important; }
            .chatbot-btn svg { width: 20px !important; height: 20px !important; }

            .chatbot-window {
                bottom: 140px !important;
                right: 15px !important;
                left: auto !important;
                width: 85vw !important;
                max-width: 270px !important;
                max-height: 60vh !important;
            }

            /* Chatbot window isi — sama seperti galeri_gym.php */
            .chat-header { padding: 8px 10px; font-size: 0.8rem; }
            .chat-header svg { width: 14px; height: 14px; }
            .close-chat { font-size: 1.2rem; }
            .chat-body { padding: 10px; height: 200px; gap: 6px; }
            .chat-msg { padding: 7px 10px; font-size: 0.6rem; line-height: 1.4; max-width: 70%; }
            .chat-footer-menu { padding: 10px; }

            .quick-replies { gap: 6px; padding-bottom: 6px; }
            .quick-replies::-webkit-scrollbar { height: 4px; }
            .btn-qr { font-size: 9px; }

            /* =========================================
               BOTTOM NAV MOBILE (MEMBER)
               ========================================= */
            .bottom-nav-mobile {
                display: flex !important;
                position: fixed !important;
                bottom: 0 !important; left: 0 !important;
                width: 100vw !important; height: 70px !important;
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
                width: 22px !important; height: 22px !important;
                stroke: currentColor !important; fill: none !important;
                stroke-width: 2 !important; stroke-linecap: round !important; stroke-linejoin: round !important;
            }
            .bottom-nav-mobile .nav-item.highlight {
                color: var(--accent-gold, #E8C999) !important;
                font-weight: bold !important;
            }
            .bottom-nav-mobile .nav-item.highlight svg { stroke: var(--accent-gold, #E8C999) !important; }
            .bottom-nav-mobile .nav-item.locked-nav { opacity: 0.4; cursor: not-allowed; }

            @keyframes jelly {
                0%, 100% { transform: scale(1,1); }
                25% { transform: scale(0.8,1.2); }
                50% { transform: scale(1.2,0.8); }
                75% { transform: scale(0.95,1.05); }
            }
            .bottom-nav-mobile .nav-item:active svg { animation: jelly 0.5s ease; }
        }

        @media (max-width: 480px) {
            .chatbot-btn { width: 40px !important; height: 40px !important; }
            .chatbot-btn svg { width: 18px !important; height: 18px !important; }
        }
    </style>
</head>
<body>

    <!-- ============ HEADER ============ -->
    <header>
        <div class="logo">
            <img src="assets/logo.png" alt="Vanda Gym Classic Logo">
        </div>

        <div class="header-right">
            <!-- Lonceng: selalu di luar hamburger -->
            <a href="perpanjang.php" class="bell-icon <?= ($peringatan_merah && !$sedang_perpanjang) ? 'active' : '' ?>" title="Tagihan Perpanjangan Membership">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <?php if ($status_member === 'aktif' && $peringatan_merah && !$sedang_perpanjang): ?>
                    <span class="bell-badge">!</span>
                <?php endif; ?>
            </a>

            <!-- Hamburger -->
            <button class="menu-toggle" id="mobile-menu" aria-label="Toggle Menu">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>
        </div>

        <!-- Dropdown Nav -->
        <nav id="nav-menu">
            <a href="member_dasbor.php" class="menu-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                Dasbor
            </a>
            <a href="https://instagram.com/vandagympky_classic" target="_blank" class="menu-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
                Hubungi Kami
            </a>
            <a href="galeri_member.php" class="menu-link active-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><path d="M21 15l-5-5L5 21"></path></svg>
                Galeri Gym
            </a>
            <a href="chatbot_member.php" class="menu-link <?= ($status_member !== 'aktif') ? 'locked-link' : '' ?>">
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

    <!-- ============ KONTEN GALERI ============ -->
    <div class="galeri-container">
        <div class="nav-top">
            <a href="member_dasbor.php" class="btn-back-square" title="Kembali ke Dasbor">←</a>
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

    <!-- ============ LIGHTBOX ============ -->
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

    <!-- ============ OFFLINE DETECTOR ============ -->
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

    <!-- ============ TOMBOL CS (hanya PC, disembunyikan di mobile via CSS) ============ -->
    <a href="https://instagram.com/vandagympky_classic" target="_blank" class="wa-btn" title="Hubungi CS via Instagram">
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

    <!-- ============ BOTTOM NAV MOBILE ============ -->
    <div class="bottom-nav-mobile">
        <a href="member_dasbor.php" class="nav-item">
            <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span>Dasbor</span>
        </a>
        <a href="kalkulator.php?source=dasbor" class="nav-item">
            <svg viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="8" y1="6" x2="16" y2="6"></line><line x1="16" y1="14" x2="16.01" y2="14"></line><line x1="12" y1="14" x2="12.01" y2="14"></line><line x1="8" y1="14" x2="8.01" y2="14"></line></svg>
            <span>Gizi</span>
        </a>
        <a href="galeri_member.php" class="nav-item highlight">
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

    <?php
    function renderGalleryItem($m) { ?>
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

        // ============ CHATBOT ============
        function toggleChat() {
            const chat = document.getElementById("chatWindow");
            chat.style.display = (chat.style.display === "flex") ? "none" : "flex";
        }
        function kirimFaq(pertanyaan, jawaban) {
            const body = document.getElementById("chatBody");
            body.innerHTML += '<div class="chat-msg user">' + pertanyaan + '</div>';
            body.scrollTop = body.scrollHeight;
            setTimeout(function() {
                body.innerHTML += '<div class="chat-msg" style="border-left:3px solid var(--accent-gold);">' + jawaban + '</div>';
                body.scrollTop = body.scrollHeight;
            }, 600);
        }

        // ============ FILTER & SHOW MORE ============
        const maxItems = 8;
        let filterKategoriSaatIni = 'semua';

        document.addEventListener('DOMContentLoaded', () => { initShowMore(); });

        function pilihKategori(kat, btnElement) {
            filterKategoriSaatIni = kat;
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            btnElement.classList.add('active');
            jalankanFilter();
        }

        function jalankanFilter() {
            const searchText = document.getElementById('searchInput').value.toLowerCase();
            const sections   = document.querySelectorAll('.category-section');
            const isSearching = searchText.trim() !== '';

            sections.forEach(section => {
                const sectionId = section.id;
                const isMatchCategory = (filterKategoriSaatIni === 'semua' || sectionId === 'sec-' + filterKategoriSaatIni);

                if (!isMatchCategory) { section.style.display = 'none'; return; }
                section.style.display = 'block';

                if (isSearching) {
                    let hasVisibleItems = false;
                    const items = section.querySelectorAll('.gallery-item');
                    const btnMore = section.querySelector('.btn-show-more');
                    if (btnMore) btnMore.style.display = 'none';

                    items.forEach(item => {
                        const judul = item.getAttribute('data-judul');
                        if (judul.includes(searchText)) { item.style.display = 'block'; hasVisibleItems = true; }
                        else { item.style.display = 'none'; }
                    });

                    const emptyState = section.querySelector('.empty-state');
                    const scrollArea = section.querySelector('.horizontal-scroll');
                    if (scrollArea) {
                        if (hasVisibleItems) {
                            scrollArea.style.display = 'grid';
                            if (emptyState) emptyState.style.display = 'none';
                        } else {
                            scrollArea.style.display = 'none';
                            if (emptyState) { emptyState.innerText = "Tidak ditemukan pencarian di kategori ini."; emptyState.style.display = 'block'; }
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
            document.querySelectorAll('.category-section').forEach(section => {
                const items = section.querySelectorAll('.gallery-item');
                const oldBtn = section.querySelector('.btn-show-more');
                if (oldBtn) oldBtn.remove();

                if (items.length > maxItems) {
                    items.forEach((item, index) => { item.style.display = index >= maxItems ? 'none' : 'block'; });
                    const btn = document.createElement('button');
                    btn.className = 'btn-show-more';
                    btn.innerText = 'Lihat Selengkapnya ▼';
                    let isExpanded = false;
                    btn.onclick = () => {
                        isExpanded = !isExpanded;
                        items.forEach((item, index) => { if (index >= maxItems) item.style.display = isExpanded ? 'block' : 'none'; });
                        btn.innerText = isExpanded ? 'Lebih Sedikit ▲' : 'Lihat Selengkapnya ▼';
                        if (!isExpanded) section.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    };
                    section.appendChild(btn);
                } else {
                    items.forEach(item => item.style.display = 'block');
                }
            });
        }

        // ============ LIGHTBOX ============
        function bukaMedia(element) {
            const path    = element.getAttribute('data-path');
            const tipe    = element.getAttribute('data-tipe');
            const judul   = element.getAttribute('data-judul-asli');
            const caption = element.getAttribute('data-caption');

            const container    = document.getElementById('lightboxContainer');
            const textContainer = document.getElementById('lightboxTextContainer');

            document.getElementById('lightboxTitle').innerText = judul;

            const captionBox = document.getElementById('lightboxCaption');
            captionBox.innerText = (caption && caption.trim() !== '') ? caption : 'Belum ada keterangan target otot atau posisi gerakan.';
            textContainer.style.display = 'flex';

            container.innerHTML = tipe === 'video'
                ? `<video src="${path}" class="lightbox-content" controls autoplay muted playsinline></video>`
                : `<img src="${path}" class="lightbox-content">`;

            document.getElementById('mediaLightbox').classList.add('show');
        }
        function tutupMedia(e) { if (e.target.id === 'mediaLightbox') tutupProses(document.getElementById('mediaLightbox')); }
        function tutupLewatTombol() { tutupProses(document.getElementById('mediaLightbox')); }
        function tutupProses(modal) { modal.classList.remove('show'); document.getElementById('lightboxContainer').innerHTML = ''; }
    </script>
</body>
</html>