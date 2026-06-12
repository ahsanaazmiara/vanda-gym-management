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
        body { font-family: 'Segoe UI', sans-serif; background-color: var(--bg-dark); color: var(--text-light); display: flex; flex-direction: column; align-items: center; min-height: 100vh; padding: 40px 20px; }

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
        .quick-replies { display: flex; flex-wrap: wrap; gap: 6px; }
        .btn-qr {
            background-color: #1a1a1a; color: var(--accent-gold); border: 1px solid #333;
            padding: 8px 12px; border-radius: 20px; font-size: 0.8rem; cursor: pointer; transition: 0.3s; text-align: left;
        }
        .btn-qr:hover { background-color: var(--accent-gold); color: #000; border-color: var(--accent-gold); }
        
        /* RESPONSIVE UNTUK HP */
        @media (max-width: 768px) {
            body { padding: 15px 10px; }
            .galeri-container { padding: 15px; }
            .btn-back-square { width: 32px; height: 32px; font-size: 1rem; }
            .form-header h2 { font-size: 1.2rem; }
            .form-header p { font-size: 0.8rem; }
            
            .search-box input { padding: 10px 15px 10px 35px; font-size: 0.85rem; }
            
            .category-filter { gap: 6px; margin-bottom: 20px; }
            .filter-btn { padding: 8px 12px; font-size: 0.75rem; }

            .category-title { font-size: 0.95rem; margin-bottom: 10px; }
            
            .horizontal-scroll { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .item-info { padding: 15px 8px 8px; }
            .item-title { font-size: 0.75rem; }
            .play-icon { width: 30px; height: 30px; }
            .play-icon svg { width: 14px; height: 14px; }

            .lightbox { padding: 10px; }
            .lightbox-inner { flex-direction: row; height: 60vh; border-radius: 8px; }
            .lightbox-media-area { flex: 1.2; border-right: 1px solid #222; }
            .lightbox-text-container { flex: 1; padding: 12px; }
            .lightbox-title { font-size: 0.9rem; margin-bottom: 8px; padding-bottom: 8px; }
            .lightbox-caption { font-size: 0.75rem; line-height: 1.4; }
            .lightbox-close { top: 5px; right: 10px; font-size: 24px; }

            /* Tombol Mengambang HP */
            .wa-btn { bottom: 20px; left: 15px; width: 45px; height: 45px; }
            .wa-btn svg { width: 22px; height: 22px; }
            .chatbot-btn { bottom: 20px; right: 15px; width: 45px; height: 45px; }
            .chatbot-btn svg { width: 22px; height: 22px; }
            .chatbot-window { bottom: 75px; right: 15px; left: 15px; width: auto; max-height: 60vh; }
        }
    </style>
</head>
<body>

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

        <!-- TOMBOL FILTER KATEGORI -->
        <div class="category-filter">
            <button class="filter-btn active" onclick="pilihKategori('semua', this)">Semua Kategori</button>
            <button class="filter-btn" onclick="pilihKategori('alat', this)">Alat Gym</button>
            <button class="filter-btn" onclick="pilihKategori('upper', this)">Upper Body</button>
            <button class="filter-btn" onclick="pilihKategori('lower', this)">Lower Body</button>
        </div>

        <!-- SECTION ALAT GYM -->
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

        <!-- SECTION UPPER BODY -->
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

        <!-- SECTION LOWER BODY -->
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

    <!-- MODAL LIGHTBOX -->
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

    <!-- TOMBOL WHATSAPP KIRI BAWAH -->
    <a href="https://wa.me/<?= $wa_link ?>" target="_blank" class="wa-btn" title="Hubungi CS via WhatsApp">
        <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" viewBox="0 0 16 16">
          <path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/>
        </svg>
    </a>

    <!-- TOMBOL CHATBOT KANAN BAWAH -->
    <button class="chatbot-btn" onclick="toggleChat()" title="Tanya Asisten Galeri">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="10" rx="2"></rect>
            <circle cx="12" cy="5" r="2"></circle>
            <path d="M12 7v4"></path>
            <line x1="8" y1="16" x2="8.01" y2="16"></line>
            <line x1="16" y1="16" x2="16.01" y2="16"></line>
        </svg>
    </button>
    
    <!-- WINDOW CHATBOT -->
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
                
                <button class="btn-qr" onclick="kirimFaq('Bisa minta diajarin langsung?', 'Tentu dong! Kalau kamu masih ragu sama <i>form</i> (posisi tubuh) alat tertentu, jangan segan buat panggil instruktur/admin yang lagi jaga di Gym ya.<br><br>Atau mau tanya-tanya CS via WhatsApp sekarang? Klik aja tombol hijau di pojok kiri bawah layar! 📱')">🗣️ Minta Bimbingan Langsung</button>
            </div>
        </div>
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
            // Mencegah modal tertutup jika pengguna mengklik area chat
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