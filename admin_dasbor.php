<?php
session_start();
require 'includes/koneksi.php';

// 1. PROTEKSI: Hanya Admin yang boleh masuk
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Pastikan tabel pengaturan_web memiliki minimal 1 baris data
$cek_pengaturan = mysqli_query($koneksi, "SELECT id FROM pengaturan_web WHERE id=1");
if(mysqli_num_rows($cek_pengaturan) == 0) {
    mysqli_query($koneksi, "INSERT INTO pengaturan_web (id) VALUES (1)");
}
// Memaksa MySQL agar kolom role bisa menerima teks bebas (termasuk 'arsip')
mysqli_query($koneksi, "ALTER TABLE users MODIFY COLUMN role VARCHAR(20) DEFAULT 'member'");

// =========================================================
// BLOK PHP: HANDLING AJAX 
// =========================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    // --- VERIFIKASI ---
    if ($action === 'terima') {
        $id_m = mysqli_real_escape_string($koneksi, $_POST['id_membership']);
        $q = mysqli_query($koneksi, "UPDATE membership SET status='aktif' WHERE id_membership='$id_m'");
        
        $cek = mysqli_query($koneksi, "SELECT id_user FROM membership WHERE id_membership='$id_m'");
        if ($data = mysqli_fetch_assoc($cek)) {
            $id_u = $data['id_user'];
            mysqli_query($koneksi, "UPDATE users SET role='member' WHERE id_user='$id_u'");
        }
        echo json_encode(['status' => $q ? 'success' : 'error']); exit;
    }
    
    if ($action === 'tolak') {
        $id_m = mysqli_real_escape_string($koneksi, $_POST['id_membership']);
        $alasan = mysqli_real_escape_string($koneksi, $_POST['alasan']);
        $q = mysqli_query($koneksi, "UPDATE membership SET status='ditolak', alasan_tolak='$alasan' WHERE id_membership='$id_m'");
        echo json_encode(['status' => $q ? 'success' : 'error']); exit;
    }

    // --- MEMBER MANAJEMEN (ARSIP & PULIHKAN) ---
    if ($action === 'arsip') {
        $id_u = mysqli_real_escape_string($koneksi, $_POST['id_user']);
        // Ubah role menjadi 'arsip' agar tidak bisa login, tapi data riwayat transaksi tetap aman!
        $q = mysqli_query($koneksi, "UPDATE users SET role='arsip' WHERE id_user='$id_u'");
        echo json_encode(['status' => $q ? 'success' : 'error']); exit;
    }

    if ($action === 'pulihkan') {
        $id_u = mysqli_real_escape_string($koneksi, $_POST['id_user']);
        // Kembalikan role ke 'member'
        $q = mysqli_query($koneksi, "UPDATE users SET role='member' WHERE id_user='$id_u'");
        echo json_encode(['status' => $q ? 'success' : 'error']); exit;
    }

    if ($action === 'edit_member') {
        $id_u = mysqli_real_escape_string($koneksi, $_POST['id_user']);
        $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
        $wa = mysqli_real_escape_string($koneksi, $_POST['wa']);
        $pass = $_POST['pass'];

        $query_upd = "UPDATE users SET nama_lengkap='$nama', no_wa='$wa'";
        if (!empty($pass)) {
            $pass_hash = password_hash($pass, PASSWORD_DEFAULT);
            $query_upd .= ", password='$pass_hash'";
        }
        $query_upd .= " WHERE id_user='$id_u'";
        
        $q = mysqli_query($koneksi, $query_upd);
        echo json_encode(['status' => $q ? 'success' : 'error']); exit;
    }
    
    if ($action === 'tambah_member') {
        $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
        $email = mysqli_real_escape_string($koneksi, $_POST['email']);
        $wa = mysqli_real_escape_string($koneksi, $_POST['wa']);
        $pass = password_hash($_POST['pass'], PASSWORD_DEFAULT);
        $paket = (int)$_POST['paket'];
        $tgl_mulai = $_POST['tgl'];

        $id_new = 0;
        $cek = mysqli_query($koneksi, "SELECT id_user, role FROM users WHERE email='$email'");
        if(mysqli_num_rows($cek) > 0) { 
            $data_u = mysqli_fetch_assoc($cek);
            if ($data_u['role'] === 'admin') { echo json_encode(['status' => 'error', 'message' => 'Email admin!']); exit; }
            $id_u = $data_u['id_user'];
            $cek_m = mysqli_query($koneksi, "SELECT status FROM membership WHERE id_user='$id_u' AND status != 'ditolak'");
            if(mysqli_num_rows($cek_m) > 0) {
                echo json_encode(['status' => 'error', 'message' => 'Email sudah terdaftar aktif.']); exit; 
            } else {
                mysqli_query($koneksi, "UPDATE users SET nama_lengkap='$nama', no_wa='$wa', password='$pass', role='member' WHERE id_user='$id_u'");
                $id_new = $id_u;
            }
        } else {
            $q1 = mysqli_query($koneksi, "INSERT INTO users (nama_lengkap, email, no_wa, password, role) VALUES ('$nama', '$email', '$wa', '$pass', 'member')");
            if($q1) $id_new = mysqli_insert_id($koneksi);
        }

        if ($id_new > 0) {
            $harga = ($paket == 1) ? 175000 : (($paket == 2) ? 350000 : 525000);
            $tgl_akhir = date('Y-m-d', strtotime($tgl_mulai . " + $paket months"));
            $q2 = mysqli_query($koneksi, "INSERT INTO membership (id_user, jenis_pengajuan, paket_bulan, total_harga, tgl_mulai, tgl_berakhir, metode_bayar, status) 
                                          VALUES ($id_new, 'daftar', $paket, $harga, '$tgl_mulai', '$tgl_akhir', 'tunai', 'aktif')");
            echo json_encode(['status' => $q2 ? 'success' : 'error']);
        }
        exit;
    }

    // --- PENGATURAN KONTEN ---
    if ($action === 'update_banner') {
        $status = mysqli_real_escape_string($koneksi, $_POST['status']);
        $teks = mysqli_real_escape_string($koneksi, $_POST['teks']);
        mysqli_query($koneksi, "UPDATE pengaturan_web SET pengumuman_aktif='$status', teks_pengumuman='$teks' WHERE id=1");
        echo json_encode(['status' => 'success']); exit;
    }
    if ($action === 'update_harga') {
        $harian = (int)$_POST['harian']; $bulanan = (int)$_POST['bulanan']; $senam = (int)$_POST['senam'];
        mysqli_query($koneksi, "UPDATE pengaturan_web SET harga_harian=$harian, harga_bulanan=$bulanan, harga_senam=$senam WHERE id=1");
        echo json_encode(['status' => 'success']); exit;
    }
    if ($action === 'update_kontak') {
        $wa = mysqli_real_escape_string($koneksi, $_POST['wa']); $ig = mysqli_real_escape_string($koneksi, $_POST['ig']);
        mysqli_query($koneksi, "UPDATE pengaturan_web SET wa_cs='$wa', ig='$ig' WHERE id=1");
        echo json_encode(['status' => 'success']); exit;
    }
    if ($action === 'update_jam') {
        $jam_json = mysqli_real_escape_string($koneksi, $_POST['data_jam']);
        mysqli_query($koneksi, "UPDATE pengaturan_web SET jam_operasional='$jam_json' WHERE id=1");
        echo json_encode(['status' => 'success']); exit;
    }
    if ($action === 'update_jadwal_senam') {
        $js_json = mysqli_real_escape_string($koneksi, $_POST['data_js']);
        mysqli_query($koneksi, "UPDATE pengaturan_web SET jadwal_senam='$js_json' WHERE id=1");
        echo json_encode(['status' => 'success']); exit;
    }

    // --- MANAJEMEN MEDIA GALERI ---
    if ($action === 'upload_galeri') {
        $judul = mysqli_real_escape_string($koneksi, $_POST['judul_media']);
        $kategori = $_POST['kategori_media'];
        $tipe = $_POST['tipe_media'];
        
        if(isset($_FILES['file_media']) && $_FILES['file_media']['error'] == 0) {
            $file = $_FILES['file_media'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_img = ['jpg', 'jpeg', 'png', 'webp'];
            $allowed_vid = ['mp4', 'webm'];
            
            if (($tipe == 'foto' && !in_array($ext, $allowed_img)) || ($tipe == 'video' && !in_array($ext, $allowed_vid))) {
                echo json_encode(['status' => 'error', 'message' => 'Format file tidak sesuai!']); exit;
            }

            $nama_file_baru = time() . '_' . rand(100,999) . '.' . $ext;
            $target_dir = "uploads/galeri/";
            if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
            $target_file = $target_dir . $nama_file_baru;

            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                $q = mysqli_query($koneksi, "INSERT INTO galeri_gym (judul, kategori, tipe_media, file_path) VALUES ('$judul', '$kategori', '$tipe', '$target_file')");
                echo json_encode(['status' => $q ? 'success' : 'error', 'message' => $q ? 'Media diupload!' : 'Gagal DB.']);
            } else { echo json_encode(['status' => 'error', 'message' => 'Gagal pindah file.']); }
        } else { echo json_encode(['status' => 'error', 'message' => 'File tidak ada/kebesaran.']); }
        exit;
    }

    if ($action === 'hapus_galeri') {
        $id_media = (int)$_POST['id_media'];
        $q_file = mysqli_query($koneksi, "SELECT file_path FROM galeri_gym WHERE id_media=$id_media");
        if ($row = mysqli_fetch_assoc($q_file)) {
            if (file_exists($row['file_path'])) unlink($row['file_path']);
            mysqli_query($koneksi, "DELETE FROM galeri_gym WHERE id_media=$id_media");
            echo json_encode(['status' => 'success']);
        } else { echo json_encode(['status' => 'error']); }
        exit;
    }
}

// =========================================================
// AMBIL DATA STATISTIK & PENGATURAN UNTUK DITAMPILKAN
// =========================================================
$count_aktif   = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as c FROM membership WHERE status='aktif'"))['c'];
$count_pending = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as c FROM membership WHERE status='pending'"))['c'];
$count_expired = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as c FROM membership WHERE status='kedaluwarsa'"))['c'];
$count_ditolak = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as c FROM membership WHERE status='ditolak'"))['c'];
$total_income  = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(total_harga) as s FROM membership WHERE status='aktif'"))['s'] ?? 0;
$rp_income = number_format($total_income, 0, ',', '.');

$web = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM pengaturan_web WHERE id=1"));

$jam = json_decode($web['jam_operasional'] ?? '{}', true);
$sjPagiL = $jam['sjPagi']['libur'] ?? false; $sjPagiB = $jam['sjPagi']['buka'] ?? '06:00'; $sjPagiT = $jam['sjPagi']['tutup'] ?? '10:30';
$sjSiangL = $jam['sjSiang']['libur'] ?? false; $sjSiangB = $jam['sjSiang']['buka'] ?? '14:15'; $sjSiangT = $jam['sjSiang']['tutup'] ?? '19:45';
$sbPagiL = $jam['sbPagi']['libur'] ?? false; $sbPagiB = $jam['sbPagi']['buka'] ?? '06:00'; $sbPagiT = $jam['sbPagi']['tutup'] ?? '10:30';
$sbSiangL = $jam['sbSiang']['libur'] ?? false; $sbSiangB = $jam['sbSiang']['buka'] ?? '14:15'; $sbSiangT = $jam['sbSiang']['tutup'] ?? '19:00';
$mgPagiL = $jam['mgPagi']['libur'] ?? true; $mgPagiB = $jam['mgPagi']['buka'] ?? ''; $mgPagiT = $jam['mgPagi']['tutup'] ?? '';
$mgSiangL = $jam['mgSiang']['libur'] ?? false; $mgSiangB = $jam['mgSiang']['buka'] ?? '14:15'; $mgSiangT = $jam['mgSiang']['tutup'] ?? '19:00';

$js = json_decode($web['jadwal_senam'] ?? '{}', true);
$harga_senam = $web['harga_senam'] ?? 25000;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dasbor - Vanda Gym Classic</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* ── BASE VARIABLES & RESET ── */
        :root {
            --bg-dark: #000000;
            --primary-red: #8E1616;
            --accent-gold: #E8C999;
            --text-light: #F8EEDF;
            --input-bg: #111111;
            --success-green: #28a745;
            --sidebar-width: 250px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: var(--bg-dark); 
            color: var(--text-light); 
            display: flex; 
            min-height: 100vh; 
            overflow-x: hidden; 
            max-width: 100vw; 
        }

        /* ── SIDEBAR ── */
        .sidebar { width: var(--sidebar-width); background-color: #0a0a0a; border-right: 1px solid #222; display: flex; flex-direction: column; position: fixed; height: 100vh; z-index: 1000; transition: transform 0.3s ease; }
        .sidebar-header { padding: 20px; text-align: center; border-bottom: 2px solid var(--primary-red); background: #050505; }
        .sidebar-header h2 { color: var(--accent-gold); font-size: 1.4rem; letter-spacing: 1px; }
        .sidebar-header p { color: #888; font-size: 0.8rem; text-transform: uppercase; margin-top: 5px; }
        .sidebar-menu { flex: 1; padding: 20px 0; overflow-y: auto; }
        .sidebar-menu::-webkit-scrollbar { width: 4px; }
        .sidebar-menu::-webkit-scrollbar-track { background: transparent; }
        .sidebar-menu::-webkit-scrollbar-thumb { background: #333; border-radius: 2px; }
        .menu-item { padding: 15px 25px; display: flex; align-items: center; gap: 15px; color: #aaa; text-decoration: none; font-weight: 600; font-size: 0.95rem; transition: 0.3s; cursor: pointer; border-left: 3px solid transparent; }
        .menu-item svg { stroke: #aaa; transition: 0.3s; min-width: 20px; }
        .menu-item:hover { background-color: #111; color: var(--text-light); }
        .menu-item:hover svg { stroke: var(--text-light); }
        .menu-item.active { background-color: rgba(232, 201, 153, 0.1); color: var(--accent-gold); border-left: 3px solid var(--accent-gold); }
        .menu-item.active svg { stroke: var(--accent-gold); }
        .sidebar-footer { padding: 20px; border-top: 1px solid #222; }
        .btn-logout { width: 100%; background: transparent; border: 1px solid var(--primary-red); color: var(--primary-red); padding: 10px; border-radius: 4px; font-weight: bold; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-logout:hover { background: var(--primary-red); color: white; }

        /* ── HAMBURGER TOGGLE ── */
        .sidebar-toggle { display: none; position: fixed; top: 15px; left: 15px; z-index: 1001; background: #111; border: 1px solid #333; color: var(--accent-gold); padding: 9px 11px; border-radius: 6px; cursor: pointer; transition: background 0.2s; line-height: 1; }
        .sidebar-toggle:hover { background: #1a1a1a; }
        .sidebar-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); z-index: 990; }
        .sidebar-backdrop.open { display: block; }

        /* ── MAIN CONTENT ── */
        .main-content { flex: 1; min-width: 0; margin-left: var(--sidebar-width); padding: 30px 40px; background-color: var(--bg-dark); }
        .top-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #333; padding-bottom: 15px; }
        .top-header h1 { color: var(--text-light); font-size: 1.8rem; margin-left: 0; }
        .admin-profile { display: flex; align-items: center; gap: 10px; color: var(--accent-gold); font-weight: bold; }

        /* ── TABS ── */
        .tab-section { display: none; animation: fadeIn 0.3s; }
        .tab-section.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* ── STATS ── */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #111; border: 1px solid #333; padding: 20px; border-radius: 8px; text-align: center; border-top: 3px solid var(--accent-gold); }
        .stat-card h3 { color: #888; font-size: 0.9rem; margin-bottom: 10px; text-transform: uppercase; }
        .stat-card .number { color: var(--text-light); font-size: 2.5rem; font-weight: bold; }
        .stat-card.alert { border-top-color: var(--primary-red); }
        .stat-card.alert .number { color: var(--primary-red); }

        /* ── ACTIVITY LIST ── */
        .activity-list { background: #0a0a0a; border: 1px solid #222; border-radius: 8px; padding: 20px; height: 100%; }
        .activity-item { display: flex; justify-content: space-between; align-items: flex-start; padding: 15px 0; border-bottom: 1px dashed #333; gap: 15px;}
        .activity-item:last-child { border-bottom: none; padding-bottom: 0; }
        .activity-text { flex: 1; color: var(--text-light); font-size: 0.9rem; line-height: 1.5; }
        .activity-time { white-space: nowrap; color: #888; font-size: 0.75rem; padding-top: 2px; }

        /* ── TABLE (SCROLL HORIZONTAL) ── */
        .table-container { 
            background: #0a0a0a; 
            border: 1px solid #222; 
            border-radius: 8px; 
            width: 100%; 
            overflow-x: auto; 
            -webkit-overflow-scrolling: touch; 
            margin-bottom: 15px; 
            display: block;
        }
        table { 
            width: 100%; 
            min-width: 800px; 
            border-collapse: collapse; 
            text-align: left; 
        }
        th, td { padding: 15px; border-bottom: 1px solid #222; }
        th { background-color: #111; color: var(--accent-gold); font-weight: 600; text-transform: uppercase; font-size: 0.85rem; white-space: nowrap; }
        td { color: #ccc; font-size: 0.9rem; vertical-align: middle; }
        tr:hover { background-color: #151515; }

        /* ── PAGINATION BUTTONS ── */
        .pagination-container { display: flex; justify-content: flex-end; gap: 5px; margin-bottom: 30px; flex-wrap: wrap; }
        .btn-page { background: #111; border: 1px solid #333; color: var(--text-light); padding: 6px 12px; border-radius: 4px; cursor: pointer; transition: 0.3s; font-size: 0.85rem;}
        .btn-page:hover { background: #222; border-color: var(--accent-gold); }
        .btn-page.active { background: var(--accent-gold); color: #000; font-weight: bold; border-color: var(--accent-gold); }

        /* ── BADGES ── */
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; display: inline-block; }
        .b-warning { background: rgba(255,193,7,0.2); color: #ffc107; border: 1px solid #ffc107; }
        .b-success { background: rgba(40,167,69,0.2); color: var(--success-green); border: 1px solid var(--success-green); }
        .b-danger { background: rgba(142,22,22,0.2); color: #ff4d4d; border: 1px solid #ff4d4d; }
        .b-info { background: rgba(0,123,255,0.2); color: #66b2ff; border: 1px solid #66b2ff; }
        .b-gray { background: rgba(136, 136, 136, 0.2); color: #aaa; border: 1px solid #888; }

        /* ── BUTTONS ── */
        .btn-action { padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: bold; transition: 0.3s; margin-right: 5px; margin-bottom: 5px; }
        .btn-acc { background: var(--success-green); color: white; }
        .btn-acc:hover { background: #218838; }
        .btn-rej { background: var(--primary-red); color: white; }
        .btn-rej:hover { background: #a81a1a; }
        .btn-view { background: #333; color: var(--accent-gold); border: 1px solid var(--accent-gold); }
        .btn-view:hover { background: var(--accent-gold); color: #000; }

        /* ── FORMS ── */
        .form-group { margin-bottom: 15px; text-align: left; }
        .form-group label { display: block; margin-bottom: 8px; color: #888; font-size: 0.85rem; font-weight: 600; }
        .form-control { width: 100%; padding: 12px 15px; background: var(--input-bg); border: 1px solid #333; border-radius: 4px; color: white; transition: 0.3s; }
        .form-control:focus { outline: none; border-color: var(--accent-gold); }
        .form-control:disabled { background: #222; color: #555; cursor: not-allowed; border-color: #333; }
        input[type="date"], input[type="time"] { color-scheme: dark; }
        .btn-submit { background: var(--accent-gold); color: #000; padding: 12px 20px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; transition: 0.3s; width: 100%; }
        .btn-submit:hover { background: #cda971; }

        /* ── GRID ── */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }

        /* ── MODALS ── */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); display: none; justify-content: center; align-items: center; z-index: 2000; padding: 20px; overflow-y: auto; }
        .modal-box { background: #111; border: 1px solid var(--accent-gold); padding: 30px; border-radius: 8px; max-width: 500px; width: 100%; position: relative; max-height: 90vh; overflow-y: auto; }
        .close-modal { position: absolute; top: 15px; right: 15px; background: transparent; border: none; color: #888; font-size: 1.5rem; cursor: pointer; transition: 0.3s; }
        .close-modal:hover { color: var(--primary-red); }

        /* ── CONTENT CARDS ── */
        .content-card { background: #0a0a0a; border: 1px solid #222; border-radius: 8px; padding: 25px; margin-bottom: 30px; height: 100%; }
        .content-card h3 { color: var(--accent-gold); margin-bottom: 20px; font-size: 1.2rem; border-bottom: 1px dashed #333; padding-bottom: 10px; }

        /* ── JAM CARDS ── */
        .jam-card { background: #151515; border: 1px solid #333; border-radius: 6px; padding: 20px; margin-bottom: 15px; }
        .jam-card label.hari { color: var(--accent-gold); font-size: 1.1rem; display: block; margin-bottom: 15px; text-transform: uppercase; font-weight: bold; }
        .error-msg { color: #ff4d4d; font-size: 0.75rem; margin-top: 5px; display: none; }

        /* ── MEDIA GRID ── */
        .media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-top: 15px; }
        .media-item { border: 1px solid #333; border-radius: 6px; overflow: hidden; background: #111; position: relative; }
        .media-item img, .media-item video { width: 100%; height: 120px; object-fit: cover; display: block; border-bottom: 1px solid #222; }
        .media-item-info { padding: 12px; }
        .media-item-info p { margin-bottom: 10px; font-size: 0.85rem; font-weight: bold; color: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .media-item-info span { display: block; font-size: 0.7rem; color: var(--accent-gold); text-transform: uppercase; margin-bottom: 10px; }

        /* ================================================================
            RESPONSIVE STYLES
           ================================================================ */

        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.sidebar-open { transform: translateX(0); z-index: 2000; }
            .sidebar-toggle { display: flex; align-items: center; justify-content: center; }
            .main-content { margin-left: 0; padding: 20px 20px 40px; }
            .top-header { padding-left: 55px; } 
            .grid-2 { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            body { font-size: 0.9rem; }
            .main-content { padding: 15px 12px 50px; }
            .top-header { flex-direction: column; align-items: flex-start; gap: 6px; padding-left: 50px; border-bottom: none; }
            .top-header h1 { font-size: 1.3rem; }
            .admin-profile { font-size: 0.85rem; }

            .stats-grid { grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px; }
            .stat-card { padding: 14px 10px; }
            .stat-card .number { font-size: 1.8rem; }
            .stat-card h3 { font-size: 0.75rem; }

            th, td { padding: 10px 12px; font-size: 0.82rem; } 
            th { font-size: 0.8rem; }

            .content-card { padding: 16px 12px; }
            .content-card h3 { font-size: 1rem; }

            #tab-verifikasi > div:first-child, #tab-member > div:first-child, #tab-arsip > div:first-child { flex-direction: column; align-items: stretch; }
            #tab-verifikasi > div:first-child > div, #tab-member > div:first-child > div, #tab-arsip > div:first-child > div { flex-direction: column; width: 100%; }
            #searchVerifikasi, #filterVerifikasiJenis, #searchMember, #filterStatus, #searchArsip, #filterArsipStatus { width: 100% !important; margin-bottom: 5px; font-size: 0.85rem;}
            #tab-member > div:first-child > button { width: 100% !important; margin-top: 10px; font-size: 0.85rem;}

            .jam-card .grid-2 { grid-template-columns: 1fr; gap: 10px; margin-bottom: 0; }
            .activity-item { flex-direction: column; gap: 6px; align-items: flex-start; }
            .activity-text { font-size: 0.85rem; }
            .activity-time { text-align: left; font-size: 0.7rem; }

            .modal-box { padding: 20px 15px; width: 95%; max-height: 85vh; }
            .modal-box .grid-2 { grid-template-columns: 1fr; gap: 10px; margin-bottom: 10px;}
            .modal-box h3 { font-size: 1.1rem; }

            .media-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
            .media-item-info p { font-size: 0.8rem; }
            .media-item-info span { font-size: 0.65rem; }
        }

        @media (max-width: 480px) {
            body { font-size: 0.85rem; }
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 8px; }
            .stat-card { padding: 12px 8px; }
            .stat-card .number { font-size: 1.4rem; }
            .stat-card h3 { font-size: 0.65rem; }
            .sidebar { width: 240px; }
            .top-header h1 { font-size: 1.1rem; }
            .badge { font-size: 0.65rem; padding: 3px 6px; }
            
            td > .btn-action { display: block; width: 100%; margin-right: 0; margin-bottom: 5px; text-align: center; font-size: 0.7rem; padding: 6px 8px;}
            td > .btn-action:last-child { margin-bottom: 0; }
            
            .media-grid { grid-template-columns: 1fr; }
            #tab-galeri .content-card > form > div[style*="display:flex"] { flex-direction: column !important; gap: 10px !important; }
            .form-control { font-size: 0.8rem; padding: 10px 12px;}
            .form-group label { font-size: 0.75rem; }
            .btn-submit { font-size: 0.85rem; padding: 10px 15px; }
        }
    </style>
</head>
<body>

    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Buka Menu">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </button>

    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="sidebar" id="mainSidebar">
        <div class="sidebar-header">
            <h2>VANDA ADMIN</h2>
            <p>Control Panel</p>
        </div>
        <div class="sidebar-menu">
            <div class="menu-item active" onclick="switchTab('tab-dasbor', this)">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                Dasbor Utama
            </div>
            <div class="menu-item" onclick="switchTab('tab-verifikasi', this)">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                Verifikasi Bayar <span class="badge b-warning" style="margin-left:auto;"><?= $count_pending ?></span>
            </div>
            <div class="menu-item" onclick="switchTab('tab-member', this)">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                Data Member
            </div>
            <div class="menu-item" onclick="switchTab('tab-arsip', this)">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8v13H3V8"></path><rect x="1" y="3" width="22" height="5"></rect><line x1="10" y1="12" x2="14" y2="12"></line></svg>
                Arsip Data
            </div>
            <div class="menu-item" onclick="switchTab('tab-konten', this)">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                Kelola Konten Web
            </div>
            <div class="menu-item" onclick="switchTab('tab-galeri', this)">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                Galeri & Media
            </div>
        </div>
        <div class="sidebar-footer">
            <button class="btn-logout" onclick="window.location.href='index.php'">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                Keluar
            </button>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">
            <h1 id="pageTitle">Dasbor Utama</h1>
            <div class="admin-profile"><span>👋 Halo, <?= $_SESSION['nama'] ?></span></div>
        </div>

        <div id="tab-dasbor" class="tab-section active">
            <div class="stats-grid">
                <div class="stat-card"><h3>Total Member Aktif</h3><div class="number"><?= $count_aktif ?></div></div>
                <div class="stat-card alert"><h3>Menunggu Verifikasi</h3><div class="number"><?= $count_pending ?></div></div>
                <div class="stat-card"><h3>Kedaluwarsa</h3><div class="number"><?= $count_expired ?></div></div>
                <div class="stat-card">
                    <h3>Total Pendapatan</h3>
                    <div class="number" style="font-size:1.8rem;line-height:2.5rem;color:var(--success-green); margin-bottom: 10px;">Rp <?= $rp_income ?></div>
                    <button onclick="bukaModalCetak()" class="btn-action btn-view" style="width: 100%; margin: 0; background: var(--success-green); color: white; border: none; padding: 10px;">
                        📄 Cetak Laporan PDF
                    </button>
                </div>
            </div>
            <div class="grid-2" style="margin-bottom:0;">
                <div class="content-card">
                    <h3>Statistik Status Member</h3>
                    <div style="position:relative;height:250px;width:100%;display:flex;justify-content:center;">
                        <canvas id="memberChart"></canvas>
                    </div>
                </div>
                <div class="content-card">
                    <h3>Riwayat Pendaftaran Terkini</h3>
                    <div class="activity-list">
                        <?php
                        $q_log = mysqli_query($koneksi, "SELECT u.nama_lengkap, m.status, m.jenis_pengajuan, m.created_at FROM membership m JOIN users u ON m.id_user = u.id_user ORDER BY m.id_membership DESC LIMIT 5");
                        if(mysqli_num_rows($q_log) == 0) {
                            echo "<div style='color:#888;text-align:center;padding:20px 0;'>Belum ada aktivitas.</div>";
                        }
                        while($log = mysqli_fetch_assoc($q_log)):
                            $waktu = date('d M Y - H:i', strtotime($log['created_at']));
                            $warna = '#fff';
                            if($log['status'] == 'aktif') $warna = 'var(--success-green)';
                            if($log['status'] == 'ditolak') $warna = 'var(--primary-red)';
                            if($log['status'] == 'pending') $warna = '#ffc107';
                        ?>
                        <div class="activity-item">
                            <div class="activity-text">
                                <strong style="color:var(--accent-gold);"><?= $log['nama_lengkap'] ?></strong> melakukan <br>
                                <span style="color:#aaa;font-size:0.8rem;"><?= ucfirst($log['jenis_pengajuan']) ?> &rarr; <span style="color:<?= $warna ?>;font-weight:bold;"><?= ucfirst($log['status']) ?></span></span>
                            </div>
                            <span class="activity-time"><?= $waktu ?></span>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>

        <div id="tab-verifikasi" class="tab-section">
            <div style="display:flex;justify-content:space-between;margin-bottom:20px;align-items:center;flex-wrap:wrap;gap:15px;">
                <p style="color:#888;margin:0;">Daftar pendaftaran dan perpanjangan yang menunggu persetujuan.</p>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <input type="text" id="searchVerifikasi" class="form-control" placeholder="Cari nama / email..." style="width:250px;" onkeyup="filterVerifikasi()">
                    <select class="form-control" id="filterVerifikasiJenis" onchange="filterVerifikasi()" style="width:180px;cursor:pointer;">
                        <option value="">Semua Jenis</option>
                        <option value="daftar">Daftar Baru</option>
                        <option value="perpanjang">Perpanjang</option>
                    </select>
                </div>
            </div>
            <div class="table-container">
                <table id="tabelVerifikasi">
                    <thead><tr><th>Tanggal</th><th>Nama / Email</th><th>Jenis</th><th>Paket & Harga</th><th>Metode</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php
                        $query_pending = mysqli_query($koneksi, "SELECT u.nama_lengkap, u.email, m.* FROM users u JOIN membership m ON u.id_user = m.id_user WHERE m.status = 'pending' ORDER BY m.id_membership DESC");
                        if (mysqli_num_rows($query_pending) == 0) echo "<tr class='no-data'><td colspan='6' style='text-align:center;padding:20px;'>Belum ada antrean.</td></tr>";
                        while ($row = mysqli_fetch_assoc($query_pending)):
                        ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                            <td><strong><?= $row['nama_lengkap'] ?></strong><br><span style="font-size:0.8rem;color:#888;"><?= $row['email'] ?></span></td>
                            <td><span class="badge b-warning jenis-label"><?= ucfirst($row['jenis_pengajuan']) ?></span></td>
                            <td><?= $row['paket_bulan'] ?> Bulan<br><span style="color:var(--accent-gold);font-weight:bold;">Rp <?= number_format($row['total_harga'],0,',','.') ?></span></td>
                            <td><?= strtoupper($row['metode_bayar']) ?></td>
                            <td style="min-width:250px;">
                                <?php if ($row['metode_bayar'] == 'qris' && $row['bukti_bayar']): ?>
                                    <button class="btn-action btn-view" onclick="lihatBuktiTransfer('uploads/<?= $row['bukti_bayar'] ?>')">Lihat Bukti</button>
                                <?php endif; ?>
                                <button class="btn-action btn-acc" onclick="konfirmasiTerima('<?= $row['id_membership'] ?>', '<?= addslashes($row['nama_lengkap']) ?>')">Terima</button>
                                <button class="btn-action btn-rej" onclick="konfirmasiTolak('<?= $row['id_membership'] ?>', '<?= addslashes($row['nama_lengkap']) ?>')">Tolak</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div class="pagination-container" id="pagVerifikasi"></div>
        </div>

        <div id="tab-member" class="tab-section">
            <div style="display:flex;justify-content:space-between;margin-bottom:20px;align-items:center;flex-wrap:wrap;gap:15px;">
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <input type="text" id="searchMember" class="form-control" placeholder="Cari nama / email..." style="width:250px;" onkeyup="filterMember()">
                    <select class="form-control" id="filterStatus" onchange="filterMember()" style="width:180px;cursor:pointer;">
                        <option value="">Semua Status</option>
                        <option value="aktif">Aktif</option>
                        <option value="kedaluwarsa">Kedaluwarsa</option>
                        <option value="ditolak">Ditolak</option>
                    </select>
                </div>
                <button class="btn-submit" style="width:auto;margin:0;" onclick="bukaModal('modalTambahMember')">+ Tambah Member Baru</button>
            </div>
            <div class="table-container">
                <table id="tabelMember">
                    <thead>
                        <tr>
                            <th>Nama / Email</th><th>Kontak WA</th><th>Pembayaran</th><th>Masa Aktif</th><th>Status</th><th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // TAB MEMBER HANYA MENAMPILKAN ROLE 'member'
                        $query_member = mysqli_query($koneksi, "
                            SELECT u.id_user, u.nama_lengkap, u.email, u.no_wa,
                                   m.status, m.tgl_mulai, m.tgl_berakhir, m.metode_bayar, m.total_harga
                            FROM users u
                            INNER JOIN (SELECT id_user, MAX(id_membership) as max_id FROM membership GROUP BY id_user) latest_m ON u.id_user = latest_m.id_user
                            INNER JOIN membership m ON latest_m.max_id = m.id_membership
                            WHERE u.role NOT IN ('admin', 'arsip') AND m.status IN ('aktif', 'kedaluwarsa', 'ditolak')
                            ORDER BY m.id_membership DESC
                        ");
                        if (mysqli_num_rows($query_member) == 0) echo "<tr class='no-data'><td colspan='6' style='text-align:center;padding:20px;'>Belum ada member terdaftar.</td></tr>";
                        while ($usr = mysqli_fetch_assoc($query_member)):
                            if ($usr['status'] == 'aktif') $b_class = 'b-success';
                            else if ($usr['status'] == 'ditolak') $b_class = 'b-danger';
                            else $b_class = 'b-warning';
                        ?>
                        <tr>
                            <td><strong><?= $usr['nama_lengkap'] ?></strong><br><span style="font-size:0.85rem;color:#aaa;"><?= $usr['email'] ?></span></td>
                            <td><?= $usr['no_wa'] ?></td>
                            <td>
                                <span style="color:var(--accent-gold);font-weight:bold;">Rp <?= number_format($usr['total_harga'],0,',','.') ?></span><br>
                                <span style="font-size:0.8rem;text-transform:uppercase;color:#888;"><?= $usr['metode_bayar'] ?></span>
                            </td>
                            <td>
                                <?= $usr['tgl_mulai'] ? date('d M Y', strtotime($usr['tgl_mulai'])) : '-' ?><br>
                                <span style="font-size:0.8rem;color:#888;">s/d <?= $usr['tgl_berakhir'] ? date('d M Y', strtotime($usr['tgl_berakhir'])) : '-' ?></span>
                            </td>
                            <td><span class="badge <?= $b_class ?> status-label"><?= ucfirst($usr['status']) ?></span></td>
                            <td style="min-width:150px;">
    <button class="btn-action btn-view" onclick="bukaEditMember('<?= $usr['id_user'] ?>', '<?= addslashes($usr['nama_lengkap']) ?>', '<?= addslashes($usr['email']) ?>', '<?= addslashes($usr['no_wa']) ?>')">Edit</button>
    
    <?php if ($usr['status'] == 'ditolak'): ?>
        <button class="btn-action btn-rej" onclick="konfirmasiHapus('<?= $usr['id_user'] ?>', '<?= addslashes($usr['nama_lengkap']) ?>')" title="Hapus Data Permanen">Hapus</button>
    <?php else: ?>
        <button class="btn-action btn-rej" style="background-color: #ffc107; border-color: #ffc107; color: #000;" onclick="konfirmasiArsip('<?= $usr['id_user'] ?>', '<?= addslashes($usr['nama_lengkap']) ?>')" title="Pindahkan ke Arsip Sementara">Arsipkan</button>
    <?php endif; ?>
</td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div class="pagination-container" id="pagMember"></div>
        </div>

        <div id="tab-arsip" class="tab-section">
            <div style="display:flex;justify-content:space-between;margin-bottom:20px;align-items:center;flex-wrap:wrap;gap:15px;">
                <p style="color:#888;margin:0;">Daftar akun yang dinonaktifkan. Data ini tidak dihapus untuk menjaga riwayat transaksi.</p>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <input type="text" id="searchArsip" class="form-control" placeholder="Cari nama / email..." style="width:250px;" onkeyup="filterArsip()">
                </div>
            </div>
            <div class="table-container">
                <table id="tabelArsip">
                    <thead>
                        <tr>
                            <th>Nama / Email</th><th>Kontak WA</th><th>Terakhir Aktif</th><th>Status Terakhir</th><th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // MENGAMBIL USER DENGAN ROLE 'arsip'
                        $query_arsip = mysqli_query($koneksi, "
                            SELECT u.id_user, u.nama_lengkap, u.email, u.no_wa,
                                   m.status, m.tgl_berakhir
                            FROM users u
                            LEFT JOIN (SELECT id_user, MAX(id_membership) as max_id FROM membership GROUP BY id_user) latest_m ON u.id_user = latest_m.id_user
                            LEFT JOIN membership m ON latest_m.max_id = m.id_membership
                            WHERE u.role = 'arsip'
                            ORDER BY u.id_user DESC
                        ");
                        if (mysqli_num_rows($query_arsip) == 0) echo "<tr class='no-data'><td colspan='5' style='text-align:center;padding:20px;'>Belum ada data yang diarsipkan.</td></tr>";
                        while ($arsip = mysqli_fetch_assoc($query_arsip)):
                        ?>
                        <tr>
                            <td><strong><?= $arsip['nama_lengkap'] ?></strong><br><span style="font-size:0.85rem;color:#aaa;"><?= $arsip['email'] ?></span></td>
                            <td><?= $arsip['no_wa'] ?></td>
                            <td><?= $arsip['tgl_berakhir'] ? date('d M Y', strtotime($arsip['tgl_berakhir'])) : '-' ?></td>
                            <td><span class="badge b-gray"><?= ucfirst($arsip['status'] ?? 'Tidak Ada') ?></span></td>
                            <td style="min-width:150px;">
                                <button class="btn-action btn-acc" onclick="konfirmasiPulihkan('<?= $arsip['id_user'] ?>', '<?= addslashes($arsip['nama_lengkap']) ?>')">Pulihkan Akun</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div class="pagination-container" id="pagArsip"></div>
        </div>

        <div id="tab-konten" class="tab-section">
            <div class="content-card">
                <h3>📢 Kelola Banner Pengumuman</h3>
                <form onsubmit="simpanBanner(event)">
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Status Tampil</label>
                            <select id="set_banner_status" class="form-control">
                                <option value="aktif" <?= ($web['pengumuman_aktif'] ?? '') == 'aktif' ? 'selected' : '' ?>>Tampilkan (Aktif)</option>
                                <option value="nonaktif" <?= ($web['pengumuman_aktif'] ?? '') == 'nonaktif' ? 'selected' : '' ?>>Sembunyikan</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Teks Pengumuman</label>
                            <textarea id="set_banner_teks" class="form-control" rows="2" required><?= $web['teks_pengumuman'] ?? '' ?></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit" style="width:auto;">Simpan Pengumuman</button>
                </form>
            </div>

            <div class="grid-2">
                <div style="display:flex;flex-direction:column;gap:30px;">
                    <div class="content-card" style="margin-bottom:0;">
                        <h3>💰 Edit Harga Membership & Senam</h3>
                        <form onsubmit="simpanHarga(event)">
                            <div class="form-group"><label>1x Visit Gym (Harian)</label><input type="text" id="set_harga_harian" class="form-control" value="<?= $web['harga_harian'] ?? '' ?>" required></div>
                            <div class="form-group"><label>Gym Bulanan (Mulai Dari)</label><input type="text" id="set_harga_bulanan" class="form-control" value="<?= $web['harga_bulanan'] ?? '' ?>" required></div>
                            <div class="form-group"><label>Kelas Senam (Per Datang)</label><input type="text" id="set_harga_senam" class="form-control" value="<?= $harga_senam ?>" required></div>
                            <button type="submit" class="btn-submit">Simpan Harga</button>
                        </form>
                    </div>
                    <div class="content-card" style="margin-bottom:0;">
                        <h3>📍 Edit Kontak & Lokasi</h3>
                        <form onsubmit="simpanKontak(event)">
                            <div class="form-group"><label>No. WA CS Gym</label><input type="text" id="set_wa" class="form-control" value="<?= $web['wa_cs'] ?? '' ?>" required></div>
                            <div class="form-group"><label>Link Instagram</label><input type="text" id="set_ig" class="form-control" value="<?= $web['ig'] ?? '' ?>" required></div>
                            <button type="submit" class="btn-submit">Simpan Info Kontak</button>
                        </form>
                    </div>
                </div>

                <div style="display:flex;flex-direction:column;gap:30px;">
                    <div class="content-card" style="margin-bottom:0;">
                        <h3>🕒 Edit Jam Operasional Gym</h3>
                        <form onsubmit="simpanJam(event)">
                            <?php
                            $sesiGym = [
                                'sjPagi'  => ['hari' => 'Senin - Jumat', 'sesi' => 'Sesi Pagi',        'libur' => $sjPagiL,  'buka' => $sjPagiB,  'tutup' => $sjPagiT],
                                'sjSiang' => ['hari' => null,             'sesi' => 'Sesi Siang/Malam', 'libur' => $sjSiangL, 'buka' => $sjSiangB, 'tutup' => $sjSiangT],
                                'sbPagi'  => ['hari' => 'Sabtu',          'sesi' => 'Sesi Pagi',        'libur' => $sbPagiL,  'buka' => $sbPagiB,  'tutup' => $sbPagiT],
                                'sbSiang' => ['hari' => null,             'sesi' => 'Sesi Siang/Malam', 'libur' => $sbSiangL, 'buka' => $sbSiangB, 'tutup' => $sbSiangT],
                                'mgPagi'  => ['hari' => 'Minggu',         'sesi' => 'Sesi Pagi',        'libur' => $mgPagiL,  'buka' => $mgPagiB,  'tutup' => $mgPagiT],
                                'mgSiang' => ['hari' => null,             'sesi' => 'Sesi Siang/Malam', 'libur' => $mgSiangL, 'buka' => $mgSiangB, 'tutup' => $mgSiangT],
                            ];
                            $open_card = false;
                            foreach($sesiGym as $key => $s):
                                if($s['hari'] !== null):
                                    if($open_card) echo '</div></div>'; // tutup grid + card sebelumnya
                                    $open_card = true;
                            ?>
                            <div class="jam-card">
                                <label class="hari"><?= $s['hari'] ?></label>
                                <div class="grid-2">
                            <?php endif; ?>
                                    <div class="form-group">
                                        <label style="display:flex;justify-content:space-between;">
                                            <span><?= $s['sesi'] ?></span>
                                            <span style="color:#ff4d4d;font-size:0.75rem;">
                                                <input type="checkbox" id="cb_<?= $key ?>" onchange="toggleLibur(this, '<?= $key ?>')" <?= $s['libur'] ? 'checked' : '' ?>> Libur
                                            </span>
                                        </label>
                                        <div style="display:flex;align-items:center;gap:8px;" id="<?= $key ?>">
                                            <input type="time" id="v_<?= $key ?>_b" class="form-control" value="<?= $s['buka'] ?>" <?= $s['libur'] ? 'disabled' : 'required' ?>>
                                            <span style="color:#888;">-</span>
                                            <input type="time" id="v_<?= $key ?>_t" class="form-control" value="<?= $s['tutup'] ?>" <?= $s['libur'] ? 'disabled' : 'required' ?>>
                                        </div>
                                    </div>
                            <?php endforeach; if($open_card) echo '</div></div>'; ?>
                            <button type="submit" class="btn-submit">Simpan Jadwal Operasional Gym</button>
                        </form>
                    </div>

                    <div class="content-card" style="margin-bottom:0;border-top-color:var(--accent-gold);">
                        <h3 style="color:var(--text-light);">🧘 Edit Jadwal Kelas Senam</h3>
                        <form onsubmit="simpanJadwalSenam(event)">
                            <?php
                            $hari_senam = ['sr'=>'Senin & Rabu','sk'=>'Selasa & Kamis','sb'=>'Sabtu','mg'=>'Minggu'];
                            foreach($hari_senam as $key => $label):
                                $l = $js[$key]['libur'] ?? false;
                            ?>
                            <div class="jam-card">
                                <label class="hari"><?= $label ?></label>
                                <div class="grid-2">
                                    <div class="form-group">
                                        <label style="display:flex;justify-content:space-between;">
                                            <span>Jam Kelas</span>
                                            <span style="color:#ff4d4d;font-size:0.75rem;">
                                                <input type="checkbox" id="libur_<?= $key ?>" onchange="toggleLibur(this, 'js_<?= $key ?>')" <?= $l?'checked':'' ?>> Libur
                                            </span>
                                        </label>
                                        <div style="display:flex;align-items:center;gap:8px;" id="js_<?= $key ?>">
                                            <input type="time" id="buka_<?= $key ?>" class="form-control" value="<?= $js[$key]['buka'] ?? '' ?>" <?= $l?'disabled':'required' ?>>
                                            <span style="color:#888;">-</span>
                                            <input type="time" id="tutup_<?= $key ?>" class="form-control" value="<?= $js[$key]['tutup'] ?? '' ?>" <?= $l?'disabled':'required' ?>>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Nama Kelas / Instruktur</label>
                                        <input type="text" id="ket_<?= $key ?>" class="form-control" value="<?= htmlspecialchars($js[$key]['ket'] ?? '') ?>" placeholder="Contoh: Zumba / BL+">
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <button type="submit" class="btn-submit" style="background:var(--text-light);">Simpan Jadwal Kelas Senam</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div id="tab-galeri" class="tab-section">
            <div class="content-card">
                <h3>Upload Media ke Galeri Publik</h3>
                <form id="formUploadGaleri" onsubmit="uploadMedia(event)" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Judul Media (Misal: Tutorial Bench Press)</label>
                        <input type="text" id="judul_media" class="form-control" required placeholder="Masukkan judul...">
                    </div>
                    <div style="display:flex;gap:15px;margin-bottom:15px;flex-wrap:wrap;" class="flex-column-mobile">
                        <div class="form-group" style="flex:1;min-width:200px;">
                            <label>Kategori</label>
                            <select id="kategori_media" class="form-control" required>
                                <option value="alat">Fasilitas & Alat Gym</option>
                                <option value="upper">Tutorial Upper Body</option>
                                <option value="lower">Tutorial Lower Body</option>
                            </select>
                        </div>
                        <div class="form-group" style="flex:1;min-width:200px;">
                            <label>Tipe Media</label>
                            <select id="tipe_media" class="form-control" required onchange="sesuaikanInputFile()">
                                <option value="foto">Foto (JPG/PNG)</option>
                                <option value="video">Video (MP4)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Pilih File (Max 10MB)</label>
                        <input type="file" id="file_media" class="form-control" accept="image/jpeg, image/png, image/webp" required style="padding:9px 15px;">
                    </div>
                    <button type="submit" id="btnUpload" class="btn-submit">Upload Media Sekarang</button>
                </form>
            </div>

            <div class="content-card">
                <h3>Daftar Media Terupload</h3>
                <p style="color:#888;font-size:0.85rem;margin-bottom:15px;">Hapus media yang sudah tidak relevan agar server tidak penuh.</p>
                <div class="media-grid">
                    <?php
                    $q_g = mysqli_query($koneksi, "SELECT * FROM galeri_gym ORDER BY id_media DESC");
                    if(mysqli_num_rows($q_g) == 0) echo "<div style='color:#666;'>Belum ada media terupload.</div>";
                    while($mg = mysqli_fetch_assoc($q_g)):
                    ?>
                    <div class="media-item">
                        <?php if($mg['tipe_media'] == 'video'): ?>
                            <video src="<?= $mg['file_path'] ?>" muted></video>
                        <?php else: ?>
                            <img src="<?= $mg['file_path'] ?>">
                        <?php endif; ?>
                        <div class="media-item-info">
                            <p title="<?= htmlspecialchars($mg['judul']) ?>"><?= htmlspecialchars($mg['judul']) ?></p>
                            <span><?= strtoupper($mg['kategori']) ?> • <?= strtoupper($mg['tipe_media']) ?></span>
                            <button class="btn-action btn-rej" style="width:100%;margin:0;" onclick="konfirmasiHapusMedia('<?= $mg['id_media'] ?>', '<?= htmlspecialchars(addslashes($mg['judul'])) ?>')">Hapus Media</button>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="modalCetakLaporan">
        <div class="modal-box" style="max-width: 400px;">
            <button class="close-modal" onclick="tutupModal('modalCetakLaporan')">×</button>
            <h3 style="color:var(--accent-gold);margin-bottom:20px;text-align:left;">Cetak Laporan Bulanan</h3>
            <form action="cetak_laporan.php" method="GET" target="_blank">
                <div class="form-group">
                    <label>Pilih Bulan</label>
                    <select name="bulan" class="form-control" required>
                        <?php
                        $months = ['01'=>'Januari', '02'=>'Februari', '03'=>'Maret', '04'=>'April', '05'=>'Mei', '06'=>'Juni', '07'=>'Juli', '08'=>'Agustus', '09'=>'September', '10'=>'Oktober', '11'=>'November', '12'=>'Desember'];
                        foreach($months as $k => $v) { $sel = ($k == date('m')) ? 'selected' : ''; echo "<option value='$k' $sel>$v</option>"; }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Pilih Tahun</label>
                    <select name="tahun" class="form-control" required>
                        <?php for($i=date('Y'); $i>=2024; $i--) { echo "<option value='$i'>$i</option>"; } ?>
                    </select>
                </div>
                <button type="submit" class="btn-submit" style="margin-top:15px;">Buka & Unduh PDF</button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="modalTambahMember">
        <div class="modal-box" style="max-width:600px;">
            <button class="close-modal" onclick="tutupModal('modalTambahMember')">×</button>
            <h3 style="color:var(--accent-gold);margin-bottom:20px;text-align:left;">Tambah Member Baru</h3>
            <form onsubmit="prosesTambahMember(event)">
                <div class="form-group"><label>Nama Lengkap</label><input type="text" id="tNama" class="form-control" required></div>
                <div class="grid-2">
                    <div class="form-group"><label>Email</label><input type="email" id="tEmail" class="form-control" required></div>
                    <div class="form-group"><label>WhatsApp</label><input type="text" id="tWa" class="form-control" required oninput="validasiAngka(this, 'errWa')"><div id="errWa" class="error-msg">Harus berupa angka.</div></div>
                </div>
                <div class="form-group"><label>Password Akun (Default: 123456)</label><input type="text" id="tPass" class="form-control" required value="123456"></div>
                <div class="grid-2">
                    <div class="form-group"><label>Paket Pilihan</label><select id="tPaket" class="form-control"><option value="1">1 Bulan Gym</option><option value="2">2 Bulan Gym</option><option value="3">3 Bulan Gym</option></select></div>
                    <div class="form-group"><label>Tanggal Mulai</label><input type="date" id="tTgl" class="form-control" required value="<?= date('Y-m-d') ?>"></div>
                </div>
                <button type="submit" id="btnSimpanMember" class="btn-submit" style="margin-top:15px;">Simpan & Aktifkan Member</button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="modalEditMember">
        <div class="modal-box" style="max-width:600px;">
            <button class="close-modal" onclick="tutupModal('modalEditMember')">×</button>
            <h3 style="color:var(--accent-gold);margin-bottom:20px;text-align:left;">Edit Data Member</h3>
            <form onsubmit="prosesEditMember(event)">
                <input type="hidden" id="eId">
                <div class="form-group"><label>Nama Lengkap</label><input type="text" id="eNama" class="form-control" required></div>
                <div class="grid-2">
                    <div class="form-group"><label>Email (Tidak bisa diubah)</label><input type="email" id="eEmail" class="form-control" disabled></div>
                    <div class="form-group"><label>WhatsApp</label><input type="text" id="eWa" class="form-control" required oninput="validasiAngka(this, 'errWaEdit')"><div id="errWaEdit" class="error-msg">Harus berupa angka.</div></div>
                </div>
                <div class="form-group"><label>Password Baru (Kosongkan jika tidak ingin diubah)</label><input type="text" id="ePass" class="form-control" placeholder="Ketik sandi baru..."></div>
                <button type="submit" id="btnSimpanEdit" class="btn-submit" style="margin-top:15px;">Simpan Perubahan</button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="modalBukti">
        <div class="modal-box">
            <button class="close-modal" onclick="tutupModal('modalBukti')">×</button>
            <h3 style="color:var(--accent-gold);margin-bottom:15px;">Bukti Transfer</h3>
            <div style="width:100%;background:#222;display:flex;align-items:center;justify-content:center;margin-bottom:15px;border-radius:4px;overflow:hidden;">
                <img id="imgBukti" src="" style="max-width:100%;max-height:400px;object-fit:contain;">
            </div>
            <button class="btn-submit" onclick="tutupModal('modalBukti')">Tutup</button>
        </div>
    </div>

    <div class="modal-overlay" id="modalTerimaPembayaran">
        <div class="modal-box">
            <button class="close-modal" onclick="tutupModal('modalTerimaPembayaran')">×</button>
            <h3 style="color:var(--success-green);margin-bottom:15px;">Verifikasi Diterima</h3>
            <p style="color:#ccc;font-size:0.9rem;margin-bottom:15px;">Aktifkan akun berikut?</p>
            <div style="background:#050505;border:1px dashed #333;padding:15px;border-radius:6px;margin-bottom:20px;">
                <span style="color:#888;">Nama:</span> <strong id="verifNama" style="color:white;"></strong>
            </div>
            <button class="btn-submit" id="btnProsesTerima" style="background:var(--success-green);color:white;" onclick="eksekusiTerima()">Ya, Aktifkan Akun</button>
        </div>
    </div>

    <div class="modal-overlay" id="modalTolakPembayaran">
        <div class="modal-box">
            <button class="close-modal" onclick="tutupModal('modalTolakPembayaran')">×</button>
            <h3 style="color:var(--primary-red);margin-bottom:15px;">Tolak Pembayaran</h3>
            <form onsubmit="eksekusiTolak(event)">
                <div class="form-group"><label>Alasan Penolakan untuk <span id="tolakNama" style="color:white;"></span></label><textarea id="alasanTolakText" class="form-control" rows="3" required></textarea></div>
                <button type="submit" id="btnProsesTolak" class="btn-submit" style="background:var(--primary-red);color:white;">Tolak Pendaftaran</button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="modalArsipMember">
        <div class="modal-box" style="max-width:400px;">
            <div style="font-size:3rem;margin-bottom:10px;text-align:center;">📦</div>
            <h3 style="color:#ffc107;margin-bottom:10px;text-align:center;">Arsipkan Data Member?</h3>
            <p style="color:#ccc;font-size:0.9rem;margin-bottom:25px;text-align:center;">Akun <strong id="arsipNamaText" style="color:white;"></strong> akan dinonaktifkan (Soft Delete) dan dipindahkan ke Tab Arsip agar riwayat pembayarannya tetap aman.</p>
            <div style="display:flex;gap:10px;">
                <button id="btnProsesArsip" class="btn-action b-warning" onclick="eksekusiArsip()" style="flex:1;padding:12px;margin:0;">Ya, Arsipkan</button>
                <button class="btn-action btn-view" onclick="tutupModal('modalArsipMember')" style="flex:1;padding:12px;margin:0;">Batal</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="modalPulihkanMember">
        <div class="modal-box" style="max-width:400px;">
            <div style="font-size:3rem;margin-bottom:10px;text-align:center;">♻️</div>
            <h3 style="color:var(--success-green);margin-bottom:10px;text-align:center;">Pulihkan Akun Member?</h3>
            <p style="color:#ccc;font-size:0.9rem;margin-bottom:25px;text-align:center;">Akun <strong id="pulihkanNamaText" style="color:white;"></strong> akan dikembalikan sebagai Member aktif.</p>
            <div style="display:flex;gap:10px;">
                <button id="btnProsesPulihkan" class="btn-action btn-acc" onclick="eksekusiPulihkan()" style="flex:1;padding:12px;margin:0;">Ya, Pulihkan</button>
                <button class="btn-action btn-view" onclick="tutupModal('modalPulihkanMember')" style="flex:1;padding:12px;margin:0;">Batal</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="modalHapusMedia">
        <div class="modal-box" style="max-width:400px;">
            <div style="font-size:3rem;margin-bottom:10px;text-align:center;">🗑️</div>
            <h3 style="color:#ff4d4d;margin-bottom:10px;text-align:center;">Hapus Media Ini?</h3>
            <p style="color:#ccc;font-size:0.9rem;margin-bottom:25px;text-align:center;">File <strong id="hapusMediaText" style="color:white;"></strong> akan dihapus dari server.</p>
            <div style="display:flex;gap:10px;">
                <button id="btnProsesHapusMedia" class="btn-action btn-rej" onclick="eksekusiHapusMedia()" style="flex:1;padding:12px;margin:0;">Hapus File</button>
                <button class="btn-action btn-view" onclick="tutupModal('modalHapusMedia')" style="flex:1;padding:12px;margin:0;">Batal</button>
            </div>
        </div>
    </div>

    <script>
        // ── CHART.JS ──
        const ctx = document.getElementById('memberChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Member Aktif', 'Menunggu Verifikasi', 'Kedaluwarsa', 'Ditolak'],
                datasets: [{
                    data: [<?= $count_aktif ?>, <?= $count_pending ?>, <?= $count_expired ?>, <?= $count_ditolak ?>],
                    backgroundColor: ['#28a745','#ffc107','#E8C999','#8E1616'],
                    borderColor: '#111',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', labels: { color: '#F8EEDF', font: { family: "'Segoe UI', sans-serif" } } }
                },
                cutout: '65%'
            }
        });

        // ── SIDEBAR TOGGLE (MOBILE) ──
        const sidebarToggle  = document.getElementById('sidebarToggle');
        const mainSidebar    = document.getElementById('mainSidebar');
        const sidebarBackdrop = document.getElementById('sidebarBackdrop');

        function openSidebar()  { mainSidebar.classList.add('sidebar-open'); sidebarBackdrop.classList.add('open'); }
        function closeSidebar() { mainSidebar.classList.remove('sidebar-open'); sidebarBackdrop.classList.remove('open'); }

        sidebarToggle.addEventListener('click', () => mainSidebar.classList.contains('sidebar-open') ? closeSidebar() : openSidebar());
        sidebarBackdrop.addEventListener('click', closeSidebar);

        // ── TABS ──
        let currentIdMembership = '', currentIdUser = '', currentIdMedia = '';

        function switchTab(tabId, el) {
            document.querySelectorAll('.tab-section').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.menu-item').forEach(m => m.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            el.classList.add('active');
            if (window.innerWidth <= 1024) closeSidebar();
        }

        // ==========================================================
        // ── PAGINATION & SEARCH LOGIC (VERIFIKASI, MEMBER, ARSIP) ──
        // ==========================================================
        let currentPageVerif = 1;
        let currentPageMember = 1;
        let currentPageArsip = 1;
        const rowsPerPage = 10;

        function renderTable(tableId, paginationId, searchInputId, filterSelectId, currentPageVarStr) {
            const sv = document.getElementById(searchInputId) ? document.getElementById(searchInputId).value.toLowerCase() : '';
            const fv = document.getElementById(filterSelectId) ? document.getElementById(filterSelectId).value.toLowerCase() : '';
            const tbody = document.querySelector(`#${tableId} tbody`);
            const trs = Array.from(tbody.querySelectorAll('tr:not(.no-data)'));
            
            let visibleRows = [];
            trs.forEach(tr => {
                let textToSearch = tr.cells[0].innerText.toLowerCase(); 
                let badgeText = '';

                if(tableId === 'tabelVerifikasi') {
                    textToSearch = tr.cells[1].innerText.toLowerCase();
                    badgeText = tr.querySelector('.jenis-label') ? tr.querySelector('.jenis-label').innerText.toLowerCase() : '';
                } else if (tableId === 'tabelMember') {
                    badgeText = tr.querySelector('.status-label') ? tr.querySelector('.status-label').innerText.toLowerCase() : '';
                }
                
                if (textToSearch.includes(sv) && (fv === '' || badgeText === fv)) {
                    visibleRows.push(tr);
                } else {
                    tr.style.display = 'none'; 
                }
            });

            const totalPages = Math.ceil(visibleRows.length / rowsPerPage);
            
            let currentPage = 1;
            if (currentPageVarStr === 'currentPageVerif') currentPage = currentPageVerif;
            else if (currentPageVarStr === 'currentPageMember') currentPage = currentPageMember;
            else if (currentPageVarStr === 'currentPageArsip') currentPage = currentPageArsip;

            if (currentPage > totalPages && totalPages > 0) currentPage = totalPages;
            if (currentPage < 1) currentPage = 1;
            
            if (currentPageVarStr === 'currentPageVerif') currentPageVerif = currentPage;
            else if (currentPageVarStr === 'currentPageMember') currentPageMember = currentPage;
            else if (currentPageVarStr === 'currentPageArsip') currentPageArsip = currentPage;

            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;

            visibleRows.forEach((tr, index) => {
                if (index >= start && index < end) { tr.style.display = ''; } else { tr.style.display = 'none'; }
            });

            const pagContainer = document.getElementById(paginationId);
            if(pagContainer) {
                pagContainer.innerHTML = '';
                if (totalPages > 1) {
                    for (let i = 1; i <= totalPages; i++) {
                        const btn = document.createElement('button');
                        btn.innerText = i;
                        btn.className = `btn-page ${i === currentPage ? 'active' : ''}`;
                        btn.onclick = () => {
                            if (currentPageVarStr === 'currentPageVerif') currentPageVerif = i;
                            else if (currentPageVarStr === 'currentPageMember') currentPageMember = i;
                            else if (currentPageVarStr === 'currentPageArsip') currentPageArsip = i;
                            renderTable(tableId, paginationId, searchInputId, filterSelectId, currentPageVarStr);
                        };
                        pagContainer.appendChild(btn);
                    }
                }
            }
        }

        function filterVerifikasi() { currentPageVerif = 1; renderTable('tabelVerifikasi', 'pagVerifikasi', 'searchVerifikasi', 'filterVerifikasiJenis', 'currentPageVerif'); }
        function filterMember() { currentPageMember = 1; renderTable('tabelMember', 'pagMember', 'searchMember', 'filterStatus', 'currentPageMember'); }
        function filterArsip() { currentPageArsip = 1; renderTable('tabelArsip', 'pagArsip', 'searchArsip', null, 'currentPageArsip'); }

        document.addEventListener('DOMContentLoaded', () => {
            renderTable('tabelVerifikasi', 'pagVerifikasi', 'searchVerifikasi', 'filterVerifikasiJenis', 'currentPageVerif');
            renderTable('tabelMember', 'pagMember', 'searchMember', 'filterStatus', 'currentPageMember');
            renderTable('tabelArsip', 'pagArsip', 'searchArsip', null, 'currentPageArsip');
        });

        // ── TOGGLE LIBUR ──
        function toggleLibur(checkbox, containerId) {
            const inputs = document.getElementById(containerId).querySelectorAll('input[type="time"]');
            inputs.forEach(input => { input.disabled = checkbox.checked; if (checkbox.checked) input.value = ''; });
        }

        // ── VALIDASI WA ──
        function validasiAngka(input, errId) {
            const error = document.getElementById(errId);
            if (/\D/g.test(input.value)) { error.style.display = 'block'; input.value = input.value.replace(/\D/g, ''); } else { error.style.display = 'none'; }
        }

        // ── MODAL HELPERS ──
        function bukaModal(id) { document.getElementById(id).style.display = 'flex'; }
        function tutupModal(id) { document.getElementById(id).style.display = 'none'; }
        function lihatBuktiTransfer(imgUrl) { document.getElementById('imgBukti').src = imgUrl; bukaModal('modalBukti'); }
        function bukaModalCetak() { document.getElementById('modalCetakLaporan').style.display = 'flex'; }

        // ── VERIFIKASI ACTIONS ──
        function konfirmasiTerima(id, nama) { currentIdMembership = id; document.getElementById('verifNama').innerText = nama; bukaModal('modalTerimaPembayaran'); }
        function eksekusiTerima() {
            const fd = new FormData(); fd.append('action', 'terima'); fd.append('id_membership', currentIdMembership);
            fetch('admin_dasbor.php', { method: 'POST', body: fd }).then(r => r.json()).then(d => { if (d.status === 'success') location.reload(); });
        }
        function konfirmasiTolak(id, nama) { currentIdMembership = id; document.getElementById('tolakNama').innerText = nama; bukaModal('modalTolakPembayaran'); }
        function eksekusiTolak(e) {
            e.preventDefault();
            const fd = new FormData(); fd.append('action', 'tolak'); fd.append('id_membership', currentIdMembership); fd.append('alasan', document.getElementById('alasanTolakText').value);
            fetch('admin_dasbor.php', { method: 'POST', body: fd }).then(r => r.json()).then(d => { if (d.status === 'success') location.reload(); });
        }

        // ── ARSIP & PULIHKAN (PENGGANTI HAPUS) ──
        function konfirmasiArsip(id, nama) { currentIdUser = id; document.getElementById('arsipNamaText').innerText = nama; bukaModal('modalArsipMember'); }
        function eksekusiArsip() {
            const fd = new FormData(); fd.append('action', 'arsip'); fd.append('id_user', currentIdUser);
            fetch('admin_dasbor.php', { method: 'POST', body: fd }).then(r => r.json()).then(d => { 
                if (d.status === 'success') {
                    location.reload(); 
                } else {
                    alert('Gagal mengarsipkan! Pastikan tidak ada error di database.');
                }
            });
        }
        
        function konfirmasiPulihkan(id, nama) { currentIdUser = id; document.getElementById('pulihkanNamaText').innerText = nama; bukaModal('modalPulihkanMember'); }
        function eksekusiPulihkan() {
            const fd = new FormData(); fd.append('action', 'pulihkan'); fd.append('id_user', currentIdUser);
            fetch('admin_dasbor.php', { method: 'POST', body: fd }).then(r => r.json()).then(d => { if (d.status === 'success') location.reload(); });
        }

        // ── EDIT MEMBER ──
        function bukaEditMember(id, nama, email, wa) {
            document.getElementById('eId').value = id; document.getElementById('eNama').value = nama;
            document.getElementById('eEmail').value = email; document.getElementById('eWa').value = wa;
            document.getElementById('ePass').value = ''; bukaModal('modalEditMember');
        }
        function prosesEditMember(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSimpanEdit'); btn.innerText = 'Menyimpan...'; btn.disabled = true;
            const fd = new FormData();
            fd.append('action', 'edit_member'); fd.append('id_user', document.getElementById('eId').value);
            fd.append('nama', document.getElementById('eNama').value); fd.append('wa', document.getElementById('eWa').value);
            fd.append('pass', document.getElementById('ePass').value);
            fetch('admin_dasbor.php', { method: 'POST', body: fd }).then(r => r.json()).then(d => {
                if (d.status === 'success') { alert('✅ Data member berhasil diperbarui!'); location.reload(); }
                else { alert('❌ Gagal memperbarui data.'); btn.innerText = 'Simpan Perubahan'; btn.disabled = false; }
            });
        }

        // ── TAMBAH MEMBER ──
        function prosesTambahMember(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSimpanMember'); btn.innerText = 'Menyimpan...'; btn.disabled = true;
            const fd = new FormData();
            fd.append('action', 'tambah_member'); fd.append('nama', document.getElementById('tNama').value);
            fd.append('email', document.getElementById('tEmail').value); fd.append('wa', document.getElementById('tWa').value);
            fd.append('pass', document.getElementById('tPass').value); fd.append('paket', document.getElementById('tPaket').value);
            fd.append('tgl', document.getElementById('tTgl').value);
            fetch('admin_dasbor.php', { method: 'POST', body: fd }).then(r => r.json()).then(d => {
                if (d.status === 'success') { alert('✅ Member berhasil ditambahkan dan otomatis Aktif!'); location.reload(); }
                else { alert('❌ Gagal: ' + (d.message || 'Terjadi kesalahan')); btn.innerText = 'Simpan & Aktifkan Member'; btn.disabled = false; }
            });
        }

        // ── PENGATURAN KONTEN ──
        function kirimPengaturan(fd, pesan) { fetch('admin_dasbor.php', { method: 'POST', body: fd }).then(r => r.json()).then(d => { if (d.status === 'success') alert(pesan); }); }
        function simpanBanner(e) { e.preventDefault(); const fd = new FormData(); fd.append('action', 'update_banner'); fd.append('status', document.getElementById('set_banner_status').value); fd.append('teks', document.getElementById('set_banner_teks').value); kirimPengaturan(fd, '✅ Banner pengumuman berhasil diperbarui!'); }
        function simpanHarga(e) { e.preventDefault(); const fd = new FormData(); fd.append('action', 'update_harga'); fd.append('harian', document.getElementById('set_harga_harian').value); fd.append('bulanan', document.getElementById('set_harga_bulanan').value); fd.append('senam', document.getElementById('set_harga_senam').value); kirimPengaturan(fd, '✅ Harga berhasil diperbarui!'); }
        function simpanKontak(e) { e.preventDefault(); const fd = new FormData(); fd.append('action', 'update_kontak'); fd.append('wa', document.getElementById('set_wa').value); fd.append('ig', document.getElementById('set_ig').value); kirimPengaturan(fd, '✅ Info kontak berhasil diperbarui!'); }
        function simpanJam(e) {
            e.preventDefault(); const keys = ['sjPagi','sjSiang','sbPagi','sbSiang','mgPagi','mgSiang']; const jamData = {};
            keys.forEach(k => { jamData[k] = { libur: document.getElementById('cb_' + k).checked, buka:  document.getElementById('v_' + k + '_b').value, tutup: document.getElementById('v_' + k + '_t').value }; });
            const fd = new FormData(); fd.append('action', 'update_jam'); fd.append('data_jam', JSON.stringify(jamData)); kirimPengaturan(fd, '✅ Jadwal Operasional Gym berhasil diperbarui!');
        }
        function simpanJadwalSenam(e) {
            e.preventDefault(); const keys = ['sr','sk','sb','mg']; const jsData = {};
            keys.forEach(k => { jsData[k] = { libur: document.getElementById('libur_' + k).checked, buka:  document.getElementById('buka_' + k).value, tutup: document.getElementById('tutup_' + k).value, ket: document.getElementById('ket_' + k).value }; });
            const fd = new FormData(); fd.append('action', 'update_jadwal_senam'); fd.append('data_js', JSON.stringify(jsData)); kirimPengaturan(fd, '✅ Jadwal Kelas Senam berhasil diperbarui!');
        }

        // ── GALERI ──
        function sesuaikanInputFile() { const tipe = document.getElementById('tipe_media').value; document.getElementById('file_media').accept = (tipe === 'foto') ? 'image/jpeg,image/png,image/webp' : 'video/mp4,video/webm'; }
        function uploadMedia(e) {
            e.preventDefault(); const btn = document.getElementById('btnUpload'); const orig = btn.innerText; btn.innerText = 'Mengupload...'; btn.disabled = true;
            const fd = new FormData(); fd.append('action', 'upload_galeri'); fd.append('judul_media', document.getElementById('judul_media').value); fd.append('kategori_media', document.getElementById('kategori_media').value); fd.append('tipe_media', document.getElementById('tipe_media').value); fd.append('file_media', document.getElementById('file_media').files[0]);
            fetch('admin_dasbor.php', { method: 'POST', body: fd }).then(r => r.json()).then(d => { alert(d.message); if (d.status === 'success') location.reload(); btn.innerText = orig; btn.disabled = false; }).catch(() => { alert('Terjadi kesalahan jaringan.'); btn.innerText = orig; btn.disabled = false; });
        }
        function konfirmasiHapusMedia(id, judul) { currentIdMedia = id; document.getElementById('hapusMediaText').innerText = judul; bukaModal('modalHapusMedia'); }
        function eksekusiHapusMedia() { const fd = new FormData(); fd.append('action', 'hapus_galeri'); fd.append('id_media', currentIdMedia); fetch('admin_dasbor.php', { method: 'POST', body: fd }).then(r => r.json()).then(d => { if (d.status === 'success') location.reload(); }); }
    </script>
</body>
</html>