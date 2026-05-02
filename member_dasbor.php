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
$tgl_mulai     = $tgl_mulai_raw ? date('d F Y', strtotime($tgl_mulai_raw)) : '-';
$tgl_berakhir  = $tgl_akhir_raw ? date('d F Y', strtotime($tgl_akhir_raw)) : '-';
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
    <style>
        :root { --bg-dark: #000000; --primary-red: #8E1616; --accent-gold: #E8C999; --text-light: #F8EEDF; --input-bg: #111111; --success-green: #28a745; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--bg-dark); color: var(--text-light); line-height: 1.6; overflow-x: hidden; }
        header { background-color: rgba(10, 10, 10, 0.95); padding: 10px 5%; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; border-bottom: 2px solid var(--primary-red); }
        .logo img { height: 70px; width: auto; object-fit: contain; }
        nav { display: flex; align-items: center; flex-wrap: wrap; justify-content: flex-end;}
        nav a.nav-link { color: var(--text-light); text-decoration: none; margin-left: 20px; font-weight: 600; transition: 0.3s; min-height: 44px; display: inline-flex; align-items: center; }
        nav a.nav-link:hover { color: var(--accent-gold); }
        nav a.active { color: var(--accent-gold); border-bottom: 2px solid var(--accent-gold); }
        .btn-logout { border: 2px solid var(--primary-red); padding: 0 20px; border-radius: 4px; color: var(--primary-red); margin-left: 20px; background: transparent; font-weight: bold; font-size: 1rem; min-height: 44px; min-width: 44px; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; justify-content: center; }
        .btn-logout:hover { background-color: var(--primary-red); color: white; }
        .profile-icon { margin-left: 20px; color: var(--text-light); display: inline-flex; align-items: center; justify-content: center; transition: 0.3s; min-height: 44px; }
        .profile-icon svg { width: 28px; height: 28px; }
        .announcement-banner { background-color: #1a1a1a; border-bottom: 1px solid #333; color: var(--text-light); padding: 16px 25px; text-align: center; font-size: 1.1rem; display: flex; justify-content: center; align-items: center; gap: 15px; z-index: 99; }
        .announcement-badge { background-color: var(--primary-red); color: white; padding: 5px 12px; border-radius: 4px; font-weight: bold; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; animation: pulse 2s infinite; }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(142, 22, 22, 0.7); } 70% { box-shadow: 0 0 0 8px rgba(142, 22, 22, 0); } 100% { box-shadow: 0 0 0 0 rgba(142, 22, 22, 0); } }
        .dashboard-container { padding: 40px 5%; max-width: 1200px; margin: 0 auto; }
        .alert-box { background-color: rgba(232, 201, 153, 0.1); border: 1px solid var(--accent-gold); color: var(--accent-gold); padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        .alert-box.danger { background-color: rgba(142, 22, 22, 0.15); border-color: var(--primary-red); color: #ff4d4d; }
        .alert-box.info { background-color: rgba(0, 123, 255, 0.1); border-color: #66b2ff; color: #66b2ff; }
        .dash-card { background-color: #0a0a0a; border: 1px solid #222; border-radius: 8px; padding: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.5); position: relative; overflow: hidden; margin-bottom: 30px; }
        .dash-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: var(--accent-gold); }
        .dash-card.card-danger::before { background: var(--primary-red); }
        .profile-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px;}
        .user-info h2 { color: var(--text-light); font-size: 2rem; text-transform: uppercase; margin-bottom: 5px;}
        .user-info p { color: #888; font-size: 1rem; }
        .status-badge { background: var(--success-green); color: white; padding: 6px 20px; border-radius: 20px; font-weight: bold; font-size: 0.9rem; letter-spacing: 1px; }
        .status-badge.danger { background: var(--primary-red); }
        .membership-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; border-top: 1px dashed #333; padding-top: 25px; }
        .detail-item span { display: block; color: #888; font-size: 0.85rem; margin-bottom: 5px;}
        .detail-item strong { display: block; color: var(--accent-gold); font-size: 1.3rem; }
        .detail-item strong.danger-text { color: #ff4d4d; }
        .setting-methods { display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap; }
        .set-method { flex: 1; border: 1px solid #333; border-radius: 6px; padding: 12px 10px; text-align: center; cursor: pointer; transition: 0.3s; background: #151515; position: relative; min-width: 140px; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .set-method input { position: absolute; opacity: 0; cursor: pointer; }
        .set-method span { font-weight: bold; color: #888; display: flex; align-items: center; gap: 8px; font-size: 0.85rem;}
        .set-method.active { border-color: var(--accent-gold); background: rgba(232, 201, 153, 0.1); }
        .set-method.active span { color: var(--accent-gold); }
        .btn-outline-gold { display: inline-flex; align-items: center; justify-content: center; background: transparent; border: 1px solid var(--accent-gold); color: var(--accent-gold); text-decoration: none; padding: 10px 20px; border-radius: 4px; font-weight: bold; font-size: 0.9rem; transition: 0.3s; cursor: pointer; }
        .btn-outline-gold:hover:not(:disabled) { background: var(--accent-gold); color: #000; }
        .btn-simulasi-notif { display: inline-flex; align-items: center; justify-content: center; background: transparent; border: 1px dashed #555; color: #aaa; padding: 10px 20px; border-radius: 4px; font-weight: bold; font-size: 0.9rem; transition: 0.3s; cursor: pointer; margin-left: 10px; }
        .popup-simulasi { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 2000; justify-content: center; align-items: center; }
        .popup-box { background: #111; border: 2px solid var(--accent-gold); padding: 30px; border-radius: 8px; max-width: 400px; text-align: center; }
        .popup-icon { display: flex; justify-content: center; color: var(--accent-gold); margin-bottom: 15px; }
        .action-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 40px;}
        .action-btn { background: #111; border: 1px solid #333; border-radius: 8px; padding: 25px; text-align: center; color: var(--text-light); text-decoration: none; transition: 0.3s; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 120px; }
        .action-btn:hover { border-color: var(--accent-gold); transform: translateY(-5px); background: #1a1a1a;}
        .action-btn.locked { opacity: 0.5; cursor: not-allowed; pointer-events: none;}
        .action-icon { margin-bottom: 15px; color: var(--accent-gold); }
        .action-btn.danger-border .action-icon { color: var(--primary-red); }
        .btn-primary { background-color: var(--primary-red); color: white; padding: 10px 20px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; transition: 0.3s; min-height: 44px; text-decoration: none; display: inline-block; }
        
        /* CSS TOMBOL MELAYANG DIPERBARUI */
        .wa-btn { position: fixed; bottom: 30px; left: 30px; background-color: #25D366; color: white; border-radius: 50%; width: 60px; height: 60px; display: flex; justify-content: center; align-items: center; box-shadow: 0 4px 15px rgba(0,0,0,0.6); z-index: 1000; transition: 0.3s; text-decoration: none; border: 2px solid transparent;}
        .wa-btn:hover { transform: scale(1.1); border-color: white;}
        
        .chatbot-btn { position: fixed; bottom: 30px; right: 30px; background-color: var(--primary-red); color: white; border-radius: 50%; width: 60px; height: 60px; display: flex; justify-content: center; align-items: center; box-shadow: 0 4px 15px rgba(0,0,0,0.6); z-index: 1000; transition: 0.3s; text-decoration: none; border: 2px solid transparent;}
        .chatbot-btn:hover { transform: scale(1.1); border-color: var(--accent-gold);}
        .chatbot-btn.locked { background-color: #333; color: #666; cursor: not-allowed; border-color: transparent;}
        
        @media (max-width: 768px) { header { flex-direction: column; padding: 15px; } nav { margin-top: 15px; justify-content: center;} nav a.nav-link { margin: 5px 10px; font-size: 0.9rem;} .btn-logout { margin-left: 0; margin-top: 10px; width: 100%; text-align: center;} .announcement-banner { flex-direction: column; text-align: center; } .btn-simulasi-notif { margin-left: 0; margin-top: 10px; width: 100%; } .btn-outline-gold { width: 100%; } }
    </style>
</head>
<body>

    <header>
        <div class="logo"><img src="assets/logo.png" alt="Logo"></div>
        <nav>
            <a href="member_dasbor.php" class="nav-link active">Dasbor</a>
            <a href="profil_gym_member.php" class="nav-link">Profil Gym</a>
            <a href="chatbot_member.php" id="navChatbot" class="nav-link <?= ($status_member !== 'aktif') ? 'locked' : '' ?>">Chatbot AI</a>
            <a href="kalkulator.php?source=dasbor" class="nav-link">Kalkulator Gizi</a>
            <a href="galeri_member.php" id="navGaleri" class="nav-link">Galeri Gym</a>
            <button class="btn-logout" onclick="window.location.href='index.php'">Keluar</button>
            <a href="profil_member.php" class="profile-icon" title="Profil Saya">
                <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            </a>
        </nav>
    </header>

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

            <div style="margin-top: 40px; border-top: 1px solid #222; padding-top: 25px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                    <span style="color: var(--accent-gold);"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg></span>
                    <h3 style="font-size: 1.1rem;">Pengaturan Pengingat Masa Aktif</h3>
                </div>
                <p style="color: #888; font-size: 0.85rem; margin-bottom: 15px;">Pilih jalur notifikasi penagihan membership Anda.</p>
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

        <h3 style="color: var(--accent-gold); border-bottom: 1px solid #333; padding-bottom: 10px; margin-bottom: 20px;">Aksi Cepat</h3>
        <div class="action-grid">
            <a href="kalkulator.php?source=dasbor" class="action-btn">
                <div class="action-icon"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="8" y1="6" x2="16" y2="6"></line><line x1="8" y1="14" x2="8.01" y2="14"></line><line x1="12" y1="14" x2="12.01" y2="14"></line><line x1="16" y1="14" x2="16.01" y2="14"></line></svg></div>
                <h3>Kalkulator Gizi</h3>
                <p>Hitung kalori & protein.</p>
            </a>
            <a href="perpanjang.php" class="action-btn <?= ($status_member !== 'aktif' || $sedang_perpanjang) ? 'danger-border' : '' ?>">
                <div class="action-icon"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg></div>
                <h3 style="<?= ($status_member !== 'aktif' || $sedang_perpanjang) ? 'color:var(--primary-red);' : '' ?>">Perpanjang Member</h3>
                <p><?= $sedang_perpanjang ? 'Sedang Diproses...' : 'Bayar tagihan Anda.' ?></p>
            </a>
            <a href="chatbot_member.php" class="action-btn <?= ($status_member !== 'aktif') ? 'locked' : '' ?>" <?= ($status_member !== 'aktif') ? 'onclick="event.preventDefault(); alert(\'Fitur AI terkunci. Silakan perpanjang membership Anda.\')"' : '' ?>>
                <div class="action-icon"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"></rect><circle cx="12" cy="5" r="2"></circle><path d="M12 7v4"></path></svg></div>
                <h3>Chatbot AI Vanda</h3>
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