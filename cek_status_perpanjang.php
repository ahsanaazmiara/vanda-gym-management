<?php
session_start();
require 'includes/koneksi.php';

// 1. PROTEKSI: Cek apakah user sudah login
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'member') {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// =========================================================
// BLOK PHP: HANDLING AJAX (CARI & BATALKAN TRANSAKSI)
// =========================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // --- AKSI 1: BATALKAN TRANSAKSI PENDING ---
    if ($_POST['action'] == 'batalkan_transaksi') {
        $id_trx = (int) $_POST['id_trx'];
        
        // Hapus data membership yang ID-nya cocok, milik user yang login, dan statusnya masih pending
        $q_batal = mysqli_query($koneksi, "DELETE FROM membership WHERE id_membership = $id_trx AND id_user = $id_user AND status = 'pending'");
        
        if ($q_batal && mysqli_affected_rows($koneksi) > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Transaksi berhasil dibatalkan.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal membatalkan. Status mungkin sudah berubah atau transaksi tidak ditemukan.']);
        }
        exit;
    }

    // --- AKSI 2: CEK TRANSAKSI MELALUI PENCARIAN EMAIL ---
    if ($_POST['action'] == 'cek_transaksi') {
        $email = mysqli_real_escape_string($koneksi, $_POST['email']);

        // Cari ID User dari Email tersebut
        $q_user = mysqli_query($koneksi, "SELECT id_user, nama_lengkap FROM users WHERE email = '$email' LIMIT 1");
        
        if (mysqli_num_rows($q_user) > 0) {
            $user_data = mysqli_fetch_assoc($q_user);
            $id_user_cari = $user_data['id_user'];
            $nama_lengkap_cari = $user_data['nama_lengkap'];

            // Ambil SEMUA Riwayat Transaksi untuk user ini (Max 10 Terakhir)
            $q_riwayat_cari = mysqli_query($koneksi, "
                SELECT * FROM membership 
                WHERE id_user = $id_user_cari 
                ORDER BY id_membership DESC LIMIT 10
            ");

            $riwayat_html = "";
            
            if (mysqli_num_rows($q_riwayat_cari) > 0) {
                $riwayat_html .= '<div class="table-container"><table class="table-riwayat">';
                $riwayat_html .= '<thead><tr><th>Tgl Pengajuan</th><th>Jenis</th><th>Paket</th><th>Status</th><th>E-Receipt</th></tr></thead><tbody>';
                
                while ($row = mysqli_fetch_assoc($q_riwayat_cari)) {
                    $tglBayar = date('d M Y', strtotime($row['created_at']));
                    $jenis = ucfirst($row['jenis_pengajuan']);
                    $paket = $row['paket_bulan'] . " Bulan";
                    $status = $row['status'];
                    $id_membership = $row['id_membership'];
                    
                    $badge = "";
                    $btnReceipt = "-";

                    // Format Detail Untuk Resi (Khusus Lunas/Aktif/Expired)
                    $harga = "Rp " . number_format($row['total_harga'], 0, ',', '.');
                    $tglMulai = date('d M Y', strtotime($row['tgl_mulai']));
                    $tglBerakhir = $row['tgl_berakhir'] ? date('d M Y', strtotime($row['tgl_berakhir'])) : '-';

                    $dataResi = htmlspecialchars(json_encode([
                        'email' => $email,
                        'tglBayar' => $tglBayar,
                        'paket' => $paket,
                        'tglMulai' => $tglMulai,
                        'tglBerakhir' => $tglBerakhir,
                        'harga' => $harga
                    ]), ENT_QUOTES, 'UTF-8');

                    if ($status == 'aktif') {
                        $badge = '<span style="color:var(--success-green); font-weight:bold;">Selesai (Lunas)</span>';
                        $btnReceipt = "<button type='button' class='btn-small-gold' onclick='bukaBukti($dataResi)'>Lihat</button>";
                    } else if ($status == 'pending') {
                        $badge = '<span style="color:var(--warning-yellow); font-weight:bold;">Menunggu Verifikasi</span>';
                        // Tombol Batalkan Transaksi di render AJAX
                        if ($id_user_cari == $_SESSION['id_user']) { // Pastikan hanya bisa batal jika emailnya milik dia sendiri
                            $btnReceipt = "<button type='button' class='btn-small-red' onclick='batalkanTransaksi($id_membership)'>Batalkan</button>";
                        }
                    } else if ($status == 'kadaluarsa') {
                        $badge = '<span style="color:var(--primary-red);">Kadaluwarsa</span>';
                        $btnReceipt = "<button type='button' class='btn-small-gold' onclick='bukaBukti($dataResi)'>Lihat</button>";
                    } else {
                        $badge = '<span style="color:#aaa;">Ditolak</span>';
                        $btnReceipt = "<span style='font-size:0.75rem; color:#888;' title='".htmlspecialchars($row['alasan_tolak'])."'>Ditolak Admin</span>";
                    }

                    $riwayat_html .= "<tr>
                                        <td>{$tglBayar}</td>
                                        <td>{$jenis}</td>
                                        <td>{$paket}</td>
                                        <td>{$badge}</td>
                                        <td>{$btnReceipt}</td>
                                      </tr>";
                }
                $riwayat_html .= '</tbody></table></div>';
            } else {
                $riwayat_html = "<div class='empty-state'>Belum ada riwayat transaksi.</div>";
            }

            echo json_encode([
                'status_code' => 'ditemukan',
                'email' => $email,
                'nama' => $nama_lengkap_cari,
                'html_riwayat' => $riwayat_html
            ]);

        } else {
            echo json_encode(['status_code' => 'tidak_ditemukan']);
        }
        exit;
    }
}

// 2. AMBIL DATA USER DEFAULT (YANG SEDANG LOGIN)
$q_user = mysqli_query($koneksi, "SELECT email, nama_lengkap FROM users WHERE id_user = $id_user LIMIT 1");
$user_data = mysqli_fetch_assoc($q_user);
$email = $user_data['email'];
$nama_lengkap = $user_data['nama_lengkap'];

// 3. AMBIL RIWAYAT TRANSAKSI DEFAULT (Maksimal 10 Terakhir)
$q_riwayat = mysqli_query($koneksi, "
    SELECT * FROM membership 
    WHERE id_user = $id_user 
    ORDER BY id_membership DESC LIMIT 10
");

// Ambil Pengaturan Web untuk nomor WA
$q_pengaturan = mysqli_query($koneksi, "SELECT wa_cs FROM pengaturan_web WHERE id=1");
$web_data = mysqli_fetch_assoc($q_pengaturan);
$wa_db = $web_data['wa_cs'] ?? '082148556601';
$wa_link = "62" . substr(preg_replace('/[^0-9]/', '', $wa_db), 1);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status & Riwayat - Vanda Gym Classic</title>
    <style>
        :root { 
            --bg-dark: #000000; --primary-red: #dc3545; --accent-gold: #E8C999; 
            --text-light: #F8EEDF; --input-bg: #111111; --success-green: #28a745;
            --warning-yellow: #ffc107;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--bg-dark); color: var(--text-light); display: flex; justify-content: center; align-items: flex-start; min-height: 100vh; padding: 40px 20px; }
        
        .status-container { background-color: #0a0a0a; border: 1px solid #333; border-top: 4px solid var(--accent-gold); border-radius: 8px; padding: 30px; width: 100%; max-width: 800px; box-shadow: 0 10px 30px rgba(0,0,0,0.8); position: relative; margin-bottom: 80px; }
        
        .nav-top { margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; }
        .btn-back-square { width: 44px; height: 44px; background-color: #1a1a1a; border: 1px solid #333; color: var(--accent-gold); border-radius: 4px; display: flex; align-items: center; justify-content: center; text-decoration: none; font-weight: bold; font-size: 1.2rem; transition: 0.3s; }
        .btn-back-square:hover { background-color: var(--primary-red); color: white; border-color: var(--primary-red); }
        
        .form-header { text-align: center; margin-bottom: 25px; border-bottom: 1px solid #222; padding-bottom: 20px;}
        .form-header h2 { color: var(--text-light); text-transform: uppercase; letter-spacing: 1px; font-size: 1.2rem; margin-bottom: 5px;}
        .form-header p { color: #888; font-size: 0.7rem; }
        
        /* TABEL RIWAYAT */
        .table-container { overflow-x: auto; margin-top: 15px; border-radius: 4px; border: 1px solid #222; }
        .table-riwayat { width: 100%; border-collapse: collapse; font-size: 0.65rem; text-align: left; }
        .table-riwayat th { background-color: #151515; color: var(--accent-gold); padding: 12px; border-bottom: 1px solid #333; white-space: nowrap; text-transform: uppercase; }
        .table-riwayat td { padding: 12px; border-bottom: 1px solid #222; color: #ccc; white-space: nowrap; vertical-align: middle; }
        .table-riwayat tr:last-child td { border-bottom: none; }
        .table-riwayat tr:hover { background-color: #111; }

        .btn-small-gold { background: #151515; border: 1px solid var(--accent-gold); color: var(--accent-gold); font-size: 0.65rem; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: bold; transition: 0.3s;}
        .btn-small-gold:hover { background: var(--accent-gold); color: #000; }

        /* TOMBOL BATALKAN (MERAH) */
        .btn-small-red { background: #151515; border: 1px solid var(--primary-red); color: var(--primary-red); font-size: 0.65rem; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: bold; transition: 0.3s;}
        .btn-small-red:hover { background: var(--primary-red); color: #fff; }

        .empty-state { text-align: center; padding: 30px; color: #666; font-style: italic; background: #111; border: 1px dashed #333; border-radius: 4px; }

        /* MODAL E-RECEIPT */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); display: none; justify-content: center; align-items: center; z-index: 2000; padding: 20px; overflow-y: auto; }
        .receipt-card { background: #fff; color: #000; width: 100%; max-width: 350px; padding: 25px 20px; border-radius: 8px; font-family: 'Courier New', Courier, monospace; position: relative; box-shadow: 0 0 20px rgba(232, 201, 153, 0.2); }
        .close-modal { position: absolute; top: -15px; right: -15px; background: var(--primary-red); color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; font-weight: bold; font-family: sans-serif; box-shadow: 0 2px 5px rgba(0,0,0,0.5); }
        .receipt-header { text-align: center; border-bottom: 2px dashed #000; padding-bottom: 15px; margin-bottom: 15px; }
        .receipt-header h3 { margin: 0; font-size: 1rem; font-family: sans-serif; font-weight: 900;}
        .receipt-header p { margin: 5px 0 0; font-size: 0.65rem; color: #555;}
        .receipt-body p { margin: 5px 0; font-size: 0.65rem; display: flex; justify-content: space-between; }
        .receipt-footer { text-align: center; border-top: 2px dashed #000; padding-top: 15px; margin-top: 15px; }
        .btn-download { display: flex; align-items: center; justify-content: center; gap: 8px; background-color: #000; color: #fff; border: none; padding: 12px; width: 100%; margin-top: 20px; font-weight: bold; border-radius: 4px; cursor: pointer; font-family: sans-serif; transition: 0.3s; }
        .btn-download:hover { background-color: #333; }
        @media print { body * { visibility: hidden; } .modal-overlay { position: absolute; left: 0; top: 0; padding: 0; background: transparent; } .receipt-card, .receipt-card * { visibility: visible; } .receipt-card { box-shadow: none; max-width: 100%; padding: 0; margin: 0; } .no-print, .close-modal { display: none !important; } }
        
        /* TOMBOL WA MELAYANG & NAVIGASI BAWAH */
        .wa-btn { position: fixed; bottom: 30px; left: 30px; background-color: #25D366; color: white; border-radius: 50%; width: 55px; height: 55px; display: flex; justify-content: center; align-items: center; box-shadow: 0 4px 15px rgba(0,0,0,0.6); z-index: 1000; transition: 0.3s; text-decoration: none; }
        .wa-btn:hover { transform: scale(1.1); background-color: #1ebe57; }
        .wa-btn svg { width: 30px; height: 30px; }

        .bottom-nav-mobile { display: none !important; }

        @media (max-width: 768px) {
            body { padding: 15px 25px 85px; }
            .status-container { padding: 20px 15px; }
            .wa-btn { bottom: 85px !important; left: 15px; width: 45px; height: 45px; }
            .wa-btn svg { width: 24px; height: 24px; }
            
            /* NAVIGASI BAWAH MOBILE (MEMBER) */
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
                transition: 0.3s;
            }

            .bottom-nav-mobile .nav-item:hover, 
            .bottom-nav-mobile .nav-item:active { color: var(--accent-gold, #E8C999) !important; }

            .bottom-nav-mobile .nav-item svg { width: 22px !important; height: 22px !important; stroke: currentColor !important; fill: none !important; stroke-width: 2 !important; stroke-linecap: round !important; stroke-linejoin: round !important; }
        }
    </style>
</head>
<body>

    <div class="status-container">
        <div class="nav-top">
            <a href="member_dasbor.php" class="btn-back-square" title="Kembali ke Dasbor">←</a>
            <span style="color: #666; font-size: 0.75rem; font-weight: bold; text-transform: uppercase;">Pusat Tagihan</span>
        </div>
        
        <div class="form-header">
            <h2>Status & <span style="color: var(--accent-gold);">Riwayat</span></h2>
            <p>Memantau seluruh transaksi dan masa aktif membership Anda.</p>
        </div>

        <div style="border-bottom: 1px solid #333; padding-bottom: 15px; margin-bottom: 15px;">
            <span style="color: #888; font-size: 0.85rem; display: block; margin-bottom: 4px;">Data Member:</span>
            <div style="font-weight: bold; color: var(--text-light); font-size: 1.1rem;">
                <?= htmlspecialchars($nama_lengkap) ?> <span style="font-weight:normal; color:#aaa; font-size:0.9rem;">(<?= htmlspecialchars($email) ?>)</span>
            </div>
        </div>
        
        <h4 style="color: var(--accent-gold); margin-bottom: 10px; text-transform: uppercase; font-size: 0.85rem;">Daftar Transaksi Terakhir</h4>
        
        <div id="tempatRiwayat">
            <?php if (mysqli_num_rows($q_riwayat) > 0): ?>
                <div class="table-container">
                    <table class="table-riwayat">
                        <thead>
                            <tr>
                                <th>Tgl Pengajuan</th>
                                <th>Jenis</th>
                                <th>Paket</th>
                                <th>Status</th>
                                <th>Aksi / E-Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            while ($row = mysqli_fetch_assoc($q_riwayat)): 
                                $tglBayar = date('d M Y', strtotime($row['created_at']));
                                $jenis = ucfirst($row['jenis_pengajuan']);
                                $paket = $row['paket_bulan'] . " Bulan";
                                $status = $row['status'];
                                $id_membership = $row['id_membership'];
                                
                                $badge = "";
                                $btnReceipt = "-";

                                // Siapkan Data untuk Resi Digital
                                $harga = "Rp " . number_format($row['total_harga'], 0, ',', '.');
                                $tglMulai = date('d M Y', strtotime($row['tgl_mulai']));
                                $tglBerakhir = $row['tgl_berakhir'] ? date('d M Y', strtotime($row['tgl_berakhir'])) : '-';

                                $dataResi = htmlspecialchars(json_encode([
                                    'email' => $email,
                                    'tglBayar' => $tglBayar,
                                    'paket' => $paket,
                                    'tglMulai' => $tglMulai,
                                    'tglBerakhir' => $tglBerakhir,
                                    'harga' => $harga
                                ]), ENT_QUOTES, 'UTF-8');

                                // Label Status
                                if ($status == 'aktif') {
                                    $badge = '<span style="color:var(--success-green); font-weight:bold;">Selesai (Lunas)</span>';
                                    $btnReceipt = "<button type='button' class='btn-small-gold' onclick='bukaBukti($dataResi)'>Lihat</button>";
                                } else if ($status == 'pending') {
                                    $badge = '<span style="color:var(--warning-yellow); font-weight:bold;">Menunggu Verifikasi</span>';
                                    // Tombol Batal Transaksi (PHP Render awal)
                                    $btnReceipt = "<button type='button' class='btn-small-red' onclick='batalkanTransaksi($id_membership)'>Batalkan</button>";
                                } else if ($status == 'kadaluarsa') {
                                    $badge = '<span style="color:var(--primary-red);">Kadaluwarsa</span>';
                                    $btnReceipt = "<button type='button' class='btn-small-gold' onclick='bukaBukti($dataResi)'>Lihat</button>";
                                } else {
                                    $badge = '<span style="color:#aaa;">Ditolak</span>';
                                    $alasan = htmlspecialchars($row['alasan_tolak'] ?? 'Bukti tidak valid');
                                    $btnReceipt = "<span style='font-size:0.75rem; color:#888;' title='$alasan'>Ditolak Admin</span>";
                                }
                            ?>
                            <tr>
                                <td><?= $tglBayar ?></td>
                                <td><?= $jenis ?></td>
                                <td><?= $paket ?></td>
                                <td><?= $badge ?></td>
                                <td><?= $btnReceipt ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">Belum ada riwayat transaksi.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal-overlay" id="receiptModal">
        <div class="receipt-card">
            <div class="close-modal no-print" onclick="tutupBukti()">X</div>
            <div class="receipt-header">
                <h3>VANDA GYM CLASSIC</h3>
                <p>E-RECEIPT PEMBAYARAN MEMBER</p>
                <p>Palangka Raya, Kalimantan Tengah</p>
            </div>
            <div class="receipt-body" id="receiptData"></div>
            <div class="receipt-footer">
                <h3 style="margin:0; font-family:sans-serif; letter-spacing:1px;">LUNAS</h3>
                <p style="font-size:0.7rem; color:#666; margin-top:5px;">Terima kasih. Simpan bukti ini sebagai referensi Anda.</p>
            </div>
            <button class="btn-download no-print" onclick="window.print()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Simpan sebagai PDF
            </button>
        </div>
    </div>

    <a href="https://instagram.com/vandagympky_classic" target="_blank" class="wa-btn" title="Hubungi CS via Instagram" style="position: fixed; bottom: 20px; left: 20px; z-index: 9999; color: #ffffff; background: var(--primary-red, #ff4d4d); border-radius: 50%; padding: 12px; box-shadow: 0 4px 15px rgba(255, 77, 77, 0.4); border: 2px solid #E8C999; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
        <circle cx="12" cy="7" r="4"></circle>
    </svg>
</a>

    <div class="bottom-nav-mobile">
        <a href="member_dasbor.php" class="nav-item">
            <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span>Dasbor</span>
        </a>
        <a href="kalkulator.php?source=dasbor" class="nav-item">
            <svg viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="8" y1="6" x2="16" y2="6"></line><line x1="16" y1="14" x2="16.01" y2="14"></line><line x1="12" y1="14" x2="12.01" y2="14"></line><line x1="8" y1="14" x2="8.01" y2="14"></line></svg>
            <span>Gizi</span>
        </a>
        <a href="galeri_member.php" class="nav-item">
            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
            <span>Galeri</span>
        </a>
        <a href="chatbot_member.php" class="nav-item">
            <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="10" rx="2"></rect><circle cx="12" cy="5" r="2"></circle><path d="M12 7v4"></path></svg>
            <span>AI Bot</span>
        </a>
        <a href="profil_member.php" class="nav-item">
            <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            <span>Profil</span>
        </a>
    </div>

    <script>
        function bukaBukti(data) { 
            const noTrx = "VG-" + Math.floor(Math.random() * 99999);
            document.getElementById('receiptData').innerHTML = `
                <p><span>No. Trx</span> <span>${noTrx}</span></p>
                <p><span>Tgl Bayar</span> <span>${data.tglBayar}</span></p>
                <p><span>Email</span> <span>${data.email}</span></p>
                <hr style="border:1px dashed #000; margin:10px 0;">
                <p><span>Paket</span> <span>${data.paket}</span></p>
                <p><span>Berlaku</span> <span>${data.tglMulai} s/d ${data.tglBerakhir}</span></p>
                <hr style="border:1px dashed #000; margin:10px 0;">
                <p style="font-weight:bold; font-size:1rem;"><span>TOTAL</span> <span>${data.harga}</span></p>
            `;
            document.getElementById('receiptModal').style.display = 'flex'; 
        }
        
        function tutupBukti() { 
            document.getElementById('receiptModal').style.display = 'none'; 
        }

        // Fungsi Untuk Membatalkan Transaksi via AJAX
        function batalkanTransaksi(idTrx) {
            if(confirm("Apakah Anda yakin ingin membatalkan transaksi ini?")) {
                const formData = new FormData();
                formData.append('action', 'batalkan_transaksi');
                formData.append('id_trx', idTrx);
                
                fetch('cek_status_perpanjang.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        alert(data.message);
                        window.location.reload();
                    } else {
                        alert(data.message || 'Gagal membatalkan transaksi.');
                    }
                })
                .catch(err => {
                    alert('Kesalahan jaringan. Silakan coba lagi.');
                });
            }
            
        }
    </script>
</body>
</html>