<?php
session_start();
require 'includes/koneksi.php';

// Proteksi: Hanya Member yang boleh mengakses halaman ini
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'member') {
    header("Location: login.php");
    exit;
}

$id_user = str_pad($_SESSION['id_user'], 4, '0', STR_PAD_LEFT); // Format ID jadi 0001

// Ambil semua data galeri dari database
$q_galeri = mysqli_query($koneksi, "SELECT * FROM galeri_gym ORDER BY id_media DESC");
$semua_media = [];
while ($row = mysqli_fetch_assoc($q_galeri)) {
    $semua_media[] = $row;
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

        .nav-top { margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; }
        .btn-back-square { width: 44px; height: 44px; background-color: #1a1a1a; border: 1px solid #333; color: var(--accent-gold); border-radius: 4px; display: flex; align-items: center; justify-content: center; text-decoration: none; font-weight: bold; font-size: 1.2rem; transition: 0.3s; }
        .btn-back-square:hover { background-color: var(--primary-red); color: white; border-color: var(--primary-red); }

        .form-header { text-align: center; margin-bottom: 30px; }
        .form-header h2 { color: var(--text-light); text-transform: uppercase; letter-spacing: 1px; font-size: 1.5rem; margin-bottom: 5px; }
        .form-header p { color: #888; font-size: 0.9rem; }

        .controls-wrapper { display: flex; flex-direction: column; gap: 20px; margin-bottom: 30px; background: #151515; padding: 20px; border-radius: 8px; border: 1px solid #222; }
        
        .search-box { width: 100%; position: relative; }
        .search-box input { width: 100%; padding: 12px 15px 12px 40px; background: #0a0a0a; border: 1px solid #333; border-radius: 4px; color: white; outline: none; transition: 0.3s; font-size: 0.95rem; }
        .search-box input:focus { border-color: var(--accent-gold); }
        .search-box svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; fill: #666; }
        
        .category-tabs { display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; }
        .tab-btn { background: #0a0a0a; color: #888; border: 1px solid #333; padding: 10px 20px; border-radius: 30px; cursor: pointer; transition: 0.3s; font-weight: bold; font-size: 0.9rem; }
        .tab-btn:hover { border-color: var(--accent-gold); color: var(--text-light); }
        .tab-btn.active { background: var(--accent-gold); color: #000; border-color: var(--accent-gold); }

        .dynamic-title-container { border-bottom: 1px solid #222; margin-bottom: 20px; padding-bottom: 10px; }
        .dynamic-title { color: var(--accent-gold); font-size: 1.2rem; text-transform: uppercase; letter-spacing: 1px; margin: 0; }

        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; }
        .gallery-item { position: relative; border-radius: 8px; overflow: hidden; background-color: var(--card-bg); border: 1px solid #222; cursor: pointer; aspect-ratio: 4/3; transition: 0.3s; display: none; }
        .gallery-item.active { display: block; animation: fadeIn 0.4s ease; }
        
        @keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }

        .gallery-item:hover { transform: translateY(-5px); border-color: var(--accent-gold); box-shadow: 0 5px 15px rgba(232, 201, 153, 0.2); }
        .gallery-item img, .gallery-item video { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; pointer-events: none; }
        .gallery-item:hover img, .gallery-item:hover video { transform: scale(1.05); }

        .item-info { position: absolute; bottom: 0; left: 0; width: 100%; background: linear-gradient(transparent, rgba(0,0,0,0.95)); padding: 25px 15px 12px; display: flex; flex-direction: column; gap: 4px; }
        .item-title { font-size: 0.95rem; font-weight: bold; color: white; text-shadow: 1px 1px 2px black; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .item-category { font-size: 0.75rem; color: var(--accent-gold); text-transform: uppercase; }

        .item-caption-short { font-size: 0.8rem; color: #ccc; margin-top: 5px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis; line-height: 1.4; }

        .play-icon { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 45px; height: 45px; background: rgba(142, 22, 22, 0.8); border-radius: 50%; display: flex; justify-content: center; align-items: center; border: 2px solid var(--text-light); transition: 0.3s; box-shadow: 0 0 10px rgba(0,0,0,0.5); z-index: 2;}
        .gallery-item:hover .play-icon { background: var(--primary-red); transform: translate(-50%, -50%) scale(1.1); }
        .play-icon svg { width: 20px; height: 20px; fill: white; margin-left: 3px; }

        .empty-state { text-align: center; padding: 40px; color: #666; font-style: italic; background: #111; border: 1px dashed #333; border-radius: 8px; width: 100%; display: none; }

        .pagination-container { display: flex; justify-content: center; gap: 8px; margin-top: 40px; flex-wrap: wrap; }
        .btn-page { background: #111; border: 1px solid #333; color: var(--text-light); padding: 8px 14px; border-radius: 4px; cursor: pointer; transition: 0.3s; font-size: 0.9rem; font-weight: bold; }
        .btn-page:hover { background: #222; border-color: var(--accent-gold); }
        .btn-page.active { background: var(--accent-gold); color: #000; border-color: var(--accent-gold); }

        /* =========================================
           MODAL LIGHTBOX (DESAIN PC: KIRI KANAN)
           ========================================= */
        .lightbox { 
            display: none; position: fixed; z-index: 9999; top: 0; left: 0; width: 100%; height: 100%; 
            background-color: rgba(0,0,0,0.92); justify-content: center; align-items: center; padding: 20px; 
        }
        .lightbox.show { display: flex; }
        
        .lightbox-inner {
            display: flex; flex-direction: row;
            background: #0a0a0a; border: 1px solid #333; border-radius: 12px; overflow: hidden;
            width: 100%; max-width: 1000px; height: 85vh;
            box-shadow: 0 0 40px rgba(0,0,0,0.8);
            position: relative;
        }

        .lightbox-media-area {
            flex: 1.5; background: #000; display: flex; justify-content: center; align-items: center;
            border-right: 1px solid #222; overflow: hidden;
        }

        .lightbox-content { 
            width: 100%; height: 100%; object-fit: contain; 
        }

        .lightbox-text-container {
            flex: 1; padding: 30px; display: flex; flex-direction: column;
            overflow-y: auto; background: #111;
        }

        .lightbox-text-container::-webkit-scrollbar { width: 6px; }
        .lightbox-text-container::-webkit-scrollbar-track { background: #111; }
        .lightbox-text-container::-webkit-scrollbar-thumb { background: #444; border-radius: 3px; }

        .lightbox-title { 
            color: var(--accent-gold); margin-top: 0; font-size: 1.4rem; font-weight: bold; 
            text-transform: uppercase; margin-bottom: 15px; border-bottom: 1px dashed #333; padding-bottom: 15px; 
        }

        .lightbox-caption { 
            color: #ddd; font-size: 0.95rem; line-height: 1.6; 
            white-space: pre-wrap; word-wrap: break-word;
        }

        .lightbox-close { 
            position: fixed; top: 15px; right: 25px; color: white; font-size: 40px; font-weight: bold; 
            cursor: pointer; transition: 0.3s; z-index: 10000; line-height: 1; text-shadow: 0 0 10px black;
        }
        .lightbox-close:hover { color: var(--primary-red); transform: scale(1.1); }
        
        /* =========================================
           RESPONSIVE UNTUK HP
           ========================================= */
        @media (max-width: 768px) {
            body { padding: 15px 10px; font-size: 0.9rem; }
            .galeri-container { padding: 15px; border-radius: 6px; }
            
            .btn-back-square { width: 36px; height: 36px; font-size: 1rem; }
            
            .form-header h2 { font-size: 1.25rem; }
            .form-header p { font-size: 0.8rem; }
            
            .controls-wrapper { padding: 15px; gap: 15px; margin-bottom: 20px; }
            .search-box input { padding: 10px 15px 10px 35px; font-size: 0.85rem; }
            .search-box svg { left: 10px; width: 16px; height: 16px; }
            
            .category-tabs { gap: 6px; }
            .tab-btn { padding: 8px 12px; font-size: 0.75rem; }
            
            .dynamic-title { font-size: 1.05rem; }
            
            .gallery-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            
            .item-info { padding: 15px 10px 8px; }
            .item-title { font-size: 0.75rem; }
            .item-category { font-size: 0.65rem; }
            .item-caption-short { display: none; }
            
            .play-icon { width: 35px; height: 35px; }
            .play-icon svg { width: 15px; height: 15px; }
            
            .btn-page { padding: 6px 10px; font-size: 0.8rem; }

            /* PERBAIKAN LIGHTBOX HP */
            .lightbox.show { display: block; }
            .lightbox { 
                align-items: flex-start; 
                padding: 10px; overflow-y: auto; 
            }
            
            .lightbox-inner {
                flex-direction: column; 
                height: auto; min-height: unset; 
                border-radius: 8px; margin-top: 15px; margin-bottom: 20px;
                border: 1px solid #333;
            }

            .lightbox-media-area {
                height: 30vh; min-height: 200px; 
                border-right: none; border-bottom: 1px solid #222;
            }

            .lightbox-text-container {
                overflow-y: visible; 
                padding: 15px;
            }

            .lightbox-title { 
                font-size: 1.1rem; 
                margin-bottom: 10px; padding-bottom: 10px; 
            }

            .lightbox-caption { 
                font-size: 0.85rem; 
                line-height: 1.5; 
            }

            .lightbox-close { 
                position: absolute;
                top: -10px; right: -5px; font-size: 26px; 
                background: var(--primary-red); border-radius: 50%; 
                width: 35px; height: 35px; 
                display: flex; justify-content: center; align-items: center; 
                text-align: center; border: 2px solid var(--text-light);
            }
        }
    </style>
</head>
<body>

    <div class="galeri-container">
        <div class="nav-top">
            <a href="member_dasbor.php" class="btn-back-square" title="Kembali ke Dasbor">←</a>
        </div>

        <div class="form-header">
            <h2>Galeri <span style="color:var(--accent-gold)">&</span> Tutorial</h2>
            <p>Kenali fasilitas alat & pelajari gerakannya</p>
        </div>

        <div class="controls-wrapper">
            <div class="search-box">
                <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                <input type="text" id="searchInput" placeholder="Cari alat atau nama gerakan..." onkeyup="filterPencarian()">
            </div>
            
            <div class="category-tabs">
                <button class="tab-btn active" onclick="setKategori('semua', 'Semua Media & Tutorial')">Semua Media</button>
                <button class="tab-btn" onclick="setKategori('alat', 'Fasilitas & Alat Gym')">Alat Gym</button>
                <button class="tab-btn" onclick="setKategori('upper', 'Tutorial Upper Body')">Tutorial Upper Body</button>
                <button class="tab-btn" onclick="setKategori('lower', 'Tutorial Lower Body')">Tutorial Lower Body</button>
            </div>
        </div>

        <div class="dynamic-title-container">
            <h3 id="categoryTitle" class="dynamic-title">Semua Media & Tutorial</h3>
        </div>

        <div class="gallery-grid" id="mainGalleryGrid">
            <?php if(!empty($semua_media)): foreach($semua_media as $m): ?>
                <div class="gallery-item" 
                     data-kategori="<?= htmlspecialchars($m['kategori']) ?>" 
                     data-judul="<?= strtolower(htmlspecialchars($m['judul'])) ?>"
                     data-judul-asli="<?= htmlspecialchars($m['judul']) ?>"
                     data-path="<?= $m['file_path'] ?>"
                     data-tipe="<?= $m['tipe_media'] ?>"
                     data-caption="<?= htmlspecialchars($m['caption'] ?? '') ?>"
                     onclick="bukaMedia(this)">
                     
                    <?php if($m['tipe_media'] == 'video'): ?>
                        <video src="<?= $m['file_path'] ?>#t=0.1" preload="metadata" muted></video>
                        <div class="play-icon"><svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></div>
                    <?php else: ?>
                        <img src="<?= $m['file_path'] ?>" loading="lazy" alt="<?= htmlspecialchars($m['judul']) ?>">
                    <?php endif; ?>
                    
                    <div class="item-info">
                        <span class="item-title" title="<?= htmlspecialchars($m['judul']) ?>"><?= htmlspecialchars($m['judul']) ?></span>
                        <span class="item-category">
                            <?php 
                            if($m['kategori'] == 'alat') echo "Alat Gym";
                            else if($m['kategori'] == 'upper') echo "Upper Body";
                            else if($m['kategori'] == 'lower') echo "Lower Body";
                            ?>
                        </span>
                        
                        <?php if(!empty($m['caption'])): ?>
                            <span class="item-caption-short"><?= htmlspecialchars($m['caption']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>

        <div id="emptyStateMsg" class="empty-state">
            <?= empty($semua_media) ? "Belum ada media yang diupload oleh Admin." : "Media yang kamu cari tidak ditemukan." ?>
        </div>

        <div id="paginationContainer" class="pagination-container"></div>
    </div>

    <div id="mediaLightbox" class="lightbox" onclick="tutupMedia(event)">
        <span class="lightbox-close" title="Tutup" onclick="tutupLewatTombol()">&times;</span>
        
        <div class="lightbox-inner" id="lightboxInner">
            <div class="lightbox-media-area" id="lightboxContainer"></div>
            
            <div class="lightbox-text-container" id="lightboxTextContainer">
                <div id="lightboxTitle" class="lightbox-title">Judul Media</div>
                <div id="lightboxCaption" class="lightbox-caption"></div>
            </div>
        </div>
    </div>

    <script>
        let currentPage = 1;
        const itemsPerPage = 12; 
        let currentKategori = 'semua';

        function setKategori(kat, judulHeader) {
            currentKategori = kat;
            currentPage = 1; 
            
            document.getElementById('categoryTitle').innerText = judulHeader;

            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelector(`.tab-btn[onclick*="'${kat}'"]`).classList.add('active');

            renderGaleri();
        }

        function filterPencarian() {
            currentPage = 1;
            renderGaleri();
        }

        function renderGaleri() {
            const searchText = document.getElementById('searchInput').value.toLowerCase();
            const items = Array.from(document.querySelectorAll('.gallery-item'));
            
            let filteredItems = items.filter(item => {
                const judul = item.getAttribute('data-judul');
                const kategori = item.getAttribute('data-kategori');
                const matchSearch = judul.includes(searchText);
                const matchKategori = (currentKategori === 'semua' || currentKategori === kategori);
                return matchSearch && matchKategori;
            });

            const totalPages = Math.ceil(filteredItems.length / itemsPerPage);
            if (currentPage > totalPages && totalPages > 0) currentPage = totalPages;
            if (currentPage < 1) currentPage = 1;

            const start = (currentPage - 1) * itemsPerPage;
            const end = start + itemsPerPage;

            items.forEach(item => item.classList.remove('active')); 
            
            if (filteredItems.length === 0) {
                document.getElementById('emptyStateMsg').style.display = 'block';
                document.getElementById('paginationContainer').innerHTML = '';
            } else {
                document.getElementById('emptyStateMsg').style.display = 'none';
                filteredItems.slice(start, end).forEach(item => item.classList.add('active'));

                const pagContainer = document.getElementById('paginationContainer');
                pagContainer.innerHTML = '';
                if (totalPages > 1) {
                    for (let i = 1; i <= totalPages; i++) {
                        const btn = document.createElement('button');
                        btn.innerText = i;
                        btn.className = `btn-page ${i === currentPage ? 'active' : ''}`;
                        btn.onclick = () => {
                            currentPage = i;
                            renderGaleri();
                            document.getElementById('categoryTitle').scrollIntoView({ behavior: 'smooth', block: 'start' });
                        };
                        pagContainer.appendChild(btn);
                    }
                }
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            renderGaleri();
        });

        // ===============================================
        // FUNGSI MEMBUKA LIGHTBOX (Bebas Bug!)
        // ===============================================
        function bukaMedia(element) {
            const path = element.getAttribute('data-path');
            const tipe = element.getAttribute('data-tipe');
            const judul = element.getAttribute('data-judul-asli');
            const caption = element.getAttribute('data-caption');

            const container = document.getElementById('lightboxContainer');
            const textContainer = document.getElementById('lightboxTextContainer');
            
            // Set Judul
            document.getElementById('lightboxTitle').innerText = judul;
            
            // Set Caption
            const captionBox = document.getElementById('lightboxCaption');
            if(caption && caption.trim() !== '') {
                captionBox.innerText = caption;
                captionBox.style.display = 'block';
                textContainer.style.display = 'flex';
            } else {
                captionBox.innerText = '';
                textContainer.style.display = 'none'; 
            }
            
            // Render Gambar/Video
            if(tipe === 'video') {
                container.innerHTML = `<video src="${path}" class="lightbox-content" controls autoplay muted></video>`;
            } else {
                container.innerHTML = `<img src="${path}" class="lightbox-content">`;
            }
            
            // Tampilkan Modal PAKE CLASS agar tidak error (Timpa inline display)
            const modal = document.getElementById('mediaLightbox');
            modal.classList.add('show');
            modal.scrollTop = 0; // Kembalikan scroll ke paling atas
        }

        // ===============================================
        // FUNGSI MENUTUP LIGHTBOX (Bebas Bug!)
        // ===============================================
        function tutupMedia(e) {
            const modal = document.getElementById('mediaLightbox');
            // Tutup jika area luar (hitam), wrapper dalam, atau tombol X diklik
            if (e.target.id === 'mediaLightbox' || e.target.classList.contains('lightbox-close') || e.target.id === 'lightboxInner') {
                tutupProses(modal);
            }
        }
        
        function tutupLewatTombol() {
            const modal = document.getElementById('mediaLightbox');
            tutupProses(modal);
        }
        
        function tutupProses(modal) {
            modal.classList.remove('show');
            document.getElementById('lightboxContainer').innerHTML = ''; // Hapus elemen agar video stop
        }
    </script>
</body>
</html>