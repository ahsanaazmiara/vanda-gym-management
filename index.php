<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vanda Gym Classic Palangkaraya</title>
    <style>
        :root {
            --bg-dark: #000000;
            --primary-red: #8E1616;
            --accent-gold: #E8C999;
            --text-light: #F8EEDF;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: var(--bg-dark); 
            color: var(--text-light); 
            line-height: 1.6;
            overflow-x: hidden;
        }

        header { 
            background-color: rgba(10, 10, 10, 0.95); 
            padding: 10px 5%; 
            display: flex; justify-content: space-between; align-items: center; 
            position: sticky; top: 0; z-index: 100; 
            border-bottom: 2px solid var(--primary-red); 
        }
        
        .logo img { height: 70px; width: auto; object-fit: contain; }

        nav { display: flex; align-items: center; }
        nav a { 
            color: var(--text-light); text-decoration: none; 
            margin-left: 20px; font-weight: 600; transition: 0.3s;
            min-height: 44px; display: inline-flex; align-items: center;
        }
        nav a:hover { color: var(--accent-gold); }
        
        .nav-login { color: var(--accent-gold); font-weight: bold; margin-right: 5px; }

        .btn-daftar { 
            border: 2px solid var(--accent-gold); padding: 0 20px; border-radius: 4px; 
            color: var(--accent-gold); margin-left: 20px; background: transparent;
            font-weight: bold; font-size: 1rem; min-height: 44px; min-width: 44px;
            cursor: pointer; transition: 0.3s;
        }
        .btn-daftar:hover { background-color: var(--accent-gold); color: var(--bg-dark); }

        .hero { 
            height: 85vh; 
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.9)), url('https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=1470&auto=format&fit=crop') center/cover; 
            display: flex; flex-direction: column; justify-content: center; align-items: center; 
            text-align: center; padding: 0 20px; 
        }
        .hero h1 { font-size: 4.5rem; margin-bottom: 10px; color: var(--accent-gold); text-transform: uppercase; text-shadow: 2px 2px 10px rgba(0,0,0,0.8); }
        .hero p { font-size: 1.2rem; margin-bottom: 40px; max-width: 700px; color: #ccc; }
        
        .btn-primary { 
            background-color: var(--primary-red); color: var(--text-light); 
            padding: 0 35px; font-size: 1.2rem; border: none; border-radius: 4px; 
            text-transform: uppercase; font-weight: bold; transition: 0.3s; 
            text-decoration: none; min-height: 44px; min-width: 44px;
            display: inline-flex; align-items: center; justify-content: center; cursor: pointer;
        }
        .btn-primary:hover { background-color: #a81a1a; box-shadow: 0 0 20px rgba(142, 22, 22, 0.6); }

        section { padding: 80px 5%; }
        .section-title { 
            color: var(--accent-gold); text-align: center; font-size: 2.5rem; 
            text-transform: uppercase; margin-bottom: 50px; position: relative; 
        }
        .section-title::after {
            content: ''; display: block; width: 80px; height: 3px; 
            background-color: var(--primary-red); margin: 10px auto 0;
        }

        .grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; }

        .card { 
            background-color: #111; border: 1px solid #333; padding: 40px 30px; 
            border-radius: 8px; text-align: center; transition: 0.3s; 
            border-top: 4px solid var(--primary-red); display: flex; flex-direction: column;
        }
        .card:hover { transform: translateY(-10px); border-color: var(--accent-gold); }
        .card h3 { color: var(--accent-gold); font-size: 1.8rem; margin-bottom: 15px; }
        .card .price { font-size: 2.5rem; font-weight: bold; color: var(--text-light); margin-bottom: 20px; }
        .card .price span { font-size: 1rem; color: #888; font-weight: normal; }

        .btn-action {
            width: 100%; background: transparent; color: var(--accent-gold);
            border: 1px solid var(--accent-gold); font-size: 1rem; font-weight: bold;
            border-radius: 4px; min-height: 44px; cursor: pointer; transition: 0.3s;
            margin-top: auto; 
        }
        .btn-action:hover { background: var(--accent-gold); color: var(--bg-dark); }
        .btn-action.solid { background: var(--primary-red); color: white; border: none; }
        .btn-action.solid:hover { background: #a81a1a; }

        .highlight-text {
            background: rgba(232, 201, 153, 0.1); border: 1px dashed var(--accent-gold);
            color: var(--accent-gold); font-weight: bold; padding: 12px; border-radius: 4px;
            font-size: 0.9rem; margin-top: auto; 
        }

        .schedule-container { display: flex; gap: 30px; flex-wrap: wrap; justify-content: center; }
        .schedule-box { 
            flex: 1; min-width: 320px; max-width: 500px; background-color: #111; 
            border-radius: 10px; overflow: hidden; box-shadow: 0 10px 20px rgba(0,0,0,0.5); 
            border: 1px solid #222;
        }
        .schedule-header { background-color: var(--primary-red); padding: 20px; text-align: center; font-size: 1.4rem; font-weight: bold; color: white; letter-spacing: 1px;}
        .schedule-header.gold { background-color: var(--accent-gold); color: #000; }
        .schedule-body { padding: 25px; }
        .schedule-row { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px dashed #333;}
        .schedule-row:last-child { border-bottom: none; }
        .schedule-day { font-weight: bold; color: var(--text-light); font-size: 1.1rem; }
        .schedule-time { color: var(--accent-gold); background: rgba(232, 201, 153, 0.1); padding: 5px 10px; border-radius: 4px; font-weight: 600;}

        .benefit-card {
            background-color: #0a0a0a; border: 1px solid #222; padding: 35px 25px;
            border-radius: 8px; text-align: center; transition: 0.3s;
        }
        .benefit-card:hover { border-color: var(--primary-red); background-color: #111; }
        .benefit-icon { font-size: 2.5rem; margin-bottom: 15px; }
        .benefit-card h3 { color: var(--text-light); font-size: 1.3rem; margin-bottom: 10px; }
        .benefit-card p { color: #888; font-size: 0.95rem; }

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
        }

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

        @media (max-width: 768px) {
            .footer-container { grid-template-columns: 1fr; text-align: center; }
        }

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
            box-shadow: 0 4px 15px rgba(0,0,0,0.6); z-index: 1000; transition: 0.3s; display: flex; justify-content: center; align-items: center;
        }
        .chatbot-btn:hover { transform: scale(1.1); }
        
        .chatbot-window { 
            display: none; position: fixed; bottom: 100px; right: 30px; width: 330px; background-color: #1a1a1a; 
            border: 1px solid var(--accent-gold); border-radius: 10px; z-index: 1000; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.8);
        }
        .chat-header { background-color: var(--primary-red); padding: 15px; font-weight: bold; display: flex; justify-content: space-between; align-items: center;}
        .close-chat { 
            background: transparent; border: none; color: white; cursor: pointer; font-weight: bold; font-size: 1.5rem; 
            min-width: 44px; min-height: 44px; display: flex; align-items: center; justify-content: center; margin: -10px;
        }
        .chat-body { height: 230px; padding: 15px; overflow-y: auto; font-size: 0.85rem; }
        .chat-msg { background-color: #333; padding: 10px 15px; border-radius: 15px; border-bottom-left-radius: 0; margin-bottom: 15px; display: inline-block; max-width: 85%; }
        .chat-msg.user { background-color: var(--accent-gold); color: #000; border-radius: 15px; border-bottom-right-radius: 0; float: right; }
        
        .chat-footer-menu { background-color: #111; border-top: 1px solid #333; padding: 10px; }
        .quick-replies { display: flex; gap: 8px; overflow-x: auto; padding-bottom: 5px; -webkit-overflow-scrolling: touch; }
        .quick-replies::-webkit-scrollbar { height: 4px; }
        .quick-replies::-webkit-scrollbar-thumb { background: var(--accent-gold); border-radius: 4px; }
        
        .btn-qr {
            background-color: transparent; border: 1px solid var(--accent-gold); color: var(--accent-gold); padding: 8px 12px; 
            border-radius: 20px; cursor: pointer; font-size: 0.8rem; white-space: nowrap; flex: 0 0 auto; min-height: 44px; transition: 0.3s;
        }
        .btn-qr:hover { background-color: var(--accent-gold); color: #000; }

        .clearfix::after { content: ""; clear: both; display: table; }
    </style>
</head>
<body>

    <header>
        <div class="logo">
            <img src="assets/logo.png" alt="Vanda Gym Classic Logo">
        </div>
        <nav>
            <a href="#beranda">Beranda</a>
            <a href="#paket">Paket & Jadwal</a>
            <a href="kalkulator.php">Kalkulator Gizi</a>
            <a href="login.php" class="nav-login">Login</a>
            <button class="btn-daftar" onclick="window.location.href='daftar.php'">Daftar Member</button>
        </nav>
    </header>

    <section id="beranda" class="hero">
        <h1>Bentuk Karakter,<br>Bangun Kekuatan</h1>
        <p>Rasakan atmosfer bodybuilding yang autentik. Komunitas lokal aktif dan raih bentuk tubuh idealmu bersama Vanda Gym Palangkaraya.</p>
        <a href="#paket" class="btn-primary">Lihat Paket Membership</a>
    </section>

    <section id="paket">
        <h2 class="section-title">Pilihan Membership</h2>
        <div class="grid-3">
            <div class="card">
                <h3>1x Visit Gym</h3>
                <div class="price">Rp 25.000</div>
                <p style="color: #aaa; margin-bottom: 15px;">Akses harian penuh ke seluruh fasilitas beban dan kardio.</p>
                <div class="highlight-text">Tidak perlu daftar online. Silakan langsung datang bayar di resepsionis.</div>
            </div>
            
            <div class="card" style="border-top-color: var(--accent-gold); transform: scale(1.05); box-shadow: 0 0 20px rgba(232, 201, 153, 0.1);">
                <h3 style="color: #fff;">Gym Bulanan</h3>
                <div class="price" style="color: var(--accent-gold);">Mulai Rp 175rb</div>
                <p style="color: #aaa; margin-bottom: 25px;">Akses gym tanpa batas dan dapatkan semua keuntungan sistem online.</p>
                <button class="btn-action solid" onclick="window.location.href='daftar.php'">Daftar</button>
            </div>

            <div class="card">
                <h3>Kelas Senam</h3>
                <div class="price">Rp 25.000<span>/datang</span></div>
                <p style="color: #aaa; margin-bottom: 15px;">Bergabunglah dengan kelas Zumba, Pilates, atau BL+ bersama instruktur ahli.</p>
                <div class="highlight-text">Tidak perlu daftar online. Silakan langsung datang bayar di resepsionis.</div>
            </div>
        </div>
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

    <section id="benefit" style="background-color: #050505; border-top: 1px solid #1a1a1a; border-bottom: 1px solid #1a1a1a;">
        <h2 class="section-title">Keuntungan Daftar Member Online</h2>
        <div class="grid-3">
            <div class="benefit-card">
                <div class="benefit-icon">🤖</div>
                <h3>Akses Chatbot AI Lanjutan</h3>
                <p>Member mendapatkan AI khusus untuk info nutrisi dasar dan tips kebugaran harian.</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon">📱</div>
                <h3>Kelola Akun & Masa Aktif</h3>
                <p>Miliki dasbor pribadi untuk memantau status membership, terima notifikasi membership kedaluwarsa, dan perpanjang online dari sistem.</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon">🎥</div>
                <h3>Video Tutorial Eksklusif</h3>
                <p>Dapatkan akses ke galeri panduan gerakan gym yang benar.</p>
            </div>
        </div>
    </section>

    <section id="galeri">
        <h2 class="section-title">Fasilitas Gym Kami</h2>
        <p style="text-align: center; color: #888; margin-bottom: 30px;">Geser untuk melihat fasilitas alat beban dan kardio.</p>
        
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

    <button class="chatbot-btn" onclick="toggleChat()">🤖</button>
    
    <div class="chatbot-window" id="chatWindow">
        <div class="chat-header">
            <span>Info Bot Vanda Gym</span>
            <button class="close-chat" onclick="toggleChat()" title="Tutup Chat">×</button>
        </div>
        <div class="chat-body clearfix" id="chatBody">
            <div class="chat-msg">Halo! Saya bot informasi. Silakan pilih topik yang ingin Anda tanyakan di bawah ini. 👇</div>
        </div>
        
        <div class="chat-footer-menu">
            <div class="quick-replies">
                <button class="btn-qr" onclick="kirimFaq('Bagaimana cara menghubungi CS?', 'Silakan hubungi WhatsApp CS Vanda Gym di nomor <br><strong><a href=\'https://wa.me/6282148556601\' target=\'_blank\' style=\'color:var(--accent-gold);\'>0821-4855-6601</a></strong><br>Atau klik tombol WhatsApp hijau di pojok kiri bawah layar.')">📞 Hubungi CS</button>
                
                <button class="btn-qr" onclick="kirimFaq('Di mana lokasi Vanda Gym?', 'Lokasi kami ada di <strong>Jl. Kapten Pierre Tendean No.17, Palangka Raya</strong>. Anda bisa melihat panduan peta (Google Maps) di bagian paling bawah halaman ini.')">📍 Info Lokasi</button>
                <button class="btn-qr" onclick="kirimFaq('Apa saja peraturan gym?', '1. Bawa handuk sendiri.<br>2. Kembalikan alat setelah dipakai.<br>3. Gunakan pakaian & sepatu olahraga.<br>4. Jaga kebersihan gym.')">📜 Peraturan</button>

                <button class="btn-qr" onclick="kirimFaq('Berapa harga paket membership?', 'Harga Gym Bulanan mulai dari Rp 175.000. Tersedia juga 1x Visit (Rp 25.000) dan Kelas Senam (Rp 25.000/datang). Pendaftaran langganan bulanan bisa dilakukan via website.')">💰 Harga Membership</button>
                <button class="btn-qr" onclick="kirimFaq('Kapan jadwal buka gym?', 'Senin-Jumat: 06.00-10.30 & 14.15-19.45.<br>Sabtu tutup jam 19.00.<br>Minggu pagi tutup, buka sore sampai jam 19.00.')">🕒 Jadwal Buka</button>
            </div>
        </div>
    </div>

    <script>
        function toggleChat() {
            const chat = document.getElementById("chatWindow");
            chat.style.display = chat.style.display === "block" ? "none" : "block";
        }

        function kirimFaq(pertanyaan, jawaban) {
            const body = document.getElementById("chatBody");

            body.innerHTML += '<div class="chat-msg user">' + pertanyaan + '</div><div class="clearfix"></div>';
            body.scrollTop = body.scrollHeight;

            setTimeout(function() {
                body.innerHTML += '<div class="chat-msg" style="border-left: 3px solid var(--accent-gold);">' + jawaban + '</div><div class="clearfix"></div>';
                body.scrollTop = body.scrollHeight;
            }, 500);
        }
    </script>
</body>
</html>