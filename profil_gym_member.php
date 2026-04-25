<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Gym - Vanda Gym Classic</title>
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

        /* Ikon Profil */
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
            background-color: #1a1a1a; border-bottom: 1px solid #333;
            color: var(--text-light); padding: 16px 25px; text-align: center;
            font-size: 1.1rem; display: flex; justify-content: center;
            align-items: center; gap: 15px; z-index: 99;
        }
        .announcement-badge {
            background-color: var(--primary-red); color: white; padding: 5px 12px; 
            border-radius: 4px; font-weight: bold; font-size: 0.85rem; 
            text-transform: uppercase; letter-spacing: 0.5px; animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(142, 22, 22, 0.7); }
            70% { box-shadow: 0 0 0 8px rgba(142, 22, 22, 0); }
            100% { box-shadow: 0 0 0 0 rgba(142, 22, 22, 0); }
        }

        /* ================= HERO SECTION ================= */
        .hero { 
            height: 60vh; 
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.9)), url('https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=1470&auto=format&fit=crop') center/cover; 
            display: flex; flex-direction: column; justify-content: center; align-items: center; 
            text-align: center; padding: 0 20px; border-bottom: 1px solid #333;
        }
        .hero h1 { font-size: 4rem; margin-bottom: 10px; color: var(--accent-gold); text-transform: uppercase; text-shadow: 2px 2px 10px rgba(0,0,0,0.8); }
        .hero p { font-size: 1.2rem; max-width: 700px; color: #ccc; }

        /* ================= KONTEN HALAMAN ================= */
        section { padding: 80px 5%; }
        .section-title { 
            color: var(--accent-gold); text-align: center; font-size: 2.5rem; 
            text-transform: uppercase; margin-bottom: 50px; position: relative; 
        }
        .section-title::after {
            content: ''; display: block; width: 80px; height: 3px; 
            background-color: var(--primary-red); margin: 10px auto 0;
        }

        /* Style Jadwal */
        .schedule-container { display: flex; gap: 30px; flex-wrap: wrap; justify-content: center; }
        .schedule-box { 
            flex: 1; min-width: 320px; max-width: 500px; background-color: #111; 
            border-radius: 10px; overflow: hidden; box-shadow: 0 10px 20px rgba(0,0,0,0.5); 
            border: 1px solid #222; transition: 0.3s;
        }
        .schedule-box:hover { border-color: var(--accent-gold); transform: translateY(-5px); }
        .schedule-header { background-color: var(--primary-red); padding: 20px; text-align: center; font-size: 1.4rem; font-weight: bold; color: white; letter-spacing: 1px;}
        .schedule-header.gold { background-color: var(--accent-gold); color: #000; }
        .schedule-body { padding: 25px; }
        .schedule-row { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px dashed #333;}
        .schedule-row:last-child { border-bottom: none; }
        .schedule-day { font-weight: bold; color: var(--text-light); font-size: 1.1rem; }
        .schedule-time { color: var(--accent-gold); background: rgba(232, 201, 153, 0.1); padding: 5px 10px; border-radius: 4px; font-weight: 600;}

        /* Fasilitas Slider */
        .gallery-slider { 
            display: flex; overflow-x: auto; scroll-snap-type: x mandatory; 
            gap: 20px; padding-bottom: 20px; -webkit-overflow-scrolling: touch;
        }
        .gallery-slider::-webkit-scrollbar { height: 8px; }
        .gallery-slider::-webkit-scrollbar-track { background: #111; border-radius: 4px; }
        .gallery-slider::-webkit-scrollbar-thumb { background: var(--primary-red); border-radius: 4px; }
        .gallery-item { 
            flex: 0 0 80%; max-width: 400px; height: 300px; 
            object-fit: cover; border-radius: 8px; scroll-snap-align: center; border: 2px solid transparent;
            transition: 0.3s;
        }
        .gallery-item:hover { border-color: var(--accent-gold); }

        /* ================= FOOTER ================= */
        footer { 
            background-color: #050505; padding: 60px 5% 30px; 
            border-top: 1px solid #1a1a1a; text-align: left; 
        }
        .footer-container { 
            display: grid; grid-template-columns: 1fr 1fr; gap: 40px; 
            align-items: center; max-width: 1200px; margin: 0 auto; 
        }
        .footer-info h3 { color: var(--accent-gold); font-size: 1.8rem; margin-bottom: 15px; }
        .footer-info p { color: #aaa; margin: 10px 0; font-size: 1rem; }
        .footer-info a { color: var(--text-light); text-decoration: none; font-weight: bold; transition: 0.3s;}
        .footer-info a:hover { color: var(--accent-gold); }
        .footer-info .cs-text { color: var(--accent-gold); }
        
        .footer-map iframe { 
            width: 100%; height: 220px; border-radius: 8px; border: 1px solid #333; background-color: #111;
        }
        
        .footer-bottom { 
            text-align: center; margin-top: 50px; padding-top: 20px; 
            border-top: 1px solid #111; font-size: 0.85rem; color: #555; 
        }

        /* ================= TOMBOL MENGAMBANG ================= */
        .wa-btn {
            position: fixed; bottom: 30px; left: 30px; background-color: #25D366; color: white; 
            border-radius: 50%; width: 60px; height: 60px; display: flex; justify-content: center; align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.6); z-index: 1000; transition: 0.3s; text-decoration: none;
        }
        .wa-btn:hover { transform: scale(1.1); background-color: #1ebe57; }
        .wa-btn svg { width: 35px; height: 35px; fill: currentColor; }

        .chatbot-btn { 
            position: fixed; bottom: 30px; right: 30px; background-color: var(--primary-red); color: white; 
            border: none; border-radius: 50%; width: 60px; height: 60px; font-size: 28px; cursor: pointer; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.6); z-index: 1000; transition: 0.3s; display: flex; justify-content: center; align-items: center; text-decoration: none;
        }
        .chatbot-btn:hover { transform: scale(1.1); }
        .chatbot-btn.locked { background-color: #333; color: #888; border: 2px solid #555; cursor: not-allowed; pointer-events: none; }

        @media (max-width: 768px) {
            header { flex-direction: column; padding: 15px; }
            nav { margin-top: 15px; justify-content: center;}
            nav a.nav-link { margin: 5px 10px; font-size: 0.9rem;}
            .btn-logout { margin-left: 0; margin-top: 10px; width: 100%;}
            .profile-icon { margin-top: 15px; }
            .footer-container { grid-template-columns: 1fr; text-align: center; }
            .announcement-banner { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>

    <header>
        <div class="logo">
            <img src="assets/logo.png" alt="Vanda Gym Classic Logo">
        </div>
        <nav>
            <a href="member_dasbor.php" class="nav-link">Dasbor</a>
            <a href="profil_gym_member.php" class="nav-link active">Profil Gym</a>
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

    <section class="hero">
        <h1>Vanda Gym Classic</h1>
        <p>Jelajahi fasilitas kebugaran terbaik di Palangka Raya. Kenali jadwal operasional kami dan jadwalkan latihanmu minggu ini.</p>
    </section>

    <section id="jadwal" style="background-color: #0a0a0a;">
        <h2 class="section-title">Jadwal Operasional & Kelas</h2>
        <div class="schedule-container">
            <div class="schedule-box">
                <div class="schedule-header">Jam Operasional Gym</div>
                <div class="schedule-body">
                    <div class="schedule-row">
                        <span class="schedule-day">Senin - Jumat</span>
                        <div style="text-align: right;">
                            <span class="schedule-time" style="display:block; margin-bottom:5px;">06.00 - 10.30 WIB</span>
                            <span class="schedule-time" style="display:block;">14.15 - 19.45 WIB</span>
                        </div>
                    </div>
                    <div class="schedule-row">
                        <span class="schedule-day">Sabtu</span>
                        <div style="text-align: right;">
                            <span class="schedule-time" style="display:block; margin-bottom:5px;">06.00 - 10.30 WIB</span>
                            <span class="schedule-time" style="display:block;">14.15 - 19.00 WIB</span>
                        </div>
                    </div>
                    <div class="schedule-row">
                        <span class="schedule-day">Minggu</span>
                        <div style="text-align: right;">
                            <span class="schedule-time" style="display:block; margin-bottom:5px; color: var(--primary-red); font-weight:bold;">Pagi Tutup</span>
                            <span class="schedule-time" style="display:block;">14.15 - 19.00 WIB</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="schedule-box">
                <div class="schedule-header gold">Jadwal Kelas Senam</div>
                <div class="schedule-body">
                    <div class="schedule-row">
                        <span class="schedule-day">Senin & Rabu</span>
                        <span class="schedule-time">16.15 - 17.15 (BL+)</span>
                    </div>
                    <div class="schedule-row">
                        <span class="schedule-day">Selasa & Kamis</span>
                        <span class="schedule-time">16.00 - 17.00 (Zumba)</span>
                    </div>
                    <div class="schedule-row">
                        <span class="schedule-day">Sabtu</span>
                        <span class="schedule-time">08.00 - 09.00 (BL+)</span>
                    </div>
                    <div class="schedule-row">
                        <span class="schedule-day">Minggu</span>
                        <span class="schedule-time">15.30 - 16.30 (Pilates)</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="galeri">
        <h2 class="section-title">Fasilitas Gym Kami</h2>
        <p style="text-align: center; color: #888; margin-bottom: 30px;">Geser untuk melihat area latihan yang tersedia untuk member.</p>
        
        <div class="gallery-slider">
            <img src="https://images.unsplash.com/photo-1540497077202-7c8a3999166f?w=600&auto=format&fit=crop" alt="Fasilitas Dumbbell" class="gallery-item">
            <img src="https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?w=600&auto=format&fit=crop" alt="Fasilitas Treadmill" class="gallery-item">
            <img src="https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=600&auto=format&fit=crop" alt="Mesin Beban" class="gallery-item">
            <img src="https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?w=600&auto=format&fit=crop" alt="Area Angkat Beban" class="gallery-item">
            <img src="https://images.unsplash.com/photo-1576678927484-cc907957088c?w=600&auto=format&fit=crop" alt="Rak Beban" class="gallery-item">
        </div>
    </section>

    <footer>
        <div class="footer-container">
            <div class="footer-info">
                <h3>Vanda Gym Classic</h3>
                <p>Membentuk Karakter, Membangun Kekuatan.</p>
                <p style="margin-top: 20px;">📍 Jl. Kapten Pierre Tendean No.17, Palangka Raya</p>
                <p>📞 CS / Pendaftaran: <a href="https://wa.me/6282148556601" target="_blank" class="cs-text">0821-4855-6601</a></p>
                <p>📱 Info Kelas Senam: 0821-5992-5490</p>
                <p style="margin-top: 15px;">📸 Instagram: <a href="https://instagram.com/vandagympky_classic" target="_blank">@vandagympky_classic</a></p>
            </div>
            
            <div class="footer-map">
                <iframe src="https://maps.google.com/maps?q=Vanda%20Gym%20Palangkaraya&t=&z=15&ie=UTF8&iwloc=&output=embed" width="100%" height="220" style="border:0; border-radius: 8px;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>

        <div class="footer-bottom">
            © 2026 Vanda Gym Classic Room. Sistem Informasi Manajemen Member.
        </div>
    </footer>

    <a href="https://wa.me/6282148556601" target="_blank" class="wa-btn" title="Hubungi CS via WhatsApp">
        <svg viewBox="0 0 24 24">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/>
        </svg>
    </a>

    <a href="chatbot_member.php" id="floatingChatbot" class="chatbot-btn" title="Tanya Chatbot AI Vanda">🤖</a>

    <script>
        // Logika Penguncian Fitur Berdasarkan URL Parameter (?status=kadaluarsa)
        const urlParams = new URLSearchParams(window.location.search);
        const statusMember = urlParams.get('status') || 'aktif';

        const floatingChatbot = document.getElementById('floatingChatbot');
        const navChatbot = document.getElementById('navChatbot');
        const navGaleri = document.getElementById('navGaleri');

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
        }
    </script>
</body>
</html>