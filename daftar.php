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
    <link rel="stylesheet" href="css/style.css">
    <style>
        .form-control.invalid-field {
            border-color: #ff4d4d !important;
            background-color: #221111 !important;
        }
        .field-error-text {
            color: #ff4d4d; font-size: 0.8rem; margin-top: 4px; display: none;
        }
        .btn-positive {
            background-color: #25D366 !important; color: white !important;
            border: none; cursor: pointer; font-weight: bold; transition: 0.3s;
        }
        .btn-positive:hover { background-color: #1ebe57 !important; }
        .btn-positive:disabled { background-color: #555 !important; cursor: not-allowed; opacity: 0.6; }
        .btn-negative {
            background-color: #8E1616 !important; color: white !important;
            border: 1px solid #ff4d4d !important; cursor: pointer; transition: 0.3s;
        }
        .btn-negative:hover { background-color: #b31e1e !important; }
        
        .draf-item { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .checkbox-container {
            margin: 15px 0; display: flex; align-items: flex-start; gap: 8px; font-size: 0.9rem; color: #ccc; text-align: left;
        }
        .checkbox-container input { margin-top: 3px; width: 16px; height: 16px; cursor: pointer; }

        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.85); display: none; justify-content: center; 
            align-items: center; z-index: 9999; padding: 20px;
        }
        .modal-box {
            background: #111; border: 1px solid #333; border-radius: 8px; 
            padding: 25px; width: 100%; max-width: 450px; position: relative;
        }

        /* TOMBOL WA KIRI BAWAH */
        .wa-btn {
            position: fixed; bottom: 30px; left: 30px; 
            background-color: #25D366; color: white; 
            border-radius: 50%; width: 60px; height: 60px; 
            display: flex; justify-content: center; align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.6); z-index: 1000; transition: 0.3s;
            text-decoration: none;
        }
        .wa-btn:hover { transform: scale(1.1); background-color: #1ebe57; }
        .wa-btn svg { width: 35px; height: 35px; fill: currentColor; }

        /* PEMADATAN LAYOUT MOBILE DAFTAR */
        @media screen and (max-width: 768px) {
            body { 
                padding: 10px !important; 
                display: flex !important; 
                align-items: center !important; 
                justify-content: center !important; 
                min-height: 100vh !important; 
            }
            .form-container { 
                padding: 20px 15px !important; 
                width: 95% !important; 
                max-width: 380px !important; 
                margin: 0 auto !important; 
                box-sizing: border-box !important;
                border-radius: 8px !important;
                box-shadow: 0 5px 15px rgba(0,0,0,0.5) !important; 
            }
            .nav-top { margin-bottom: 10px !important; }
            .btn-back-square { width: 32px !important; height: 32px !important; font-size: 0.9rem !important; }
            .form-header h2 { font-size: 1.25rem !important; margin-bottom: 3px !important; }
            .form-header p { font-size: 0.75rem !important; margin-bottom: 12px !important; }
            .section-divider { font-size: 0.9rem !important; padding-bottom: 3px !important; margin-bottom: 12px !important; }
            .form-group { margin-bottom: 12px !important; }
            .form-group label { font-size: 0.75rem !important; margin-bottom: 4px !important; }
            .form-control { padding: 8px 10px !important; min-height: 38px !important; font-size: 0.8rem !important; }
            
            .grid-2 { display: flex !important; flex-direction: column !important; gap: 0 !important; }
            
            .btn-submit, .btn-negative { min-height: 38px !important; padding: 8px !important; font-size: 0.85rem !important; margin-top: 5px !important; }
            .login-footer { margin-top: 15px !important; font-size: 0.75rem !important; }
            .error-msg, .field-error-text { font-size: 0.7rem !important; }
            
            /* WA button mobile */
            .wa-btn { width: 45px !important; height: 45px !important; bottom: 15px !important; left: 15px !important; }
            .wa-btn svg { width: 22px !important; height: 22px !important; }
        }
    </style>
</head>
<body>

    <div class="form-container">
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
                    <select id="regPaket" name="regPaket" class="form-control" onchange="updateNominal()" style="cursor: pointer;">
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

            <div class="nominal-box" id="boxNominal" style="padding: 10px; margin-bottom: 12px; font-size: 0.85rem;">
                <span>Total Tagihan:</span>
                <span id="textNominal" style="font-size: 1rem;">Rp 0</span>
            </div>

            <div class="form-group">
                <label>Metode Pembayaran</label>
                <div class="payment-methods" style="gap: 5px; flex-direction: column;">
                    <label class="pay-method active" id="labelQris" style="padding: 8px; font-size: 0.8rem;">
                        <input type="radio" name="metodeBayar" value="qris" checked onchange="ubahMetode()">
                        <span>📱 Transfer / QRIS</span>
                    </label>
                    <label class="pay-method" id="labelTunai" style="padding: 8px; font-size: 0.8rem;">
                        <input type="radio" name="metodeBayar" value="tunai" onchange="ubahMetode()">
                        <span>💵 Bayar Tunai (Kasir)</span>
                    </label>
                </div>
            </div>

            <div id="detailQris" class="pay-details" style="display: block; padding: 10px; margin-top: 10px;">
                <div class="qris-box" style="padding: 10px; margin-bottom: 10px;">
                    <p style="font-size: 0.75rem; color: #ccc;">Scan QR Code atau transfer ke:<br><strong>BCA 123-456-789 a.n Vanda Gym</strong></p>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=Pembayaran+Member+Baru+Vanda+Gym" alt="QRIS Vanda Gym" style="width:100px;">
                </div>
                <div class="file-upload-wrapper">
                    <label style="font-size: 0.75rem; color: #888; margin-bottom: 5px; display: block;">Upload Bukti Transfer (Wajib)</label>
                    <div class="btn-upload" style="padding: 8px; font-size: 0.8rem;">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/></svg>
                        <span id="namaFile">Pilih Screenshot...</span>
                    </div>
                    <input type="file" id="regBukti" name="regBukti" accept="image/*" onchange="tampilkanNamaFile(this)">
                    <div id="err_regBukti" class="field-error-text">Bukti transfer wajib diunggah</div>
                </div>
            </div>

            <div id="detailTunai" class="pay-details" style="padding: 10px; margin-top: 10px;">
                <div style="text-align: center;">
                    <p style="font-size: 0.75rem; color: #888; margin-top: 5px; background: #0a0a0a; padding: 10px; border-radius: 4px; border: 1px solid #333;">
                        <strong>Silahkan kirim pendaftaran</strong> lalu ke resepsionis untuk melakukan pembayaran agar akun aktif.
                    </p>
                </div>
            </div>

            <button type="submit" class="btn-submit btn-positive" style="width: 100%; margin-top: 15px;">Kirim Pendaftaran</button>

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
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
          <path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/>
        </svg>
    </a>

    <script>
        function validasiAngka(input) {
            const error = document.getElementById('errorHp');
            if (/\D/g.test(input.value)) {
                error.style.display = 'block';
                input.classList.add('invalid');
                input.value = input.value.replace(/\D/g, ''); 
            } else {
                error.style.display = 'none';
                input.classList.remove('invalid');
            }
        }

        function cekPassword(input) {
            const error = document.getElementById('errorPass');
            const regex = /^(?=.*[0-9])(?=.*[a-zA-Z])([a-zA-Z0-9]+)$/;
            if (!regex.test(input.value) && input.value.length > 0) {
                error.style.display = 'block';
                input.classList.add('invalid');
            } else {
                error.style.display = 'none';
                input.classList.remove('invalid');
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
                namaFileEl.style.color = "white";
            } else {
                namaFileEl.innerText = "Pilih Screenshot...";
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
                    bukti.classList.add('invalid-field');
                    errBukti.style.display = 'block';
                    isValid = false;
                } else {
                    bukti.classList.remove('invalid-field');
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
            
            content.style.background = '#111';
            content.style.borderColor = '#333';
            content.style.borderTop = 'none';
            
            content.innerHTML = `
                <h3 style="color:var(--accent-gold); border-bottom:1px solid #333; padding-bottom:10px; text-align:center; font-size:1.1rem;">Konfirmasi Data</h3>
                <div style="margin:15px 0; font-size: 0.85rem; color:#ccc;">
                    <div class="draf-item"><span style="color:#888;">Nama:</span> <span style="text-align:right;">${namaLengkap}</span></div>
                    <div class="draf-item"><span style="color:#888;">Kontak:</span> <span style="text-align:right;">${noHp} <br> ${email}</span></div>
                    <div class="draf-item"><span style="color:#888;">Paket Latihan:</span> <span style="text-align:right;">${namaPaket} <br> Mulai: ${tglMulai}</span></div>
                    <div class="draf-item"><span style="color:#888;">Metode:</span> <span style="text-align:right;">${metode.toUpperCase()}</span></div>
                    <div class="draf-item" style="border-top:1px dashed #333; margin-top:5px; padding-top:10px;">
                        <span style="color:var(--text-light); font-weight:bold;">Total Tagihan:</span> 
                        <span style="color:var(--accent-gold); font-weight:bold; font-size:1rem;">${hargaPaket}</span>
                    </div>
                </div>
                
                <div class="checkbox-container">
                    <input type="checkbox" id="chkYakin" onchange="toggleTombolBayar(this)">
                    <label for="chkYakin" style="font-size:0.8rem;">Saya yakin data ini sudah benar.</label>
                </div>
                
                <button id="btnFinalBayar" class="btn-submit btn-positive" style="margin-top:0; width:100%; min-height:38px; font-size:0.85rem;" onclick="kirimFinal('${metode}', '${email}')" disabled>Kirim Pendaftaran</button>
                <button type="button" class="btn-negative" onclick="document.getElementById('modalOverlay').style.display='none'" style="border-radius:4px; width:100%; margin-top:8px; cursor:pointer; min-height:38px; font-size:0.85rem;">Batal / Edit</button>
            `;
        }

        function kirimFinal(metode, email) {
            const content = document.getElementById('modalContent');
            const form = document.getElementById('formPendaftaran');
            
            content.innerHTML = `<div style="text-align:center;"><p style="font-weight:bold; font-size:0.9rem; color:var(--accent-gold);">Menyimpan data...</p></div>`;

            const formData = new FormData(form);
            formData.append('action', 'register');

            fetch('daftar.php', { method: 'POST', body: formData })
            .then(response => {
                if(!response.ok) throw new Error('Offline');
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    let pesanStatus = (metode === 'tunai') ? `<strong style="color: #ffc107;">Menunggu Pembayaran</strong>` : `<strong style="color: #ffc107;">Sedang Diproses</strong>`;
                    let instruksi = (metode === 'tunai') ? `Silakan datang ke resepsionis Vanda Gym untuk melakukan pembayaran tunai.` : `Admin sedang memverifikasi bukti pembayaran Anda.`;
                    let tombolWa = "";

                    if (metode !== 'tunai') {
                        const pesanWa = encodeURIComponent(`Halo Admin Vanda Gym, saya baru saja melakukan pendaftaran member baru dengan email *${email}*. Tolong dicek ya. Terima kasih.`);
                        tombolWa = `<a href="https://wa.me/6282148556601?text=${pesanWa}" target="_blank" style="display: flex; align-items: center; justify-content: center; background-color: #25D366; color: white; text-decoration: none; padding: 8px; border-radius: 4px; font-weight: bold; margin-top: 10px; min-height: 38px; font-size: 0.8rem;">📱 Konfirmasi WhatsApp</a>`;
                    }

                    content.innerHTML = `
                        <h3 style="color:var(--accent-gold); text-align:center; font-size:1.1rem;">Berhasil!</h3>
                        <p style="margin:5px 0; text-align:center; font-size:0.85rem;">Status: ${pesanStatus}</p>
                        <div style="background:#050505; padding:10px; border:1px solid #222; border-radius:4px; font-size:0.75rem; line-height:1.4;">
                            <strong style="color:white;">Selanjutnya:</strong><br>
                            <span style="color:#aaa;">${instruksi}</span>
                            ${tombolWa}
                        </div>
                        <button class="btn-submit btn-positive" style="margin-top:15px; width:100%; min-height:38px; font-size:0.85rem;" onclick="window.location.href='cek_status.php'">Cek Status</button>
                    `;
                } else {
                    content.innerHTML = `
                        <h3 style="color:#ff4d4d; text-align:center; font-size:1.1rem;">Gagal!</h3>
                        <p style="margin:10px 0; text-align:center; font-size: 0.85rem;">${data.message}</p>
                        <button class="btn-negative" onclick="document.getElementById('modalOverlay').style.display='none'" style="border-radius:4px; width:100%; min-height:38px; font-size:0.85rem;">Kembali</button>
                    `;
                }
            })
            .catch(error => {
                content.style.background = '#0f0a0a';
                content.style.borderColor = '#ff4d4d';
                content.style.borderTop = '4px solid #ff4d4d';
                content.innerHTML = `
                    <div style="text-align:center; padding: 5px;">
                        <div style="width: 40px; height: 40px; background: #221111; border: 2px solid #ff4d4d; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px auto;">
                            <span style="color: #ff4d4d; font-size: 1.3rem; font-weight: bold;">!</span>
                        </div>
                        <h3 style="color:#ff4d4d; font-weight:bold; margin-bottom:5px; font-size:1rem;">Koneksi Gagal!</h3>
                        <p style="font-size:0.75rem; color:#ccc; margin-bottom:15px; line-height:1.4;">Sistem gagal terhubung ke database. Pastikan modul Apache & MySQL di XAMPP telah dinyalakan.</p>
                        <button class="btn-positive" style="width:100%; min-height:38px; border-radius:4px; font-size:0.85rem;" onclick="kirimFinal('${metode}', '${email}')">🔄 Coba Lagi</button>
                        <button class="btn-negative" style="width:100%; min-height:38px; border-radius:4px; margin-top:8px; font-size:0.85rem;" onclick="document.getElementById('modalOverlay').style.display='none'">Batal</button>
                    </div>`;
            });
        }
    </script>
</body>
</html>