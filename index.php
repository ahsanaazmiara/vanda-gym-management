<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vanda Gym Classic Palangkaraya</title>
    <style>
        /* Mengatur Palet Warna Sesuai Proposal */
        :root {
            --bg-dark: #000000;
            --primary-red: #8E1616;
            --accent-gold: #E8C999;
            --text-light: #F8EEDF;
        }

        /* Reset & Base Styles */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: var(--bg-dark); 
            color: var(--text-light); 
            line-height: 1.6;
        }

        /* Navigation Bar */
        header { 
            background-color: rgba(10, 10, 10, 0.95); 
            padding: 10px 5%; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            position: sticky; 
            top: 0; 
            z-index: 100; 
            border-bottom: 2px solid var(--primary-red); 
        }
        
        /* LOGO IMAGE SETTING */
        .logo img { 
            height: 70px; /* Ukuran proporsional logo */
            width: auto;
            object-fit: contain;
        }

        nav { display: flex; align-items: center; }
        nav a { 
            color: var(--text-light); 
            text-decoration: none; 
            margin-left: 20px; 
            font-weight: 600; 
            transition: 0.3s;
            /* Rule 44x44px Touch Target */
            min-height: 44px;
            display: inline-flex;
            align-items: center;
        }
        nav a:hover { color: var(--accent-gold); }
        
        .btn-daftar { 
            border: 2px solid var(--accent-gold); 
            padding: 0 20px; 
            border-radius: 4px; 
            color: var(--accent-gold); 
            margin-left: 25px; 
            background: transparent;
            font-weight: bold;
            font-size: 1rem;
            /* Rule 44x44px Touch Target */
            min-height: 44px;
            min-width: 44px;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-daftar:hover { background-color: var(--accent-gold); color: var(--bg-dark); }

        /* Hero Section */
        .hero { 
            height: 85vh; 
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.9)), url('https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=1470&auto=format&fit=crop') center/cover; 
            display: flex; 
            flex-direction: column; 
            justify-content: center; 
            align-items: center; 
            text-align: center; 
            padding: 0 20px; 
        }
        .hero h1 { font-size: 4.5rem; margin-bottom: 10px; color: var(--accent-gold); text-transform: uppercase; text-shadow: 2px 2px 10px rgba(0,0,0,0.8); }
        .hero p { font-size: 1.2rem; margin-bottom: 40px; max-width: 700px; color: #ccc; }
        
        .btn-primary { 
            background-color: var(--primary-red); color: var(--text-light); 
            padding: 0 35px; font-size: 1.2rem; border: none; border-radius: 4px; 
            text-transform: uppercase; font-weight: bold; 
            transition: 0.3s; text-decoration: none; 
            /* Rule 44x44px Touch Target */
            min-height: 44px;
            min-width: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .btn-primary:hover { background-color: #a81a1a; box-shadow: 0 0 20px rgba(142, 22, 22, 0.6); }

        /* Section Layouts */
        section { padding: 80px 5%; }
        .section-title { 
            color: var(--accent-gold); text-align: center; font-size: 2.5rem; 
            text-transform: uppercase; margin-bottom: 50px; 
            position: relative; 
        }
        .section-title::after {
            content: ''; display: block; width: 80px; height: 3px; 
            background-color: var(--primary-red); margin: 10px auto 0;
        }

        /* Grid System */
        .grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; }

        /* Cards (Membership) */
        .card { 
            background-color: #111; border: 1px solid #333; padding: 40px 30px; 
            border-radius: 8px; text-align: center; transition: 0.3s; 
            border-top: 4px solid var(--primary-red); 
        }
        .card:hover { transform: translateY(-10px); border-color: var(--accent-gold); }
        .card h3 { color: var(--accent-gold); font-size: 1.8rem; margin-bottom: 15px; }
        .card .price { font-size: 2.5rem; font-weight: bold; color: var(--text-light); margin-bottom: 20px; }
        .card .price span { font-size: 1rem; color: #888; font-weight: normal; }

        /* Tombol aksi dalam form/card */
        .btn-action {
            width: 100%;
            background: transparent;
            color: var(--accent-gold);
            border: 1px solid var(--accent-gold);
            font-size: 1rem;
            font-weight: bold;
            border-radius: 4px;
            /* Rule 44x44px Touch Target */
            min-height: 44px;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-action:hover { background: var(--accent-gold); color: var(--bg-dark); }
        .btn-action.solid { background: var(--primary-red); color: white; border: none; }
        .btn-action.solid:hover { background: #a81a1a; }

        /* Schedule Box Redesign (Lebih Menarik) */
        .schedule-container { display: flex; gap: 30px; flex-wrap: wrap; justify-content: center; }
        .schedule-box { 
            flex: 1; min-width: 320px; max-width: 500px;
            background-color: #111; border-radius: 10px; overflow: hidden;
            box-shadow: 0 10px 20px rgba(0,0,0,0.5); border: 1px solid #222;
        }
        .schedule-header {
            background-color: var(--primary-red);
            padding: 20px; text-align: center; font-size: 1.4rem; font-weight: bold; color: white;
            letter-spacing: 1px;
        }
        .schedule-header.gold { background-color: var(--accent-gold); color: #000; }
        .schedule-body { padding: 25px; }
        .schedule-row { 
            display: flex; justify-content: space-between; align-items: center;
            padding: 15px 0; border-bottom: 1px dashed #333;
        }
        .schedule-row:last-child { border-bottom: none; }
        .schedule-day { font-weight: bold; color: var(--text-light); font-size: 1.1rem; }
        .schedule-time { color: var(--accent-gold); background: rgba(232, 201, 153, 0.1); padding: 5px 10px; border-radius: 4px; font-weight: 600;}

        /* Gallery Grid */
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; }
        .gallery-item { 
            width: 100%; height: 250px; object-fit: cover; 
            border-radius: 8px; transition: transform 0.3s ease, border 0.3s ease; 
            cursor: pointer; border: 2px solid transparent;
        }
        .gallery-item:hover { transform: scale(1.03); border: 2px solid var(--accent-gold); }

        /* Footer */
        footer { background-color: #050505; padding: 50px 5%; text-align: center; border-top: 1px solid #222; }
        footer h3 { color: var(--accent-gold); font-size: 1.5rem; margin-bottom: 15px; }
        footer p { color: #888; margin: 5px 0; }

        /* Floating Chatbot UI */
        .chatbot-btn { 
            position: fixed; bottom: 30px; right: 30px; 
            background-color: var(--primary-red); color: white; 
            border: none; border-radius: 50%; width: 60px; height: 60px; /* Diperbesar memenuhi syarat 44x44px */
            font-size: 28px; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.6); 
            z-index: 1000; transition: 0.3s;
            display: flex; justify-content: center; align-items: center;
        }
        .chatbot-btn:hover { transform: scale(1.1); }
        
        .chatbot-window { 
            display: none; position: fixed; bottom: 100px; right: 30px; 
            width: 350px; background-color: #1a1a1a; border: 1px solid var(--accent-gold); 
            border-radius: 10px; z-index: 1000; overflow: hidden; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.8);
        }
        .chat-header { background-color: var(--primary-red); padding: 15px; font-weight: bold; display: flex; justify-content: space-between; align-items: center;}
        /* Button close chat mengikuti aturan 44x44px minimal (dipenuhi dgn padding) */
        .close-chat { 
            background: transparent; border: none; color: white; cursor: pointer; 
            font-weight: bold; font-size: 1.5rem; min-width: 44px; min-height: 44px; 
            display: flex; align-items: center; justify-content: center; margin: -10px;
        }
        .chat-body { height: 300px; padding: 15px; overflow-y: auto; font-size: 0.95rem; }
        .chat-msg { background-color: #333; padding: 10px 15px; border-radius: 15px; border-bottom-left-radius: 0; margin-bottom: 15px; display: inline-block; max-width: 85%; }
        .chat-msg.user { background-color: var(--accent-gold); color: #000; border-radius: 15px; border-bottom-right-radius: 0; float: right; }
        
        .chat-input-area { display: flex; border-top: 1px solid #333; }
        .chat-input-area input { flex: 1; padding: 15px; border: none; background: #0a0a0a; color: white; outline: none; }
        .chat-input-area button { min-width: 60px; min-height: 44px; background: var(--accent-gold); color: #000; border: none; font-weight: bold; cursor: pointer; }

        /* Clearfix for chat */
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
            <a href="#paket">Harga</a>
            <a href="#jadwal">Jadwal & Kelas</a>
            <a href="#galeri">Galeri Gym</a>
            <a href="#kalkulator">Kalkulator Gizi</a>
            <button class="btn-daftar" onclick="window.location.href='daftar.php'">Daftar Member</button>
        </nav>
    </header>

    <section id="beranda" class="hero">
        <h1>Bentuk Karakter,<br>Bangun Kekuatan</h1>
        <p>Rasakan atmosfer bodybuilding yang autentik. Komunitas lokal aktif dan raih bentuk tubuh idealmu bersama Vanda Gym Palangkaraya.</p>
        <a href="#paket" class="btn-primary">Lihat Paket Membership</a>
    </section>

    <section id="paket">
        <h2 class="section-title">Harga Membership</h2>
        <div class="grid-3">
            <div class="card">
                <h3>1x Visit</h3>
                <div class="price">Rp 25.000</div>
                <p style="color: #aaa; margin-bottom: 25px;">Akses harian penuh ke seluruh fasilitas beban dan kardio.</p>
                <button class="btn-action" onclick="window.location.href='daftar.php'">Pilih Paket Harian</button>
            </div>
            
            <div class="card" style="border-top-color: var(--accent-gold); transform: scale(1.05); box-shadow: 0 0 20px rgba(232, 201, 153, 0.1);">
                <h3 style="color: #fff;">1 Bulan Gym</h3>
                <div class="price" style="color: var(--accent-gold);">Rp 175.000</div>
                <p style="color: #aaa; margin-bottom: 25px;">Akses gym tanpa batas selama 30 hari. Bebas gunakan semua alat.</p>
                <button class="btn-action solid" onclick="window.location.href='daftar.php'">Daftar 1 Bulan</button>
            </div>

            <div class="card">
                <h3>Kelas Senam</h3>
                <div class="price">Rp 25.000<span>/datang</span></div>
                <p style="color: #aaa; margin-bottom: 25px;">Bergabunglah dengan kelas Zumba, Pilates, atau BL+ bersama instruktur ahli.</p>
                <button class="btn-action" onclick="window.location.href='daftar.php'">Daftar Kelas</button>
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
                        <span class="schedule-time">06.00 - 19.00 WIB</span>
                    </div>
                    <div class="schedule-row">
                        <span class="schedule-day">Minggu</span>
                        <span class="schedule-time" style="color: var(--primary-red); font-weight:bold;">Pagi Tutup - Buka Sore</span>
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
        <h2 class="section-title">Galeri Gym & Fasilitas</h2>
        <div class="gallery-grid">
            <img src="https://images.unsplash.com/photo-1540497077202-7c8a3999166f?w=500&auto=format&fit=crop" alt="Fasilitas Gym 1" class="gallery-item">
            <img src="https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?w=500&auto=format&fit=crop" alt="Fasilitas Gym 2" class="gallery-item">
            <img src="https://images.unsplash.com/photo-1518611012118-696072aa579a?w=500&auto=format&fit=crop" alt="Kelas Senam" class="gallery-item">
            <img src="https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=500&auto=format&fit=crop" alt="Alat Beban" class="gallery-item">
        </div>
        <div style="text-align: center; margin-top: 40px;">
            <button class="btn-action" style="width: auto; padding: 0 40px;" onclick="alert('Membuka Semua Galeri Video & Foto...')">Lihat Semua Media</button>
        </div>
    </section>

    <footer>
        <h3>Vanda Gym Classic Palangkaraya</h3>
        <p>📍 Jl. Kapten Pierre Tendean No.17</p>
        <p>📞 CS Gym: 081248394890 | 📱 Info Kelas: 0821-5992-5490</p>
        <p style="margin-top: 30px; font-size: 0.85rem; color: #555;">&copy; 2026 Vanda Gym Classic Room. Sistem Informasi Manajemen Member.</p>
    </footer>

    <button class="chatbot-btn" onclick="toggleChat()">🤖</button>
    
    <div class="chatbot-window" id="chatWindow">
        <div class="chat-header">
            <span>Vanda AI Assistant</span>
            <button class="close-chat" onclick="toggleChat()" title="Tutup Chat">×</button>
        </div>
        <div class="chat-body clearfix" id="chatBody">
            <div class="chat-msg">Saya asisten AI Vanda Gym. Ada yang bisa saya bantu terkait jadwal, harga, atau info latihan?</div>
        </div>
        <div class="chat-input-area">
            <input type="text" placeholder="Ketik pertanyaanmu..." id="chatInput" onkeypress="handleEnter(event)">
            <button onclick="sendChat()">Kirim</button>
        </div>
    </div>

    <script>
        function toggleChat() {
            const chat = document.getElementById("chatWindow");
            chat.style.display = chat.style.display === "block" ? "none" : "block";
        }

        function handleEnter(event) {
            if (event.key === "Enter") { sendChat(); }
        }

        function sendChat() {
            const input = document.getElementById("chatInput");
            const body = document.getElementById("chatBody");
            const text = input.value.trim();
            
            if(text !== "") {
                // Cetak pesan User
                body.innerHTML += '<div class="chat-msg user">' + text + '</div><div class="clearfix"></div>';
                input.value = "";
                body.scrollTop = body.scrollHeight;

                // Simulasi delay respons API Gemini
                setTimeout(function() {
                    body.innerHTML += '<div class="chat-msg" style="border-left: 3px solid var(--accent-gold);"><em>Memproses respon dengan Gemini API... (Fitur Backend segera diimplementasikan)</em></div><div class="clearfix"></div>';
                    body.scrollTop = body.scrollHeight;
                }, 1000);
            }
        }
    </script>
</body>
</html>