<?php
session_start();
require 'includes/koneksi.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'register') {
    header('Content-Type: application/json'); 

    $nama      = mysqli_real_escape_string($koneksi, $_POST['regNama']);
    $email     = mysqli_real_escape_string($koneksi, $_POST['regEmail']);
    $wa        = mysqli_real_escape_string($koneksi, $_POST['regHp']);
    $password  = password_hash($_POST['regPass'], PASSWORD_DEFAULT);
    $harga     = (int) $_POST['regPaket']; 
    $tgl_mulai = $_POST['regTgl'];
    $metode    = $_POST['metodeBayar'];

    $durasi = 1;
    if ($harga == 350000) $durasi = 2;
    else if ($harga == 525000) $durasi = 3;

    $tgl_berakhir = date('Y-m-d', strtotime($tgl_mulai . " + $durasi months"));

    $id_user = 0;
    $cek_email = mysqli_query($koneksi, "SELECT id_user, role FROM users WHERE email='$email'");
    
    if (mysqli_num_rows($cek_email) > 0) {
        $data_u = mysqli_fetch_assoc($cek_email);
        
        if ($data_u['role'] === 'admin') {
            echo json_encode(['status' => 'error', 'message' => 'Email <strong>'.$email.'</strong> tidak dapat digunakan.']);
            exit;
        }

        $id_existing = $data_u['id_user'];
        $cek_m = mysqli_query($koneksi, "SELECT status FROM membership WHERE id_user='$id_existing' AND status != 'ditolak'");
        
        if (mysqli_num_rows($cek_m) > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Email <strong>'.$email.'</strong> sudah terdaftar dan masih memiliki transaksi Aktif/Pending/Kedaluwarsa. Sistem mencegah pembayaran ganda.']);
            exit;
        } else {
            $query_update = "UPDATE users SET nama_lengkap='$nama', no_wa='$wa', password='$password', role='calon_member' WHERE id_user='$id_existing'";
            if(mysqli_query($koneksi, $query_update)) {
                $id_user = $id_existing;
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui data user lama.']);
                exit;
            }
        }
    } else {
        $query_insert = "INSERT INTO users (nama_lengkap, email, no_wa, password, role) VALUES ('$nama', '$email', '$wa', '$password', 'calon_member')";
        if (mysqli_query($koneksi, $query_insert)) {
            $id_user = mysqli_insert_id($koneksi);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data user baru.']);
            exit;
        }
    }

    if ($id_user > 0) {
        $nama_file_bukti = NULL;
        if ($metode == 'qris' && isset($_FILES['regBukti']['name']) && $_FILES['regBukti']['name'] != '') {
            $ext = pathinfo($_FILES['regBukti']['name'], PATHINFO_EXTENSION);
            $nama_bersih = str_replace(' ', '_', preg_replace('/[^A-Za-z0-9 ]/', '', $nama));
            $nama_file_bukti = "Bukti_Daftar_" . $nama_bersih . "_" . date('dmy_His') . "." . $ext;
            
            // Menggunakan direktori uploads/ sesuai preferensi pengguna
            move_uploaded_file($_FILES['regBukti']['tmp_name'], 'uploads/' . $nama_file_bukti);
        }

        $query_member = "INSERT INTO membership (id_user, jenis_pengajuan, paket_bulan, total_harga, tgl_mulai, tgl_berakhir, metode_bayar, bukti_bayar, status) 
                         VALUES ($id_user, 'daftar', $durasi, $harga, '$tgl_mulai', '$tgl_berakhir', '$metode', '$nama_file_bukti', 'pending')";
        
        if (mysqli_query($koneksi, $query_member)) {
            echo json_encode(['status' => 'success']); 
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal memproses data paket.']);
        }
    }
    exit; 
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Member - Vanda Gym Classic</title>
    <style>
        :root { 
            --bg-dark: #000000; --primary-red: #dc3545; --accent-gold: #E8C999; 
            --text-light: #F8EEDF; --input-bg: #111111; --success-green: #28a745; 
            --warning-yellow: #ffc107;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--bg-dark); color: var(--text-light); display: flex; justify-content: center; align-items: flex-start; min-height: 100vh; padding: 40px 20px; }
        
        .pay-container { background-color: #0a0a0a; border: 1px solid #333; border-top: 4px solid var(--accent-gold); border-radius: 8px; padding: 30px; width: 100%; max-width: 650px; box-shadow: 0 10px 30px rgba(0,0,0,0.8); position: relative; }
        
        .nav-top { margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; }
        .btn-back-square { width: 40px; height: 40px; background-color: #1a1a1a; border: 1px solid #333; color: var(--accent-gold); border-radius: 4px; display: flex; align-items: center; justify-content: center; text-decoration: none; font-weight: bold; font-size: 1.2rem; transition: 0.3s; }
        .btn-back-square:hover { background-color: var(--primary-red); color: white; border-color: var(--primary-red); }
        
        .form-header { text-align: center; margin-bottom: 25px; }
        .form-header h2 { color: var(--text-light); text-transform: uppercase; font-size: 1.4rem; letter-spacing: 1px; margin-bottom: 5px;}
        .form-header h2 span { color: var(--accent-gold); }
        .form-header p { color: #888; font-size: 0.85rem; }
        
        .section-divider { border-bottom: 1px solid #222; margin: 25px 0 15px; padding-bottom: 8px; color: var(--accent-gold); font-weight: bold; text-transform: uppercase; font-size: 0.9rem; display: flex; justify-content: space-between; align-items: center;}
        
        .form-group { margin-bottom: 15px; text-align: left; }
        .form-group label { display: block; margin-bottom: 6px; color: #ccc; font-weight: 600; font-size: 0.8rem; }
        .form-control { width: 100%; padding: 10px 12px; background-color: var(--input-bg); border: 1px solid #333; border-radius: 4px; color: white; font-size: 0.9rem; transition: 0.3s; }
        .form-control:focus { outline: none; border-color: var(--accent-gold); }
        input[type="date"] { color-scheme: dark; cursor: pointer; }
        select { cursor: pointer; }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        
        /* Validasi Formulir Pendaftaran */
        .form-control.invalid-field { border-color: var(--primary-red) !important; background-color: #221111 !important; }
        .field-error-text { color: var(--primary-red); font-size: 0.75rem; margin-top: 4px; display: none; }
        .error-msg { color: var(--primary-red); font-size: 0.75rem; margin-top: 4px; display: none; }

        .payment-methods { display: flex; gap: 10px; margin-bottom: 15px; }
        .pay-method { flex: 1; border: 1px solid #333; border-radius: 4px; padding: 12px 10px; text-align: center; cursor: pointer; transition: 0.3s; background: #151515; position: relative; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .pay-method input { position: absolute; opacity: 0; cursor: pointer; }
        .pay-method span { font-weight: bold; color: #888; font-size: 0.85rem;}
        .pay-method.active { border-color: var(--accent-gold); background: rgba(232, 201, 153, 0.1); }
        .pay-method.active span { color: var(--accent-gold); }
        
        .pay-details { background: #111; border: 1px solid #222; padding: 20px; border-radius: 4px; margin-bottom: 20px; display: none; text-align: center; }
        .qris-box img { max-width: 150px; border-radius: 8px; margin: 10px 0; border: 2px solid white; background: #fff; padding: 5px; }
        .file-upload-wrapper { position: relative; margin-top: 15px; text-align: left; }
        .file-upload-wrapper input[type="file"] { position: absolute; left: 0; top: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
        .btn-upload { display: flex; align-items: center; justify-content: center; gap: 10px; background: #1a1a1a; border: 1px dashed var(--accent-gold); color: var(--accent-gold); padding: 10px; border-radius: 4px; width: 100%; font-size: 0.85rem; transition: 0.3s; }
        
        /* TOMBOL AKSI */
        .btn-action { width: 100%; border: none; min-height: 44px; font-size: 0.9rem; font-weight: bold; border-radius: 4px; cursor: pointer; text-transform: uppercase; transition: 0.3s; display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 15px; }
        .btn-success { background-color: var(--success-green); color: white; }
        .btn-success:hover { background-color: #218838; }
        .btn-success:disabled { background-color: #1e5c2b; color: #888; cursor: not-allowed; }
        .btn-outline { background-color: transparent; border: 1px solid #444; color: #aaa; margin-top: 10px; text-decoration: none;}
        .btn-outline:hover { border-color: var(--primary-red); color: var(--primary-red); }
        
        /* CHECKBOX KONFIRMASI */
        .checkbox-container { display: flex; align-items: flex-start; gap: 10px; margin: 20px 0; background: #151515; padding: 12px; border-radius: 4px; border: 1px solid #333; text-align: left; }
        .checkbox-container input { margin-top: 3px; cursor: pointer; width: 16px; height: 16px; accent-color: var(--success-green); }
        .checkbox-container label { font-size: 0.8rem; color: #ccc; cursor: pointer; line-height: 1.4; }

        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); display: none; justify-content: center; align-items: center; z-index: 1000; padding: 20px; }
        .modal-box { background: #111; border: 1px solid var(--accent-gold); padding: 25px; border-radius: 8px; width: 100%; max-width: 400px; }
        
        .draf-item { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .login-footer { margin-top: 25px; text-align: center; font-size: 0.85rem; display: flex; flex-direction: column; gap: 10px; }
        .login-footer a { color: var(--accent-gold); text-decoration: none; font-weight: bold; }

        .wa-btn { position: fixed; bottom: 30px; left: 30px; width: 55px; height: 55px; background-color: #25D366; color: white; border-radius: 50%; display: flex; justify-content: center; align-items: center; box-shadow: 0 4px 10px rgba(0,0,0,0.5); z-index: 1000; text-decoration: none; transition: 0.3s; }
        .wa-btn:hover { transform: scale(1.1); background-color: #1ebe57; }
        .wa-btn svg { width: 30px; height: 30px; fill: currentColor; }

        /* ====================================================
           OPTIMASI TAMPILAN MOBILE (LAYAR KECIL)
           ==================================================== */
        @media (max-width: 768px) {
            body { 
                padding: 15px 10px; /* Jarak halaman lebih kecil */
                align-items: flex-start; /* Pastikan tidak terpotong di atas */
            }
            
            .pay-container { 
                padding: 20px 15px; /* Padding dalam kontainer lebih kecil */
                margin-bottom: 70px; /* Ruang untuk tombol WA */
                max-width: 400px; /* Batasi lebar maksimal di mobile */
            }
            
            /* Navigasi & Header */
            .nav-top { margin-bottom: 15px; }
            .btn-back-square { width: 35px; height: 35px; font-size: 1rem; }
            
            .form-header { margin-bottom: 20px; }
            .form-header h2 { font-size: 1.25rem; margin-bottom: 3px; }
            .form-header p { font-size: 0.75rem; }
            
            /* Section Divider */
            .section-divider { 
                margin: 20px 0 10px; 
                padding-bottom: 5px; 
                font-size: 0.8rem; 
            }
            
            /* Form Input */
            .grid-2 { grid-template-columns: 1fr; gap: 10px; } /* Jadikan 1 kolom */
            .form-group { margin-bottom: 12px; }
            .form-group label { font-size: 0.75rem; margin-bottom: 4px; }
            .form-control { 
                padding: 8px 10px; 
                font-size: 0.85rem; 
                min-height: 38px; /* Lebih pendek */
            }
            
            /* Toggle Password */
            #togglePassword { min-height: 38px !important; min-width: 38px !important; }
            #eyeIcon { width: 16px; height: 16px; }
            
            /* Box Nominal & Pembayaran */
            #boxNominal { padding: 10px; margin-bottom: 12px; }
            #boxNominal span { font-size: 0.8rem; }
            #textNominal { font-size: 1rem !important; }
            
            .payment-methods { gap: 8px; margin-bottom: 12px; }
            .pay-method { padding: 10px 8px; }
            .pay-method span { font-size: 0.75rem; }
            
            .pay-details { padding: 15px; margin-bottom: 15px; }
            .btn-upload { padding: 8px; font-size: 0.8rem; }
            
            /* Tombol Aksi */
            .btn-action { min-height: 40px; font-size: 0.85rem; margin-top: 10px; }
            
            /* Footer Login */
            .login-footer { margin-top: 20px; font-size: 0.75rem; gap: 8px; }
            
            /* Tombol WA Melayang */
            .wa-btn { bottom: 15px; left: 15px; width: 45px; height: 45px; }
            .wa-btn svg { width: 24px; height: 24px; }
            
            /* Modal / Draf */
            .modal-box { padding: 20px 15px; }
            .draf-item { font-size: 0.8rem; }
            .checkbox-container { padding: 10px; margin: 15px 0; }
            .checkbox-container label { font-size: 0.75rem; }
        }
    </style>
</head>
<body>

    <div class="pay-container">
        <div class="nav-top">
            <a href="index.php" class="btn-back-square" title="Kembali ke Beranda">←</a>
        </div>

        <div class="form-header">
            <h2>Daftar <span>Membership</span></h2>
            <p>Lengkapi formulir untuk bergabung di Vanda Gym</p>
        </div>

        <form id="formPendaftaran" onsubmit="validasiDanBukaDraf(event)">
            
            <div class="section-divider">1. Data Pribadi</div>
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" id="regNama" name="regNama" class="form-control" placeholder="Masukkan nama lengkap">
                <div id="err_regNama" class="field-error-text">Nama tidak boleh kosong</div>
            </div>
            
            <div class="grid-2">
                <div class="form-group">
                    <label>Nomor WhatsApp</label>
                    <input type="text" id="regHp" name="regHp" class="form-control" oninput="validasiAngka(this)" placeholder="0812xxxx">
                    <div id="errorHp" class="error-msg">Wajib angka saja.</div>
                    <div id="err_regHp" class="field-error-text">Nomor WhatsApp tidak boleh kosong</div>
                </div>
                <div class="form-group">
                    <label>Alamat Email</label>
                    <input type="email" id="regEmail" name="regEmail" class="form-control" placeholder="nama@email.com">
                    <div id="err_regEmail" class="field-error-text">Alamat Email tidak boleh kosong</div>
                </div>
            </div>

            <div class="section-divider">2. Keamanan Akun</div>
            <div class="form-group">
                <label>Password Akun</label>
                <div style="position: relative;">
                    <input type="password" id="regPass" name="regPass" class="form-control" placeholder="Kombinasi angka & huruf" oninput="cekPassword(this)" style="padding-right: 50px;">
                    <span id="togglePassword" onclick="toggleVisibility()" style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); cursor: pointer; min-height: 38px; min-width: 38px; display: flex; align-items: center; justify-content: center; z-index: 10;">
                        <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </span>
                </div>
                <div id="errorPass" class="error-msg">Gunakan minimal satu huruf dan satu angka.</div>
                <div id="err_regPass" class="field-error-text">Password tidak boleh kosong</div>
                <p style="font-size: 0.75rem; color: #888; margin-top: 4px;">Gunakan email Anda sebagai identitas login nantinya.</p>
            </div>

            <div class="section-divider">3. Paket Latihan & Pembayaran</div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Pilih Durasi</label>
                    <select id="regPaket" name="regPaket" class="form-control" onchange="updateNominal()">
                        <option value="" disabled selected>-- Pilih Paket --</option>
                        <option value="175000" data-nama="1 Bulan Gym">1 Bulan Gym</option>
                        <option value="350000" data-nama="2 Bulan Gym">2 Bulan Gym</option>
                        <option value="525000" data-nama="3 Bulan Gym">3 Bulan Gym</option>
                    </select>
                    <div id="err_regPaket" class="field-error-text">Paket latihan wajib dipilih</div>
                </div>
                <div class="form-group">
                    <label>Tanggal Mulai</label>
                    <input type="date" id="regTgl" name="regTgl" class="form-control">
                    <div id="err_regTgl" class="field-error-text">Tanggal mulai wajib diisi</div>
                </div>
            </div>

            <div id="boxNominal" style="display: none; justify-content: space-between; align-items: center; background: rgba(232, 201, 153, 0.1); border: 1px dashed var(--accent-gold); padding: 12px; border-radius: 4px; margin-bottom: 15px;">
                <span style="font-size: 0.85rem; color: var(--text-light);">Total Tagihan:</span>
                <span id="textNominal" style="font-size: 1.1rem; font-weight: bold; color: var(--accent-gold);">Rp 0</span>
            </div>

            <div class="form-group">
                <label>Metode Pembayaran</label>
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
                <div class="qris-box">
                    <p style="font-size: 0.8rem; color: #ccc;">Scan QR Code atau transfer ke:<br><strong>BCA 123-456-789 a.n Vanda Gym</strong></p>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=Pembayaran+Member+Baru+Vanda+Gym" alt="QRIS Vanda Gym">
                </div>
                <div class="file-upload-wrapper">
                    <div class="btn-upload">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/></svg>
                        <span id="namaFile">Upload Bukti Transfer *</span>
                    </div>
                    <input type="file" id="regBukti" name="regBukti" accept="image/*" onchange="tampilkanNamaFile(this)">
                    <div id="err_regBukti" class="field-error-text">Bukti transfer wajib diunggah</div>
                </div>
            </div>

            <div id="detailTunai" class="pay-details">
                <p style="font-size: 0.8rem; color: #888;">
                    <strong>Kirim draf pendaftaran ini</strong> lalu datang ke resepsionis untuk melakukan pembayaran tunai agar akun dapat aktif.
                </p>
            </div>

            <button type="submit" class="btn-action btn-success">Kirim Pendaftaran</button>

            <div class="login-footer">
                <div>
                    <span style="color: #888;">Menunggu verifikasi?</span>
                    <a href="cek_status.php">Cek Status</a>
                </div>
                <div>
                    <span style="color: #888;">Sudah punya akun?</span>
                    <a href="login.php">Login</a>
                </div>
            </div>
        </form>
    </div>

    <div class="modal-overlay" id="modalOverlay">
        <div class="modal-box" id="modalContent"></div>
    </div>

    <?php
    $q_wa = mysqli_query($koneksi, "SELECT wa_cs FROM pengaturan_web WHERE id=1");
    $wa_data = mysqli_fetch_assoc($q_wa);
    $wa_db = $wa_data['wa_cs'] ?? '082148556601';
    $wa_link = "62" . substr(preg_replace('/[^0-9]/', '', $wa_db), 1);
    ?>
    <a href="https://wa.me/<?= $wa_link ?>" target="_blank" class="wa-btn" title="Hubungi CS via WhatsApp">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
          <path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/>
        </svg>
    </a>

    <script>
        function validasiAngka(input) {
            const error = document.getElementById('errorHp');
            if (/\D/g.test(input.value)) {
                error.style.display = 'block';
                input.classList.add('invalid-field');
                input.value = input.value.replace(/\D/g, ''); 
            } else {
                error.style.display = 'none';
                input.classList.remove('invalid-field');
            }
        }

        function cekPassword(input) {
            const error = document.getElementById('errorPass');
            const regex = /^(?=.*[0-9])(?=.*[a-zA-Z])([a-zA-Z0-9]+)$/;
            if (!regex.test(input.value) && input.value.length > 0) {
                error.style.display = 'block';
                input.classList.add('invalid-field');
            } else {
                error.style.display = 'none';
                input.classList.remove('invalid-field');
            }
        }

        function toggleVisibility() {
            const passwordInput = document.getElementById('regPass');
            const eyeIcon = document.getElementById('eyeIcon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML = `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>`;
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>`;
            }
        }

        function updateNominal() {
            const paket = document.getElementById('regPaket');
            const boxNominal = document.getElementById('boxNominal');
            const textNominal = document.getElementById('textNominal');
            if (paket.value) {
                boxNominal.style.display = 'flex';
                textNominal.innerText = "Rp " + parseInt(paket.value).toLocaleString('id-ID');
            } else {
                boxNominal.style.display = 'none';
            }
        }

        function ubahMetode() {
            const isQris = document.querySelector('input[name="metodeBayar"]:checked').value === 'qris';
            document.getElementById('labelQris').classList.toggle('active', isQris);
            document.getElementById('labelTunai').classList.toggle('active', !isQris);
            document.getElementById('detailQris').style.display = isQris ? 'block' : 'none';
            document.getElementById('detailTunai').style.display = isQris ? 'none' : 'block';
        }

        function tampilkanNamaFile(input) {
            const namaFileEl = document.getElementById('namaFile');
            if (input.files && input.files[0]) {
                namaFileEl.innerText = input.files[0].name;
                namaFileEl.style.color = "var(--text-light)";
            } else {
                namaFileEl.innerText = "Upload Bukti Transfer *";
                namaFileEl.style.color = "var(--accent-gold)";
            }
        }

        function validasiFormKosong() {
            let isValid = true;
            const fields = [
                { id: 'regNama' }, { id: 'regHp' }, { id: 'regEmail' }, 
                { id: 'regPass' }, { id: 'regPaket' }, { id: 'regTgl' }
            ];

            fields.forEach(field => {
                const el = document.getElementById(field.id);
                const errEl = document.getElementById('err_' + field.id);
                if (!el.value || el.value.trim() === "") {
                    el.classList.add('invalid-field');
                    errEl.style.display = 'block';
                    isValid = false;
                } else {
                    el.classList.remove('invalid-field');
                    errEl.style.display = 'none';
                }
            });

            const metode = document.querySelector('input[name="metodeBayar"]:checked').value;
            if (metode === 'qris') {
                const bukti = document.getElementById('regBukti');
                const errBukti = document.getElementById('err_regBukti');
                if (bukti.files.length === 0) {
                    document.querySelector('.btn-upload').style.borderColor = "var(--primary-red)";
                    document.querySelector('.btn-upload').style.color = "var(--primary-red)";
                    errBukti.style.display = 'block';
                    isValid = false;
                } else {
                    document.querySelector('.btn-upload').style.borderColor = "var(--accent-gold)";
                    document.querySelector('.btn-upload').style.color = "var(--accent-gold)";
                    errBukti.style.display = 'none';
                }
            }
            return isValid;
        }

        function toggleTombolBayar(checkbox) {
            document.getElementById('btnFinalBayar').disabled = !checkbox.checked;
        }

        function validasiDanBukaDraf(e) {
            e.preventDefault();
            if (!validasiFormKosong()) {
                alert("Harap lengkapi seluruh kolom formulir yang bertanda merah.");
                return;
            }

            const namaLengkap = document.getElementById('regNama').value;
            const noHp = document.getElementById('regHp').value;
            const email = document.getElementById('regEmail').value;
            const pass = document.getElementById('regPass').value;
            const tglMulai = document.getElementById('regTgl').value;
            const metode = document.querySelector('input[name="metodeBayar"]:checked').value;
            
            const regex = /^(?=.*[0-9])(?=.*[a-zA-Z])([a-zA-Z0-9]+)$/;
            if (!regex.test(pass)) {
                alert("Harap perbaiki kriteria password sebelum melanjutkan.");
                return;
            }

            const selectPaket = document.getElementById('regPaket');
            const namaPaket = selectPaket.options[selectPaket.selectedIndex].getAttribute('data-nama');
            const hargaPaket = "Rp " + parseInt(selectPaket.value).toLocaleString('id-ID');

            const modal = document.getElementById('modalOverlay');
            const content = document.getElementById('modalContent');
            modal.style.display = 'flex';
            
            // Draf ringkasan ditampilkan sebelum checkbox persetujuan
            content.innerHTML = `
                <h3 style="color:var(--text-light); text-transform:uppercase; text-align:center; font-size:1.1rem; letter-spacing:1px; margin-bottom:5px;">Konfirmasi Data</h3>
                <div style="margin:20px 0; font-size: 0.85rem; color:#ccc;">
                    <div class="draf-item"><span style="color:#888;">Nama:</span> <span style="text-align:right; color:white;">${namaLengkap}</span></div>
                    <div class="draf-item"><span style="color:#888;">Kontak:</span> <span style="text-align:right; color:white;">${noHp} <br> ${email}</span></div>
                    <div class="draf-item"><span style="color:#888;">Paket Latihan:</span> <span style="text-align:right; color:white;">${namaPaket} <br> Mulai: ${tglMulai}</span></div>
                    <div class="draf-item"><span style="color:#888;">Metode:</span> <span style="text-align:right; color:white; text-transform: uppercase;">${metode}</span></div>
                    <div class="draf-item" style="border-top:1px dashed #333; margin-top:10px; padding-top:15px;">
                        <span style="color:var(--text-light); font-weight:bold;">Total Tagihan:</span> 
                        <span style="color:var(--accent-gold); font-weight:bold; font-size:1.1rem;">${hargaPaket}</span>
                    </div>
                </div>
                
                <div class="checkbox-container">
                    <input type="checkbox" id="chkYakin" onchange="toggleTombolBayar(this)">
                    <label for="chkYakin">Saya yakin data dan bukti pembayaran yang saya masukkan sudah benar dan sesuai.</label>
                </div>
                
                <button id="btnFinalBayar" class="btn-action btn-success" style="margin-top:0;" onclick="kirimFinal('${metode}', '${email}')" disabled>Kirim Pendaftaran</button>
                <button type="button" class="btn-action btn-outline" onclick="document.getElementById('modalOverlay').style.display='none'">Batal & Edit</button>
            `;
        }

        function kirimFinal(metode, email) {
            const content = document.getElementById('modalContent');
            const form = document.getElementById('formPendaftaran');
            
            content.innerHTML = `<div style="text-align:center;"><p style="font-weight:bold; font-size:0.9rem; color:var(--accent-gold);">Menyimpan data...</p><p style="color:#888; font-size:0.8rem; margin-top:10px;">Mohon tunggu sebentar.</p></div>`;

            const formData = new FormData(form);
            formData.append('action', 'register');

            fetch('daftar.php', { method: 'POST', body: formData })
            .then(response => {
                if(!response.ok) throw new Error('Offline');
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    let pesanStatus = (metode === 'tunai') ? `<strong style="color: var(--warning-yellow);">Menunggu Pembayaran</strong>` : `<strong style="color: var(--warning-yellow);">Sedang Diproses</strong>`;
                    let instruksi = (metode === 'tunai') ? `Silakan datang ke resepsionis Vanda Gym untuk melakukan pembayaran tunai.` : `Admin sedang memverifikasi bukti pembayaran Anda.`;
                    let tombolWa = "";

                    if (metode !== 'tunai') {
                        const pesanWa = encodeURIComponent(`Halo Admin Vanda Gym, saya baru saja melakukan pendaftaran member baru dengan email *${email}*. Tolong dicek ya. Terima kasih.`);
                        tombolWa = `<a href="https://wa.me/6282148556601?text=${pesanWa}" target="_blank" class="btn-action" style="background-color: #25D366; color: white; text-decoration: none; font-size: 0.8rem; margin-top: 15px;">📱 Konfirmasi WhatsApp</a>`;
                    }

                    content.innerHTML = `
                        <h3 style="color:var(--success-green); text-align:center; font-size:1.2rem; text-transform:uppercase;">Berhasil!</h3>
                        <p style="margin:5px 0 15px 0; text-align:center; font-size:0.85rem; color:#ccc;">Status: ${pesanStatus}</p>
                        <div style="background:#151515; padding:15px; border:1px solid #333; border-radius:4px; font-size:0.8rem; line-height:1.5;">
                            <strong style="color:white; display:block; margin-bottom:5px;">Langkah Selanjutnya:</strong>
                            <span style="color:#aaa;">${instruksi}</span>
                            ${tombolWa}
                        </div>
                        <button class="btn-action btn-success" onclick="window.location.href='cek_status.php'">Cek Status Pendaftaran</button>
                    `;
                } else {
                    content.innerHTML = `
                        <h3 style="color:var(--primary-red); text-align:center; font-size:1.2rem; text-transform:uppercase;">Gagal!</h3>
                        <p style="margin:15px 0; text-align:center; font-size: 0.85rem; color:#ccc;">${data.message}</p>
                        <button class="btn-action btn-outline" onclick="document.getElementById('modalOverlay').style.display='none'">Kembali</button>
                    `;
                }
            })
            .catch(error => {
                content.innerHTML = `
                    <div style="text-align:center; padding: 5px;">
                        <h3 style="color:var(--primary-red); font-weight:bold; margin-bottom:10px; font-size:1.1rem; text-transform:uppercase;">Koneksi Gagal!</h3>
                        <p style="font-size:0.8rem; color:#ccc; margin-bottom:20px; line-height:1.5;">Sistem gagal terhubung ke server. Periksa koneksi internet Anda.</p>
                        <button class="btn-action btn-success" onclick="kirimFinal('${metode}', '${email}')">🔄 Coba Lagi</button>
                        <button class="btn-action btn-outline" onclick="document.getElementById('modalOverlay').style.display='none'">Batal</button>
                    </div>`;
            });
        }
    </script>
</body>
</html>