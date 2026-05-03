<?php
session_start();
require 'includes/koneksi.php';

// 1. PROTEKSI: Cek Login
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'member') {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// =========================================================
// BLOK PHP: HANDLING AJAX SUBMISSION (PERPANJANG)
// =========================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'perpanjang') {
    header('Content-Type: application/json');

    $paket     = (int) $_POST['paketHarga']; // 175000, 350000, dst
    $tgl_mulai = $_POST['tglMulaiInput'];
    $metode    = $_POST['metodeBayar'];
    
    // Tentukan durasi bulan
    $durasi = 1;
    if ($paket == 350000) $durasi = 2;
    else if ($paket == 525000) $durasi = 3;

    $tgl_berakhir = date('Y-m-d', strtotime($tgl_mulai . " + $durasi months"));

    // Proses Upload Gambar (Jika QRIS)
    $nama_file_bukti = NULL;
    if ($metode == 'qris' && isset($_FILES['buktiFile']['name']) && $_FILES['buktiFile']['name'] != '') {
        $ext = pathinfo($_FILES['buktiFile']['name'], PATHINFO_EXTENSION);
        $nama_bersih = str_replace(' ', '_', preg_replace('/[^A-Za-z0-9 ]/', '', $_SESSION['nama']));
        $nama_file_bukti = "Bukti_Perpanjang_" . $nama_bersih . "_" . date('dmy_His') . "." . $ext;
        move_uploaded_file($_FILES['buktiFile']['tmp_name'], 'uploads/' . $nama_file_bukti);
    }

    // Simpan ke tabel membership sebagai pengajuan baru (Status Pending)
    $query = "INSERT INTO membership (id_user, jenis_pengajuan, paket_bulan, total_harga, tgl_mulai, tgl_berakhir, metode_bayar, bukti_bayar, status) 
              VALUES ($id_user, 'perpanjang', $durasi, $paket, '$tgl_mulai', '$tgl_berakhir', '$metode', '$nama_file_bukti', 'pending')";
    
    if (mysqli_query($koneksi, $query)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data ke database.']);
    }
    exit;
}

// =========================================================
// AMBIL DATA MEMBER & STATUS AKTIF SAAT INI
// =========================================================
$q_user = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = $id_user");
$user = mysqli_fetch_assoc($q_user);

$q_member = mysqli_query($koneksi, "SELECT tgl_berakhir, status FROM membership WHERE id_user = $id_user ORDER BY id_membership DESC LIMIT 1");
$m_data = mysqli_fetch_assoc($q_member);

$tgl_akhir_db = $m_data['tgl_berakhir'] ?? date('Y-m-d');
$status_db    = $m_data['status'] ?? 'expired';

// Ambil Harga dari Pengaturan Web
$q_web = mysqli_query($koneksi, "SELECT harga_bulanan FROM pengaturan_web WHERE id=1");
$web = mysqli_fetch_assoc($q_web);
$harga_base = $web['harga_bulanan'] ?? 175000;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perpanjang Member - Vanda Gym Classic</title>
    <style>
        /* [SELURUH CSS KAMU YANG ASLI TETAP DI SINI] */
        :root { --bg-dark: #000000; --primary-red: #8E1616; --accent-gold: #E8C999; --text-light: #F8EEDF; --input-bg: #111111; --success-green: #28a745; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--bg-dark); color: var(--text-light); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 40px 20px; }
        .pay-container { background-color: #0a0a0a; border: 1px solid #333; border-top: 4px solid var(--primary-red); border-radius: 8px; padding: 30px; width: 100%; max-width: 600px; box-shadow: 0 10px 30px rgba(0,0,0,0.8); position: relative; }
        .nav-top { margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; }
        .btn-back-square { width: 44px; height: 44px; background-color: #1a1a1a; border: 1px solid #333; color: var(--accent-gold); border-radius: 4px; display: flex; align-items: center; justify-content: center; text-decoration: none; font-weight: bold; font-size: 1.2rem; transition: 0.3s; }
        .btn-back-square:hover { background-color: var(--primary-red); color: white; border-color: var(--primary-red); }
        .form-header { text-align: center; margin-bottom: 25px; }
        .form-header h2 { color: var(--text-light); text-transform: uppercase; font-size: 1.5rem; letter-spacing: 1px; margin-bottom: 5px;}
        .form-header p { color: #888; font-size: 0.9rem; }
        .section-divider { border-bottom: 1px solid #222; margin: 25px 0 15px; padding-bottom: 8px; color: var(--accent-gold); font-weight: bold; text-transform: uppercase; font-size: 0.9rem; }
        .status-box { background: rgba(232, 201, 153, 0.05); border: 1px dashed var(--accent-gold); padding: 15px; border-radius: 6px; margin-bottom: 25px; text-align: center; }
        .status-box h4 { color: #ccc; margin-bottom: 8px; font-size: 0.85rem;}
        .status-badge { display: inline-block; padding: 6px 15px; border-radius: 20px; font-size: 0.85rem; font-weight: bold; letter-spacing: 0.5px; }
        .badge-expired { background: var(--primary-red); color: white; }
        .badge-active { background: var(--success-green); color: white; }
        .form-group { margin-bottom: 15px; text-align: left; }
        .form-group label { display: block; margin-bottom: 8px; color: #ccc; font-weight: 600; font-size: 0.85rem; }
        .form-control { width: 100%; padding: 12px 15px; background-color: var(--input-bg); border: 1px solid #333; border-radius: 4px; color: white; font-size: 0.95rem; transition: 0.3s; }
        .form-control:focus { outline: none; border-color: var(--accent-gold); }
        .form-control[readonly] { color: #888; cursor: not-allowed; }
        input[type="date"] { color-scheme: dark; cursor: pointer; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .payment-methods { display: flex; gap: 15px; margin-bottom: 20px; }
        .pay-method { flex: 1; border: 1px solid #333; border-radius: 6px; padding: 15px 10px; text-align: center; cursor: pointer; transition: 0.3s; background: #151515; position: relative; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .pay-method input { position: absolute; opacity: 0; cursor: pointer; }
        .pay-method span { font-weight: bold; color: #888; display: block; font-size: 0.9rem;}
        .pay-method.active { border-color: var(--accent-gold); background: rgba(232, 201, 153, 0.1); }
        .pay-method.active span { color: var(--accent-gold); }
        .pay-details { background: #111; border: 1px solid #222; padding: 20px; border-radius: 6px; margin-bottom: 20px; display: none; text-align: center; }
        .qris-box img { max-width: 150px; border-radius: 8px; margin: 10px 0; border: 2px solid white; background: #fff; padding: 5px; }
        .file-upload-wrapper { position: relative; margin-top: 15px; text-align: left; }
        .file-upload-wrapper input[type="file"] { position: absolute; left: 0; top: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
        .btn-upload { display: flex; align-items: center; justify-content: center; gap: 10px; background: #1a1a1a; border: 1px dashed var(--accent-gold); color: var(--accent-gold); padding: 12px; border-radius: 4px; width: 100%; font-size: 0.9rem; transition: 0.3s; }
        .btn-submit { width: 100%; background-color: var(--primary-red); color: white; border: none; min-height: 48px; font-size: 1rem; font-weight: bold; border-radius: 4px; cursor: pointer; text-transform: uppercase; transition: 0.3s; margin-top: 10px; }
        .btn-submit:hover { background-color: #a81a1a; transform: translateY(-2px); }
        .action-link { display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; padding: 10px; border-radius: 4px; font-weight: bold; margin-top: 15px; min-height: 44px; transition: 0.3s; font-size: 0.9rem; }
        .action-link.wa { background-color: #25D366; color: white; }
        .action-link.outline { background: transparent; color: #888; border: 1px solid #333; margin-top: 10px; }
        .login-footer { text-align: center; margin-top: 25px; padding-top: 15px; border-top: 1px solid #222; display: flex; flex-direction: column; gap: 10px; }
        .login-footer a { color: var(--accent-gold); text-decoration: none; font-weight: bold; border: 1px solid var(--accent-gold); padding: 8px 15px; border-radius: 4px; display: inline-flex; align-items: center; justify-content: center; min-height: 38px; transition: 0.3s; }
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); display: none; justify-content: center; align-items: center; z-index: 1000; padding: 20px; overflow-y: auto; }
        .modal-box { background: #111; border: 1px solid var(--accent-gold); padding: 25px; border-radius: 8px; width: 100%; max-width: 450px; }
        .draf-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #222; font-size: 0.9rem;}
    </style>
</head>
<body>

    <div class="pay-container">
        <div class="nav-top">
            <a href="member_dasbor.php" class="btn-back-square" title="Kembali ke Dasbor">←</a>
        </div>

        <div class="form-header">
            <h2>Perpanjang <span>Membership</span></h2>
            <p>Aktifkan kembali masa berlaku gym Anda</p>
        </div>

        <div class="status-box">
            <h4>Status Membership Saat Ini</h4>
            <?php 
    $hari_ini = date('Y-m-d');
    $is_expired = ($tgl_akhir_db < $hari_ini);
    
    // Kamus Nama Bulan Indonesia
    $bulanIndo = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
        '04' => 'April', '05' => 'Mei', '06' => 'Juni',
        '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
        '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    ];
    
    // Ubah format tanggal menjadi bahasa Indonesia
    $waktu = strtotime($tgl_akhir_db);
    $format_tgl = date('d', $waktu) . ' ' . $bulanIndo[date('m', $waktu)] . ' ' . date('Y', $waktu);
?>
            <div id="badgeStatus" class="status-badge <?= $is_expired ? 'badge-expired' : 'badge-active' ?>">
                <?= $is_expired ? 'KADALUWARSA' : 'AKTIF' ?>
            </div>
            <div style="color: #ccc; font-size: 0.85rem; margin-top: 8px;">
                Masa aktif: <strong style="color: white;"><?= $format_tgl ?></strong>
            </div>
        </div>

        <form id="formPerpanjang" onsubmit="bukaModalKonfirmasi(event)">
            <div class="section-divider">Data Member & Paket</div>
            
            <div class="form-group">
                <label>Nama Member</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($user['nama_lengkap']) ?>" readonly>
            </div>
            <div class="form-group">
                <label>Email Pendaftaran</label>
                <input type="email" id="emailMember" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" readonly>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Pilih Paket</label>
                    <select id="paketPilih" name="paketPilih" class="form-control" required onchange="updateTotalHarga()">
                        <option value="" disabled selected>-- Pilih Paket --</option>
                        <option value="<?= $harga_base ?>" data-nama="1 Bulan Gym">1 Bulan Gym</option>
                        <option value="<?= $harga_base * 2 ?>" data-nama="2 Bulan Gym">2 Bulan Gym</option>
                        <option value="<?= $harga_base * 3 ?>" data-nama="3 Bulan Gym">3 Bulan Gym</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tanggal Mulai Baru</label>
                    <input type="date" id="tglMulai" name="tglMulai" class="form-control" required 
                           value="<?= ($is_expired) ? $hari_ini : $tgl_akhir_db ?>" 
                           min="<?= ($is_expired) ? $hari_ini : $tgl_akhir_db ?>">
                </div>
            </div>

            <div class="section-divider">Pembayaran</div>

            <div class="form-group">
                <div class="payment-methods">
                    <label class="pay-method active" id="labelQris">
                        <input type="radio" name="metodeBayar" value="qris" checked onchange="ubahMetode()">
                        <span>📱 QRIS / Transfer</span>
                    </label>
                    <label class="pay-method" id="labelTunai">
                        <input type="radio" name="metodeBayar" value="tunai" onchange="ubahMetode()">
                        <span>💵 Tunai (Kasir)</span>
                    </label>
                </div>
            </div>

            <div id="detailQris" class="pay-details" style="display: block;">
                <p style="font-size: 0.85rem; color: #ccc;">Transfer ke: <strong>BCA 123-456-789 (Vanda Gym)</strong></p>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=Pembayaran+Perpanjang+Vanda+Gym" alt="QRIS">
                <h3 style="color: var(--accent-gold); margin-top: 10px;" id="totalBayarQris">Rp 0</h3>

                <div class="file-upload-wrapper">
                    <div class="btn-upload">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/></svg>
                        <span id="namaFile">Upload Bukti Transfer</span>
                    </div>
                    <input type="file" id="buktiFile" name="buktiFile" accept="image/*" onchange="tampilkanNamaFile(this)">
                </div>
            </div>

            <div id="detailTunai" class="pay-details">
                <p style="font-size: 0.9rem; color: #ccc;">Total Tagihan: <strong style="color: var(--accent-gold);" id="totalBayarTunai">Rp 0</strong></p>
                <p style="font-size: 0.8rem; color: #888; margin-top: 5px;">Kirim draf ini, lalu bayar langsung ke Kasir.</p>
            </div>

            <button type="submit" class="btn-submit">Kirim Pengajuan</button>

            <div class="login-footer">
                <div><a href="cek_status.php">Cek Status Membership</a></div>
            </div>
        </form>
    </div>

    <div class="modal-overlay" id="modalOverlay">
        <div class="modal-box" id="modalContent"></div>
    </div>

    <script>
        function updateTotalHarga() {
            const val = document.getElementById('paketPilih').value;
            const rp = "Rp " + parseInt(val).toLocaleString('id-ID');
            document.getElementById('totalBayarQris').innerText = rp;
            document.getElementById('totalBayarTunai').innerText = rp;
        }

        function ubahMetode() {
            const isQris = document.querySelector('input[name="metodeBayar"]:checked').value === 'qris';
            document.getElementById('labelQris').classList.toggle('active', isQris);
            document.getElementById('labelTunai').classList.toggle('active', !isQris);
            document.getElementById('detailQris').style.display = isQris ? 'block' : 'none';
            document.getElementById('detailTunai').style.display = isQris ? 'none' : 'block';
            document.getElementById('buktiFile').required = isQris;
        }

        function tampilkanNamaFile(input) {
            document.getElementById('namaFile').innerText = input.files[0] ? input.files[0].name : "Upload Bukti Transfer";
        }

        function bukaModalKonfirmasi(e) {
            e.preventDefault();
            const modal = document.getElementById('modalOverlay');
            const content = document.getElementById('modalContent');
            const select = document.getElementById('paketPilih');
            const namaPaket = select.options[select.selectedIndex].getAttribute('data-nama');
            
            modal.style.display = 'flex';
            content.innerHTML = `
                <h3 style="color:var(--accent-gold); border-bottom:1px solid #333; padding-bottom:10px; text-align:center;">Konfirmasi</h3>
                <div style="margin:15px 0; font-size: 0.9rem;">
                    <div class="draf-item"><span>Paket Baru:</span> <span>${namaPaket}</span></div>
                    <div class="draf-item"><span>Mulai Aktif:</span> <span>${document.getElementById('tglMulai').value}</span></div>
                </div>
                <button class="btn-submit" onclick="kirimFinal()">Kirim Sekarang</button>
                <button onclick="document.getElementById('modalOverlay').style.display='none'" class="action-link outline" style="width:100%">Batal</button>
            `;
        }

        function kirimFinal() {
            const content = document.getElementById('modalContent');
            content.innerHTML = "<p style='text-align:center; color:var(--accent-gold);'>Memproses pengajuan...</p>";

            const formData = new FormData();
            formData.append('action', 'perpanjang');
            formData.append('paketHarga', document.getElementById('paketPilih').value);
            formData.append('tglMulaiInput', document.getElementById('tglMulai').value);
            formData.append('metodeBayar', document.querySelector('input[name="metodeBayar"]:checked').value);
            if(document.getElementById('buktiFile').files[0]) {
                formData.append('buktiFile', document.getElementById('buktiFile').files[0]);
            }

            fetch('perpanjang.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    content.innerHTML = `
                        <h3 style="color:var(--accent-gold); text-align:center;">Berhasil!</h3>
                        <p style="text-align:center; margin:15px 0; font-size:0.9rem;">Pengajuan perpanjangan Anda telah dikirim dan sedang menunggu verifikasi Admin.</p>
                        <button class="btn-submit" onclick="window.location.href='member_dasbor.php'">Ke Dasbor</button>
                    `;
                } else { alert(data.message); }
            });
        }
    </script>
</body>
</html>