<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galeri & Tutorial - Vanda Gym Classic</title>
    <style>
        :root {
            --bg-dark: #000000;
            --primary-red: #8E1616;
            --accent-gold: #E8C999;
            --text-light: #F8EEDF;
            --card-bg: #111111;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: var(--bg-dark); 
            color: var(--text-light); 
            display: flex; flex-direction: column; align-items: center;
            min-height: 100vh; padding: 40px 20px;
        }

        .galeri-container {
            background-color: #0a0a0a;
            border: 1px solid #333; border-top: 4px solid var(--primary-red);
            border-radius: 8px; padding: 30px;
            width: 100%; max-width: 900px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.8);
        }

        .nav-top { margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; }
        .btn-back-square { 
            width: 44px; height: 44px; 
            background-color: #1a1a1a; border: 1px solid #333; 
            color: var(--accent-gold); border-radius: 4px;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; font-weight: bold; font-size: 1.2rem;
            transition: 0.3s;
        }
        .btn-back-square:hover { background-color: var(--primary-red); color: white; border-color: var(--primary-red); }

        .form-header { text-align: center; margin-bottom: 30px; }
        .form-header h2 { color: var(--text-light); text-transform: uppercase; letter-spacing: 1px; font-size: 1.5rem; margin-bottom: 5px; }
        .form-header p { color: #888; font-size: 0.9rem; }

        /* KONTROL PENCARIAN & FILTER */
        .controls-wrapper {
            display: flex; gap: 15px; margin-bottom: 30px;
            flex-wrap: wrap; background: #151515; padding: 15px; border-radius: 6px; border: 1px solid #222;
        }
        .search-box { flex: 2; min-width: 200px; position: relative; }
        .search-box input {
            width: 100%; padding: 12px 15px 12px 40px; background: #0a0a0a; border: 1px solid #333;
            border-radius: 4px; color: white; outline: none; transition: 0.3s; font-size: 0.95rem;
        }
        .search-box input:focus { border-color: var(--accent-gold); }
        .search-box svg {
            position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
            width: 18px; height: 18px; fill: #666;
        }
        
        .filter-box { flex: 1; min-width: 150px; }
        .filter-box select {
            width: 100%; padding: 12px 15px; background: #0a0a0a; border: 1px solid #333;
            border-radius: 4px; color: white; outline: none; cursor: pointer; font-size: 0.95rem;
        }
        .filter-box select:focus { border-color: var(--accent-gold); }

        /* PEMISAH SEKSI */
        .section-header { 
            display: flex; align-items: center;
            border-bottom: 1px solid #222; padding-bottom: 10px; margin: 30px 0 15px;
        }
        .section-header h3 { color: var(--accent-gold); text-transform: uppercase; font-size: 1.1rem; letter-spacing: 0.5px; }

        /* GRID GALERI */
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 15px; }
        .gallery-item {
            position: relative; border-radius: 8px; overflow: hidden;
            background-color: var(--card-bg); border: 1px solid #222;
            cursor: pointer; aspect-ratio: 4/3; transition: 0.3s;
        }
        .gallery-item:hover { transform: translateY(-5px); border-color: var(--accent-gold); box-shadow: 0 5px 15px rgba(232, 201, 153, 0.2); }
        .gallery-item img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
        .gallery-item:hover img { transform: scale(1.05); }

        .item-info {
            position: absolute; bottom: 0; left: 0; width: 100%;
            background: linear-gradient(transparent, rgba(0,0,0,0.9));
            padding: 25px 15px 12px; display: flex; flex-direction: column; gap: 4px;
        }
        .item-title { font-size: 0.95rem; font-weight: bold; color: white; text-shadow: 1px 1px 2px black; }
        .item-category { font-size: 0.75rem; color: var(--accent-gold); }

        .play-icon {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            width: 45px; height: 45px; background: rgba(142, 22, 22, 0.8);
            border-radius: 50%; display: flex; justify-content: center; align-items: center;
            border: 2px solid var(--text-light); transition: 0.3s; box-shadow: 0 0 10px rgba(0,0,0,0.5);
        }
        .gallery-item:hover .play-icon { background: var(--primary-red); transform: translate(-50%, -50%) scale(1.1); }
        .play-icon svg { width: 20px; height: 20px; fill: white; margin-left: 3px; }

        .empty-state { text-align: center; padding: 20px; color: #666; font-style: italic; display: none; grid-column: 1 / -1; }
    </style>
</head>
<body>

    <div class="galeri-container">
        
        <div class="nav-top">
            <a href="member_dasbor.php" class="btn-back-square" title="Kembali ke Dasbor">←</a>
            <span style="color: #444; font-size: 0.8rem;">ID Member: VGYM-202604</span>
        </div>

        <div class="form-header">
            <h2>Galeri <span style="color:var(--accent-gold)">&</span> Tutorial</h2>
            <p>Kenali fasilitas alat & pelajari gerakannya</p>
        </div>

        <div class="controls-wrapper">
            <div class="search-box">
                <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                <input type="text" id="searchInput" placeholder="Cari alat atau nama gerakan..." onkeyup="filterMedia()">
            </div>
            <div class="filter-box">
                <select id="kategoriFilter" onchange="filterMedia()">
                    <option value="semua">Semua Kategori</option>
                    <option value="alat">Hanya Alat Gym</option>
                    <option value="upper">Hanya Upper Body</option>
                    <option value="lower">Hanya Lower Body</option>
                </select>
            </div>
        </div>

        <div id="sectionAlat">
            <div class="section-header">
                <h3>Galeri Fasilitas & Alat Gym</h3>
            </div>
            <div class="gallery-grid" id="gridAlat">
                <div class="gallery-item foto" data-kategori="alat" data-judul="Lat Pulldown Machine" onclick="bukaMedia('Lat Pulldown Machine')">
                    <img src="https://images.unsplash.com/photo-1576678927484-cc907957088c?auto=format&fit=crop&w=500&q=80" alt="Alat Gym">
                    <div class="item-info">
                        <span class="item-title">Lat Pulldown Machine</span>
                        <span class="item-category">Alat Gym</span>
                    </div>
                </div>
                <div class="gallery-item foto" data-kategori="alat" data-judul="Area Beban Bebas Dumbbell" onclick="bukaMedia('Area Beban Bebas')">
                    <img src="https://images.unsplash.com/photo-1534367610401-9f5ed68180aa?auto=format&fit=crop&w=500&q=80" alt="Fasilitas">
                    <div class="item-info">
                        <span class="item-title">Area Beban Bebas (Dumbbell)</span>
                        <span class="item-category">Alat Gym</span>
                    </div>
                </div>
                <div id="emptyAlat" class="empty-state">Media tidak ditemukan di kategori ini.</div>
            </div>
        </div>

        <div id="sectionUpper">
            <div class="section-header">
                <h3>Tutorial Gerakan: Upper Body</h3>
            </div>
            <div class="gallery-grid" id="gridUpper">
                <div class="gallery-item video" data-kategori="upper" data-judul="Tutorial Bench Press Dada" onclick="bukaMedia('Tutorial Bench Press')">
                    <img src="https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=500&q=80" alt="Video Gym">
                    <div class="play-icon"><svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></div>
                    <div class="item-info">
                        <span class="item-title">Tutorial Bench Press</span>
                        <span class="item-category">Gerakan Dada (Upper)</span>
                    </div>
                </div>
                <div class="gallery-item video" data-kategori="upper" data-judul="Tutorial Bicep Curl Lengan" onclick="bukaMedia('Tutorial Bicep Curl')">
                    <img src="https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?auto=format&fit=crop&w=500&q=80" alt="Video Bicep">
                    <div class="play-icon"><svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></div>
                    <div class="item-info">
                        <span class="item-title">Tutorial Dumbbell Bicep Curl</span>
                        <span class="item-category">Gerakan Lengan (Upper)</span>
                    </div>
                </div>
                <div id="emptyUpper" class="empty-state">Media tidak ditemukan di kategori ini.</div>
            </div>
        </div>

        <div id="sectionLower">
            <div class="section-header">
                <h3>Tutorial Gerakan: Lower Body</h3>
            </div>
            <div class="gallery-grid" id="gridLower">
                <div class="gallery-item video" data-kategori="lower" data-judul="Tutorial Squat Kaki" onclick="bukaMedia('Tutorial Squat yang Benar')">
                    <img src="https://images.unsplash.com/photo-1574680096145-d05b474e2155?auto=format&fit=crop&w=500&q=80" alt="Video Squat">
                    <div class="play-icon"><svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></div>
                    <div class="item-info">
                        <span class="item-title">Tutorial Barbell Squat</span>
                        <span class="item-category">Gerakan Kaki (Lower)</span>
                    </div>
                </div>
                <div class="gallery-item video" data-kategori="lower" data-judul="Tutorial Leg Press" onclick="bukaMedia('Tutorial Leg Press')">
                    <img src="https://images.unsplash.com/photo-1538805060514-97d9cc17730c?auto=format&fit=crop&w=500&q=80" alt="Leg Press">
                    <div class="play-icon"><svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></div>
                    <div class="item-info">
                        <span class="item-title">Tutorial Leg Press Machine</span>
                        <span class="item-category">Gerakan Kaki (Lower)</span>
                    </div>
                </div>
                <div id="emptyLower" class="empty-state">Media tidak ditemukan di kategori ini.</div>
            </div>
        </div>

    </div>

    <script>
        function filterMedia() {
            const searchText = document.getElementById('searchInput').value.toLowerCase();
            const filterKategori = document.getElementById('kategoriFilter').value;
            
            const itemAlat = document.querySelectorAll('#gridAlat .gallery-item');
            const itemUpper = document.querySelectorAll('#gridUpper .gallery-item');
            const itemLower = document.querySelectorAll('#gridLower .gallery-item');

            // Helper function untuk memproses tiap grid
            function prosesFilter(items) {
                let count = 0;
                items.forEach(item => {
                    const judul = item.getAttribute('data-judul').toLowerCase();
                    const kategori = item.getAttribute('data-kategori');
                    const matchSearch = judul.includes(searchText);
                    const matchKategori = (filterKategori === 'semua' || filterKategori === kategori);

                    if (matchSearch && matchKategori) {
                        item.style.display = 'block';
                        count++;
                    } else {
                        item.style.display = 'none';
                    }
                });
                return count;
            }

            const hitungAlat = prosesFilter(itemAlat);
            const hitungUpper = prosesFilter(itemUpper);
            const hitungLower = prosesFilter(itemLower);

            // Tampilkan pesan kosong per seksi jika hasil search/filter tidak ada
            document.getElementById('emptyAlat').style.display = (hitungAlat === 0) ? 'block' : 'none';
            document.getElementById('emptyUpper').style.display = (hitungUpper === 0) ? 'block' : 'none';
            document.getElementById('emptyLower').style.display = (hitungLower === 0) ? 'block' : 'none';

            // Sembunyikan seluruh blok seksi (termasuk judulnya) jika dropdown memfilter spesifik
            document.getElementById('sectionAlat').style.display = (filterKategori === 'upper' || filterKategori === 'lower') ? 'none' : 'block';
            document.getElementById('sectionUpper').style.display = (filterKategori === 'alat' || filterKategori === 'lower') ? 'none' : 'block';
            document.getElementById('sectionLower').style.display = (filterKategori === 'alat' || filterKategori === 'upper') ? 'none' : 'block';
        }

        function bukaMedia(judul) {
            alert(`Membuka Media: ${judul}\n\nDi tahap backend, ini akan memunculkan popup Lightbox atau memutar video.`);
        }
    </script>
</body>
</html>