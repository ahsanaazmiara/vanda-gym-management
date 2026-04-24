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

        /* Navbar */
        header { 
            background-color: rgba(10, 10, 10, 0.95); 
            padding: 10px 5%; 
            display: flex; justify-content: space-between; align-items: center; 
            position: sticky; top: 0; z-index: 100; 
            border-bottom: 2px solid var(--primary-red); 
        }
        
        .logo img { height: 70px; width: auto; object-fit: contain; }

        nav { display: flex; align-items: center; flex-wrap: wrap; justify-content: flex-end;}
        nav a { 
            color: var(--text-light); text-decoration: none; 
            margin-left: 20px; font-weight: 600; transition: 0.3s;
            min-height: 44px; display: inline-flex; align-items: center;
        }
        nav a:hover { color: var(--accent-gold); }
        
        .nav-login { color: var(--accent-gold); font-weight: bold; margin-right: 5px; }

        .btn-logout { 
            border: 2px solid var(--primary-red); padding: 0 20px; border-radius: 4px; 
            color: var(--primary-red); margin-left: 20px; background: transparent;
            font-weight: bold; font-size: 1rem; min-height: 44px; min-width: 44px;
            cursor: pointer; transition: 0.3s;
        }
        .btn-logout:hover { background-color: var(--primary-red); color: white; }

        /* Container Utama Dasbor */
        .dashboard-container { padding: 40px 5%; max-width: 1200px; margin: 0 auto; }

        /* Notifikasi Alert */
        .alert-box {
            background-color: rgba(232, 201, 153, 0.1); border: 1px solid var(--accent-gold);
            color: var(--accent-gold); padding: 15px 20px; border-radius: 8px;
            margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;
        }
        .alert-box.danger { background-color: rgba(142, 22, 22, 0.15); border-color: var(--primary-red); color: #ff4d4d; }

        /* Grid Atas: Info Profil & Kehadiran */
        .grid-top { display: grid; grid-template-columns: 1.5fr 1fr; gap: 25px; margin-bottom: 30px; }
        @media (max-width: 768px) { .grid-top { grid-template-columns: 1fr; } }

        .dash-card {
            background-color: #0a0a0a; border: 1px solid #222; border-radius: 8px; padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.5); position: relative; overflow: hidden;
        }
        .dash-card::before {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px;
            background: var(--accent-gold);
        }
        .dash-card.card-danger::before { background: var(--primary-red); }

        /* Detail Status Member */
        .profile-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;}
        .user-info h2 { color: var(--text-light); font-size: 1.8rem; text-transform: uppercase; margin-bottom: 5px;}
        .user-info p { color: #888; font-size: 0.95rem; }
        
        .status-badge {
            background: var(--success-green); color: white; padding: 5px 15px; 
            border-radius: 20px; font-weight: bold; font-size: 0.85rem; letter-spacing: 1px;
        }
        .status-badge.danger { background: var(--primary-red); }

        .membership-details { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 20px; border-top: 1px dashed #333; padding-top: 20px;}
        .detail-item span { display: block; color: #888; font-size: 0.85rem; margin-bottom: 3px;}
        .detail-item strong { display: block; color: var(--accent-gold); font-size: 1.2rem; }
        .detail-item strong.danger-text { color: #ff4d4d; }
        
        .payment-proof { font-size: 0.75rem; color: #aaa; margin-top: 2px; font-weight: normal;}

        /* Rekap Kehadiran */
        .attendance-tracker { display: flex; justify-content: space-between; margin-top: 20px;}
        .day-circle {
            width: 40px; height: 40px; border-radius: 50%; border: 2px solid #333;
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            font-size: 0.75rem; color: #666; background: #111;
        }
        .day-circle.hadir { border-color: var(--success-green); background: rgba(40, 167, 69, 0.1); color: var(--success-green); font-weight: bold;}
        .day-circle.absen { border-color: var(--primary-red); background: rgba(142, 22, 22, 0.1); color: var(--primary-red); }
        .day-circle span { font-size: 0.65rem; }

        /* Grid Bawah: Menu Aksi Cepat */
        .action-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 40px;}
        
        .action-btn {
            background: #111; border: 1px solid #333; border-radius: 8px; padding: 25px;
            text-align: center; color: var(--text-light); text-decoration: none; transition: 0.3s;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            min-height: 120px;
        }
        .action-btn:hover { border-color: var(--accent-gold); transform: translateY(-5px); background: #1a1a1a;}
        
        /* Tombol Terkunci */
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
        .btn-primary.disabled { background-color: #333; color: #888; cursor: not-allowed;}

        /* Style Jadwal */
        .section-title { 
            color: var(--accent-gold); text-align: center; font-size: 2rem; 
            text-transform: uppercase; margin-bottom: 30px; position: relative; 
            border-bottom: 1px solid #333; padding-bottom: 10px;
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

        /* Tombol WA & Chatbot (Kiri dan Kanan Bawah) */
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
        .chatbot-btn.locked { 
            background-color: #333; color: #888; border: 2px solid #555; cursor: not-allowed; pointer-events: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            header { flex-direction: column; padding: 15px; }
            nav { margin-top: 15px; justify-content: center;}
            nav a { margin: 5px 10px; font-size: 0.9rem;}
            .btn-logout { margin-left: 0; margin-top: 10px; width: 100%; text-align: center;}
        }
    </style>
</head>
<body>

    <header>
        <div class="logo">
            <img src="assets/logo.png" alt="Vanda Gym Classic Logo">
        </div>
        <nav>
            <a href="profil_member.php">Profil</a>
            <a href="chatbot_member.php" id="navChatbot">Chatbot AI</a>
            <a href="kalkulator.php?source=dasbor">Kalkulator Gizi</a>
            <a href="galeri_member.php" id="navGaleri">Galeri Gym</a>
            <button class="btn-logout" onclick="window.location.href='login.php'">Keluar</button>
        </nav>
    </header>

    <div class="dashboard-container" id="mainDashboard">
        </div>

    <div class="dashboard-container" style="padding-top: 0;">
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
    </div>

    <a href="https://wa.me/6282148556601" target="_blank" class="wa-btn" title="Hubungi CS via WhatsApp">
        <svg viewBox="0 0 24 24">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/>
        </svg>
    </a>

    <a href="chatbot_member.php" id="floatingChatbot" class="chatbot-btn" title="Tanya Chatbot AI Vanda">🤖</a>

    <script>
        // Simulasi Perubahan Tampilan Berdasarkan URL Parameter (status=kadaluarsa)
        const urlParams = new URLSearchParams(window.location.search);
        const statusMember = urlParams.get('status') || 'aktif'; // Default aktif

        const dashboardContainer = document.getElementById('mainDashboard');
        const floatingChatbot = document.getElementById('floatingChatbot');
        
        // Ambil elemen menu navbar
        const navChatbot = document.getElementById('navChatbot');
        const navGaleri = document.getElementById('navGaleri');

        if (statusMember === 'kadaluarsa') {
            // --- KUNCI NAVBAR ---
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

            // --- KUNCI CHATBOT FLOATING ---
            floatingChatbot.classList.add('locked');
            floatingChatbot.title = "AI Terkunci";
            floatingChatbot.href = "#";
            floatingChatbot.onclick = (e) => {
                e.preventDefault();
                alert('Fitur Chatbot AI terkunci. Silakan perpanjang membership Anda.');
            };

            // --- TAMPILAN KEDALUWARSA ---
            dashboardContainer.innerHTML = `
                <div class="alert-box danger">
                    <div>
                        <strong>Perhatian:</strong> Masa aktif membership Anda telah <strong>KEDALUWARSA</strong>.
                    </div>
                    <a href="perpanjang.php" class="btn-primary" style="min-height: 35px; padding: 5px 15px; font-size: 0.9rem;">Perpanjang Sekarang</a>
                </div>

                <div class="grid-top">
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

                    <div class="dash-card" style="border-top-color: #333; opacity: 0.7;">
                        <h3 style="color: var(--text-light); margin-bottom: 5px; font-size: 1.1rem;">Rekap Kehadiran (Minggu Ini)</h3>
                        <p style="color: #ff4d4d; font-size: 0.85rem;">Membership Anda tidak aktif.</p>
                        
                        <div class="attendance-tracker">
                            <div class="day-circle" title="Terkunci">Sen<span>-</span></div>
                            <div class="day-circle" title="Terkunci">Sel<span>-</span></div>
                            <div class="day-circle" title="Terkunci">Rab<span>-</span></div>
                            <div class="day-circle" title="Terkunci">Kam<span>-</span></div>
                            <div class="day-circle" title="Terkunci">Jum<span>-</span></div>
                            <div class="day-circle" title="Terkunci">Sab<span>-</span></div>
                            <div class="day-circle" title="Terkunci">Min<span>-</span></div>
                        </div>
                        
                        <div style="margin-top: 20px; text-align: center;">
                            <button class="btn-primary disabled" style="width: 100%;">Absen Terkunci</button>
                        </div>
                    </div>
                </div>

                <h3 style="color: var(--accent-gold); border-bottom: 1px solid #333; padding-bottom: 10px; margin-bottom: 20px;">Aksi Cepat</h3>

                <div class="action-grid">
                    <a href="kalkulator.php?source=dasbor" class="action-btn">
                        <div class="action-icon">⚖️</div>
                        <h3>Kalkulator Gizi</h3>
                        <p>Hitung ulang kalori & protein harian.</p>
                    </a>

                    <a href="perpanjang.php" class="action-btn" style="border-color: var(--primary-red);" onclick="alert('Diarahkan ke form perpanjangan tagihan...')">
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
            // --- TAMPILAN AKTIF ---
            dashboardContainer.innerHTML = `
                <div class="alert-box">
                    <div>
                        <strong>Info:</strong> Masa aktif membership Anda tersisa <strong style="font-size:1.2rem;">7 Hari</strong> lagi.
                    </div>
                    <a href="perpanjang.php" class="btn-primary" style="min-height: 35px; padding: 5px 15px; font-size: 0.9rem;">Perpanjang Sekarang</a>
                </div>

                <div class="grid-top">
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

                    <div class="dash-card" style="border-top-color: #333;">
                        <h3 style="color: var(--text-light); margin-bottom: 5px; font-size: 1.1rem;">Rekap Kehadiran (Minggu Ini)</h3>
                        <p style="color: #888; font-size: 0.85rem;">Pantau jadwal latihan harian Anda.</p>
                        
                        <div class="attendance-tracker">
                            <div class="day-circle hadir" title="Hadir">Sen<span>✔</span></div>
                            <div class="day-circle absen" title="Bolos">Sel<span>✖</span></div>
                            <div class="day-circle hadir" title="Hadir">Rab<span>✔</span></div>
                            <div class="day-circle" title="Belum">Kam<span>-</span></div>
                            <div class="day-circle" title="Belum">Jum<span>-</span></div>
                            <div class="day-circle" title="Belum">Sab<span>-</span></div>
                            <div class="day-circle" title="Belum">Min<span>-</span></div>
                        </div>
                        
                        <div style="margin-top: 20px; text-align: center;">
                            <button class="btn-primary" style="width: 100%; background-color: var(--accent-gold); color: #000;" onclick="alert('Fitur Absensi Scan Barcode segera hadir!')">Absen Kehadiran Hari Ini</button>
                        </div>
                    </div>
                </div>

                <h3 style="color: var(--accent-gold); border-bottom: 1px solid #333; padding-bottom: 10px; margin-bottom: 20px;">Aksi Cepat</h3>

                <div class="action-grid">
                    <a href="kalkulator.php?source=dasbor" class="action-btn">
                        <div class="action-icon">⚖️</div>
                        <h3>Kalkulator Gizi</h3>
                        <p>Hitung ulang kalori & protein harian.</p>
                    </a>

                    <a href="perpanjang.php" class="action-btn" onclick="alert('Diarahkan ke form perpanjangan tagihan...')">
                        <div class="action-icon">💳</div>
                        <h3>Perpanjang Member</h3>
                        <p>Bayar tagihan bulan berikutnya.</p>
                    </a>

                    <a href="chatbot_member.php" class="action-btn">
                        <div class="action-icon">🤖</div>
                        <h3>Tanya Chatbot AI Vanda</h3>
                        <p>Cek kalori makanan via foto.</p>
                    </a>

                    <a href="galeri_member.php" class="action-btn" onclick="alert('Membuka akses galeri video panduan gerakan gym eksklusif.')">
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