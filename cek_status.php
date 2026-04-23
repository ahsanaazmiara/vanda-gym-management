<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Status Pendaftaran - Vanda Gym Classic</title>
    <style>
        :root {
            --bg-dark: #000000;
            --primary-red: #8E1616;
            --accent-gold: #E8C999;
            --text-light: #F8EEDF;
            --input-bg: #111111;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: var(--bg-dark); 
            color: var(--text-light); 
            display: flex; justify-content: center; align-items: center;
            min-height: 100vh; padding: 40px 20px;
        }

        .status-container {
            background-color: #0a0a0a;
            border: 1px solid #333; border-top: 4px solid var(--primary-red);
            border-radius: 8px; padding: 30px; width: 100%; max-width: 500px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.8);
            text-align: center;
            position: relative; 
        }

        .btn-back-square { 
            width: 44px; height: 44px; 
            background-color: #1a1a1a; border: 1px solid #333; 
            color: var(--accent-gold); border-radius: 4px;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; font-weight: bold; font-size: 1.2rem;
            margin-bottom: 20px;
            transition: 0.3s;
        }
        .btn-back-square:hover { background-color: var(--primary-red); color: white; border-color: var(--primary-red); }

        .form-group { margin: 25px 0; text-align: left; position: relative; }
        .form-group label { display: block; margin-bottom: 8px; color: #ccc; }
        
        .form-control {
            width: 100%; padding: 10px 15px; min-height: 44px;
            background-color: var(--input-bg); border: 1px solid #333;
            border-radius: 4px; color: white; font-size: 1rem;
            transition: 0.3s;
        }
        .form-control:focus { outline: none; border-color: var(--accent-gold); }
        .form-control.invalid { border-color: var(--primary-red); }

        .error-msg { color: #ff4d4d; font-size: 0.85rem; margin-top: 5px; display: none; }

        .btn-search {
            width: 100%; background-color: var(--primary-red); color: white;
            border: none; min-height: 48px; font-size: 1rem; font-weight: bold;
            border-radius: 4px; cursor: pointer; text-transform: uppercase;
            transition: 0.3s;
        }
        .btn-search:hover { background-color: #a81a1a; }

        /* Result Box Style */
        .result-box {
            margin-top: 30px; padding: 20px; border-radius: 4px;
            background: #111; border: 1px solid #222; display: none;
            text-align: left;
        }
        .status-badge {
            display: inline-block; padding: 5px 12px; border-radius: 20px;
            font-size: 0.85rem; font-weight: bold; margin-top: 10px;
        }
        .status-pending { background: #856404; color: #fff; } /* Menunggu Verifikasi */
        .status-cash { background: #004085; color: #fff; }    /* Bayar di Tempat */
        .status-active { background: #155724; color: #fff; }  /* Aktif */
        .status-rejected { background: #721c24; color: #fff; }/* Ditolak */

        /* ================= Tombol WA (Floating) ================= */
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
    </style>
</head>
<body>

    <div class="status-container">
        <a href="daftar.php" class="btn-back-square" title="Kembali">←</a>
        
        <h2 style="color: var(--accent-gold);">Cek Status Verifikasi</h2>
        <p style="color: #888; font-size: 0.9rem; margin-top: 10px;">
            Masukkan Username Anda untuk melihat status aktivasi membership.
        </p>

        <div class="form-group">
            <label>Username Pendaftaran</label>
            <input type="text" id="cekUser" class="form-control" placeholder="Minimal 6 karakter" oninput="cekUsername(this)">
            <div id="errorUser" class="error-msg">Username minimal harus 6 karakter.</div>
        </div>

        <button class="btn-search" onclick="cariStatus()">Cari Data</button>

        <div id="hasilCek" class="result-box">
            <div style="border-bottom: 1px solid #333; padding-bottom: 10px; margin-bottom: 10px;">
                <span style="color: #888; font-size: 0.85rem;">Nama Pendaftar:</span>
                <div id="resNama" style="font-weight: bold;">-</div>
            </div>
            
            <span style="color: #888; font-size: 0.85rem;">Status Verifikasi:</span>
            <div id="resStatus"></div>
            
            <div id="resPesan" style="margin-top: 15px; font-size: 0.85rem; color: #ccc; line-height: 1.4;"></div>
        </div>
    </div>

    <a href="https://wa.me/6282148556601?text=Halo%20Admin%20Vanda%20Gym,%20saya%20butuh%20bantuan%20terkait%20informasi%20pendaftaran%20member." target="_blank" class="wa-btn" title="Hubungi CS via WhatsApp">
        <svg viewBox="0 0 24 24">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/>
        </svg>
    </a>

    <script>
        function cekUsername(input) {
            const error = document.getElementById('errorUser');
            if (input.value.length < 6 && input.value.length > 0) {
                error.style.display = 'block';
                input.classList.add('invalid');
            } else {
                error.style.display = 'none';
                input.classList.remove('invalid');
            }
        }

        function cariStatus() {
            const user = document.getElementById('cekUser').value.trim();
            const resultBox = document.getElementById('hasilCek');
            const inputElement = document.getElementById('cekUser');
            const errorElement = document.getElementById('errorUser');
            
            if (!user) {
                alert("Silakan masukkan username terlebih dahulu.");
                return;
            }

            if (user.length < 6) {
                errorElement.style.display = 'block';
                inputElement.classList.add('invalid');
                return;
            }

            resultBox.style.display = 'block';
            const resNama = document.getElementById('resNama');
            const resStatus = document.getElementById('resStatus');
            const resPesan = document.getElementById('resPesan');

            resNama.innerText = user;

            // ==========================================
            // SIMULASI PROTOTIPE UNTUK MELIHAT PERBEDAAN
            // ==========================================
            
            // 1. Kondisi Jika AKTIF
            if (user.toLowerCase().includes('aktif')) {
                
                resStatus.innerHTML = '<span class="status-badge status-active">Aktif Terverifikasi</span>';
                resPesan.innerHTML = `
                    <strong style="color: var(--accent-gold);">Pendaftaran Berhasil!</strong><br>
                    Selamat, akun membership Anda sudah aktif. Anda sekarang memiliki akses penuh ke sistem Vanda Gym Classic.
                    
                    <a href="login.php" style="display: flex; align-items: center; justify-content: center; background-color: var(--accent-gold); color: #000; text-decoration: none; padding: 10px; border-radius: 4px; font-weight: bold; margin-top: 15px; min-height: 44px; transition: 0.3s; font-size: 0.9rem;">
                        🔑 Login ke Dasbor
                    </a>
                `;

            // 2. Kondisi Jika DITOLAK
            } else if (user.toLowerCase().includes('tolak') || user.toLowerCase().includes('gagal')) {
                
                resStatus.innerHTML = '<span class="status-badge status-rejected">Pendaftaran Ditolak</span>';
                
                // Pesan WA khusus untuk yang ditolak
                const pesanWaTolak = encodeURIComponent(`Halo Admin Vanda Gym, pendaftaran member saya dengan username *${user}* berstatus ditolak. Boleh mohon info perbaikannya?`);
                const linkWaTolak = `https://wa.me/6282148556601?text=${pesanWaTolak}`;

                resPesan.innerHTML = `
                    <strong style="color: #ff4d4d;">Verifikasi Gagal!</strong><br>
                    Pendaftaran Anda tidak dapat diverifikasi (kemungkinan karena bukti bayar tidak valid, buram, atau nominal tidak sesuai).
                    
                    <a href="${linkWaTolak}" target="_blank" style="display: flex; align-items: center; justify-content: center; background-color: #8E1616; color: white; border: 1px solid #ff4d4d; text-decoration: none; padding: 10px; border-radius: 4px; font-weight: bold; margin-top: 15px; min-height: 44px; transition: 0.3s; font-size: 0.9rem;">
                        📞 Tanya Alasan ke CS
                    </a>
                    <a href="daftar.php" style="display: flex; align-items: center; justify-content: center; background: transparent; color: #888; text-decoration: none; padding: 10px; border-radius: 4px; font-weight: bold; margin-top: 10px; min-height: 44px; transition: 0.3s; font-size: 0.9rem;">
                        Daftar Ulang
                    </a>
                `;

            // 3. Kondisi DEFAULT (Menunggu)
            } else {
                
                resStatus.innerHTML = '<span class="status-badge status-pending">Menunggu Verifikasi</span>';
                
                const pesanWa = encodeURIComponent(`Halo Admin Vanda Gym, saya ingin mengkonfirmasi pendaftaran member baru saya dengan username *${user}*. Apakah pembayarannya sudah diverifikasi? Terima kasih.`);
                const linkWa = `https://wa.me/6282148556601?text=${pesanWa}`;

                resPesan.innerHTML = `
                    <strong>Cara Cek Status:</strong><br>
                    Admin sedang memverifikasi data Anda. Jika sudah aktif, Anda bisa login menggunakan username yang didaftarkan.
                    
                    <a href="${linkWa}" target="_blank" style="display: flex; align-items: center; justify-content: center; background-color: #25D366; color: white; text-decoration: none; padding: 10px; border-radius: 4px; font-weight: bold; margin-top: 15px; min-height: 44px; transition: 0.3s; font-size: 0.9rem;">
                        📞 Konfirmasi ke WhatsApp CS
                    </a>
                `;
            }
        }
    </script>
</body>
</html>