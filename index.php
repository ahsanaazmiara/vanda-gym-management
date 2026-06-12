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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vanda Gym Classic Palangkaraya</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
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
        .btn-retry:hover { background-color: #1ebe57; }

        /* =========================================
           CSS KHUSUS MENU BOTTOM & LAYOUT MOBILE 
           ========================================= */
        
        /* Sembunyikan div bottom nav di Desktop */
        .bottom-nav-mobile { display: none !important; }

        @media screen and (max-width: 768px) {
            body { 
                padding-bottom: 85px !important; /* Jarak agar footer tidak ketutup menu */
            }
            
            /* Matikan menu desktop aslinya agar tidak dobel */
            header #nav-menu, header .menu-toggle { 
                display: none !important; 
            }

            /* ---------- LAYOUT KOMPAK ---------- */
            .hero { padding: 100px 20px !important; min-height: auto !important; }
            .hero h1 { font-size: 1.8rem !important; line-height: 1.2 !important; margin-bottom: 10px !important; }
            .hero p { font-size: 0.85rem !important; margin-bottom: 20px !important; line-height: 1.4 !important; }
            .hero .btn-primary { padding: 10px 20px !important; font-size: 0.9rem !important; }

            section { padding: 40px 15px !important; }
            .section-title { font-size: 1.4rem !important; margin-bottom: 20px !important; }

            /* REVISI: Perbaikan Box/Card agar tidak memanjang penuh */
            .grid-3 { 
                display: flex !important; 
                flex-direction: column !important; 
                align-items: center !important; 
                gap: 15px !important; 
            }
            .card, .benefit-card { 
                width: 100% !important; 
                max-width: 330px !important; /* Membatasi lebar agar tidak melebar penuh */
                margin: 0 auto !important; /* Tengahkan card */
                padding: 20px 15px !important; /* Sesuaikan jarak dalam */
                box-sizing: border-box !important;
            }
            .card h3, .benefit-card h3 { font-size: 1.1rem !important; margin-bottom: 5px !important; }
            .card .price { font-size: 1.1rem !important; margin: 5px 0 10px 0 !important; }
            .card p, .benefit-card p { font-size: 0.8rem !important; line-height: 1.4 !important; margin-bottom: 10px !important; }
            .benefit-icon { margin-bottom: 10px !important; }
            .benefit-icon svg { width: 30px !important; height: 30px !important; }
            .highlight-text { font-size: 0.75rem !important; padding: 8px !important; }
            
            /* Pengecilan Kotak Jadwal */
            .schedule-box { padding: 15px !important; max-width: 350px !important; margin: 0 auto 15px auto !important; }
            .schedule-header { font-size: 1rem !important; padding: 8px 10px !important; }
            .schedule-row { padding: 8px 0 !important; }
            .schedule-day { font-size: 0.85rem !important; }
            .schedule-time { font-size: 0.8rem !important; }

            /* Pengecilan Footer */
            .footer-info h3 { font-size: 1.2rem !important; margin-bottom: 10px !important; }
            .footer-info p { font-size: 0.8rem !important; margin-bottom: 8px !important; }
            .footer-info svg { width: 16px !important; height: 16px !important; }
            
            /* ---------- NAVIGASI BAWAH ---------- */
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
            }

            .bottom-nav-mobile .nav-item:hover, .bottom-nav-mobile .nav-item:active {
                color: var(--accent-gold, #E8C999) !important;
            }

            .bottom-nav-mobile .nav-item svg {
                width: 22px !important;
                height: 22px !important;
                stroke: currentColor !important;
                fill: none !important;
            }

            .bottom-nav-mobile .nav-item.highlight {
                color: #ff4d4d !important;
                font-weight: bold !important;
            }
            .bottom-nav-mobile .nav-item.highlight svg {
                stroke: none !important;
                fill: #ff4d4d !important;
            }

            /* Angkat dan perkecil sedikit tombol WA dan Chatbot */
            .wa-btn { bottom: 85px !important; width: 50px !important; height: 50px !important; left: 15px !important; }
            .wa-btn svg { width: 26px !important; height: 26px !important; }
            .chatbot-btn { bottom: 85px !important; width: 50px !important; height: 50px !important; right: 15px !important; }
            .chatbot-btn svg { width: 24px !important; height: 24px !important; }
            
            /* REVISI: Perbaiki Posisi Jendela Chatbot agar menempel ke kanan */
            .chatbot-window { 
                bottom: 145px !important; 
                right: 15px !important; 
                left: auto !important; /* Hapus sifat left dari desktop agar tidak ke kiri */
                width: 85vw !important; 
                max-width: 320px !important;
                transform: none !important; /* Reset transform dari CSS bawaan */
            }
            .chat-msg { font-size: 0.8rem !important; padding: 8px 12px !important; }
            .btn-qr { font-size: 0.75rem !important; padding: 6px 10px !important; }
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
            <a href="#paket">Paket Membership</a>
            <a href="#jadwal">Jadwal</a>
            <a href="#galeri">Galeri Gym</a>
            <a href="kalkulator.php">Kalkulator Gizi</a>
            <a href="login.php" class="nav-login">Login</a>
            <button class="btn-daftar" onclick="window.location.href='daftar.php'">Daftar Member</button>
        </nav>
    </header>

    <?php if (($web_data['pengumuman_aktif'] ?? '') === 'aktif'): ?>
    <div class="announcement-banner" id="infoBanner">
        <span class="announcement-badge">Info Terkini</span>
        <span class="announcement-text"><?= htmlspecialchars($web_data['teks_pengumuman'] ?? '') ?></span>
    </div>
    <?php endif; ?>

    <section id="beranda" class="hero">
        <h1>Bentuk Karakter,<br>Bangun Kekuatan</h1>
        <p>Rasakan atmosfer bodybuilding yang autentik. Komunitas lokal aktif dan raih bentuk tubuh idealmu bersama Vanda Gym Palangkaraya.</p>
        <a href="#paket" class="btn-primary">Lihat Paket Membership</a>
    </section>

    <section id="paket">
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

    <section id="jadwal" style="background-color: #0a0a0a;">
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

    <section id="benefit" style="background-color: #050505; border-top: 1px solid #1a1a1a; border-bottom: 1px solid #1a1a1a;">
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

    <section id="galeri">
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
            <a href="galeri_gym.php" class="btn-primary" style="padding: 10px 30px; font-size: 0.9rem;">Lihat Galeri Lengkap</a>
        </div>
    </section>

    <footer>
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
                        <span>CS / Pendaftaran: <?= htmlspecialchars($web_data['wa_cs'] ?? '0821-4855-6601') ?></span>
                    </p>

                    <p style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent-gold)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
                            <line x1="12" y1="18" x2="12.01" y2="18"></line>
                        </svg>
                        Info Kelas Senam: 0821-5992-5490
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
            © 2026 Vanda Gym Classic Room.
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

    <?php
    $wa_db = $web_data['wa_cs'] ?? '082148556601';
    $wa_link = "62" . substr(preg_replace('/[^0-9]/', '', $wa_db), 1);
    ?>
    <a href="https://wa.me/<?= $wa_link ?>" target="_blank" class="wa-btn" title="Hubungi CS via WhatsApp">
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
          <path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/>
        </svg>
    </a>

    <button class="chatbot-btn" onclick="toggleChat()">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
                Info Bot Vanda Gym
            </span>
            <button class="close-chat" onclick="toggleChat()" title="Tutup Chat">×</button>
        </div>
        <div class="chat-body clearfix" id="chatBody">
            <div class="chat-msg">
                Halo Brosis! 👋 Selamat datang di Vanda Gym Classic. Aku siap bantu jawab pertanyaan seputar gym kita nih. Yuk, pilih topik yang mau ditanyakan di bawah! 💪
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: text-bottom; margin-left: 4px;">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <polyline points="19 12 12 19 5 12"></polyline>
                </svg>
            </div>
        </div>
        <div class="chat-footer-menu">
            <div class="quick-replies">
                <button class="btn-qr" onclick="kirimFaq('Apa saja peraturan gym?', 'Sip! Biar latihan makin nyaman, ini beberapa *rules* kita ya:<br><br>1️⃣ Jangan lupa bawa handuk sendiri (biar higienis bro!).<br>2️⃣ Rapikan & kembalikan alat (dumbell/plat) ke tempat semula setelah dipakai.<br>3️⃣ Area gym bebas asap rokok & vape ya.<br>4️⃣ Jaga kebersihan bareng-bareng.<br><br>Gampang kan? <i>Let\'s go!</i> 🔥')">📜 Peraturan</button>
                <button class="btn-qr" onclick="kirimFaq('Bagaimana cara menghubungi CS?', 'Butuh bantuan lebih lanjut? Tenang, langsung aja chat admin kita via WhatsApp di sini:<br><strong><a href=\'https://wa.me/<?= $wa_link ?>\' target=\'_blank\' style=\'color:var(--accent-gold);\'><?= htmlspecialchars($web_data['wa_cs'] ?? '0821-4855-6601') ?></a></strong><br><br>Bisa juga klik tombol WA hijau di pojok kiri layar. Admin kita *standby* kok! 📱')">📞 Hubungi CS</button>
                <button class="btn-qr" onclick="kirimFaq('Di mana lokasi Vanda Gym?', 'Vanda Gym Classic berlokasi strategis di <strong>Jl. Kapten Pierre Tendean No.17, Palangka Raya</strong>. 📍<br><br>Cek aja peta Google Maps di bagian paling bawah halaman ini kalau takut nyasar. Ditunggu kedatangannya ya! 🏋️‍♂️')">📍 Info Lokasi</button>
                <button class="btn-qr" onclick="kirimFaq('Berapa harga paket membership?', 'Wah, tertarik join ya? Mantap! 🤩<br><br>Untuk Gym Bulanan harganya cuma <strong>Rp <?= number_format($web_data['harga_bulanan'] ?? 175000, 0, ',', '.') ?></strong> (Udah bebas akses!).<br>Kalau mau nyoba dulu, bisa pilih 1x Visit (<strong>Rp <?= number_format($web_data['harga_harian'] ?? 25000, 0, ',', '.') ?></strong>).<br>Ada juga Kelas Senam seharga <strong>Rp <?= number_format($web_data['harga_senam'] ?? 25000, 0, ',', '.') ?>/datang</strong>.<br><br>Langsung aja klik Daftar di menu bawah biar gampang! 🚀')">💰 Harga Membership</button>
            </div>
        </div>
    </div>

    <div class="bottom-nav-mobile">
        <a href="#paket" class="nav-item">
            <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
            <span>Paket</span>
        </a>
        <a href="#jadwal" class="nav-item">
            <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            <span>Jadwal</span>
        </a>
        <a href="#galeri" class="nav-item">
            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
            <span>Galeri</span>
        </a>
        <a href="kalkulator.php" class="nav-item">
            <svg viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="8" y1="6" x2="16" y2="6"></line><line x1="16" y1="14" x2="16.01" y2="14"></line><line x1="12" y1="14" x2="12.01" y2="14"></line><line x1="8" y1="14" x2="8.01" y2="14"></line><line x1="16" y1="18" x2="16.01" y2="18"></line><line x1="12" y1="18" x2="12.01" y2="18"></line><line x1="8" y1="18" x2="8.01" y2="18"></line></svg>
            <span>Gizi</span>
        </a>
        <a href="login.php" class="nav-item">
            <svg viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
            <span>Login</span>
        </a>
        <a href="daftar.php" class="nav-item highlight">
            <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/></svg>
            <span>Daftar</span>
        </a>
    </div>

    <script>
        // SCRIPT NAVIGASI DESKTOP
        const menuToggle = document.getElementById('mobile-menu');
        const navMenu = document.getElementById('nav-menu');

        menuToggle.addEventListener('click', () => {
            menuToggle.classList.toggle('active');
            navMenu.classList.toggle('active');
        });

        // SCRIPT CHATBOT FAQ
        function toggleChat() {
            const chat = document.getElementById("chatWindow");
            chat.style.display = chat.style.display === "block" ? "none" : "block";
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

        // SCRIPT DETEKSI KONEKSI OFFLINE
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