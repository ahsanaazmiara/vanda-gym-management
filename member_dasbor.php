<?php
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
    
    // Reset semua jadi 0 dulu, baru set yang dipilih jadi 1 (notif_email kita paksa 0)
    mysqli_query($koneksi, "UPDATE users SET notif_wa=0, notif_email=0, notif_dash=0 WHERE id_user=$id_user");
    
    // $pref nilainya hanya 'wa' atau 'dash'
    $column = "notif_" . $pref; 
    $q = mysqli_query($koneksi, "UPDATE users SET $column=1 WHERE id_user=$id_user");
    
    echo json_encode(['status' => $q ? 'success' : 'error']);
    exit;
}

// =========================================================
// 2. AMBIL DATA MEMBER & STATUS DENGAN LOGIKA PRIORITAS
// =========================================================
$query = "
    SELECT u.*, m.paket_bulan, m.tgl_mulai, m.tgl_berakhir, m.status, m.metode_bayar 
    FROM users u 
    LEFT JOIN membership m ON u.id_user = m.id_user 
    WHERE u.id_user = $id_user 
    ORDER BY 
        CASE 
            WHEN m.status = 'aktif' THEN 1
            ELSE 2
        END,
        m.id_membership DESC 
    LIMIT 1
";

$result = mysqli_query($koneksi, $query);
$data = mysqli_fetch_assoc($result);

// Cek apakah ada status pending perpanjangan secara terpisah
$cek_pending = mysqli_query($koneksi, "SELECT status FROM membership WHERE id_user = $id_user AND status = 'pending' AND jenis_pengajuan = 'perpanjang' LIMIT 1");
$sedang_perpanjang = (mysqli_num_rows($cek_pending) > 0);

// Ambil Data Pengaturan Web
$q_web = mysqli_query($koneksi, "SELECT * FROM pengaturan_web WHERE id=1");
$web_data = mysqli_fetch_assoc($q_web);

// Format Nomor WA agar dinamis mengikuti pengaturan Admin
$wa_raw = preg_replace('/[^0-9]/', '', $web_data['wa_cs'] ?? '082148556601');
$wa_link = (substr($wa_raw, 0, 1) == '0') ? '62' . substr($wa_raw, 1) : $wa_raw;

// 3. SIAPKAN VARIABEL
$nama_lengkap  = $data['nama_lengkap'] ?? 'Member Vanda';
$email_user    = $data['email'] ?? '';
$status_member = $data['status'] ?? 'belum_daftar'; 
$paket         = $data['paket_bulan'] ? $data['paket_bulan'] . ' Bulan Gym' : 'Belum Ada Paket';
$tgl_mulai_raw = $data['tgl_mulai'];
$tgl_akhir_raw = $data['tgl_berakhir'];

// Kamus Nama Bulan Indonesia
$bulanIndo = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
    '04' => 'April', '05' => 'Mei', '06' => 'Juni',
    '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
    '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

// Konversi ke format Indonesia
if ($tgl_mulai_raw) {
    $time_mulai = strtotime($tgl_mulai_raw);
    $tgl_mulai = date('d', $time_mulai) . ' ' . $bulanIndo[date('m', $time_mulai)] . ' ' . date('Y', $time_mulai);
} else {
    $tgl_mulai = '-';
}

if ($tgl_akhir_raw) {
    $time_akhir = strtotime($tgl_akhir_raw);
    $tgl_berakhir = date('d', $time_akhir) . ' ' . $bulanIndo[date('m', $time_akhir)] . ' ' . date('Y', $time_akhir);
} else {
    $tgl_berakhir = '-';
}
$metode_bayar  = $data['metode_bayar'] ? strtoupper($data['metode_bayar']) : '-';

// Tentukan preferensi notifikasi saat ini untuk tampilan radio button
$current_notif = 'wa'; // Default WA
if ($data['notif_dash'] == 1) {
    $current_notif = 'dasbor';
}

// 4. HITUNG SISA HARI
$sisa_hari = 0;
if ($status_member === 'aktif' && $tgl_akhir_raw) {
    $sekarang = time();
    $batas_waktu = strtotime($tgl_akhir_raw);
    $selisih = $batas_waktu - $sekarang;
    $sisa_hari = max(0, round($selisih / (60 * 60 * 24)));
    
    // Auto-update jika lewat waktu
    if ($sisa_hari <= 0 && $tgl_akhir_raw < date('Y-m-d')) {
        $status_member = 'kadaluarsa';
        mysqli_query($koneksi, "UPDATE membership SET status='kadaluarsa' WHERE id_user=$id_user AND status='aktif'");
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dasbor Member - Vanda Gym Classic</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
    <div class="logo">
        <img src="assets/logo.png" alt="Logo">
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
        <a href="member_dasbor.php" class="nav-link active">Dasbor</a>
        <a href="profil_gym_member.php" class="nav-link">Profil Gym</a>
        <a href="chatbot_member.php" class="nav-link <?= ($status_member !== 'aktif') ? 'locked' : '' ?>">Chatbot AI</a>
        <a href="kalkulator.php?source=dasbor" class="nav-link">Kalkulator Gizi</a>
        <a href="galeri_member.php" class="nav-link">Galeri Gym</a>
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
    color: var(--accent-gold); 
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
    .header-right { order: 3; }
    nav { order: 2; flex: 1; justify-content: flex-end; }
    .profile-icon-header { margin-left: 15px; border: 1px solid #333; border-radius: 4px; }
}

/* =========================================
   PENGECILAN KHUSUS DASHBOARD (MOBILE)
   ========================================= */
@media (max-width: 768px) {
    .header-right { gap: 5px; }
    .profile-icon-header { color: var(--accent-gold); }
    .btn-logout { width: 85% !important; margin: 20px auto !important; }

    .dashboard-container { padding: 20px 15px; }
    
    .alert-box { flex-direction: column; text-align: center; gap: 10px; padding: 12px 15px; font-size: 0.9rem; }
    .alert-box a { width: 100%; text-align: center; padding: 8px 15px; font-size: 0.85rem; }
    
    .dash-card { padding: 20px 15px; margin-bottom: 20px; }

    /* Info Profil */
    .profile-header { flex-direction: column; align-items: center; text-align: center; gap: 10px; margin-bottom: 15px; }
    .user-info h2 { font-size: 1.4rem; margin-bottom: 2px;}
    .user-info p { font-size: 0.85rem; }
    .status-badge { font-size: 0.8rem; padding: 4px 15px; }

    /* DETAIL MEMBERSHIP - Diubah jadi Kiri Kanan agar mudah dibaca */
    .membership-details { 
        display: flex;
        flex-direction: column;
        gap: 8px;
        padding-top: 15px;
    }
    .detail-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px dashed #333;
        padding-bottom: 8px;
    }
    .detail-item:last-child { border-bottom: none; padding-bottom: 0; }
    .detail-item span { font-size: 0.85rem; margin-bottom: 0; text-align: left; }
    .detail-item strong { font-size: 0.95rem; text-align: right; } 

    /* Pengaturan Notifikasi */
    .setting-methods { gap: 10px; margin-bottom: 10px; }
    .set-method { min-width: 100%; padding: 10px; } 
    .set-method span { font-size: 0.8rem; }
    .btn-outline-gold { width: 100%; font-size: 0.85rem; padding: 8px 15px; margin-bottom: 10px; }
    .btn-simulasi-notif { margin-left: 0; width: 100%; font-size: 0.85rem; padding: 8px 15px; } 

    /* AKSI CEPAT - Diubah jadi 1 Baris Ke Bawah dengan Ikon di Kiri */
    .action-grid { 
        grid-template-columns: 1fr; 
        gap: 12px; 
        margin-bottom: 20px; 
    }
    .action-btn { 
        display: grid;
        grid-template-columns: auto 1fr;
        grid-template-rows: auto auto;
        column-gap: 15px;
        row-gap: 4px;
        padding: 15px; 
        min-height: auto; 
        text-align: left;
        align-items: center;
    }
    .action-icon { 
        grid-column: 1;
        grid-row: 1 / span 2;
        margin-bottom: 0; 
        display: flex;
        align-items: center;
    }
    .action-icon svg { width: 32px; height: 32px; } 
    .action-btn h3 { grid-column: 2; grid-row: 1; font-size: 1rem; margin-bottom: 0; line-height: 1.2;} 
    .action-btn p { grid-column: 2; grid-row: 2; font-size: 0.8rem; line-height: 1.2; color: #aaa; margin-bottom: 0;} 

    /* Popups */
    .popup-box { padding: 20px; width: 90%; }
    .popup-icon { font-size: 1.5rem; margin-bottom: 10px; }
    #popupTitle { font-size: 1.2rem; }
}

/* Penyesuaian akhir untuk layar HP super kecil */
@media (max-width: 480px) {
    .wa-btn, .chatbot-btn { width: 45px; height: 45px; bottom: 20px; }
    .wa-btn { left: 15px; }
    .chatbot-btn { right: 15px; }
    .wa-btn svg, .chatbot-btn svg { width: 20px; height: 20px; }
}
</style>

    <?php if (($web_data['pengumuman_aktif'] ?? '') === 'aktif'): ?>
    <div class="announcement-banner">
        <span class="announcement-badge">Info Terkini</span>
        <span class="announcement-text"><?= htmlspecialchars($web_data['teks_pengumuman']) ?></span>
    </div>
    <?php endif; ?>

    <div class="dashboard-container">
        
        <?php if ($sedang_perpanjang && $status_member === 'aktif'): ?>
            <div class="alert-box info">
                <div><strong>Info:</strong> Permintaan <strong>Perpanjangan</strong> Anda sedang diverifikasi oleh Admin. Masa aktif Anda saat ini masih berjalan.</div>
            </div>
        <?php endif; ?>

        <?php if ($status_member === 'aktif'): ?>
            <div class="alert-box">
                <div><strong>Info:</strong> Masa aktif membership Anda tersisa <strong style="font-size:1.2rem;"><?= $sisa_hari ?> Hari</strong> lagi.</div>
                <?php if (!$sedang_perpanjang): ?>
                    <a href="perpanjang.php" class="btn-primary" style="min-height: 35px; padding: 5px 15px; font-size: 0.9rem;">Perpanjang</a>
                <?php endif; ?>
            </div>
        <?php elseif ($status_member === 'kadaluarsa'): ?>
            <div class="alert-box danger">
                <div><strong>Perhatian:</strong> Masa aktif membership Anda telah <strong>KEDALUWARSA</strong>.</div>
                <a href="perpanjang.php" class="btn-primary" style="min-height: 35px; padding: 5px 15px; font-size: 0.9rem;">Perpanjang Sekarang</a>
            </div>
        <?php endif; ?>

        <div class="dash-card <?= ($status_member !== 'aktif') ? 'card-danger' : '' ?>">
            <div class="profile-header">
                <div class="user-info">
                    <h2><?= htmlspecialchars($nama_lengkap) ?></h2>
                    <p><?= htmlspecialchars($email_user) ?></p>
                </div>
                <div class="status-badge <?= ($status_member !== 'aktif') ? 'danger' : '' ?>">
                    <?= strtoupper($status_member) ?>
                </div>
            </div>

            <div class="membership-details">
                <div class="detail-item"><span>Paket Saat Ini</span><strong><?= $paket ?></strong></div>
                <div class="detail-item"><span>Status Pembayaran</span><strong style="color: <?= ($status_member === 'aktif') ? 'var(--success-green)' : 'var(--primary-red)' ?>;"><?= ($status_member === 'aktif') ? 'LUNAS' : 'MENUNGGU' ?></strong></div>
                <div class="detail-item"><span>Tanggal Mulai</span><strong><?= $tgl_mulai ?></strong></div>
                <div class="detail-item"><span>Tanggal Berakhir</span><strong class="<?= ($status_member !== 'aktif') ? 'danger-text' : '' ?>"><?= $tgl_berakhir ?></strong></div>
            </div>

            <div style="margin-top: 30px; border-top: 1px solid #222; padding-top: 20px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                    <span style="color: var(--accent-gold);"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg></span>
                    <h3 style="font-size: 1rem;">Pengaturan Pengingat</h3>
                </div>
                <p style="color: #888; font-size: 0.8rem; margin-bottom: 15px;">Pilih jalur notifikasi penagihan membership Anda.</p>
                <form id="formNotif" onsubmit="simpanNotif(event)">
                    <div class="setting-methods">
                        <label class="set-method <?= ($current_notif == 'wa') ? 'active' : '' ?>">
                            <input type="radio" name="prefNotif" value="wa" <?= ($current_notif == 'wa') ? 'checked' : '' ?> onchange="visualRadio(this)">
                            <span>WhatsApp</span>
                        </label>
                        <label class="set-method <?= ($current_notif == 'dasbor') ? 'active' : '' ?>">
                            <input type="radio" name="prefNotif" value="dash" <?= ($current_notif == 'dasbor') ? 'checked' : '' ?> onchange="visualRadio(this)">
                            <span>Hanya Dasbor</span>
                        </label>
                    </div>
                    <button type="submit" id="btnSimpanNotif" class="btn-outline-gold">Simpan Pengaturan</button>
                    <button type="button" class="btn-simulasi-notif" onclick="testSimulasiNotif()">▶ Test Simulasi</button>
                </form>
            </div>
        </div>

        <h3 style="color: var(--accent-gold); border-bottom: 1px solid #333; padding-bottom: 10px; margin-bottom: 15px; font-size: 1.1rem;">Aksi Cepat</h3>
        <div class="action-grid">
            <a href="kalkulator.php?source=dasbor" class="action-btn">
                <div class="action-icon"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="8" y1="6" x2="16" y2="6"></line><line x1="8" y1="14" x2="8.01" y2="14"></line><line x1="12" y1="14" x2="12.01" y2="14"></line><line x1="16" y1="14" x2="16.01" y2="14"></line></svg></div>
                <h3>Kalkulator Gizi</h3>
                <p>Hitung kalori & protein.</p>
            </a>
            <a href="perpanjang.php" class="action-btn <?= ($status_member !== 'aktif' || $sedang_perpanjang) ? 'danger-border' : '' ?>">
                <div class="action-icon"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg></div>
                <h3 style="<?= ($status_member !== 'aktif' || $sedang_perpanjang) ? 'color:var(--primary-red);' : '' ?>">Perpanjang</h3>
                <p><?= $sedang_perpanjang ? 'Sedang Diproses...' : 'Bayar tagihan Anda.' ?></p>
            </a>
            <a href="chatbot_member.php" class="action-btn <?= ($status_member !== 'aktif') ? 'locked' : '' ?>" <?= ($status_member !== 'aktif') ? 'onclick="event.preventDefault(); alert(\'Fitur AI terkunci. Silakan perpanjang membership Anda.\')"' : '' ?>>
                <div class="action-icon"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"></rect><circle cx="12" cy="5" r="2"></circle><path d="M12 7v4"></path></svg></div>
                <h3>Chatbot AI</h3>
                <p>Cek kalori via foto.</p>
            </a>
            <a href="galeri_member.php" class="action-btn <?= ($status_member !== 'aktif') ? 'locked' : '' ?>" <?= ($status_member !== 'aktif') ? 'onclick="event.preventDefault(); alert(\'Galeri terkunci. Silakan perpanjang membership Anda.\')"' : '' ?>>
                <div class="action-icon"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg></div>
                <h3>Video Latihan</h3>
                <p>Panduan alat gym.</p>
            </a>
        </div>
    </div>

    <div id="popupSimulasi" class="popup-simulasi">
        <div class="popup-box">
            <div id="popupIcon" class="popup-icon"></div>
            <h3 id="popupTitle"></h3>
            <p id="popupMsg" style="margin-bottom: 20px; font-size: 0.9rem; color: #ccc;"></p>
            <button onclick="document.getElementById('popupSimulasi').style.display='none'" class="btn-primary">Tutup</button>
        </div>
    </div>

    <a href="https://wa.me/<?= $wa_link ?>" target="_blank" class="wa-btn" title="Hubungi CS Vanda Gym">
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
          <path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/>
        </svg>
    </a>
    
    <a href="chatbot_member.php" class="chatbot-btn <?= ($status_member !== 'aktif') ? 'locked' : '' ?>" <?= ($status_member !== 'aktif') ? 'onclick="event.preventDefault(); alert(\'Fitur AI terkunci.\')"' : '' ?> title="Chatbot Vanda AI">
        <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="10" rx="2"></rect>
            <circle cx="12" cy="5" r="2"></circle>
            <path d="M12 7v4"></path>
            <line x1="8" y1="16" x2="8.01" y2="16"></line>
            <line x1="16" y1="16" x2="16.01" y2="16"></line>
        </svg>
    </a>

    <script>
    const menuToggle = document.getElementById('mobile-menu');
    const navMenu = document.getElementById('nav-menu');

    menuToggle.addEventListener('click', () => {
        menuToggle.classList.toggle('active');
        navMenu.classList.toggle('active');
    });

    // Menutup menu jika link diklik
    document.querySelectorAll('#nav-menu a').forEach(link => {
        link.addEventListener('click', () => {
            menuToggle.classList.remove('active');
            navMenu.classList.remove('active');
        });
    });

        function visualRadio(el) {
            document.querySelectorAll('.set-method').forEach(box => box.classList.remove('active'));
            el.closest('.set-method').classList.add('active');
        }

        function simpanNotif(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSimpanNotif');
            const prevText = btn.innerText;
            btn.innerText = "Menyimpan..."; btn.disabled = true;

            const formData = new FormData(document.getElementById('formNotif'));
            formData.append('action', 'update_notif');

            fetch('member_dasbor.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    btn.innerText = "Tersimpan! ✔️";
                    setTimeout(() => { btn.innerText = prevText; btn.disabled = false; }, 2000);
                }
            });
        }

        function testSimulasiNotif() {
            const selected = document.querySelector('input[name="prefNotif"]:checked').value;
            const popup = document.getElementById('popupSimulasi');
            const icon = document.getElementById('popupIcon');
            const title = document.getElementById('popupTitle');
            const msg = document.getElementById('popupMsg');

            if(selected === 'wa') {
                const text = encodeURIComponent("[Vanda Gym] Halo <?= htmlspecialchars($nama_lengkap) ?>, masa aktif membership Anda tersisa 7 hari lagi.");
                window.open(`https://wa.me/<?= $wa_link ?>?text=${text}`, '_blank');
            } else if (selected === 'dash') {
                popup.style.display = 'flex';
                icon.innerHTML = '🔔'; 
                title.innerText = "Simulasi Dasbor"; 
                msg.innerText = "Sistem hanya akan memunculkan banner peringatan di halaman ini saat masa aktif hampir habis.";
            }
        }
    </script>
</body>
</html>