<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dasbor Member - Vanda Gym Classic</title>
    <style>
        :root {
            --bg-dark: #000000;
            --primary-red: #8E1616;
            --accent-gold: #E8C999;
            --text-light: #F8EEDF;
            --input-bg: #111111;
            --success-green: #28a745;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: var(--bg-dark); 
            color: var(--text-light); 
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* ================= NAVBAR ================= */
        header { 
            background-color: rgba(10, 10, 10, 0.95); 
            padding: 10px 5%; 
            display: flex; justify-content: space-between; align-items: center; 
            position: sticky; top: 0; z-index: 100; 
            border-bottom: 2px solid var(--primary-red); 
        }
        
        .logo img { height: 70px; width: auto; object-fit: contain; }

        nav { display: flex; align-items: center; flex-wrap: wrap; justify-content: flex-end;}
        nav a.nav-link { 
            color: var(--text-light); text-decoration: none; 
            margin-left: 20px; font-weight: 600; transition: 0.3s;
            min-height: 44px; display: inline-flex; align-items: center;
        }
        nav a.nav-link:hover { color: var(--accent-gold); }
        nav a.active { color: var(--accent-gold); border-bottom: 2px solid var(--accent-gold); }

        .btn-logout { 
            border: 2px solid var(--primary-red); padding: 0 20px; border-radius: 4px; 
            color: var(--primary-red); margin-left: 20px; background: transparent;
            font-weight: bold; font-size: 1rem; min-height: 44px; min-width: 44px;
            cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; justify-content: center;
        }
        .btn-logout:hover { background-color: var(--primary-red); color: white; }

        .profile-icon {
            margin-left: 20px;
            color: var(--text-light);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: 0.3s;
            min-height: 44px;
        }
        .profile-icon svg { width: 28px; height: 28px; }
        .profile-icon:hover { color: var(--accent-gold); transform: scale(1.1); }

        /* ================= BANNER PENGUMUMAN ================= */
        .announcement-banner {
            background-color: #1a1a1a;
            border-bottom: 1px solid #333;
            color: var(--text-light);
            padding: 16px 25px; 
            text-align: center;
            font-size: 1.1rem; 
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px; 
            z-index: 99;
        }
        .announcement-badge {
            background-color: var(--primary-red);
            color: white;
            padding: 5px 12px; 
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.85rem; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(142, 22, 22, 0.7); }
            70% { box-shadow: 0 0 0 8px rgba(142, 22, 22, 0); }
            100% { box-shadow: 0 0 0 0 rgba(142, 22, 22, 0); }
        }
        .announcement-text { font-weight: 500; }

        /* Container Utama Dasbor */
        .dashboard-container { padding: 40px 5%; max-width: 1200px; margin: 0 auto; }

        /* Notifikasi Alert */
        .alert-box {
            background-color: rgba(232, 201, 153, 0.1); border: 1px solid var(--accent-gold);
            color: var(--accent-gold); padding: 15px 20px; border-radius: 8px;
            margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;
        }
        .alert-box.danger { background-color: rgba(142, 22, 22, 0.15); border-color: var(--primary-red); color: #ff4d4d; }

        /* Kartu Profil Member */
        .dash-card {
            background-color: #0a0a0a; border: 1px solid #222; border-radius: 8px; padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.5); position: relative; overflow: hidden; margin-bottom: 30px;
        }
        .dash-card::before {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px;
            background: var(--accent-gold);
        }
        .dash-card.card-danger::before { background: var(--primary-red); }

        .profile-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px;}
        .user-info h2 { color: var(--text-light); font-size: 2rem; text-transform: uppercase; margin-bottom: 5px;}
        .user-info p { color: #888; font-size: 1rem; }
        
        .status-badge {
            background: var(--success-green); color: white; padding: 6px 20px; 
            border-radius: 20px; font-weight: bold; font-size: 0.9rem; letter-spacing: 1px;
        }
        .status-badge.danger { background: var(--primary-red); }

        /* Grid Detail Membership dibuat sejajar 4 kolom jika layar lebar */
        .membership-details { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 20px; border-top: 1px dashed #333; padding-top: 25px;
        }
        .detail-item span { display: block; color: #888; font-size: 0.85rem; margin-bottom: 5px;}
        .detail-item strong { display: block; color: var(--accent-gold); font-size: 1.3rem; }
        .detail-item strong.danger-text { color: #ff4d4d; }
        .payment-proof { font-size: 0.8rem; color: #aaa; font-weight: normal; display: block; margin-top: 3px;}

        /* ================= PENGATURAN NOTIFIKASI ================= */
        .setting-methods { display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap; }
        .set-method {
            flex: 1; border: 1px solid #333; border-radius: 6px; padding: 12px 10px;
            text-align: center; cursor: pointer; transition: 0.3s; background: #151515;
            position: relative; min-width: 140px;
        }
        .set-method input { position: absolute; opacity: 0; cursor: pointer; }
        .set-method span { font-weight: bold; color: #888; display: block; font-size: 0.85rem;}
        
        .set-method.active { border-color: var(--accent-gold); background: rgba(232, 201, 153, 0.1); }
        .set-method.active span { color: var(--accent-gold); }

        .btn-outline-gold {
            display: inline-flex; align-items: center; justify-content: center;
            background: transparent; border: 1px solid var(--accent-gold);
            color: var(--accent-gold); text-decoration: none; padding: 10px 20px;
            border-radius: 4px; font-weight: bold; font-size: 0.9rem;
            transition: 0.3s; cursor: pointer;
        }
        .btn-outline-gold:hover:not(:disabled) { background: var(--accent-gold); color: #000; }
        .btn-outline-gold:disabled { opacity: 0.7; cursor: wait; }

        .btn-simulasi-notif {
            display: inline-flex; align-items: center; justify-content: center;
            background: transparent; border: 1px dashed #555; color: #aaa;
            padding: 10px 20px; border-radius: 4px; font-weight: bold; font-size: 0.9rem;
            transition: 0.3s; cursor: pointer; margin-left: 10px;
        }
        .btn-simulasi-notif:hover { background: #222; color: #fff; border-color: #888;}

        /* Popup Simulasi */
        .popup-simulasi {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.8); z-index: 2000; justify-content: center; align-items: center;
        }
        .popup-box { background: #111; border: 2px solid var(--accent-gold); padding: 30px; border-radius: 8px; max-width: 400px; text-align: center; }

        /* Grid Bawah: Menu Aksi Cepat */
        .action-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 40px;}
        .action-btn {
            background: #111; border: 1px solid #333; border-radius: 8px; padding: 25px;
            text-align: center; color: var(--text-light); text-decoration: none; transition: 0.3s;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            min-height: 120px;
        }
        .action-btn:hover { border-color: var(--accent-gold); transform: translateY(-5px); background: #1a1a1a;}
        .action-btn.locked { opacity: 0.5; border-color: #222; cursor: not-allowed; pointer-events: none;}
        .action-btn.locked h3 { color: #777; }
        .action-icon { font-size: 2.5rem; margin-bottom: 15px; }
        .action-btn h3 { color: var(--accent-gold); font-size: 1.1rem; margin-bottom: 5px; }
        .action-btn p { color: #888; font-size: 0.85rem; }

        .btn-primary { 
            background-color: var(--primary-red); color: white; padding: 10px 20px; 
            border: none; border-radius: 4px; font-weight: bold; cursor: pointer; transition: 0.3s;
            min-height: 44px; text-decoration: none; display: inline-block;
        }
        .btn-primary:hover { background-color: #a81a1a; }

        /* Tombol WA & Chatbot */
        .wa-btn { position: fixed; bottom: 30px; left: 30px; background-color: #25D366; color: white; border-radius: 50%; width: 60px; height: 60px; display: flex; justify-content: center; align-items: center; box-shadow: 0 4px 15px rgba(0,0,0,0.6); z-index: 1000; transition: 0.3s; text-decoration: none; }
        .wa-btn:hover { transform: scale(1.1); background-color: #1ebe57; }
        .wa-btn svg { width: 35px; height: 35px; fill: currentColor; }

        .chatbot-btn { position: fixed; bottom: 30px; right: 30px; background-color: var(--primary-red); color: white; border: none; border-radius: 50%; width: 60px; height: 60px; font-size: 28px; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.6); z-index: 1000; transition: 0.3s; display: flex; justify-content: center; align-items: center; text-decoration: none; }
        .chatbot-btn:hover { transform: scale(1.1); }
        .chatbot-btn.locked { background-color: #333; color: #888; border: 2px solid #555; cursor: not-allowed; pointer-events: none; }

        /* Responsive */
        @media (max-width: 768px) {
            header { flex-direction: column; padding: 15px; }
            nav { margin-top: 15px; justify-content: center;}
            nav a.nav-link { margin: 5px 10px; font-size: 0.9rem;}
            .btn-logout { margin-left: 0; margin-top: 10px; width: 100%; text-align: center;}
            .profile-icon { margin-top: 15px; }
            .announcement-banner { flex-direction: column; text-align: center; }
            .btn-simulasi-notif { margin-left: 0; margin-top: 10px; width: 100%; }
            .btn-outline-gold { width: 100%; max-width: none; }
            .profile-header { flex-direction: column; gap: 15px; }
        }
    </style>
</head>
<body>

    <header>
        <div class="logo">
            <img src="assets/logo.png" alt="Vanda Gym Classic Logo">
        </div>
        <nav>
            <a href="member_dasbor.php" class="nav-link active">Dasbor</a>
            <a href="profil_gym_member.php" class="nav-link">Profil Gym</a>
            <a href="chatbot_member.php" id="navChatbot" class="nav-link">Chatbot AI</a>
            <a href="kalkulator.php?source=dasbor" class="nav-link">Kalkulator Gizi</a>
            <a href="galeri_member.php" id="navGaleri" class="nav-link">Galeri Gym</a>
            <button class="btn-logout" onclick="window.location.href='login.php'">Keluar</button>
            <a href="profil_member.php" class="profile-icon" title="Profil Saya">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </a>
        </nav>
    </header>

    <div class="announcement-banner" id="infoBanner">
        <span class="announcement-badge">Info Terkini</span>
        <span class="announcement-text">Gym TUTUP pada hari Jumat, 1 Mei 2026 karena libur nasional. Buka kembali hari Sabtu.</span>
    </div>

    <div class="dashboard-container" id="mainDashboard">
        </div>

    <div id="popupSimulasi" class="popup-simulasi">
        <div class="popup-box">
            <div id="popupIcon" style="font-size: 3rem; margin-bottom: 10px;">📧</div>
            <h3 id="popupTitle" style="color: var(--accent-gold); margin-bottom: 10px;">Simulasi Email</h3>
            <p id="popupMsg" style="color: #ccc; font-size: 0.9rem; margin-bottom: 20px;">Pesan simulasi muncul di sini.</p>
            <button onclick="document.getElementById('popupSimulasi').style.display='none'" class="btn-primary">Tutup</button>
        </div>
    </div>

    <a href="https://wa.me/6282148556601" target="_blank" class="wa-btn" title="Hubungi CS via WhatsApp">
        <svg viewBox="0 0 24 24">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/>
        </svg>
    </a>

    <a href="chatbot_member.php" id="floatingChatbot" class="chatbot-btn" title="Tanya Chatbot AI Vanda">🤖</a>

    <script>
        // ================= PENGATURAN & SIMULASI NOTIFIKASI =================
        function ubahNotif(inputEl) {
            document.querySelectorAll('.set-method').forEach(el => el.classList.remove('active'));
            inputEl.closest('.set-method').classList.add('active');
        }

        function simpanNotif(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
            const prevText = btn.innerText;
            
            const selectedVal = document.querySelector('input[name="prefNotif"]:checked').value;
            localStorage.setItem('vanda_notif_pref', selectedVal);

            btn.innerText = "Menyimpan...";
            btn.disabled = true;

            setTimeout(() => {
                btn.innerText = "Tersimpan! ✔️";
                btn.style.backgroundColor = "var(--success-green)";
                btn.style.color = "white";
                btn.style.borderColor = "var(--success-green)";
                
                setTimeout(() => {
                    btn.innerText = prevText;
                    btn.style.backgroundColor = "transparent";
                    btn.style.color = "var(--accent-gold)";
                    btn.style.borderColor = "var(--accent-gold)";
                    btn.disabled = false;
                }, 2000);
            }, 800);
        }

        function testSimulasiNotif() {
            const savedPref = localStorage.getItem('vanda_notif_pref') || 'wa';
            const namaMember = "Ahsana Azmiara";
            
            if (savedPref === 'wa') {
                const pesanWa = encodeURIComponent(`[Sistem Vanda Gym] Halo ${namaMember}, ini adalah pengingat otomatis. Masa aktif membership Anda tersisa 7 Hari lagi. Harap segera melakukan perpanjangan.`);
                window.open(`https://wa.me/6282148556601?text=${pesanWa}`, '_blank');
            } else if (savedPref === 'email') {
                document.getElementById('popupIcon').innerText = "📧";
                document.getElementById('popupTitle').innerText = "Simulasi Inbox Email";
                document.getElementById('popupMsg').innerHTML = `Sistem berhasil mengirimkan email tagihan otomatis ke alamat email Anda.`;
                document.getElementById('popupSimulasi').style.display = 'flex';
            } else if (savedPref === 'dasbor') {
                document.getElementById('popupIcon').innerText = "🔕";
                document.getElementById('popupTitle').innerText = "Simulasi Mode Senyap";
                document.getElementById('popupMsg').innerHTML = `Anda memilih "Hanya Dasbor". Sistem <strong>TIDAK</strong> akan mengirim pesan WA atau Email. Anda hanya akan melihat kotak peringatan merah di atas halaman ini saat login.`;
                document.getElementById('popupSimulasi').style.display = 'flex';
            }
        }

        // ================= RENDER TAMPILAN DASHBOARD =================
        const urlParams = new URLSearchParams(window.location.search);
        const statusMember = urlParams.get('status') || 'aktif';

        const dashboardContainer = document.getElementById('mainDashboard');
        const floatingChatbot = document.getElementById('floatingChatbot');
        const navChatbot = document.getElementById('navChatbot');
        const navGaleri = document.getElementById('navGaleri');

        const savedNotif = localStorage.getItem('vanda_notif_pref') || 'wa';
        const isWa = savedNotif === 'wa' ? 'checked' : '';
        const isEmail = savedNotif === 'email' ? 'checked' : '';
        const isDasbor = savedNotif === 'dasbor' ? 'checked' : '';
        const activeWa = savedNotif === 'wa' ? 'active' : '';
        const activeEmail = savedNotif === 'email' ? 'active' : '';
        const activeDasbor = savedNotif === 'dasbor' ? 'active' : '';

        const komponenNotifikasi = `
            <div class="dash-card" style="margin-bottom: 40px; border-top-color: #333;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                    <span style="font-size: 1.5rem;">🔔</span>
                    <h3 style="color: var(--text-light); font-size: 1.1rem;">Pengaturan Pengingat Masa Aktif</h3>
                </div>
                <p style="color: #888; font-size: 0.85rem; margin-bottom: 15px;">Pilih bagaimana Anda ingin menerima notifikasi tagihan membership dari Admin.</p>
                
                <form onsubmit="simpanNotif(event)">
                    <div class="setting-methods">
                        <label class="set-method ${activeWa}" title="Kirim notifikasi via WhatsApp">
                            <input type="radio" name="prefNotif" value="wa" ${isWa} onchange="ubahNotif(this)">
                            <span>🟢 WhatsApp</span>
                        </label>
                        <label class="set-method ${activeEmail}" title="Kirim notifikasi via Email">
                            <input type="radio" name="prefNotif" value="email" ${isEmail} onchange="ubahNotif(this)">
                            <span>📧 Email</span>
                        </label>
                        <label class="set-method ${activeDasbor}" title="Jangan kirim pesan, hanya tampilkan alert merah di Dasbor web">
                            <input type="radio" name="prefNotif" value="dasbor" ${isDasbor} onchange="ubahNotif(this)">
                            <span>🔕 Hanya Dasbor</span>
                        </label>
                    </div>
                    <div style="display: flex; align-items: center; flex-wrap: wrap;">
                        <button type="submit" class="btn-outline-gold" style="width: auto;">Simpan Pengaturan</button>
                        <button type="button" class="btn-simulasi-notif" onclick="testSimulasiNotif()">▶ Test Simulasi Notifikasi</button>
                    </div>
                </form>
            </div>
        `;

        if (statusMember === 'kadaluarsa') {
            navChatbot.style.color = "#777";
            navChatbot.onclick = function(e) {
                e.preventDefault();
                alert('Fitur Chatbot AI terkunci. Silakan perpanjang membership Anda.');
            };

            navGaleri.style.color = "#777";
            navGaleri.onclick = function(e) {
                e.preventDefault();
                alert('Galeri Eksklusif terkunci. Silakan perpanjang membership Anda.');
            };

            floatingChatbot.classList.add('locked');
            floatingChatbot.title = "AI Terkunci";
            floatingChatbot.href = "#";
            floatingChatbot.onclick = (e) => {
                e.preventDefault();
                alert('Fitur Chatbot AI terkunci. Silakan perpanjang membership Anda.');
            };

            dashboardContainer.innerHTML = `
                <div class="alert-box danger">
                    <div>
                        <strong>Perhatian:</strong> Masa aktif membership Anda telah <strong>KEDALUWARSA</strong>.
                    </div>
                    <a href="perpanjang.php" class="btn-primary" style="min-height: 35px; padding: 5px 15px; font-size: 0.9rem;">Perpanjang Sekarang</a>
                </div>

                <div class="dash-card card-danger">
                    <div class="profile-header">
                        <div class="user-info">
                            <h2>Ahsana Azmiara</h2>
                            <p>Username: ahsana123</p>
                        </div>
                        <div class="status-badge danger">KADALUWARSA</div>
                    </div>

                    <div class="membership-details">
                        <div class="detail-item">
                            <span>Paket Terakhir</span>
                            <strong>1 Bulan Gym</strong>
                        </div>
                        <div class="detail-item">
                            <span>Status Pembayaran</span>
                            <strong style="font-size: 1rem; color: var(--primary-red);">Menunggu Tagihan</strong>
                        </div>
                        <div class="detail-item">
                            <span>Tanggal Mulai</span>
                            <strong style="color: var(--text-light); font-size: 1rem;">15 Maret 2026</strong>
                        </div>
                        <div class="detail-item">
                            <span>Tanggal Berakhir</span>
                            <strong class="danger-text">15 April 2026</strong>
                        </div>
                    </div>
                </div>

                ${komponenNotifikasi}

                <h3 style="color: var(--accent-gold); border-bottom: 1px solid #333; padding-bottom: 10px; margin-bottom: 20px;">Aksi Cepat</h3>

                <div class="action-grid">
                    <a href="kalkulator.php?source=dasbor" class="action-btn">
                        <div class="action-icon">⚖️</div>
                        <h3>Kalkulator Gizi</h3>
                        <p>Hitung ulang kalori & protein harian.</p>
                    </a>

                    <a href="perpanjang.php" class="action-btn" style="border-color: var(--primary-red);">
                        <div class="action-icon">💳</div>
                        <h3 style="color: var(--primary-red);">Perpanjang Member</h3>
                        <p>Bayar tagihan bulan berikutnya.</p>
                    </a>

                    <a href="#" class="action-btn locked" onclick="event.preventDefault(); alert('Fitur AI terkunci. Silakan perpanjang membership Anda terlebih dahulu.')">
                        <div class="action-icon">🔒</div>
                        <h3>Tanya Chatbot AI Vanda</h3>
                        <p>Fitur khusus member aktif.</p>
                    </a>

                    <a href="#" class="action-btn locked" onclick="event.preventDefault(); alert('Galeri Eksklusif terkunci. Silakan perpanjang membership Anda terlebih dahulu.')">
                        <div class="action-icon">🔒</div>
                        <h3>Video Latihan</h3>
                        <p>Fitur khusus member aktif.</p>
                    </a>
                </div>
            `;
        } else {
            dashboardContainer.innerHTML = `
                <div class="alert-box">
                    <div>
                        <strong>Info:</strong> Masa aktif membership Anda tersisa <strong style="font-size:1.2rem;">7 Hari</strong> lagi.
                    </div>
                    <a href="perpanjang.php" class="btn-primary" style="min-height: 35px; padding: 5px 15px; font-size: 0.9rem;">Perpanjang Sekarang</a>
                </div>

                <div class="dash-card">
                    <div class="profile-header">
                        <div class="user-info">
                            <h2>Ahsana Azmiara</h2>
                            <p>Username: ahsana123</p>
                        </div>
                        <div class="status-badge">AKTIF</div>
                    </div>

                    <div class="membership-details">
                        <div class="detail-item">
                            <span>Paket Saat Ini</span>
                            <strong>1 Bulan Gym</strong>
                        </div>
                        <div class="detail-item">
                            <span>Status Pembayaran</span>
                            <strong style="font-size: 1rem; color: var(--success-green);">Lunas <br><span class="payment-proof">(Via QRIS/Transfer)</span></strong>
                        </div>
                        <div class="detail-item">
                            <span>Tanggal Mulai</span>
                            <strong style="color: var(--text-light); font-size: 1rem;">25 April 2026</strong>
                        </div>
                        <div class="detail-item">
                            <span>Tanggal Berakhir</span>
                            <strong style="color: var(--text-light); font-size: 1rem;">25 Mei 2026</strong>
                        </div>
                    </div>
                </div>

                ${komponenNotifikasi}

                <h3 style="color: var(--accent-gold); border-bottom: 1px solid #333; padding-bottom: 10px; margin-bottom: 20px;">Aksi Cepat</h3>

                <div class="action-grid">
                    <a href="kalkulator.php?source=dasbor" class="action-btn">
                        <div class="action-icon">⚖️</div>
                        <h3>Kalkulator Gizi</h3>
                        <p>Hitung ulang kalori & protein harian.</p>
                    </a>

                    <a href="perpanjang.php" class="action-btn">
                        <div class="action-icon">💳</div>
                        <h3>Perpanjang Member</h3>
                        <p>Bayar tagihan bulan berikutnya.</p>
                    </a>

                    <a href="chatbot_member.php" class="action-btn">
                        <div class="action-icon">🤖</div>
                        <h3>Tanya Chatbot AI Vanda</h3>
                        <p>Cek kalori makanan via foto.</p>
                    </a>

                    <a href="galeri_member.php" class="action-btn">
                        <div class="action-icon">🎥</div>
                        <h3>Video Latihan</h3>
                        <p>Panduan gerakan alat gym.</p>
                    </a>
                </div>
            `;
        }
    </script>
</body>
</html>