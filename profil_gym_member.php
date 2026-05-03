<?php
session_start();
require 'includes/koneksi.php'; // Pastikan path ini benar

// Proteksi Member: Hanya yang sudah login boleh akses
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'member') {
    header("Location: login.php");
    exit;
}

// 1. AMBIL DATA PENGATURAN WEB DARI DATABASE
$q_pengaturan = mysqli_query($koneksi, "SELECT * FROM pengaturan_web WHERE id=1");
$web_data = mysqli_fetch_assoc($q_pengaturan);

// 2. DECODE JAM OPERASIONAL GYM (JSON ke Array)
$jam = isset($web_data['jam_operasional']) && !empty($web_data['jam_operasional']) ? json_decode($web_data['jam_operasional'], true) : [];

// Default jika database kosong
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

// 3. DECODE JADWAL KELAS SENAM (JSON ke Array)
$js_data = isset($web_data['jadwal_senam']) && !empty($web_data['jadwal_senam']) ? json_decode($web_data['jadwal_senam'], true) : [];

if (empty($js_data)) {
    $js_data = [
        'sr' => ['libur' => false, 'buka' => '16.15', 'tutup' => '17.15', 'ket' => 'BL+'],
        'sk' => ['libur' => false, 'buka' => '16.00', 'tutup' => '17.00', 'ket' => 'Zumba'],
        'sb' => ['libur' => false, 'buka' => '08.00', 'tutup' => '09.00', 'ket' => 'BL+'],
        'mg' => ['libur' => false, 'buka' => '15.30', 'tutup' => '16.30', 'ket' => 'Pilates']
    ];
}

// Fungsi Helper untuk render Jam Gym
function renderJam($jam_array, $sesi_key) {
    $libur = $jam_array[$sesi_key]['libur'] ?? false;
    $buka  = $jam_array[$sesi_key]['buka'] ?? '';
    $tutup = $jam_array[$sesi_key]['tutup'] ?? '';
    
    if ($libur === true || $libur === 'true') {
        return '<span class="schedule-time" style="display:block; margin-bottom:5px; color: var(--primary-red); font-weight:bold;">Libur / Tutup</span>';
    } else {
        return '<span class="schedule-time" style="display:block; margin-bottom:5px;">'.$buka.' - '.$tutup.' WIB</span>';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Gym - Vanda Gym Classic</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <header>
        <div class="logo">
            <img src="assets/logo.png" alt="Vanda Gym Classic Logo">
        </div>

        <div class="header-right">
            <a href="profil_member.php" class="profile-icon-header" title="Profil Saya">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
        </a>

            <button class="menu-toggle" id="mobile-menu" aria-label="Toggle Menu">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>
        </div>

        <nav id="nav-menu">
            <a href="member_dasbor.php" class="nav-link">Dasbor</a>
            <a href="profil_gym_member.php" class="nav-link active">Profil Gym</a>
            <a href="chatbot_member.php" id="navChatbot" class="nav-link">Chatbot AI</a>
            <a href="kalkulator.php?source=dasbor" class="nav-link">Kalkulator Gizi</a>
            <a href="galeri_member.php" id="navGaleri" class="nav-link">Galeri Gym</a>
            <button class="btn-logout" onclick="window.location.href='index.php'">Keluar</button>
        </nav>
    </header>
    
    <style>
    /* Container untuk membungkus ikon profil & hamburger */
    .header-right {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* Styling Ikon Profil di Header */
    .profile-icon-header {
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--accent-gold); /* Warna emas agar menonjol */
        width: 40px;
        height: 40px;
        transition: 0.3s;
        border: 1px solid transparent;
    }

    .profile-icon-header:hover {
        transform: scale(1.1);
        color: var(--text-light);
    }

    /* Sembunyikan profil di dalam nav-menu jika ada */
    nav .profile-icon { display: none; }

    /* Penyesuaian untuk tampilan Desktop */
    @media (min-width: 769px) {
        .header-right {
            order: 3; /* Pindahkan ke paling kanan */
        }
        nav {
            order: 2; /* Menu di tengah */
            flex: 1;
            justify-content: flex-end;
        }
        .profile-icon-header {
            margin-left: 15px;
            border: 1px solid #333;
            border-radius: 4px;
        }
    }

    /* Penyesuaian Mobile */
    @media (max-width: 768px) {
        .header-right {
            gap: 5px;
        }
        .profile-icon-header {
            color: var(--accent-gold);
        }
        /* Pastikan tombol logout di dalam hamburger tetap rapi */
        .btn-logout {
            width: 85% !important;
            margin: 20px auto !important;
        }
    }
    </style>

    <?php if (($web_data['pengumuman_aktif'] ?? '') === 'aktif'): ?>
    <div class="announcement-banner" id="infoBanner">
        <span class="announcement-badge">Info Terkini</span>
        <span class="announcement-text"><?= htmlspecialchars($web_data['teks_pengumuman'] ?? '') ?></span>
    </div>
    <?php endif; ?>

    <section class="hero" style="height: 60vh; border-bottom: 1px solid #333;">
        <h1>Vanda Gym Classic</h1>
        <p>Jelajahi fasilitas kebugaran terbaik di Palangka Raya. Kenali jadwal operasional kami dan jadwalkan latihanmu minggu ini.</p>
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
                        <span class="schedule-time">
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

    <section id="galeri">
        <h2 class="section-title">Fasilitas Gym Kami</h2>
        <p style="text-align: center; color: #888; margin-bottom: 30px;">Geser untuk melihat beberapa fasilitas alat beban dan area gym.</p>
        
        <div class="gallery-slider">
            <img src="assets/foto-gym-1.jpeg" alt="Area Gym Utama Mesin Beban" class="gallery-item">
            <img src="assets/foto-gym-2.jpeg" alt="Fasilitas Mesin Gym Lengkap" class="gallery-item">
            <img src="assets/foto-gym-3.jpeg" alt="Area Angkat Beban Bebas (Free Weight)" class="gallery-item">
            <img src="assets/foto-gym-4.jpeg" alt="Rak Dumbbell Lengkap" class="gallery-item">
            <img src="assets/foto-gym-7.jpeg" alt="Pintu Masuk Vanda Gym Classic" class="gallery-item">
            <img src="assets/foto-gym-5.jpeg" alt="Area Parkir Mobil Luas" class="gallery-item">
            <img src="assets/foto-gym-6.jpeg" alt="Lobby Gym" class="gallery-item">
        </div>

        <div style="text-align: center; margin-top: 50px;">
            <a href="galeri_member.php" id="btnGaleriSection" class="btn-primary" style="padding: 12px 40px; font-size: 1rem;">Lihat Galeri & Tutorial Lengkap</a>
        </div>
    </section>

    <footer>
        <div class="footer-container">
            <div class="footer-info">
                <h3>Vanda Gym Classic</h3>
                <p>Membentuk Karakter, Membangun Kekuatan.</p>
                
                <div style="margin-top: 25px;">
                    <p style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent-gold)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        Jl. Kapten Pierre Tendean No.17, Palangka Raya
                    </p>
                    
                    <p style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent-gold)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                        <span>CS / Pendaftaran: <?= htmlspecialchars($web_data['wa_cs'] ?? '0821-4855-6601') ?></span>
                    </p>

                    <p style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent-gold)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
                            <line x1="12" y1="18" x2="12.01" y2="18"></line>
                        </svg>
                        Info Kelas Senam: 0821-5992-5490
                    </p>

                    <p style="display: flex; align-items: center; gap: 12px; margin-top: 20px;">
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

        <div class="footer-bottom">© 2026 Vanda Gym Classic Room.</div>
    </footer>

    <?php
    $wa_db = $web_data['wa_cs'] ?? '082148556601';
    $wa_link = "62" . substr(preg_replace('/[^0-9]/', '', $wa_db), 1);
    ?>
    <a href="https://wa.me/<?= $wa_link ?>" target="_blank" class="wa-btn" title="Hubungi CS via WhatsApp">
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
          <path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/>
        </svg>
    </a>

    <a href="chatbot_member.php" id="floatingChatbot" class="chatbot-btn" title="Tanya Chatbot AI Vanda">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"></rect><circle cx="12" cy="5" r="2"></circle><path d="M12 7v4"></path><line x1="8" y1="16" x2="8.01" y2="16"></line><line x1="16" y1="16" x2="16.01" y2="16"></line></svg>
    </a>

    <script>
        const menuToggle = document.getElementById('mobile-menu');
        const navMenu = document.getElementById('nav-menu');

        menuToggle.addEventListener('click', () => {
            menuToggle.classList.toggle('active');
            navMenu.classList.toggle('active');
        });

        document.querySelectorAll('#nav-menu a').forEach(link => {
            link.addEventListener('click', () => {
                menuToggle.classList.remove('active');
                navMenu.classList.remove('active');
            });
        });

        const urlParams = new URLSearchParams(window.location.search);
        const statusMember = urlParams.get('status') || 'aktif';
        const floatingChatbot = document.getElementById('floatingChatbot');
        const navChatbot = document.getElementById('navChatbot');
        const navGaleri = document.getElementById('navGaleri');
        const btnGaleriSection = document.getElementById('btnGaleriSection');

        if (statusMember === 'kadaluarsa') {
            const pesanTerkunci = 'Fitur Eksklusif terkunci. Silakan perpanjang membership Anda terlebih dahulu.';
            navChatbot.style.color = "#777";
            navChatbot.onclick = function(e) { e.preventDefault(); alert(pesanTerkunci); };
            navGaleri.style.color = "#777";
            navGaleri.onclick = function(e) { e.preventDefault(); alert(pesanTerkunci); };
            if(btnGaleriSection) {
                btnGaleriSection.style.backgroundColor = "#333";
                btnGaleriSection.style.color = "#888";
                btnGaleriSection.onclick = function(e) { e.preventDefault(); alert(pesanTerkunci); };
            }
            floatingChatbot.classList.add('locked');
            floatingChatbot.title = "AI Terkunci";
            floatingChatbot.href = "#";
            floatingChatbot.onclick = (e) => { e.preventDefault(); alert(pesanTerkunci); };
        }
    </script>
</body>
</html>