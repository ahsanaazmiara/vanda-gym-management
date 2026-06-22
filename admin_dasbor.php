<?php
// Atur masa aktif session menjadi 1 hari (86400 detik)
ini_set('session.gc_maxlifetime', 86400);
session_set_cookie_params(86400);

session_start();
require 'includes/koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$cek_pengaturan = mysqli_query($koneksi, "SELECT id FROM pengaturan_web WHERE id=1");
if(mysqli_num_rows($cek_pengaturan) == 0) {
    mysqli_query($koneksi, "INSERT INTO pengaturan_web (id) VALUES (1)");
}
mysqli_query($koneksi, "ALTER TABLE users MODIFY COLUMN role VARCHAR(20) DEFAULT 'member'");

// =========================================================
// AJAX HANDLERS
// =========================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

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

    if ($action === 'arsip') {
        $id_u = mysqli_real_escape_string($koneksi, $_POST['id_user']);
        $q = mysqli_query($koneksi, "UPDATE users SET role='arsip' WHERE id_user='$id_u'");
        echo json_encode(['status' => $q ? 'success' : 'error']); exit;
    }

    if ($action === 'pulihkan') {
        $id_u = mysqli_real_escape_string($koneksi, $_POST['id_user']);
        $q = mysqli_query($koneksi, "UPDATE users SET role='member' WHERE id_user='$id_u'");
        echo json_encode(['status' => $q ? 'success' : 'error']); exit;
    }

    if ($action === 'hapus') {
        $id_u = mysqli_real_escape_string($koneksi, $_POST['id_user']);
        mysqli_query($koneksi, "DELETE FROM membership WHERE id_user='$id_u'");
        $q = mysqli_query($koneksi, "DELETE FROM users WHERE id_user='$id_u'");
        echo json_encode(['status' => $q ? 'success' : 'error']); exit;
    }

    // BULK ACTIONS
    if ($action === 'bulk_hapus') {
        $ids_raw = $_POST['ids'] ?? '';
        $ids = array_filter(array_map('intval', explode(',', $ids_raw)));
        if (empty($ids)) { echo json_encode(['status' => 'error', 'message' => 'Tidak ada data dipilih.']); exit; }
        $ids_str = implode(',', $ids);
        mysqli_query($koneksi, "DELETE FROM membership WHERE id_user IN ($ids_str)");
        $q = mysqli_query($koneksi, "DELETE FROM users WHERE id_user IN ($ids_str)");
        echo json_encode(['status' => $q ? 'success' : 'error', 'deleted' => $ids]); exit;
    }

    if ($action === 'bulk_arsip') {
        $ids_raw = $_POST['ids'] ?? '';
        $ids = array_filter(array_map('intval', explode(',', $ids_raw)));
        if (empty($ids)) { echo json_encode(['status' => 'error', 'message' => 'Tidak ada data dipilih.']); exit; }
        $ids_str = implode(',', $ids);
        $q = mysqli_query($koneksi, "UPDATE users SET role='arsip' WHERE id_user IN ($ids_str)");
        echo json_encode(['status' => $q ? 'success' : 'error', 'archived' => $ids]); exit;
    }

    if ($action === 'bulk_pulihkan') {
        $ids_raw = $_POST['ids'] ?? '';
        $ids = array_filter(array_map('intval', explode(',', $ids_raw)));
        if (empty($ids)) { echo json_encode(['status' => 'error', 'message' => 'Tidak ada data dipilih.']); exit; }
        $ids_str = implode(',', $ids);
        $q = mysqli_query($koneksi, "UPDATE users SET role='member' WHERE id_user IN ($ids_str)");
        echo json_encode(['status' => $q ? 'success' : 'error', 'restored' => $ids]); exit;
    }

    if ($action === 'edit_member') {
        $id_u  = mysqli_real_escape_string($koneksi, $_POST['id_user']);
        $nama  = mysqli_real_escape_string($koneksi, $_POST['nama']);
        $wa    = mysqli_real_escape_string($koneksi, $_POST['wa']);
        $pass  = $_POST['pass'];
        $email = mysqli_real_escape_string($koneksi, $_POST['email'] ?? '');

        $query_upd = "UPDATE users SET nama_lengkap='$nama', no_wa='$wa'";
        if (!empty($email)) {
            $query_upd .= ", email='$email'";
        }
        if (!empty($pass)) {
            $pass_hash = password_hash($pass, PASSWORD_DEFAULT);
            $query_upd .= ", password='$pass_hash'";
        }
        $query_upd .= " WHERE id_user='$id_u'";
        $q = mysqli_query($koneksi, $query_upd);
        echo json_encode(['status' => $q ? 'success' : 'error']); exit;
    }

    if ($action === 'tambah_member') {
        $nama      = mysqli_real_escape_string($koneksi, $_POST['nama']);
        $email     = trim($_POST['email'] ?? '');
        $wa        = mysqli_real_escape_string($koneksi, $_POST['wa'] ?? '');
        $paket     = (int)$_POST['paket'];
        $tgl_mulai = $_POST['tgl'];
        $buat_akun = $_POST['buat_akun'] ?? 'tidak';
        $jenis     = $_POST['jenis'] ?? 'baru';
        $id_lama   = (int)($_POST['id_member_lama'] ?? 0);
        $id_new    = 0;

        if ($jenis === 'perpanjang' && $id_lama > 0) {
            $id_new = $id_lama;
            mysqli_query($koneksi, "UPDATE users SET nama_lengkap='$nama', no_wa='$wa' WHERE id_user='$id_lama'");
        } else {
            if ($buat_akun === 'ya' && !empty($email)) {
                $email_esc = mysqli_real_escape_string($koneksi, $email);
                $cek = mysqli_query($koneksi, "SELECT id_user, role FROM users WHERE email='$email_esc'");
                if (mysqli_num_rows($cek) > 0) {
                    $data_u = mysqli_fetch_assoc($cek);
                    if ($data_u['role'] === 'admin') { echo json_encode(['status'=>'error','message'=>'Email adalah akun Admin!']); exit; }
                    $id_u = $data_u['id_user'];
                    $cek_m = mysqli_query($koneksi, "SELECT status FROM membership WHERE id_user='$id_u' AND status='aktif'");
                    if (mysqli_num_rows($cek_m) > 0) { echo json_encode(['status'=>'error','message'=>'Email sudah terdaftar aktif.']); exit; }
                    $pass_hash = password_hash($_POST['pass'] ?? '123456', PASSWORD_DEFAULT);
                    mysqli_query($koneksi, "UPDATE users SET nama_lengkap='$nama', no_wa='$wa', password='$pass_hash', role='member' WHERE id_user='$id_u'");
                    $id_new = $id_u;
                } else {
                    $pass_hash = password_hash($_POST['pass'] ?? '123456', PASSWORD_DEFAULT);
                    $q1 = mysqli_query($koneksi, "INSERT INTO users (nama_lengkap, email, no_wa, password, role) VALUES ('$nama', '$email_esc', '$wa', '$pass_hash', 'member')");
                    if ($q1) $id_new = mysqli_insert_id($koneksi);
                }
            } else {
                $placeholder = 'noakun_' . time() . '_' . rand(100,999) . '@noemail.local';
                $q1 = mysqli_query($koneksi, "INSERT INTO users (nama_lengkap, email, no_wa, password, role) VALUES ('$nama', '$placeholder', '$wa', '', 'member')");
                if ($q1) $id_new = mysqli_insert_id($koneksi);
            }
        }

        if ($id_new > 0) {
            $harga = ($paket == 1) ? 175000 : (($paket == 2) ? 350000 : 525000);
            $tgl_akhir = date('Y-m-d', strtotime($tgl_mulai . " + $paket months"));
            $jenis_pengajuan = ($jenis === 'perpanjang') ? 'perpanjang' : 'daftar';
            $q2 = mysqli_query($koneksi, "INSERT INTO membership (id_user, jenis_pengajuan, paket_bulan, total_harga, tgl_mulai, tgl_berakhir, metode_bayar, status) VALUES ($id_new, '$jenis_pengajuan', $paket, $harga, '$tgl_mulai', '$tgl_akhir', 'tunai', 'aktif')");
            echo json_encode(['status' => $q2 ? 'success' : 'error']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal membuat data pengguna.']);
        }
        exit;
    }

    if ($action === 'cari_member_lama') {
        $keyword = mysqli_real_escape_string($koneksi, $_POST['keyword']);
        $result = mysqli_query($koneksi, "
            SELECT u.id_user, u.nama_lengkap, u.no_wa, u.email,
                   m.status, m.tgl_berakhir, m.paket_bulan
            FROM users u
            LEFT JOIN (SELECT id_user, MAX(id_membership) as max_id FROM membership GROUP BY id_user) lm ON u.id_user = lm.id_user
            LEFT JOIN membership m ON lm.max_id = m.id_membership
            WHERE u.role NOT IN ('admin') AND (u.nama_lengkap LIKE '%$keyword%' OR u.no_wa LIKE '%$keyword%' OR u.email LIKE '%$keyword%')
            LIMIT 10
        ");
        $data = [];
        while ($r = mysqli_fetch_assoc($result)) $data[] = $r;
        echo json_encode(['status' => 'success', 'data' => $data]); exit;
    }

    if ($action === 'riwayat_member') {
        $id_u = mysqli_real_escape_string($koneksi, $_POST['id_user']);
        $q = mysqli_query($koneksi, "SELECT * FROM membership WHERE id_user='$id_u' ORDER BY tgl_mulai DESC, id_membership DESC");
        $data = [];
        while($r = mysqli_fetch_assoc($q)) {
            $r['tgl_mulai_format'] = date('d M Y', strtotime($r['tgl_mulai']));
            $r['tgl_akhir_format'] = $r['tgl_berakhir'] ? date('d M Y', strtotime($r['tgl_berakhir'])) : '-';
            $r['tgl_buat_format'] = date('d M Y', strtotime($r['created_at']));
            $data[] = $r;
        }
        echo json_encode(['status' => 'success', 'data' => $data]);
        exit;
    }

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

    if ($action === 'upload_galeri') {
        $judul = mysqli_real_escape_string($koneksi, $_POST['judul_media']);
        $caption = mysqli_real_escape_string($koneksi, $_POST['caption_media']);
        $kategori = $_POST['kategori_media'];
        $tipe = $_POST['tipe_media'];
        if(isset($_FILES['file_media']) && $_FILES['file_media']['error'] == 0) {
            $file = $_FILES['file_media'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_img = ['jpg','jpeg','png','webp']; $allowed_vid = ['mp4','webm'];
            if (($tipe == 'foto' && !in_array($ext, $allowed_img)) || ($tipe == 'video' && !in_array($ext, $allowed_vid))) {
                echo json_encode(['status'=>'error','message'=>'Format file tidak sesuai!']); exit;
            }
            $nama_file_baru = time() . '_' . rand(100,999) . '.' . $ext;
            $target_dir = "uploads/galeri/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $target_file = $target_dir . $nama_file_baru;
            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                $q = mysqli_query($koneksi, "INSERT INTO galeri_gym (judul, caption, kategori, tipe_media, file_path) VALUES ('$judul', '$caption', '$kategori', '$tipe', '$target_file')");
                echo json_encode(['status' => $q ? 'success' : 'error', 'message' => $q ? 'Media diupload!' : 'Gagal DB.']);
            } else { echo json_encode(['status'=>'error','message'=>'Gagal pindah file.']); }
        } else { echo json_encode(['status'=>'error','message'=>'File tidak ada/kebesaran.']); }
        exit;
    }

    if ($action === 'edit_galeri') {
        $id_media = (int)$_POST['id_media'];
        $judul = mysqli_real_escape_string($koneksi, $_POST['judul_media']);
        $caption = mysqli_real_escape_string($koneksi, $_POST['caption_media']);
        $kategori = $_POST['kategori_media'];
        if (isset($_FILES['file_media_edit']) && $_FILES['file_media_edit']['error'] == 0) {
            $q_file_lama = mysqli_query($koneksi, "SELECT file_path, tipe_media FROM galeri_gym WHERE id_media=$id_media");
            $row_lama = mysqli_fetch_assoc($q_file_lama);
            $tipe_lama = $row_lama['tipe_media'];
            $file = $_FILES['file_media_edit'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_img = ['jpg','jpeg','png','webp']; $allowed_vid = ['mp4','webm'];
            if (($tipe_lama == 'foto' && !in_array($ext, $allowed_img)) || ($tipe_lama == 'video' && !in_array($ext, $allowed_vid))) {
                echo json_encode(['status'=>'error','message'=>'Format file tidak sesuai dengan tipe media aslinya!']); exit;
            }
            $nama_file_baru = time() . '_' . rand(100,999) . '.' . $ext;
            $target_dir = "uploads/galeri/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $target_file = $target_dir . $nama_file_baru;
            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                if (file_exists($row_lama['file_path'])) unlink($row_lama['file_path']);
                $q = mysqli_query($koneksi, "UPDATE galeri_gym SET judul='$judul', caption='$caption', kategori='$kategori', file_path='$target_file' WHERE id_media=$id_media");
                echo json_encode(['status' => $q ? 'success' : 'error', 'message' => $q ? 'Media diperbarui!' : 'Gagal update DB.']);
            } else { echo json_encode(['status'=>'error','message'=>'Gagal memindahkan file baru.']); }
        } else {
            $q = mysqli_query($koneksi, "UPDATE galeri_gym SET judul='$judul', caption='$caption', kategori='$kategori' WHERE id_media=$id_media");
            echo json_encode(['status' => $q ? 'success' : 'error', 'message' => $q ? 'Info media diperbarui!' : 'Gagal update DB.']);
        }
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
// DATA STATISTIK & PENGATURAN
// =========================================================
$count_pending = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as c FROM membership WHERE status='pending'"))['c'];
$total_income  = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(total_harga) as s FROM membership WHERE status IN ('aktif', 'kedaluwarsa')"))['s'] ?? 0;
$rp_income = number_format($total_income, 0, ',', '.');

// Hitung Member Aktif & Kedaluwarsa (MURNI DARI STATUS TERAKHIR TIAP USER)
$count_aktif = 0;
$count_expired = 0;

$query_stats = mysqli_query($koneksi, "
    SELECT m.status, COUNT(*) as jumlah
    FROM users u
    INNER JOIN (
        SELECT id_user, MAX(id_membership) as max_id 
        FROM membership 
        GROUP BY id_user
    ) latest_m ON u.id_user = latest_m.id_user
    INNER JOIN membership m ON latest_m.max_id = m.id_membership
    WHERE u.role NOT IN ('admin','arsip')
    GROUP BY m.status
");

while ($row = mysqli_fetch_assoc($query_stats)) {
    if ($row['status'] == 'aktif') {
        $count_aktif = $row['jumlah'];
    } else if ($row['status'] == 'kedaluwarsa') {
        $count_expired = $row['jumlah'];
    }
}

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
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--bg-dark); color: var(--text-light); display: flex; min-height: 100vh; overflow-x: hidden; max-width: 100vw; }

        /* SIDEBAR */
        .sidebar { width: var(--sidebar-width); background-color: #0a0a0a; border-right: 1px solid #222; display: flex; flex-direction: column; position: fixed; height: 100vh; z-index: 1000; transition: transform 0.3s ease; }
        .sidebar-header { padding: 20px; text-align: center; border-bottom: 2px solid var(--primary-red); background: #050505; }
        .sidebar-header h2 { color: var(--accent-gold); font-size: 1.4rem; letter-spacing: 1px; }
        .sidebar-header p { color: #888; font-size: 0.8rem; text-transform: uppercase; margin-top: 5px; }
        .sidebar-menu { flex: 1; padding: 20px 0; overflow-y: auto; }
        .sidebar-menu::-webkit-scrollbar { width: 4px; }
        .sidebar-menu::-webkit-scrollbar-thumb { background: #333; border-radius: 2px; }
        .menu-item { padding: 15px 25px; display: flex; align-items: center; gap: 15px; color: #aaa; text-decoration: none; font-weight: 600; font-size: 0.95rem; transition: 0.3s; cursor: pointer; border-left: 3px solid transparent; }
        .menu-item svg { stroke: #aaa; transition: 0.3s; min-width: 20px; }
        .menu-item:hover { background-color: #111; color: var(--text-light); }
        .menu-item:hover svg { stroke: var(--text-light); }
        .menu-item.active { background-color: rgba(232,201,153,0.1); color: var(--accent-gold); border-left: 3px solid var(--accent-gold); }
        .menu-item.active svg { stroke: var(--accent-gold); }
        .sidebar-footer { padding: 20px; border-top: 1px solid #222; }
        .btn-logout { width: 100%; background: transparent; border: 1px solid var(--primary-red); color: var(--primary-red); padding: 10px; border-radius: 4px; font-weight: bold; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-logout:hover { background: var(--primary-red); color: white; }

        /* HAMBURGER */
        .sidebar-toggle { display: none; position: fixed; top: 15px; left: 15px; z-index: 1001; background: #111; border: 1px solid #333; color: var(--accent-gold); padding: 9px 11px; border-radius: 6px; cursor: pointer; transition: background 0.2s; line-height: 1; }
        .sidebar-toggle:hover { background: #1a1a1a; }
        .sidebar-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); z-index: 990; }
        .sidebar-backdrop.open { display: block; }

        /* MAIN */
        .main-content { flex: 1; min-width: 0; margin-left: var(--sidebar-width); padding: 30px 40px; background-color: var(--bg-dark); }
        .top-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #333; padding-bottom: 15px; }
        .top-header h1 { color: var(--text-light); font-size: 1.8rem; }
        .admin-profile { display: flex; align-items: center; gap: 10px; color: var(--accent-gold); font-weight: bold; }

        /* TABS */
        .tab-section { display: none; animation: fadeIn 0.3s; }
        .tab-section.active { display: block; }
        @keyframes fadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }

        /* STATS */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #111; border: 1px solid #333; padding: 20px; border-radius: 8px; text-align: center; border-top: 3px solid var(--accent-gold); }
        .stat-card h3 { color: #888; font-size: 0.9rem; margin-bottom: 10px; text-transform: uppercase; }
        .stat-card .number { color: var(--text-light); font-size: 2.5rem; font-weight: bold; }
        .stat-card.alert { border-top-color: var(--primary-red); }
        .stat-card.alert .number { color: var(--primary-red); }

        /* ACTIVITY */
        .activity-list { background: #0a0a0a; border: 1px solid #222; border-radius: 8px; padding: 20px; height: 100%; }
        .activity-item { display: flex; justify-content: space-between; align-items: flex-start; padding: 15px 0; border-bottom: 1px dashed #333; gap: 15px; }
        .activity-item:last-child { border-bottom: none; padding-bottom: 0; }
        .activity-text { flex: 1; color: var(--text-light); font-size: 0.9rem; line-height: 1.5; }
        .activity-time { white-space: nowrap; color: #888; font-size: 0.75rem; padding-top: 2px; }

        /* TABLE */
        .table-container { background: #0a0a0a; border: 1px solid #222; border-radius: 8px; width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; margin-bottom: 15px; display: block; }
        table { width: 100%; min-width: 800px; border-collapse: collapse; text-align: left; }
        th, td { padding: 15px; border-bottom: 1px solid #222; }
        th { background-color: #111; color: var(--accent-gold); font-weight: 600; text-transform: uppercase; font-size: 0.85rem; white-space: nowrap; }
        td { color: #ccc; font-size: 0.9rem; vertical-align: middle; }
        tr:hover { background-color: #151515; }

        /* BULK */
        .bulk-checkbox { width: 16px; height: 16px; accent-color: var(--accent-gold); cursor: pointer; }
        .bulk-toolbar { display: none; align-items: center; gap: 10px; padding: 10px 15px; background: rgba(232,201,153,0.08); border: 1px solid var(--accent-gold); border-radius: 6px; margin-bottom: 12px; flex-wrap: wrap; }
        .bulk-toolbar.visible { display: flex; }
        .bulk-count { color: var(--accent-gold); font-weight: bold; font-size: 0.9rem; flex: 1; min-width: 100px; }
        .btn-bulk { padding: 7px 14px; border-radius: 4px; border: none; font-weight: bold; font-size: 0.82rem; cursor: pointer; transition: 0.2s; }
        .btn-bulk-danger { background: var(--primary-red); color: white; }
        .btn-bulk-danger:hover { background: #a81a1a; }
        .btn-bulk-warn { background: #ffc107; color: #000; }
        .btn-bulk-warn:hover { background: #e0a800; }
        .btn-bulk-success { background: var(--success-green); color: white; }
        .btn-bulk-success:hover { background: #218838; }
        .btn-bulk-cancel { background: #333; color: #ccc; border: 1px solid #555; }
        .btn-bulk-cancel:hover { background: #444; }

        /* PAGINATION */
        .pagination-container { display: flex; justify-content: flex-end; gap: 5px; margin-bottom: 30px; flex-wrap: wrap; }
        .btn-page { background: #111; border: 1px solid #333; color: var(--text-light); padding: 6px 12px; border-radius: 4px; cursor: pointer; transition: 0.3s; font-size: 0.85rem; }
        .btn-page:hover { background: #222; border-color: var(--accent-gold); }
        .btn-page.active { background: var(--accent-gold); color: #000; font-weight: bold; border-color: var(--accent-gold); }

        /* BADGES */
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; display: inline-block; }
        .b-warning { background: rgba(255,193,7,0.2); color: #ffc107; border: 1px solid #ffc107; }
        .b-success { background: rgba(40,167,69,0.2); color: var(--success-green); border: 1px solid var(--success-green); }
        .b-danger { background: rgba(142,22,22,0.2); color: #ff4d4d; border: 1px solid #ff4d4d; }
        .b-info { background: rgba(0,123,255,0.2); color: #66b2ff; border: 1px solid #66b2ff; }
        .b-gray { background: rgba(136,136,136,0.2); color: #aaa; border: 1px solid #888; }

        /* BUTTONS */
        .btn-action { padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: bold; transition: 0.3s; margin-right: 5px; margin-bottom: 5px; }
        .btn-acc { background: var(--success-green); color: white; }
        .btn-acc:hover { background: #218838; }
        .btn-rej { background: var(--primary-red); color: white; }
        .btn-rej:hover { background: #a81a1a; }
        .btn-view { background: #333; color: var(--accent-gold); border: 1px solid var(--accent-gold); }
        .btn-view:hover { background: var(--accent-gold); color: #000; }

        /* ICON BUTTONS */
        .btn-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: 0.3s;
            margin-right: 5px;
            margin-bottom: 5px;
            color: #fff;
        }
        .btn-icon svg { width: 16px; height: 16px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
        .btn-icon.bi-history { background: #17a2b8; }
        .btn-icon.bi-history:hover { background: #138496; }
        .btn-icon.bi-edit { background: #333; border: 1px solid var(--accent-gold); color: var(--accent-gold); }
        .btn-icon.bi-edit:hover { background: var(--accent-gold); color: #000; }
        .btn-icon.bi-arsip { background: #ffc107; color: #000; }
        .btn-icon.bi-arsip:hover { background: #e0a800; }
        .btn-icon.bi-hapus { background: var(--primary-red); }
        .btn-icon.bi-hapus:hover { background: #a81a1a; }

        /* FORMS */
        .form-group { margin-bottom: 15px; text-align: left; }
        .form-group label { display: block; margin-bottom: 8px; color: #888; font-size: 0.85rem; font-weight: 600; }
        .form-control { width: 100%; padding: 12px 15px; background: var(--input-bg); border: 1px solid #333; border-radius: 4px; color: white; transition: 0.3s; }
        .form-control:focus { outline: none; border-color: var(--accent-gold); }
        .form-control:disabled { background: #222; color: #555; cursor: not-allowed; border-color: #333; }
        input[type="date"], input[type="time"] { color-scheme: dark; }
        .btn-submit { background: var(--accent-gold); color: #000; padding: 12px 20px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; transition: 0.3s; width: 100%; }
        .btn-submit:hover { background: #cda971; }

        /* TOGGLE SWITCH */
        .toggle-row { display: flex; align-items: center; gap: 12px; margin-bottom: 15px; }
        .toggle-label { color: #888; font-size: 0.85rem; font-weight: 600; }
        .toggle-switch { position: relative; display: inline-block; width: 44px; height: 24px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #333; transition: .3s; border-radius: 24px; }
        .toggle-slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: #888; transition: .3s; border-radius: 50%; }
        input:checked + .toggle-slider { background-color: var(--accent-gold); }
        input:checked + .toggle-slider:before { transform: translateX(20px); background-color: #000; }

        /* INNER TABS (modal) */
        .inner-tab-bar { display: flex; border-bottom: 1px solid #333; margin-bottom: 20px; }
        .inner-tab-btn { padding: 10px 18px; background: none; border: none; color: #888; font-weight: 600; font-size: 0.9rem; cursor: pointer; border-bottom: 2px solid transparent; transition: 0.2s; }
        .inner-tab-btn.active { color: var(--accent-gold); border-bottom-color: var(--accent-gold); }
        .inner-tab-pane { display: none; }
        .inner-tab-pane.active { display: block; }

        /* AUTOCOMPLETE */
        .autocomplete-box { position: relative; }
        .autocomplete-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: #1a1a1a; border: 1px solid #444; border-radius: 4px; z-index: 999; max-height: 200px; overflow-y: auto; display: none; }
        .autocomplete-item { padding: 10px 14px; cursor: pointer; border-bottom: 1px solid #2a2a2a; transition: background 0.15s; }
        .autocomplete-item:hover { background: #252525; }
        .autocomplete-item .ac-name { color: var(--text-light); font-weight: 600; font-size: 0.9rem; }
        .autocomplete-item .ac-sub { color: #888; font-size: 0.75rem; margin-top: 2px; }

        /* GRID */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }

        /* MODALS */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); display: none; justify-content: center; align-items: center; z-index: 2000; padding: 20px; overflow-y: auto; }
        .modal-box { background: #111; border: 1px solid var(--accent-gold); padding: 30px; border-radius: 8px; max-width: 500px; width: 100%; position: relative; max-height: 90vh; overflow-y: auto; }
        .close-modal { position: absolute; top: 15px; right: 15px; background: transparent; border: none; color: #888; font-size: 1.5rem; cursor: pointer; transition: 0.3s; }
        .close-modal:hover { color: var(--primary-red); }

        /* CARDS */
        .content-card { background: #0a0a0a; border: 1px solid #222; border-radius: 8px; padding: 25px; margin-bottom: 30px; height: 100%; }
        .content-card h3 { color: var(--accent-gold); margin-bottom: 20px; font-size: 1.2rem; border-bottom: 1px dashed #333; padding-bottom: 10px; }
        .jam-card { background: #151515; border: 1px solid #333; border-radius: 6px; padding: 20px; margin-bottom: 15px; }
        .jam-card label.hari { color: var(--accent-gold); font-size: 1.1rem; display: block; margin-bottom: 15px; text-transform: uppercase; font-weight: bold; }
        .error-msg { color: #ff4d4d; font-size: 0.75rem; margin-top: 5px; display: none; }

        /* MEDIA GRID */
        .media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-top: 15px; }
        .media-item { border: 1px solid #333; border-radius: 6px; overflow: hidden; background: #111; position: relative; }
        .media-item img, .media-item video { width: 100%; height: 120px; object-fit: cover; display: block; border-bottom: 1px solid #222; }
        .media-item-info { padding: 12px; }
        .media-item-info p { margin-bottom: 10px; font-size: 0.85rem; font-weight: bold; color: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .media-item-info span { display: block; font-size: 0.7rem; color: var(--accent-gold); text-transform: uppercase; margin-bottom: 10px; }

        /* TOAST */
        #toast { position: fixed; bottom: 25px; right: 25px; background: #1a1a1a; border: 1px solid var(--accent-gold); color: var(--text-light); padding: 14px 20px; border-radius: 8px; font-size: 0.9rem; z-index: 9999; display: none; box-shadow: 0 4px 20px rgba(0,0,0,0.6); max-width: 320px; }
        #toast.show { display: block; animation: slideUp 0.3s ease; }
        #toast.toast-success { border-color: var(--success-green); }
        #toast.toast-error { border-color: #ff4d4d; }
        @keyframes slideUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }

        /* RESPONSIVE */
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
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px; }
            .stat-card { padding: 14px 10px; }
            .stat-card .number { font-size: 1.8rem; }
            .stat-card h3 { font-size: 0.75rem; }
            th, td { padding: 10px 12px; font-size: 0.82rem; }
            th { font-size: 0.8rem; }
            .content-card { padding: 16px 12px; }
            .content-card h3 { font-size: 1rem; }
            .jam-card .grid-2 { grid-template-columns: 1fr; gap: 10px; margin-bottom: 0; }
            .activity-item { flex-direction: column; gap: 6px; align-items: flex-start; }
            .modal-box { padding: 20px 15px; width: 95%; max-height: 85vh; }
            .modal-box .grid-2 { grid-template-columns: 1fr; gap: 10px; margin-bottom: 10px; }
            .media-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
            .bulk-toolbar { gap: 7px; }
        }
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 8px; }
            .stat-card { padding: 12px 8px; }
            .stat-card .number { font-size: 1.4rem; }
            .stat-card h3 { font-size: 0.65rem; }
            .sidebar { width: 240px; }
            .top-header h1 { font-size: 1.1rem; }
            .badge { font-size: 0.65rem; padding: 3px 6px; }
            td > .btn-action { display: block; width: 100%; margin-right: 0; margin-bottom: 5px; text-align: center; font-size: 0.7rem; padding: 6px 8px; }
            td > .btn-action:last-child { margin-bottom: 0; }
            .media-grid { grid-template-columns: 1fr; }
            .form-control { font-size: 0.8rem; padding: 10px 12px; }
            .btn-bulk { font-size: 0.75rem; padding: 6px 10px; }
        }
    </style>
</head>
<body>

<div id="toast"></div>

<button class="sidebar-toggle" id="sidebarToggle" aria-label="Buka Menu">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line>
    </svg>
</button>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<div class="sidebar" id="mainSidebar">
    <div class="sidebar-header"><h2>VANDA ADMIN</h2><p>Control Panel</p></div>
    <div class="sidebar-menu">
        <div class="menu-item active" onclick="switchTab('tab-dasbor', this)">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            Dasbor Utama
        </div>
        <div class="menu-item" onclick="switchTab('tab-verifikasi', this)">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
            Verifikasi Bayar <span class="badge b-warning" style="margin-left:auto;" id="badgePending"><?= $count_pending ?></span>
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
        <div class="admin-profile"><span>Halo, <?= $_SESSION['nama'] ?></span></div>
    </div>

    <div id="tab-dasbor" class="tab-section active">
        <div class="stats-grid">
            <div class="stat-card"><h3>Total Member Aktif</h3><div class="number" id="statAktif"><?= $count_aktif ?></div></div>
            <div class="stat-card alert"><h3>Menunggu Verifikasi</h3><div class="number" id="statPending"><?= $count_pending ?></div></div>
            <div class="stat-card"><h3>Kedaluwarsa</h3><div class="number" id="statExpired"><?= $count_expired ?></div></div>
            <div class="stat-card">
                <h3>Total Pendapatan</h3>
                <div class="number" style="font-size:1.8rem;line-height:2.5rem;color:var(--success-green);margin-bottom:10px;">Rp <?= $rp_income ?></div>
                <button onclick="bukaModalCetak()" class="btn-action btn-view" style="width:100%;margin:0;background:var(--success-green);color:white;border:none;padding:10px;">Cetak Laporan PDF</button>
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
                    if(mysqli_num_rows($q_log) == 0) echo "<div style='color:#888;text-align:center;padding:20px 0;'>Belum ada aktivitas.</div>";
                    while($log = mysqli_fetch_assoc($q_log)):
                        $waktu = date('d M Y - H:i', strtotime($log['created_at']));
                        $warna = '#fff';
                        if($log['status']=='aktif') $warna='var(--success-green)';
                        if($log['status']=='ditolak' || $log['status']=='kedaluwarsa') $warna='var(--primary-red)';
                        if($log['status']=='pending') $warna='#ffc107';
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
        <div style="display:flex;justify-content:space-between;margin-bottom:12px;align-items:center;flex-wrap:wrap;gap:15px;">
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
                <thead><tr><th>Tgl Pengajuan</th><th>Nama / Email</th><th>Jenis</th><th>Paket & Tgl Mulai</th><th>Metode</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php
                    $query_pending = mysqli_query($koneksi, "SELECT u.nama_lengkap, u.email, m.* FROM users u JOIN membership m ON u.id_user = m.id_user WHERE m.status='pending' ORDER BY m.id_membership DESC");
                    if(mysqli_num_rows($query_pending) == 0) echo "<tr class='no-data'><td colspan='6' style='text-align:center;padding:20px;'>Belum ada antrean.</td></tr>";
                    while($row = mysqli_fetch_assoc($query_pending)):
                    ?>
                    <tr data-id="<?= $row['id_membership'] ?>">
                        <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                        <td><strong><?= $row['nama_lengkap'] ?></strong><br><span style="font-size:0.8rem;color:#888;"><?= $row['email'] ?></span></td>
                        <td><span class="badge b-warning jenis-label"><?= ucfirst($row['jenis_pengajuan']) ?></span></td>
                        
                        <td>
                            <?= $row['paket_bulan'] ?> Bulan<br>
                            <span style="color:var(--accent-gold);font-weight:bold;">Rp <?= number_format($row['total_harga'],0,',','.') ?></span><br>
                            <span style="font-size:0.8rem;color:#aaa;">Mulai: <?= date('d M Y', strtotime($row['tgl_mulai'])) ?></span>
                        </td>

                        <td><?= strtoupper($row['metode_bayar']) ?></td>
                        <td style="min-width:250px;">
                            <?php if($row['metode_bayar']=='qris' && $row['bukti_bayar']): ?>
                                <button class="btn-action btn-view" onclick="lihatBuktiTransfer('uploads/<?= $row['bukti_bayar'] ?>')">Lihat Bukti</button>
                            <?php endif; ?>
                            <button class="btn-action btn-acc" onclick="konfirmasiTerima('<?= $row['id_membership'] ?>','<?= addslashes($row['nama_lengkap']) ?>')">Terima</button>
                            <button class="btn-action btn-rej" onclick="konfirmasiTolak('<?= $row['id_membership'] ?>','<?= addslashes($row['nama_lengkap']) ?>')">Tolak</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div class="pagination-container" id="pagVerifikasi"></div>
    </div>

    <div id="tab-member" class="tab-section">
        <div style="display:flex;justify-content:space-between;margin-bottom:12px;align-items:center;flex-wrap:wrap;gap:15px;">
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <input type="text" id="searchMember" class="form-control" placeholder="Cari nama / email..." style="width:250px;" onkeyup="filterMember()">
                <select class="form-control" id="filterStatus" onchange="filterMember()" style="width:180px;cursor:pointer;">
                    <option value="">Semua Status</option>
                    <option value="aktif">Aktif</option>
                    <option value="kedaluwarsa">Kedaluwarsa</option>
                    <option value="ditolak">Ditolak</option>
                </select>
            </div>
            <button class="btn-submit" style="width:auto;margin:0;" onclick="bukaModal('modalTambahMember')">+ Tambah Member</button>
        </div>
        <div class="bulk-toolbar" id="bulkToolbarMember">
            <span class="bulk-count" id="bulkCountMember">0 dipilih</span>
            <button class="btn-bulk btn-bulk-warn" onclick="bulkArsip()">📦 Arsipkan</button>
            <button class="btn-bulk btn-bulk-danger" onclick="bulkHapus()">🗑️ Hapus Permanen</button>
            <button class="btn-bulk btn-bulk-cancel" onclick="clearBulkMember()">Batal</button>
        </div>
        <div class="table-container">
            <table id="tabelMember">
                <thead>
                    <tr>
                        <th style="width:40px;"><input type="checkbox" class="bulk-checkbox" id="checkAllMember" onchange="toggleCheckAll('tabelMember','row-check-member','checkAllMember','bulkToolbarMember','bulkCountMember')"></th>
                        <th>Nama / Email</th><th>Kontak WA</th><th>Pembayaran</th><th>Masa Aktif</th><th>Status</th><th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query_member = mysqli_query($koneksi, "
                        SELECT u.id_user, u.nama_lengkap, u.email, u.no_wa,
                               m.status, m.tgl_mulai, m.tgl_berakhir, m.metode_bayar, m.total_harga
                        FROM users u
                        INNER JOIN (SELECT id_user, MAX(id_membership) as max_id FROM membership GROUP BY id_user) latest_m ON u.id_user = latest_m.id_user
                        INNER JOIN membership m ON latest_m.max_id = m.id_membership
                        WHERE u.role NOT IN ('admin','arsip') AND m.status IN ('aktif','kedaluwarsa','ditolak')
                        ORDER BY m.id_membership DESC
                    ");
                    if(mysqli_num_rows($query_member) == 0) echo "<tr class='no-data'><td colspan='7' style='text-align:center;padding:20px;'>Belum ada member terdaftar.</td></tr>";
                    
                    while($usr = mysqli_fetch_assoc($query_member)):
                        if($usr['status']=='aktif') $b='b-success';
                        else if($usr['status']=='ditolak') $b='b-danger';
                        else $b='b-warning';
                        $tampil_email = (strpos($usr['email'],'@noemail.local')!==false) ? '<em style="color:#555;">—</em>' : htmlspecialchars($usr['email']);

                        // LOGIKA RANTAI TANGGAL AKTIF BERSAMBUNG
                        $tgl_mulai_tampil = $usr['tgl_mulai'];
                        if($usr['status'] == 'aktif') {
                            $q_chain = mysqli_query($koneksi, "SELECT tgl_mulai, tgl_berakhir FROM membership WHERE id_user='{$usr['id_user']}' AND status='aktif' ORDER BY tgl_mulai DESC");
                            
                            $current_start = $usr['tgl_mulai'];
                            while($chain = mysqli_fetch_assoc($q_chain)) {
                                $end_prev = strtotime($chain['tgl_berakhir']);
                                $start_curr = strtotime($current_start);
                                
                                // Jika paket sebelumnya nyambung (memberi toleransi jeda 7 hari)
                                if ($end_prev >= strtotime('-7 days', $start_curr)) {
                                    $current_start = $chain['tgl_mulai'];
                                }
                            }
                            $tgl_mulai_tampil = $current_start;
                        }
                    ?>
                    <tr data-uid="<?= $usr['id_user'] ?>">
                        <td><input type="checkbox" class="bulk-checkbox row-check-member" onchange="updateBulkToolbar('row-check-member','checkAllMember','bulkToolbarMember','bulkCountMember')"></td>
                        <td><strong><?= $usr['nama_lengkap'] ?></strong><br><span style="font-size:0.85rem;color:#aaa;"><?= $tampil_email ?></span></td>
                        <td><?= $usr['no_wa'] ?></td>
                        <td><span style="color:var(--accent-gold);font-weight:bold;">Rp <?= number_format($usr['total_harga'],0,',','.') ?></span><br><span style="font-size:0.8rem;text-transform:uppercase;color:#888;"><?= $usr['metode_bayar'] ?></span></td>
                        
                        <td><?= $tgl_mulai_tampil ? date('d M Y',strtotime($tgl_mulai_tampil)) : '-' ?><br><span style="font-size:0.8rem;color:#888;">s/d <?= $usr['tgl_berakhir'] ? date('d M Y',strtotime($usr['tgl_berakhir'])) : '-' ?></span></td>
                        
                        <td><span class="badge <?= $b ?> status-label"><?= ucfirst($usr['status']) ?></span></td>
                        
                        <td style="min-width:120px; white-space:nowrap;">
                            <button class="btn-icon bi-history" title="Riwayat Transaksi" onclick="lihatRiwayat('<?= $usr['id_user'] ?>','<?= addslashes($usr['nama_lengkap']) ?>')">
                                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            </button>
                            
                            <button class="btn-icon bi-edit" title="Edit Member" onclick="bukaEditMember('<?= $usr['id_user'] ?>','<?= addslashes($usr['nama_lengkap']) ?>','<?= addslashes($usr['email']) ?>','<?= addslashes($usr['no_wa']) ?>')">
                                <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            </button>
                            
                            <?php if($usr['status']=='ditolak'): ?>
                                <button class="btn-icon bi-hapus" title="Hapus Permanen" onclick="konfirmasiHapus('<?= $usr['id_user'] ?>','<?= addslashes($usr['nama_lengkap']) ?>')">
                                    <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                </button>
                            <?php else: ?>
                                <button class="btn-icon bi-arsip" title="Arsipkan Data" onclick="konfirmasiArsip('<?= $usr['id_user'] ?>','<?= addslashes($usr['nama_lengkap']) ?>')">
                                    <svg viewBox="0 0 24 24"><polyline points="21 8 21 21 3 21 3 8"></polyline><rect x="1" y="3" width="22" height="5"></rect><line x1="10" y1="12" x2="14" y2="12"></line></svg>
                                </button>
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
        <div style="display:flex;justify-content:space-between;margin-bottom:12px;align-items:center;flex-wrap:wrap;gap:15px;">
            <p style="color:#888;margin:0;">Akun dinonaktifkan — riwayat transaksi tetap aman.</p>
            <input type="text" id="searchArsip" class="form-control" placeholder="Cari nama / email..." style="width:250px;" onkeyup="filterArsip()">
        </div>
        <div class="bulk-toolbar" id="bulkToolbarArsip">
            <span class="bulk-count" id="bulkCountArsip">0 dipilih</span>
            <button class="btn-bulk btn-bulk-success" onclick="bulkPulihkan()">♻️ Pulihkan</button>
            <button class="btn-bulk btn-bulk-danger" onclick="bulkHapusArsip()">🗑️ Hapus Permanen</button>
            <button class="btn-bulk btn-bulk-cancel" onclick="clearBulkArsip()">Batal</button>
        </div>
        <div class="table-container">
            <table id="tabelArsip">
                <thead>
                    <tr>
                        <th style="width:40px;"><input type="checkbox" class="bulk-checkbox" id="checkAllArsip" onchange="toggleCheckAll('tabelArsip','row-check-arsip','checkAllArsip','bulkToolbarArsip','bulkCountArsip')"></th>
                        <th>Nama / Email</th><th>Kontak WA</th><th>Terakhir Aktif</th><th>Status Terakhir</th><th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query_arsip = mysqli_query($koneksi, "
                        SELECT u.id_user, u.nama_lengkap, u.email, u.no_wa,
                               m.status, m.tgl_berakhir
                        FROM users u
                        LEFT JOIN (SELECT id_user, MAX(id_membership) as max_id FROM membership GROUP BY id_user) latest_m ON u.id_user = latest_m.id_user
                        LEFT JOIN membership m ON latest_m.max_id = m.id_membership
                        WHERE u.role='arsip'
                        ORDER BY u.id_user DESC
                    ");
                    if(mysqli_num_rows($query_arsip)==0) echo "<tr class='no-data'><td colspan='6' style='text-align:center;padding:20px;'>Belum ada data yang diarsipkan.</td></tr>";
                    while($arsip = mysqli_fetch_assoc($query_arsip)):
                        $tampil_email_a = (strpos($arsip['email'],'@noemail.local')!==false) ? '<em style="color:#555;">—</em>' : htmlspecialchars($arsip['email']);
                    ?>
                    <tr data-uid="<?= $arsip['id_user'] ?>">
                        <td><input type="checkbox" class="bulk-checkbox row-check-arsip" onchange="updateBulkToolbar('row-check-arsip','checkAllArsip','bulkToolbarArsip','bulkCountArsip')"></td>
                        <td><strong><?= $arsip['nama_lengkap'] ?></strong><br><span style="font-size:0.85rem;color:#aaa;"><?= $tampil_email_a ?></span></td>
                        <td><?= $arsip['no_wa'] ?></td>
                        <td><?= $arsip['tgl_berakhir'] ? date('d M Y',strtotime($arsip['tgl_berakhir'])) : '-' ?></td>
                        <td><span class="badge b-gray"><?= ucfirst($arsip['status'] ?? 'Tidak Ada') ?></span></td>
                        <td style="min-width:150px;">
                            <button class="btn-action btn-acc" onclick="konfirmasiPulihkan('<?= $arsip['id_user'] ?>','<?= addslashes($arsip['nama_lengkap']) ?>')">Pulihkan</button>
                            <button class="btn-action btn-rej" onclick="konfirmasiHapus('<?= $arsip['id_user'] ?>','<?= addslashes($arsip['nama_lengkap']) ?>')">Hapus</button>
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
            <h3>Kelola Banner Pengumuman</h3>
            <form onsubmit="simpanBanner(event)">
                <div class="grid-2">
                    <div class="form-group"><label>Status Tampil</label>
                        <select id="set_banner_status" class="form-control">
                            <option value="aktif" <?= ($web['pengumuman_aktif']??'')==='aktif'?'selected':'' ?>>Tampilkan (Aktif)</option>
                            <option value="nonaktif" <?= ($web['pengumuman_aktif']??'')==='nonaktif'?'selected':'' ?>>Sembunyikan</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Teks Pengumuman</label><textarea id="set_banner_teks" class="form-control" rows="2" required><?= $web['teks_pengumuman']??'' ?></textarea></div>
                </div>
                <button type="submit" class="btn-submit" style="width:auto;">Simpan Pengumuman</button>
            </form>
        </div>
        <div class="grid-2">
            <div style="display:flex;flex-direction:column;gap:30px;">
                <div class="content-card" style="margin-bottom:0;">
                    <h3>Edit Harga Membership & Senam</h3>
                    <form onsubmit="simpanHarga(event)">
                        <div class="form-group"><label>1x Visit Gym (Harian)</label><input type="text" id="set_harga_harian" class="form-control" value="<?= $web['harga_harian']??'' ?>" required></div>
                        <div class="form-group"><label>Gym Bulanan (Mulai Dari)</label><input type="text" id="set_harga_bulanan" class="form-control" value="<?= $web['harga_bulanan']??'' ?>" required></div>
                        <div class="form-group"><label>Kelas Senam (Per Datang)</label><input type="text" id="set_harga_senam" class="form-control" value="<?= $harga_senam ?>" required></div>
                        <button type="submit" class="btn-submit">Simpan Harga</button>
                    </form>
                </div>
                <div class="content-card" style="margin-bottom:0;">
                    <h3>Edit Kontak & Lokasi</h3>
                    <form onsubmit="simpanKontak(event)">
                        <div class="form-group"><label>No. WA CS Gym</label><input type="text" id="set_wa" class="form-control" value="<?= $web['wa_cs']??'' ?>" required></div>
                        <div class="form-group"><label>Link Instagram</label><input type="text" id="set_ig" class="form-control" value="<?= $web['ig']??'' ?>" required></div>
                        <button type="submit" class="btn-submit">Simpan Info Kontak</button>
                    </form>
                </div>
            </div>
            <div style="display:flex;flex-direction:column;gap:30px;">
                <div class="content-card" style="margin-bottom:0;">
                    <h3>Edit Jam Operasional Gym</h3>
                    <form onsubmit="simpanJam(event)">
                        <?php
                        $sesiGym = [
                            'sjPagi'  => ['hari'=>'Senin - Jumat','sesi'=>'Sesi Pagi',        'libur'=>$sjPagiL, 'buka'=>$sjPagiB, 'tutup'=>$sjPagiT],
                            'sjSiang' => ['hari'=>null,           'sesi'=>'Sesi Siang/Malam', 'libur'=>$sjSiangL,'buka'=>$sjSiangB,'tutup'=>$sjSiangT],
                            'sbPagi'  => ['hari'=>'Sabtu',        'sesi'=>'Sesi Pagi',        'libur'=>$sbPagiL, 'buka'=>$sbPagiB, 'tutup'=>$sbPagiT],
                            'sbSiang' => ['hari'=>null,           'sesi'=>'Sesi Siang/Malam', 'libur'=>$sbSiangL,'buka'=>$sbSiangB,'tutup'=>$sbSiangT],
                            'mgPagi'  => ['hari'=>'Minggu',       'sesi'=>'Sesi Pagi',        'libur'=>$mgPagiL, 'buka'=>$mgPagiB, 'tutup'=>$mgPagiT],
                            'mgSiang' => ['hari'=>null,           'sesi'=>'Sesi Siang/Malam', 'libur'=>$mgSiangL,'buka'=>$mgSiangB,'tutup'=>$mgSiangT],
                        ];
                        $open_card = false;
                        foreach($sesiGym as $key => $s):
                            if($s['hari']!==null): if($open_card) echo '</div></div>'; $open_card=true; ?>
                        <div class="jam-card"><label class="hari"><?= $s['hari'] ?></label><div class="grid-2">
                        <?php endif; ?>
                            <div class="form-group">
                                <label style="display:flex;justify-content:space-between;"><span><?= $s['sesi'] ?></span><span style="color:#ff4d4d;font-size:0.75rem;"><input type="checkbox" id="cb_<?= $key ?>" onchange="toggleLibur(this,'<?= $key ?>')" <?= $s['libur']?'checked':'' ?>> Libur</span></label>
                                <div style="display:flex;align-items:center;gap:8px;" id="<?= $key ?>">
                                    <input type="time" id="v_<?= $key ?>_b" class="form-control" value="<?= $s['buka'] ?>" <?= $s['libur']?'disabled':'required' ?>>
                                    <span style="color:#888;">-</span>
                                    <input type="time" id="v_<?= $key ?>_t" class="form-control" value="<?= $s['tutup'] ?>" <?= $s['libur']?'disabled':'required' ?>>
                                </div>
                            </div>
                        <?php endforeach; if($open_card) echo '</div></div>'; ?>
                        <button type="submit" class="btn-submit">Simpan Jadwal Operasional Gym</button>
                    </form>
                </div>
                <div class="content-card" style="margin-bottom:0;border-top-color:var(--accent-gold);">
                    <h3 style="color:var(--text-light);">Edit Jadwal Kelas Senam</h3>
                    <form onsubmit="simpanJadwalSenam(event)">
                        <?php $hari_senam=['sr'=>'Senin & Rabu','sk'=>'Selasa & Kamis','sb'=>'Sabtu','mg'=>'Minggu'];
                        foreach($hari_senam as $key=>$label): $l=$js[$key]['libur']??false; ?>
                        <div class="jam-card">
                            <label class="hari"><?= $label ?></label>
                            <div class="grid-2">
                                <div class="form-group">
                                    <label style="display:flex;justify-content:space-between;"><span>Jam Kelas</span><span style="color:#ff4d4d;font-size:0.75rem;"><input type="checkbox" id="libur_<?= $key ?>" onchange="toggleLibur(this,'js_<?= $key ?>')" <?= $l?'checked':'' ?>> Libur</span></label>
                                    <div style="display:flex;align-items:center;gap:8px;" id="js_<?= $key ?>">
                                        <input type="time" id="buka_<?= $key ?>" class="form-control" value="<?= $js[$key]['buka']??'' ?>" <?= $l?'disabled':'required' ?>>
                                        <span style="color:#888;">-</span>
                                        <input type="time" id="tutup_<?= $key ?>" class="form-control" value="<?= $js[$key]['tutup']??'' ?>" <?= $l?'disabled':'required' ?>>
                                    </div>
                                </div>
                                <div class="form-group"><label>Nama Kelas / Instruktur</label><input type="text" id="ket_<?= $key ?>" class="form-control" value="<?= htmlspecialchars($js[$key]['ket']??'') ?>" placeholder="Contoh: Zumba / BL+"></div>
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
                <div class="form-group"><label>Judul Media</label><input type="text" id="judul_media" class="form-control" required placeholder="Masukkan judul..."></div>
                <div class="form-group"><label>Caption / Penjelasan (Opsional)</label><textarea id="caption_media" class="form-control" rows="3" placeholder="Tuliskan fungsi alat..."></textarea></div>
                <div style="display:flex;gap:15px;margin-bottom:15px;flex-wrap:wrap;">
                    <div class="form-group" style="flex:1;min-width:200px;"><label>Kategori</label>
                        <select id="kategori_media" class="form-control" required>
                            <option value="alat">Fasilitas & Alat Gym</option><option value="upper">Tutorial Upper Body</option><option value="lower">Tutorial Lower Body</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex:1;min-width:200px;"><label>Tipe Media</label>
                        <select id="tipe_media" class="form-control" required onchange="sesuaikanInputFile()">
                            <option value="foto">Foto (JPG/PNG)</option><option value="video">Video (MP4)</option>
                        </select>
                    </div>
                </div>
                <div class="form-group"><label>Pilih File (Max 10MB)</label><input type="file" id="file_media" class="form-control" accept="image/jpeg,image/png,image/webp" required style="padding:9px 15px;"></div>
                <button type="submit" id="btnUpload" class="btn-submit">Upload Media Sekarang</button>
            </form>
        </div>
        <div class="content-card">
            <h3>Daftar Media Terupload</h3>
            <p style="color:#888;font-size:0.85rem;margin-bottom:15px;">Kelola media yang tampil di halaman utama dan menu Galeri.</p>
            <div style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;">
                <input type="text" id="searchGaleri" placeholder="Cari judul media..." class="form-control" onkeyup="filterGaleri()" style="max-width:300px;margin-bottom:0;">
                <select id="filterKategoriGaleri" class="form-control" onchange="filterGaleri()" style="max-width:200px;margin-bottom:0;cursor:pointer;">
                    <option value="">Semua Kategori</option><option value="alat">Fasilitas & Alat Gym</option><option value="upper">Tutorial Upper Body</option><option value="lower">Tutorial Lower Body</option>
                </select>
            </div>
            <div class="media-grid" id="containerGaleri">
                <?php
                $q_g = mysqli_query($koneksi, "SELECT * FROM galeri_gym ORDER BY id_media DESC");
                if(mysqli_num_rows($q_g)==0) echo "<div class='no-media-msg' style='color:#666;'>Belum ada media terupload.</div>";
                while($mg = mysqli_fetch_assoc($q_g)):
                ?>
                <div class="media-item" data-judul="<?= htmlspecialchars(strtolower($mg['judul'])) ?>" data-kat="<?= $mg['kategori'] ?>">
                    <?php if($mg['tipe_media']=='video'): ?>
                        <video src="<?= $mg['file_path'] ?>#t=0.1" preload="metadata" muted></video>
                    <?php else: ?>
                        <img src="<?= $mg['file_path'] ?>" loading="lazy" alt="<?= htmlspecialchars($mg['judul']) ?>">
                    <?php endif; ?>
                    <div class="media-item-info">
                        <p title="<?= htmlspecialchars($mg['judul']) ?>"><?= htmlspecialchars($mg['judul']) ?></p>
                        <span><?= strtoupper($mg['kategori']) ?></span>
                        <div style="display:flex;gap:5px;">
                            <button type="button" class="btn-action btn-view" style="flex:1;margin:0;text-align:center;" onclick="bukaEditGaleri(<?= $mg['id_media'] ?>, '<?= htmlspecialchars(addslashes($mg['judul'])) ?>', '<?= htmlspecialchars(addslashes($mg['caption'])) ?>', '<?= $mg['kategori'] ?>')">Edit</button>
                            <button type="button" class="btn-action btn-rej" style="flex:1;margin:0;text-align:center;" onclick="hapusGaleri(<?= $mg['id_media'] ?>)">Hapus</button>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div> 

<div id="modalBukti" class="modal-overlay">
    <div class="modal-box">
        <button type="button" class="close-modal" onclick="tutupModal('modalBukti')">&times;</button>
        <h3 style="color:var(--accent-gold);margin-bottom:15px;border-bottom:1px dashed #333;padding-bottom:10px;">Bukti Pembayaran</h3>
        <img id="imgBukti" src="" style="width:100%;border-radius:8px;border:1px solid #333;margin-top:10px;" alt="Bukti Bayar">
    </div>
</div>

<div id="modalTambahMember" class="modal-overlay">
    <div class="modal-box" style="max-width:600px;">
        <button type="button" class="close-modal" onclick="tutupModal('modalTambahMember')">&times;</button>
        <h3 style="color:var(--accent-gold);margin-bottom:15px;border-bottom:1px dashed #333;padding-bottom:10px;">Pendaftaran Member</h3>
        <form onsubmit="simpanTambahMember(event)" id="formTambahMember">
            <input type="hidden" name="action" value="tambah_member">
            
            <div class="inner-tab-bar">
                <button type="button" class="inner-tab-btn active" onclick="switchInnerTab('baru', this)">Member Baru</button>
                <button type="button" class="inner-tab-btn" onclick="switchInnerTab('perpanjang', this)">Perpanjang / Member Lama</button>
            </div>
            
            <input type="hidden" id="jenis_daftar" name="jenis" value="baru">
            <input type="hidden" id="id_member_lama" name="id_member_lama" value="0">

            <div id="areaCariLama" style="display:none;margin-bottom:15px;" class="autocomplete-box">
                <label style="color:#888;font-size:0.85rem;font-weight:600;display:block;margin-bottom:8px;">Cari Member (Ketik Nama / No WA)</label>
                <input type="text" id="cari_lama" class="form-control" placeholder="Mulai mengetik..." onkeyup="cariMemberLama()">
                <div id="dropdownLama" class="autocomplete-dropdown"></div>
            </div>

            <div class="grid-2">
                <div class="form-group"><label>Nama Lengkap</label><input type="text" name="nama" id="tm_nama" class="form-control" required></div>
                <div class="form-group"><label>No. WhatsApp</label><input type="text" name="wa" id="tm_wa" class="form-control" required></div>
            </div>
            
            <div id="areaAkun" class="form-group">
                <label>Beri Akses Akun? (Untuk Login & Chatbot)</label>
                <select name="buat_akun" id="tm_buat_akun" class="form-control" onchange="toggleAkunFields()">
                    <option value="tidak">Tidak Perlu</option>
                    <option value="ya">Ya, Buatkan Akun</option>
                </select>
            </div>
            
            <div id="areaEmailPass" class="grid-2" style="display:none;">
                <div class="form-group"><label>Email Pengguna</label><input type="email" name="email" id="tm_email" class="form-control"></div>
                <div class="form-group"><label>Password Akun</label><input type="password" name="pass" id="tm_pass" class="form-control"></div>
            </div>

            <div class="grid-2">
                <div class="form-group"><label>Durasi Paket</label>
                    <select name="paket" class="form-control" required>
                        <option value="1">1 Bulan (Rp 175.000)</option>
                        <option value="2">2 Bulan (Rp 350.000)</option>
                        <option value="3">3 Bulan (Rp 525.000)</option>
                    </select>
                </div>
                <div class="form-group"><label>Tanggal Mulai Berjalan</label><input type="date" id="tm_tgl" name="tgl" class="form-control" required value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>"></div>
            </div>

            <button type="submit" class="btn-submit">Simpan & Aktifkan Member</button>
        </form>
    </div>
</div>

<div id="modalEditMember" class="modal-overlay">
    <div class="modal-box">
        <button type="button" class="close-modal" onclick="tutupModal('modalEditMember')">&times;</button>
        <h3 style="color:var(--accent-gold);margin-bottom:15px;border-bottom:1px dashed #333;padding-bottom:10px;">Edit Data Member</h3>
        <form onsubmit="simpanEditMember(event)" id="formEditMember">
            <input type="hidden" name="action" value="edit_member">
            <input type="hidden" name="id_user" id="em_id">
            <div class="form-group"><label>Nama Lengkap</label><input type="text" name="nama" id="em_nama" class="form-control" required></div>
            <div class="form-group"><label>No. WhatsApp</label><input type="text" name="wa" id="em_wa" class="form-control" required></div>
            <div class="form-group"><label>Alamat Email (Kosongkan jika tidak ada)</label><input type="email" name="email" id="em_email" class="form-control"></div>
            <div class="form-group"><label>Ubah Password (Kosongkan jika tetap)</label><input type="password" name="pass" class="form-control" placeholder="***"></div>
            <button type="submit" class="btn-submit">Simpan Perubahan</button>
        </form>
    </div>
</div>

<div id="modalEditGaleri" class="modal-overlay">
    <div class="modal-box">
        <button type="button" class="close-modal" onclick="tutupModal('modalEditGaleri')">&times;</button>
        <h3 style="color:var(--accent-gold);margin-bottom:15px;border-bottom:1px dashed #333;padding-bottom:10px;">Edit Info Media</h3>
        <form id="formEditGaleri" onsubmit="simpanEditGaleri(event)" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit_galeri">
            <input type="hidden" name="id_media" id="eg_id">
            <div class="form-group"><label>Judul Media</label><input type="text" name="judul_media" id="eg_judul" class="form-control" required></div>
            <div class="form-group"><label>Caption / Keterangan</label><textarea name="caption_media" id="eg_caption" class="form-control" rows="3"></textarea></div>
            <div class="form-group"><label>Kategori</label>
                <select name="kategori_media" id="eg_kategori" class="form-control" required>
                    <option value="alat">Fasilitas & Alat Gym</option>
                    <option value="upper">Tutorial Upper Body</option>
                    <option value="lower">Tutorial Lower Body</option>
                </select>
            </div>
            <div class="form-group"><label>Ganti File? (Kosongkan jika tidak diganti)</label><input type="file" name="file_media_edit" class="form-control" style="padding:9px 15px;"></div>
            <button type="submit" class="btn-submit">Update Data Media</button>
        </form>
    </div>
</div>

<div id="modalCetak" class="modal-overlay">
    <div class="modal-box">
        <button type="button" class="close-modal" onclick="tutupModal('modalCetak')">&times;</button>
        <h3 style="color:var(--accent-gold);margin-bottom:15px;border-bottom:1px dashed #333;padding-bottom:10px;">Cetak Laporan Pendapatan</h3>
        <form action="cetak_laporan.php" method="GET" target="_blank">
            
            <div class="form-group">
                <label>Jenis Laporan</label>
                <select name="jenis" id="jenis_laporan" class="form-control" onchange="toggleBulanCetak()">
                    <option value="bulanan">Laporan Bulanan</option>
                    <option value="tahunan">Laporan Tahunan</option>
                </select>
            </div>

            <div class="grid-2" id="areaWaktuCetak" style="margin-bottom: 0;">
                <div class="form-group" id="grupBulan">
                    <label>Pilih Bulan</label>
                    <select name="bulan" id="bulan_cetak" class="form-control">
                        <option value="01">Januari</option>
                        <option value="02">Februari</option>
                        <option value="03">Maret</option>
                        <option value="04">April</option>
                        <option value="05">Mei</option>
                        <option value="06">Juni</option>
                        <option value="07">Juli</option>
                        <option value="08">Agustus</option>
                        <option value="09">September</option>
                        <option value="10">Oktober</option>
                        <option value="11">November</option>
                        <option value="12">Desember</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Pilih Tahun</label>
                    <select name="tahun" class="form-control">
                        <?php
                        $tahun_sekarang = date('Y');
                        // Looping tahun dari tahun ini mundur ke 2024
                        for($i = $tahun_sekarang; $i >= 2024; $i--) {
                            echo "<option value='$i'>$i</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <p style="font-size:0.8rem;color:#888;margin-bottom:15px;">Laporan akan dicetak dalam format PDF menggunakan fitur Print bawaan browser.</p>
            <button type="submit" class="btn-submit" style="background:var(--success-green);color:white;">Buat & Cetak Laporan</button>
        </form>
    </div>
</div>

<div id="modalRiwayatMember" class="modal-overlay">
    <div class="modal-box" style="max-width: 750px;">
        <button type="button" class="close-modal" onclick="tutupModal('modalRiwayatMember')">&times;</button>
        <h3 style="color:var(--accent-gold);margin-bottom:15px;border-bottom:1px dashed #333;padding-bottom:10px;">
            Riwayat Transaksi: <span id="namaRiwayatMember" style="color:var(--text-light);"></span>
        </h3>
        <p style="color:#888; font-size:0.85rem; margin-bottom:15px;">Daftar lengkap seluruh paket dan perpanjangan yang pernah dilakukan oleh member ini.</p>
        
        <div class="table-container" style="max-height: 400px; overflow-y: auto;">
            <table style="width: 100%; font-size: 0.85rem;">
                <thead>
                    <tr>
                        <th>Tgl Transaksi</th>
                        <th>Jenis</th>
                        <th>Periode Berlaku</th>
                        <th>Paket</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="bodyRiwayatMember">
                    </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // ==========================================
    // LOGIKA UI: TABS & SIDEBAR
    // ==========================================
    function switchTab(tabId, elem) {
        document.querySelectorAll('.tab-section').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.menu-item').forEach(m => m.classList.remove('active'));
        document.getElementById(tabId).classList.add('active');
        if(elem) elem.classList.add('active');
        
        let title = elem ? elem.innerText.replace(/[\n\r0-9]/g, '').trim() : 'Dasbor Utama';
        document.getElementById('pageTitle').innerText = title;
        
        if(window.innerWidth <= 1024) toggleSidebar(); 
    }

    function switchInnerTab(jenis, elem) {
        document.querySelectorAll('.inner-tab-btn').forEach(btn => btn.classList.remove('active'));
        elem.classList.add('active');
        document.getElementById('jenis_daftar').value = jenis;
        
        let tglInput = document.getElementById('tm_tgl');
        let d = new Date();
        let tglSekarang = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');

        if(jenis === 'perpanjang') {
            document.getElementById('areaCariLama').style.display = 'block';
            document.getElementById('areaAkun').style.display = 'none';
            document.getElementById('areaEmailPass').style.display = 'none';
        } else {
            document.getElementById('areaCariLama').style.display = 'none';
            document.getElementById('areaAkun').style.display = 'block';
            toggleAkunFields();
            document.getElementById('id_member_lama').value = '0';
            document.getElementById('tm_nama').value = '';
            document.getElementById('tm_wa').value = '';
            document.getElementById('cari_lama').value = '';
            
            // Reset tanggal ke hari ini
            tglInput.min = tglSekarang;
            tglInput.value = tglSekarang;
        }
    }

    function toggleAkunFields() {
        const val = document.getElementById('tm_buat_akun').value;
        const area = document.getElementById('areaEmailPass');
        if(val === 'ya') {
            area.style.display = 'grid';
            document.getElementById('tm_email').required = true;
            document.getElementById('tm_pass').required = true;
        } else {
            area.style.display = 'none';
            document.getElementById('tm_email').required = false;
            document.getElementById('tm_pass').required = false;
        }
    }

    const sidebar = document.getElementById('mainSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    
    function toggleSidebar() {
        sidebar.classList.toggle('sidebar-open');
        backdrop.classList.toggle('open');
    }
    
    document.getElementById('sidebarToggle').addEventListener('click', toggleSidebar);
    backdrop.addEventListener('click', toggleSidebar);

    // ==========================================
    // LOGIKA MODAL & TOAST
    // ==========================================
    function bukaModal(id) { document.getElementById(id).style.display = 'flex'; }
    function tutupModal(id) { document.getElementById(id).style.display = 'none'; }
    
    function lihatBuktiTransfer(url) {
        document.getElementById('imgBukti').src = url;
        bukaModal('modalBukti');
    }

    function bukaEditMember(id, nama, email, wa) {
        document.getElementById('em_id').value = id;
        document.getElementById('em_nama').value = nama;
        document.getElementById('em_wa').value = wa;
        document.getElementById('em_email').value = (email.includes('@noemail.local')) ? '' : email;
        bukaModal('modalEditMember');
    }

    function bukaEditGaleri(id, judul, caption, kategori) {
        document.getElementById('eg_id').value = id;
        document.getElementById('eg_judul').value = judul;
        document.getElementById('eg_caption').value = caption;
        document.getElementById('eg_kategori').value = kategori;
        bukaModal('modalEditGaleri');
    }

    function showToast(msg, type='success') {
        const toast = document.getElementById('toast');
        toast.innerText = msg;
        toast.className = 'show toast-' + type;
        setTimeout(() => { toast.className = toast.className.replace('show', ''); }, 3000);
    }

    function bukaModalCetak() {
        bukaModal('modalCetak');
    }

    // ==========================================
    // REQUEST AJAX (FETCH API)
    // ==========================================
    async function sendData(data) {
        try {
            const fd = new FormData();
            for(let key in data) fd.append(key, data[key]);
            const res = await fetch('', { method: 'POST', body: fd });
            return await res.json();
        } catch (e) { console.error(e); return {status: 'error', message: 'Gangguan server/koneksi.'}; }
    }

    async function sendForm(formId) {
        try {
            const form = document.getElementById(formId);
            const fd = new FormData(form);
            const res = await fetch('', { method: 'POST', body: fd });
            return await res.json();
        } catch (e) { console.error(e); return {status: 'error', message: 'Gangguan server/koneksi.'}; }
    }

    // VERIFIKASI AKSI
    async function konfirmasiTerima(id, nama) {
        if(!confirm(`Setujui pendaftaran member ${nama}?`)) return;
        let r = await sendData({action:'terima', id_membership:id});
        if(r.status==='success') { showToast('Member diterima!'); location.reload(); }
    }

    async function konfirmasiTolak(id, nama) {
        let alasan = prompt(`Tuliskan alasan penolakan untuk ${nama}:`);
        if(alasan===null) return;
        let r = await sendData({action:'tolak', id_membership:id, alasan:alasan});
        if(r.status==='success') { showToast('Pendaftaran ditolak'); location.reload(); }
    }

    // MEMBER & ARSIP AKSI
    async function konfirmasiArsip(id, nama) {
        if(!confirm(`Pindahkan ${nama} ke arsip?`)) return;
        let r = await sendData({action:'arsip', id_user:id});
        if(r.status==='success') { showToast('Data diarsipkan'); location.reload(); }
    }

    async function konfirmasiHapus(id, nama) {
        if(!confirm(`HAPUS PERMANEN akun ${nama}? Aksi ini tidak dapat dibatalkan.`)) return;
        let r = await sendData({action:'hapus', id_user:id});
        if(r.status==='success') { showToast('Data dihapus permanen'); location.reload(); }
    }

    async function konfirmasiPulihkan(id, nama) {
        if(!confirm(`Kembalikan data ${nama} ke daftar member?`)) return;
        let r = await sendData({action:'pulihkan', id_user:id});
        if(r.status==='success') { showToast('Akun berhasil dipulihkan'); location.reload(); }
    }

    // PENGATURAN KONTEN AKSI
    async function simpanBanner(e) {
        e.preventDefault();
        let r = await sendData({action:'update_banner', status:document.getElementById('set_banner_status').value, teks:document.getElementById('set_banner_teks').value});
        if(r.status==='success') showToast('Pengumuman disimpan!');
    }

    async function simpanHarga(e) {
        e.preventDefault();
        let r = await sendData({action:'update_harga', harian:document.getElementById('set_harga_harian').value, bulanan:document.getElementById('set_harga_bulanan').value, senam:document.getElementById('set_harga_senam').value});
        if(r.status==='success') showToast('Tarif berhasil diperbarui!');
    }

    async function simpanKontak(e) {
        e.preventDefault();
        let r = await sendData({action:'update_kontak', wa:document.getElementById('set_wa').value, ig:document.getElementById('set_ig').value});
        if(r.status==='success') showToast('Info kontak disimpan!');
    }

    function toggleLibur(cb, key) {
        document.getElementById(key).querySelectorAll('input[type="time"]').forEach(el => el.disabled = cb.checked);
    }

    async function simpanJam(e) {
        e.preventDefault();
        let data = {};
        ['sjPagi','sjSiang','sbPagi','sbSiang','mgPagi','mgSiang'].forEach(k => {
            data[k] = {
                libur: document.getElementById('cb_'+k).checked,
                buka: document.getElementById('v_'+k+'_b').value,
                tutup: document.getElementById('v_'+k+'_t').value
            };
        });
        let r = await sendData({action:'update_jam', data_jam: JSON.stringify(data)});
        if(r.status==='success') showToast('Jam operasional gym diperbarui!');
    }

    async function simpanJadwalSenam(e) {
        e.preventDefault();
        let data = {};
        ['sr','sk','sb','mg'].forEach(k => {
            data[k] = {
                libur: document.getElementById('libur_'+k).checked,
                buka: document.getElementById('buka_'+k).value,
                tutup: document.getElementById('tutup_'+k).value,
                ket: document.getElementById('ket_'+k).value
            };
        });
        let r = await sendData({action:'update_jadwal_senam', data_js: JSON.stringify(data)});
        if(r.status==='success') showToast('Jadwal kelas senam diperbarui!');
    }

    // GALERI AKSI
    function sesuaikanInputFile() {
        const t = document.getElementById('tipe_media').value;
        const i = document.getElementById('file_media');
        i.accept = (t === 'foto') ? 'image/jpeg,image/png,image/webp' : 'video/mp4,video/webm';
    }

    async function uploadMedia(e) {
        e.preventDefault();
        const btn = document.getElementById('btnUpload');
        btn.disabled = true; btn.innerText = 'Tunggu sebentar...';
        let r = await sendForm('formUploadGaleri');
        if(r.status==='success') { showToast(r.message); location.reload(); }
        else { showToast(r.message || 'Gagal Upload', 'error'); btn.disabled = false; btn.innerText = 'Upload Media Sekarang'; }
    }

    async function simpanEditGaleri(e) {
        e.preventDefault();
        let r = await sendForm('formEditGaleri');
        if(r.status==='success') { showToast(r.message || 'Tersimpan'); location.reload(); }
        else showToast(r.message || 'Gagal mengubah', 'error');
    }

    async function hapusGaleri(id) {
        if(!confirm('Yakin ingin menghapus media ini dari galeri publik?')) return;
        let r = await sendData({action:'hapus_galeri', id_media:id});
        if(r.status==='success') { showToast('Media terhapus'); location.reload(); }
    }

    // MEMBER KELOLA AKSI
    async function simpanTambahMember(e) {
        e.preventDefault();
        let r = await sendForm('formTambahMember');
        if(r.status==='success') { showToast('Pendaftaran Berhasil'); location.reload(); }
        else showToast(r.message || 'Gagal memproses', 'error');
    }

    async function simpanEditMember(e) {
        e.preventDefault();
        let r = await sendForm('formEditMember');
        if(r.status==='success') { showToast('Profil diperbarui'); location.reload(); }
        else showToast(r.message || 'Gagal update', 'error');
    }

    async function cariMemberLama() {
        const kw = document.getElementById('cari_lama').value;
        const box = document.getElementById('dropdownLama');
        if(kw.length < 3) { box.style.display = 'none'; return; }
        
        let r = await sendData({action:'cari_member_lama', keyword:kw});
        if(r.status==='success') {
            box.innerHTML = '';
            if(r.data.length === 0) {
                box.innerHTML = '<div style="padding:10px;color:#888;font-size:0.85rem;">Tidak ditemukan.</div>';
            } else {
                r.data.forEach(m => {
                    let div = document.createElement('div');
                    div.className = 'autocomplete-item';
                    div.innerHTML = `<div class="ac-name">${m.nama_lengkap}</div><div class="ac-sub">${m.no_wa} | Tgl Akhir: ${m.tgl_berakhir||'-'}</div>`;
                    div.onclick = () => {
                        document.getElementById('id_member_lama').value = m.id_user;
                        document.getElementById('tm_nama').value = m.nama_lengkap;
                        document.getElementById('tm_wa').value = m.no_wa;
                        document.getElementById('cari_lama').value = m.nama_lengkap;
                        box.style.display = 'none';
                        
                        // LOGIKA SET TANGGAL OTOMATIS
                        let tglInput = document.getElementById('tm_tgl');
                        if (m.tgl_berakhir) {
                            let tglAkhir = new Date(m.tgl_berakhir);
                            let hariIni = new Date();
                            hariIni.setHours(0,0,0,0);
                            
                            // Fungsi format tanggal lokal YYYY-MM-DD
                            const formatTgl = (date) => {
                                let y = date.getFullYear();
                                let month = String(date.getMonth() + 1).padStart(2, '0');
                                let day = String(date.getDate()).padStart(2, '0');
                                return `${y}-${month}-${day}`;
                            };

                            if (tglAkhir >= hariIni) {
                                // Jika status member masih aktif (tgl akhir >= hari ini)
                                // Set tanggal mulai persis di tanggal berakhir (Hari H)
                                let hariPas = new Date(tglAkhir);
                                
                                let tglPas = formatTgl(hariPas);
                                tglInput.min = tglPas;
                                tglInput.value = tglPas;
                                
                                showToast('Tgl disesuaikan otomatis menyambung masa aktif.');
                            } else {
                                // Jika sudah expired, set ke hari ini
                                let tglSekarang = formatTgl(new Date());
                                tglInput.min = tglSekarang;
                                tglInput.value = tglSekarang;
                            }
                        }
                    };
                    box.appendChild(div);
                });
            }
            box.style.display = 'block';
        }
    }

    document.addEventListener('click', function(e) {
        if(document.getElementById('dropdownLama') && !e.target.closest('#areaCariLama')) {
            document.getElementById('dropdownLama').style.display = 'none';
        }
    });

    // ==========================================
    // LOGIKA BULK ACTIONS
    // ==========================================
    function toggleCheckAll(tblId, rowClass, checkAllId, toolbarId, countId) {
        const checkAll = document.getElementById(checkAllId);
        document.querySelectorAll(`#${tblId} tbody tr:not(.no-data) .${rowClass}`).forEach(cb => {
            if(cb.offsetParent !== null) cb.checked = checkAll.checked; 
        });
        updateBulkToolbar(rowClass, checkAllId, toolbarId, countId);
    }

    function updateBulkToolbar(rowClass, checkAllId, toolbarId, countId) {
        const chk = document.querySelectorAll(`.${rowClass}:checked`);
        const bar = document.getElementById(toolbarId);
        if(chk.length > 0) {
            bar.classList.add('visible');
            document.getElementById(countId).innerText = chk.length + ' dipilih';
        } else {
            bar.classList.remove('visible');
            document.getElementById(checkAllId).checked = false;
        }
    }

    function getSelectedIds(rowClass) {
        let ids = [];
        document.querySelectorAll(`.${rowClass}:checked`).forEach(cb => {
            const tr = cb.closest('tr');
            if(tr && tr.dataset.uid) ids.push(tr.dataset.uid);
        });
        return ids.join(',');
    }

    function clearBulkMember() {
        document.querySelectorAll('.row-check-member').forEach(cb => cb.checked = false);
        document.getElementById('checkAllMember').checked = false;
        document.getElementById('bulkToolbarMember').classList.remove('visible');
    }
    
    async function bulkArsip() {
        const ids = getSelectedIds('row-check-member');
        if(ids && confirm('Pindahkan data ke arsip?')) {
            let r = await sendData({action:'bulk_arsip', ids:ids});
            if(r.status==='success') location.reload();
        }
    }
    
    async function bulkHapus() {
        const ids = getSelectedIds('row-check-member');
        if(ids && confirm('Hapus permanen data yang dipilih?')) {
            let r = await sendData({action:'bulk_hapus', ids:ids});
            if(r.status==='success') location.reload();
        }
    }

    function clearBulkArsip() {
        document.querySelectorAll('.row-check-arsip').forEach(cb => cb.checked = false);
        document.getElementById('checkAllArsip').checked = false;
        document.getElementById('bulkToolbarArsip').classList.remove('visible');
    }
    
    async function bulkPulihkan() {
        const ids = getSelectedIds('row-check-arsip');
        if(ids && confirm('Pulihkan data ke member aktif?')) {
            let r = await sendData({action:'bulk_pulihkan', ids:ids});
            if(r.status==='success') location.reload();
        }
    }
    
    async function bulkHapusArsip() {
        const ids = getSelectedIds('row-check-arsip');
        if(ids && confirm('Hapus permanen arsip yang dipilih?')) {
            let r = await sendData({action:'bulk_hapus', ids:ids});
            if(r.status==='success') location.reload();
        }
    }

    // ==========================================
    // CHART.JS & FUNGSI FILTER/SEARCH PENCARIAN
    // ==========================================
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('memberChart');
        if(ctx) {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Aktif', 'Pending', 'Kedaluwarsa'],
                    datasets: [{
                        data: [<?= $count_aktif ?>, <?= $count_pending ?>, <?= $count_expired ?>],
                        backgroundColor: ['#28a745', '#ffc107', '#8E1616'],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { color: '#ccc' } } },
                    cutout: '70%'
                }
            });
        }
    });

    function filterTable(inputId, selectId, tableId, filterIndex) {
        let input = document.getElementById(inputId).value.toLowerCase();
        let select = selectId ? document.getElementById(selectId).value.toLowerCase() : "";
        let table = document.getElementById(tableId);
        let tr = table.getElementsByTagName("tr");
        
        for (let i = 1; i < tr.length; i++) {
            if(tr[i].classList.contains('no-data')) continue;
            let tdText = tr[i].getElementsByTagName("td")[1]; 
            let tdStatus = filterIndex !== null ? tr[i].getElementsByTagName("td")[filterIndex] : null;
            
            if (tdText) {
                let textVal = tdText.textContent || tdText.innerText;
                let statusVal = tdStatus ? (tdStatus.textContent || tdStatus.innerText).toLowerCase() : "";
                
                let matchText = textVal.toLowerCase().indexOf(input) > -1;
                let matchStatus = select === "" || statusVal.indexOf(select) > -1;
                tr[i].style.display = (matchText && matchStatus) ? "" : "none";
            }
        }
    }

    function filterVerifikasi() { filterTable("searchVerifikasi", "filterVerifikasiJenis", "tabelVerifikasi", 2); }
    function filterMember() { filterTable("searchMember", "filterStatus", "tabelMember", 5); }
    function filterArsip() { filterTable("searchArsip", null, "tabelArsip", null); }
    
    function filterGaleri() {
        let input = document.getElementById("searchGaleri").value.toLowerCase();
        let select = document.getElementById("filterKategoriGaleri").value;
        let items = document.querySelectorAll(".media-item");
        
        items.forEach(item => {
            let matchText = item.dataset.judul.indexOf(input) > -1;
            let matchSelect = select === "" || item.dataset.kat === select;
            item.style.display = (matchText && matchSelect) ? "block" : "none";
        });
    }

    function toggleBulanCetak() {
        const jenis = document.getElementById('jenis_laporan').value;
        const grupBulan = document.getElementById('grupBulan');
        
        if (jenis === 'tahunan') {
            grupBulan.style.display = 'none';
        } else {
            grupBulan.style.display = 'block';
        }
    }

    // Set bulan saat ini sebagai default dropdown
    document.addEventListener("DOMContentLoaded", function() {
        const currentMonth = "<?= date('m') ?>";
        const selectBulan = document.getElementById('bulan_cetak');
        if(selectBulan) selectBulan.value = currentMonth;
    });

    // Tampilkan Riwayat Member Khusus
    async function lihatRiwayat(id, nama) {
        document.getElementById('namaRiwayatMember').innerText = nama;
        document.getElementById('bodyRiwayatMember').innerHTML = '<tr><td colspan="5" style="text-align:center; padding:20px;">Memuat riwayat transaksi...</td></tr>';
        bukaModal('modalRiwayatMember');

        let formData = new FormData();
        formData.append('action', 'riwayat_member');
        formData.append('id_user', id);

        try {
            let res = await fetch('', { method: 'POST', body: formData });
            let r = await res.json();
            
            let html = '';
            if (r.status === 'success' && r.data.length > 0) {
                r.data.forEach(m => {
                    let badgeColor = 'b-gray';
                    if(m.status === 'aktif') badgeColor = 'b-success';
                    else if(m.status === 'pending') badgeColor = 'b-warning';
                    else if(m.status === 'ditolak' || m.status === 'kedaluwarsa') badgeColor = 'b-danger';

                    html += `<tr>
                        <td>${m.tgl_buat_format}</td>
                        <td><strong style="text-transform:uppercase; color:var(--accent-gold);">${m.jenis_pengajuan}</strong></td>
                        <td>${m.tgl_mulai_format} <br><span style="color:#888;">s/d</span> ${m.tgl_akhir_format}</td>
                        <td>${m.paket_bulan} Bln</td>
                        <td><span class="badge ${badgeColor}">${m.status.toUpperCase()}</span></td>
                    </tr>`;
                });
            } else {
                html = '<tr><td colspan="5" style="text-align:center; padding:20px;">Tidak ada riwayat transaksi.</td></tr>';
            }
            document.getElementById('bodyRiwayatMember').innerHTML = html;
        } catch (e) {
            document.getElementById('bodyRiwayatMember').innerHTML = '<tr><td colspan="5" style="text-align:center; color:var(--primary-red); padding:20px;">Gagal mengambil data koneksi!</td></tr>';
        }
    }
</script>
</body>
</html>