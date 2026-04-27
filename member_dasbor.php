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
            position: relative; min-width: 140px; display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .set-method input { position: absolute; opacity: 0; cursor: pointer; }
        .set-method span { font-weight: bold; color: #888; display: flex; align-items: center; gap: 8px; font-size: 0.85rem;}
        
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
        .popup-icon { display: flex; justify-content: center; color: var(--accent-gold); margin-bottom: 15px; }

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
        .action-icon { margin-bottom: 15px; color: var(--accent-gold); }
        .action-btn.danger-border .action-icon { color: var(--primary-red); }
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

        .chatbot-btn { position: fixed; bottom: 30px; right: 30px; background-color: var(--primary-red); color: white; border: none; border-radius: 50%; width: 60px; height: 60px; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.6); z-index: 1000; transition: 0.3s; display: flex; justify-content: center; align-items: center; text-decoration: none; }
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
            <div id="popupIcon" class="popup-icon">
                </div>
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

    <a href="chatbot_member.php" id="floatingChatbot" class="chatbot-btn" title="Tanya Chatbot AI Vanda">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"></rect><circle cx="12" cy="5" r="2"></circle><path d="M12 7v4"></path><line x1="8" y1="16" x2="8.01" y2="16"></line><line x1="16" y1="16" x2="16.01" y2="16"></line></svg>
    </a>

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
                document.getElementById('popupIcon').innerHTML = `<svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>`;
                document.getElementById('popupTitle').innerText = "Simulasi Inbox Email";
                document.getElementById('popupMsg').innerHTML = `Sistem berhasil mengirimkan email tagihan otomatis ke alamat email Anda.`;
                document.getElementById('popupSimulasi').style.display = 'flex';
            } else if (savedPref === 'dasbor') {
                document.getElementById('popupIcon').innerHTML = `<svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M13.73 21a2 2 0 0 1-3.46 0"></path><path d="M18.63 13A17.89 17.89 0 0 1 18 8"></path><path d="M6.26 6.26A5.86 5.86 0 0 0 6 8c0 7-3 9-3 9h14"></path><path d="M18 8a6 6 0 0 0-9.33-5"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`;
                document.getElementById('popupTitle').innerText = "Simulasi Mode Senyap";
                document.getElementById('popupMsg').innerHTML = `Anda memilih "Hanya Dasbor". Sistem <strong>TIDAK</strong> akan mengirim pesan WA atau Email. Anda hanya akan melihat kotak peringatan merah di atas halaman ini saat login.`;
                document.getElementById('popupSimulasi').style.display = 'flex';
            }
        }

        // ================= RENDER TAMPILAN DASHBOARD =================
        const urlParams = new URLSearchParams(window.location.search);
        const statusMember = urlParams.get('status') || 'kadaluarsa';

        const dashboardContainer = document.getElementById('mainDashboard');
        const floatingChatbot = document.getElementById('floatingChatbot');
        const navChatbot = document.getElementById('navChatbot');

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
                    <span style="color: var(--accent-gold);">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                    </span>
                    <h3 style="color: var(--text-light); font-size: 1.1rem;">Pengaturan Pengingat Masa Aktif</h3>
                </div>
                <p style="color: #888; font-size: 0.85rem; margin-bottom: 15px;">Pilih bagaimana Anda ingin menerima notifikasi tagihan membership dari Admin.</p>
                
                <form onsubmit="simpanNotif(event)">
                    <div class="setting-methods">
                        <label class="set-method ${activeWa}" title="Kirim notifikasi via WhatsApp">
                            <input type="radio" name="prefNotif" value="wa" ${isWa} onchange="ubahNotif(this)">
                            <span>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                                WhatsApp
                            </span>
                        </label>
                        <label class="set-method ${activeEmail}" title="Kirim notifikasi via Email">
                            <input type="radio" name="prefNotif" value="email" ${isEmail} onchange="ubahNotif(this)">
                            <span>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                                Email
                            </span>
                        </label>
                        <label class="set-method ${activeDasbor}" title="Jangan kirim pesan, hanya tampilkan alert merah di Dasbor web">
                            <input type="radio" name="prefNotif" value="dasbor" ${isDasbor} onchange="ubahNotif(this)">
                            <span>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13.73 21a2 2 0 0 1-3.46 0"></path><path d="M18.63 13A17.89 17.89 0 0 1 18 8"></path><path d="M6.26 6.26A5.86 5.86 0 0 0 6 8c0 7-3 9-3 9h14"></path><path d="M18 8a6 6 0 0 0-9.33-5"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                                Hanya Dasbor
                            </span>
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
                            <p>ahsana@email.com</p>
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
                        <div class="action-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="8" y1="6" x2="16" y2="6"></line><line x1="16" y1="14" x2="16.01" y2="14"></line><line x1="12" y1="14" x2="12.01" y2="14"></line><line x1="8" y1="14" x2="8.01" y2="14"></line><line x1="16" y1="18" x2="16.01" y2="18"></line><line x1="12" y1="18" x2="12.01" y2="18"></line><line x1="8" y1="18" x2="8.01" y2="18"></line></svg>
                        </div>
                        <h3>Kalkulator Gizi</h3>
                        <p>Hitung ulang kalori & protein harian.</p>
                    </a>

                    <a href="perpanjang.php" class="action-btn danger-border" style="border-color: var(--primary-red);">
                        <div class="action-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                        </div>
                        <h3 style="color: var(--primary-red);">Perpanjang Member</h3>
                        <p>Bayar tagihan bulan berikutnya.</p>
                    </a>

                    <a href="#" class="action-btn locked" onclick="event.preventDefault(); alert('Fitur AI terkunci. Silakan perpanjang membership Anda terlebih dahulu.')">
                        <div class="action-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        </div>
                        <h3>Tanya Chatbot AI Vanda</h3>
                        <p>Fitur khusus member aktif.</p>
                    </a>

                    <a href="galeri_member.php" class="action-btn">
                        <div class="action-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>
                        </div>
                        <h3>Video Latihan</h3>
                        <p>Panduan gerakan alat gym.</p>
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
                            <p>ahsana@email.com</p>
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
                        <div class="action-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="8" y1="6" x2="16" y2="6"></line><line x1="16" y1="14" x2="16.01" y2="14"></line><line x1="12" y1="14" x2="12.01" y2="14"></line><line x1="8" y1="14" x2="8.01" y2="14"></line><line x1="16" y1="18" x2="16.01" y2="18"></line><line x1="12" y1="18" x2="12.01" y2="18"></line><line x1="8" y1="18" x2="8.01" y2="18"></line></svg>
                        </div>
                        <h3>Kalkulator Gizi</h3>
                        <p>Hitung ulang kalori & protein harian.</p>
                    </a>

                    <a href="perpanjang.php" class="action-btn">
                        <div class="action-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                        </div>
                        <h3>Perpanjang Member</h3>
                        <p>Bayar tagihan bulan berikutnya.</p>
                    </a>

                    <a href="chatbot_member.php" class="action-btn">
                        <div class="action-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"></rect><circle cx="12" cy="5" r="2"></circle><path d="M12 7v4"></path><line x1="8" y1="16" x2="8.01" y2="16"></line><line x1="16" y1="16" x2="16.01" y2="16"></line></svg>
                        </div>
                        <h3>Tanya Chatbot AI Vanda</h3>
                        <p>Cek kalori makanan via foto.</p>
                    </a>

                    <a href="galeri_member.php" class="action-btn">
                        <div class="action-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>
                        </div>
                        <h3>Video Latihan</h3>
                        <p>Panduan gerakan alat gym.</p>
                    </a>
                </div>
            `;
        }
    </script>
</body>
</html>