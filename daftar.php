<?php
session_start();
require 'includes/koneksi.php'; // Pastikan path ini benar

// BLOK PHP UNTUK MENERIMA DATA DARI JAVASCRIPT (AJAX)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'register') {
    header('Content-Type: application/json'); // Format kembalian ke JS

    // 1. Ambil data
    $nama      = mysqli_real_escape_string($koneksi, $_POST['regNama']);
    $email     = mysqli_real_escape_string($koneksi, $_POST['regEmail']);
    $wa        = mysqli_real_escape_string($koneksi, $_POST['regHp']);
    $password  = password_hash($_POST['regPass'], PASSWORD_DEFAULT);
    $harga     = (int) $_POST['regPaket']; // Value-nya 175000, 350000, dst
    $tgl_mulai = $_POST['regTgl'];
    $metode    = $_POST['metodeBayar'];

    // Tentukan durasi bulan berdasarkan harga
    $durasi = 1;
    if ($harga == 350000) $durasi = 2;
    else if ($harga == 525000) $durasi = 3;

    $tgl_berakhir = date('Y-m-d', strtotime($tgl_mulai . " + $durasi months"));

    // 2. Cek Email Ganda dan Logika "Timpa" Jika Ditolak
    $id_user = 0;
    $cek_email = mysqli_query($koneksi, "SELECT id_user, role FROM users WHERE email='$email'");
    
    if (mysqli_num_rows($cek_email) > 0) {
        $data_u = mysqli_fetch_assoc($cek_email);
        
        // Jangan biarkan email admin ditimpa
        if ($data_u['role'] === 'admin') {
            echo json_encode(['status' => 'error', 'message' => 'Email <strong>'.$email.'</strong> tidak dapat digunakan.']);
            exit;
        }

        $id_existing = $data_u['id_user'];
        
        // Cek apakah user ini punya membership yang belum "ditolak" (aktif, pending, kedaluwarsa)
        $cek_m = mysqli_query($koneksi, "SELECT status FROM membership WHERE id_user='$id_existing' AND status != 'ditolak'");
        
        if (mysqli_num_rows($cek_m) > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Email <strong>'.$email.'</strong> sudah terdaftar dan masih memiliki transaksi Aktif/Pending/Kedaluwarsa.']);
            exit;
        } else {
            // Aman untuk ditimpa (Update data user yang lama yang pernah ditolak)
            $query_update = "UPDATE users SET nama_lengkap='$nama', no_wa='$wa', password='$password', role='calon_member' WHERE id_user='$id_existing'";
            if(mysqli_query($koneksi, $query_update)) {
                $id_user = $id_existing;
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui data user lama.']);
                exit;
            }
        }
    } else {
        // 3. Simpan ke tabel users (Email benar-benar baru)
        $query_insert = "INSERT INTO users (nama_lengkap, email, no_wa, password, role) VALUES ('$nama', '$email', '$wa', '$password', 'calon_member')";
        if (mysqli_query($koneksi, $query_insert)) {
            $id_user = mysqli_insert_id($koneksi);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data user baru.']);
            exit;
        }
    }

    // Pastikan $id_user valid sebelum lanjut menyimpan paket
    if ($id_user > 0) {
        // 4. Proses Upload Gambar Bukti
        $nama_file_bukti = NULL;
        if ($metode == 'qris' && isset($_FILES['regBukti']['name']) && $_FILES['regBukti']['name'] != '') {
            $ext = pathinfo($_FILES['regBukti']['name'], PATHINFO_EXTENSION);
            $nama_bersih = str_replace(' ', '_', preg_replace('/[^A-Za-z0-9 ]/', '', $nama));
            $nama_file_bukti = "Bukti_Daftar_" . $nama_bersih . "_" . date('dmy_His') . "." . $ext;
            
            // Simpan ke folder uploads
            move_uploaded_file($_FILES['regBukti']['tmp_name'], 'uploads/' . $nama_file_bukti);
        }

        // 5. Simpan ke tabel membership dengan status PENDING
        $query_member = "INSERT INTO membership (id_user, jenis_pengajuan, paket_bulan, total_harga, tgl_mulai, tgl_berakhir, metode_bayar, bukti_bayar, status) 
                         VALUES ($id_user, 'daftar', $durasi, $harga, '$tgl_mulai', '$tgl_berakhir', '$metode', '$nama_file_bukti', 'pending')";
        
        if (mysqli_query($koneksi, $query_member)) {
            echo json_encode(['status' => 'success']); // Beritahu JS kalau sukses
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal memproses data paket.']);
        }
    }
    exit; // Hentikan PHP
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Member - Vanda Gym Classic</title>
   <link rel="stylesheet" href="css/style.css">
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
                <input type="text" id="regNama" name="regNama" class="form-control" required placeholder="">
            </div>
            
            <div class="grid-2">
                <div class="form-group">
                    <label>Nomor WhatsApp</label>
                    <input type="text" id="regHp" name="regHp" class="form-control" required oninput="validasiAngka(this)" placeholder="0812xxxx">
                    <div id="errorHp" class="error-msg">Wajib angka saja.</div>
                </div>
                <div class="form-group">
                    <label>Alamat Email</label>
                    <input type="email" id="regEmail" name="regEmail" class="form-control" required placeholder="nama@email.com">
                </div>
            </div>

            <div class="section-divider">2. Keamanan Akun</div>
            <div class="form-group">
                <label>Password Akun</label>
                <div style="position: relative;">
                    <input type="password" id="regPass" name="regPass" class="form-control" required placeholder="Kombinasi angka & huruf" oninput="cekPassword(this)" style="padding-right: 50px;">
                    <span id="togglePassword" onclick="toggleVisibility()" style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); cursor: pointer; min-height: 44px; min-width: 44px; display: flex; align-items: center; justify-content: center; z-index: 10;">
                        <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </span>
                </div>
                <div id="errorPass" class="error-msg">Gunakan minimal satu huruf dan satu angka.</div>
                <p style="font-size: 0.8rem; color: #888; margin-top: 8px;">Gunakan email Anda sebagai identitas login nantinya.</p>
            </div>

            <div class="section-divider">3. Paket Latihan & Pembayaran</div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Pilih Durasi</label>
                    <select id="regPaket" name="regPaket" class="form-control" onchange="updateNominal()" required style="cursor: pointer;">
                        <option value="" disabled selected>-- Pilih Paket --</option>
                        <option value="175000" data-nama="1 Bulan Gym">1 Bulan Gym</option>
                        <option value="350000" data-nama="2 Bulan Gym">2 Bulan Gym</option>
                        <option value="525000" data-nama="3 Bulan Gym">3 Bulan Gym</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tanggal Mulai</label>
                    <input type="date" id="regTgl" name="regTgl" class="form-control" required>
                </div>
            </div>

            <div class="nominal-box" id="boxNominal">
                <span>Total Tagihan Pendaftaran:</span>
                <span id="textNominal">Rp 0</span>
            </div>

            <div class="form-group">
                <label>Metode Pembayaran</label>
                <div class="payment-methods">
                    <label class="pay-method active" id="labelQris">
                        <input type="radio" name="metodeBayar" value="qris" checked onchange="ubahMetode()">
                        <span>📱 Transfer / QRIS</span>
                    </label>
                    <label class="pay-method" id="labelTunai">
                        <input type="radio" name="metodeBayar" value="tunai" onchange="ubahMetode()">
                        <span>💵 Bayar Tunai (Kasir)</span>
                    </label>
                </div>
            </div>

            <div id="detailQris" class="pay-details" style="display: block;">
                <div class="qris-box">
                    <p style="font-size: 0.85rem; color: #ccc;">Scan QR Code di bawah atau transfer ke:<br><strong>BCA 123-456-789 a.n Vanda Gym</strong></p>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=Pembayaran+Member+Baru+Vanda+Gym" alt="QRIS Vanda Gym">
                </div>
                <div class="file-upload-wrapper">
                    <label style="font-size: 0.8rem; color: #888; margin-bottom: 5px; display: block;">Upload Bukti Transfer (Wajib)</label>
                    <div class="btn-upload">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/></svg>
                        <span id="namaFile">Pilih Gambar / Screenshot...</span>
                    </div>
                    <input type="file" id="regBukti" name="regBukti" accept="image/*" required onchange="tampilkanNamaFile(this)">
                </div>
            </div>

            <div id="detailTunai" class="pay-details">
                <div style="text-align: center;">
                    <svg viewBox="0 0 24 24" width="40" height="40" fill="var(--accent-gold)" style="margin-bottom: 10px;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.41 16.09V20h-2.67v-1.93c-1.71-.36-3.16-1.46-3.27-3.4h1.96c.1 1.05.82 1.87 2.65 1.87 1.96 0 2.4-.98 2.4-1.59 0-.83-.44-1.61-2.67-2.14-2.48-.6-4.18-1.62-4.18-3.67 0-1.72 1.39-2.84 3.11-3.21V4h2.67v1.95c1.86.45 2.79 1.86 2.85 3.39H14.3c-.05-1.11-.64-1.87-2.22-1.87-1.5 0-2.4.68-2.4 1.64 0 .84.65 1.39 2.67 1.91 2.95.73 4.18 1.9 4.18 3.91-.01 1.83-1.38 2.83-3.12 3.16z"/></svg>
                    <p style="font-size: 0.8rem; color: #888; margin-top: 5px; background: #0a0a0a; padding: 10px; border-radius: 4px; border: 1px solid #333;">
                        <strong>Silahkan kirim pendaftaran</strong> dan datang ke resepsionis untuk melakukan pembayaran agar akun aktif.
                    </p>
                </div>
            </div>

            <button type="submit" class="btn-submit">Kirim Pendaftaran</button>

            <div class="login-footer">
                <div>
                    <span style="color: #888;">Menunggu verifikasi Admin?</span>
                    <a href="cek_status.php">Cek Status</a>
                </div>
                <div>
                    <span style="color: #888;">Sudah memiliki akun?</span>
                    <a href="login.php">Login Sekarang</a>
                </div>
            </div>
        </form>
    </div>

    <div class="modal-overlay" id="modalOverlay">
        <div class="modal-box" id="modalContent"></div>
    </div>

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
                eyeIcon.innerHTML = `
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                    <line x1="1" y1="1" x2="23" y2="23"></line>
                `;
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = `
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                `;
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

            document.getElementById('regBukti').required = isQris;
        }

        function tampilkanNamaFile(input) {
            const namaFileEl = document.getElementById('namaFile');
            if (input.files && input.files[0]) {
                namaFileEl.innerText = input.files[0].name;
                namaFileEl.style.color = "white";
            } else {
                namaFileEl.innerText = "Pilih Gambar / Screenshot...";
                namaFileEl.style.color = "var(--accent-gold)";
            }
        }

        function validasiDanBukaDraf(e) {
            e.preventDefault();
            
            const namaLengkap = document.getElementById('regNama').value;
            const noHp = document.getElementById('regHp').value;
            const email = document.getElementById('regEmail').value;
            const pass = document.getElementById('regPass').value;
            const tglMulai = document.getElementById('regTgl').value;
            const metode = document.querySelector('input[name="metodeBayar"]:checked').value;
            
            const regex = /^(?=.*[0-9])(?=.*[a-zA-Z])([a-zA-Z0-9]+)$/;

            if (!regex.test(pass)) {
                alert("Harap perbaiki kesalahan (tulisan merah) pada formulir sebelum melanjutkan.");
                return;
            }

            const selectPaket = document.getElementById('regPaket');
            const namaPaket = selectPaket.options[selectPaket.selectedIndex].getAttribute('data-nama');
            const hargaPaket = "Rp " + parseInt(selectPaket.value).toLocaleString('id-ID');

            const modal = document.getElementById('modalOverlay');
            const content = document.getElementById('modalContent');
            modal.style.display = 'flex';
            
            content.innerHTML = `
                <h3 style="color:var(--accent-gold); border-bottom:1px solid #333; padding-bottom:10px; text-align:center; font-size:1.3rem;">Konfirmasi Data</h3>
                <div style="margin:15px 0; font-size: 0.9rem; color:#ccc;">
                    <div class="draf-item"><span style="color:#888;">Nama:</span> <span style="text-align:right;">${namaLengkap}</span></div>
                    <div class="draf-item"><span style="color:#888;">Kontak:</span> <span style="text-align:right;">${noHp} <br> ${email}</span></div>
                    <div class="draf-item"><span style="color:#888;">Paket Latihan:</span> <span style="text-align:right;">${namaPaket} <br> Mulai: ${tglMulai}</span></div>
                    <div class="draf-item"><span style="color:#888;">Metode:</span> <span style="text-align:right;">${metode.toUpperCase()}</span></div>
                    <div class="draf-item" style="border-top:1px dashed #333; margin-top:5px; padding-top:15px;">
                        <span style="color:var(--text-light); font-weight:bold; font-size:1.1rem;">Total Tagihan:</span> 
                        <span style="color:var(--accent-gold); font-weight:bold; font-size:1.2rem;">${hargaPaket}</span>
                    </div>
                </div>
                <p style="font-size:0.8rem; color:#888; margin-bottom:15px; text-align:center;">Pastikan data pendaftaran Anda sudah benar.</p>
                <button class="btn-submit" style="margin-top:0;" onclick="kirimFinal('${metode}', '${email}')">Kirim Pendaftaran</button>
                <button type="button" onclick="document.getElementById('modalOverlay').style.display='none'" style="background:transparent; border:1px solid #333; border-radius:4px; color:#888; width:100%; margin-top:10px; cursor:pointer; min-height:44px; transition:0.3s;" onmouseover="this.style.background='#1a1a1a'" onmouseout="this.style.background='transparent'">Kembali Edit</button>
            `;
        }

        function kirimFinal(metode, email) {
            const content = document.getElementById('modalContent');
            const form = document.getElementById('formPendaftaran');
            
            content.innerHTML = `<div style="text-align:center;"><p style="font-weight:bold; color:var(--accent-gold);">Sedang menyimpan data pendaftaran ke sistem...</p></div>`;

            const formData = new FormData(form);
            formData.append('action', 'register');

            fetch('daftar.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    let pesanStatus = "";
                    let instruksi = "";
                    let tombolWa = "";

                    if (metode === 'tunai') {
                        pesanStatus = `<strong style="color: #ffc107;">Menunggu Pembayaran</strong>`;
                        instruksi = `Silakan datang ke resepsionis Vanda Gym untuk melakukan pembayaran tunai. Akun Anda akan diaktifkan setelah pembayaran diselesaikan.`;
                    } else {
                        pesanStatus = `<strong style="color: #ffc107;">Sedang Diproses</strong>`;
                        instruksi = `Admin sedang memverifikasi bukti pembayaran Anda. Jika sudah aktif, Anda bisa login menggunakan email yang didaftarkan.`;
                        
                        const pesanWa = encodeURIComponent(`Halo Admin Vanda Gym, saya baru saja melakukan pendaftaran member baru dengan email *${email}*. Tolong dicek ya. Terima kasih.`);
                        const linkWa = `https://wa.me/6282148556601?text=${pesanWa}`;
                        
                        tombolWa = `
                        <a href="${linkWa}" target="_blank" style="display: flex; align-items: center; justify-content: center; background-color: #25D366; color: white; text-decoration: none; padding: 10px; border-radius: 4px; font-weight: bold; margin-top: 15px; min-height: 44px; transition: 0.3s; font-size: 0.9rem;">
                            📱 Konfirmasi ke WhatsApp CS
                        </a>`;
                    }

                    content.innerHTML = `
                        <h3 style="color:var(--accent-gold); text-align:center; font-size:1.4rem;">Pendaftaran Berhasil!</h3>
                        <p style="margin:10px 0; text-align:center; font-size:0.95rem;">Status: ${pesanStatus}</p>
                        <div style="background:#050505; padding:15px; border:1px solid #222; border-radius:4px; font-size:0.85rem; line-height:1.6; text-align:left;">
                            <strong style="color:white;">Langkah Selanjutnya:</strong><br>
                            <span style="color:#aaa;">${instruksi}</span>
                            ${tombolWa}
                        </div>
                        <button class="btn-submit" style="margin-top:20px;" onclick="window.location.href='cek_status.php'">Cek Status Pendaftaran</button>
                        <button onclick="window.location.href='index.php'" style="background:transparent; border:none; color:#888; width:100%; margin-top:5px; cursor:pointer; min-height:44px;">Kembali ke Beranda</button>
                    `;
                } else {
                    content.innerHTML = `
                        <h3 style="color:#ff4d4d; text-align:center; font-size:1.4rem;">Pendaftaran Gagal!</h3>
                        <p style="margin:15px 0; text-align:center; font-size: 0.95rem;">${data.message}</p>
                        <button onclick="document.getElementById('modalOverlay').style.display='none'" style="background:transparent; border:1px solid #333; border-radius:4px; color:#888; width:100%; margin-top:10px; cursor:pointer; min-height:44px;">Kembali ke Form</button>
                    `;
                }
            })
            .catch(error => {
                content.innerHTML = `<p style="color:#ff4d4d; text-align:center;">Terjadi kesalahan sistem. Silakan coba lagi nanti.</p>
                <button onclick="document.getElementById('modalOverlay').style.display='none'" style="background:transparent; border:1px solid #333; border-radius:4px; color:#888; width:100%; margin-top:10px; cursor:pointer; min-height:44px;">Kembali</button>`;
            });
        }
    </script>
</body>
</html>