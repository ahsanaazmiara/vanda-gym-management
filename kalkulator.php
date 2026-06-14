<?php
// Ambil sumber halaman untuk menentukan navigasi mana yang ditampilkan
$source = $_GET['source'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalkulator Gizi - Vanda Gym Classic</title>
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

        .calc-container {
            background-color: #0a0a0a;
            border: 1px solid #333; border-top: 4px solid var(--primary-red);
            border-radius: 8px; padding: 30px; width: 100%; max-width: 650px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.8);
        }

        .nav-top { margin-bottom: 20px; }
        .btn-back-square { 
            width: 44px; height: 44px; 
            background-color: #1a1a1a; border: 1px solid #333; 
            color: var(--accent-gold); border-radius: 4px;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; font-weight: bold; font-size: 1.2rem;
            transition: 0.3s;
        }
        .btn-back-square:hover { background-color: var(--primary-red); color: #000; }

        .form-header { text-align: center; margin-bottom: 25px; }
        .form-header h2 { color: var(--text-light); text-transform: uppercase; }
        .form-header span { color: var(--accent-gold); }
        .form-header p { color: #888; font-size: 0.9rem; margin-top: 5px; line-height: 1.4; }

        .form-group { margin-bottom: 15px; position: relative; }
        .form-group label { display: flex; align-items: center; margin-bottom: 8px; color: #ccc; font-weight: 600; font-size: 0.9rem;}
        
        .form-control {
            width: 100%; padding: 10px 15px; min-height: 44px;
            background-color: var(--input-bg); border: 1px solid #333;
            border-radius: 4px; color: white; font-size: 1rem;
        }
        .form-control:focus { outline: none; border-color: var(--accent-gold); }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }

        .btn-submit {
            width: 100%; background-color: var(--primary-red); color: white;
            border: none; min-height: 48px; font-size: 1.1rem; font-weight: bold;
            border-radius: 4px; cursor: pointer; text-transform: uppercase; margin-top: 10px;
            transition: 0.3s;
        }
        .btn-submit:hover { background-color: #a81a1a; }

        /* Ikon Bantuan & Warning */
        .help-icon {
            display: inline-flex; align-items: center; justify-content: center;
            width: 18px; height: 18px; border-radius: 50%;
            background-color: #333; color: var(--accent-gold);
            font-size: 0.75rem; font-weight: bold; cursor: pointer;
            margin-left: 8px; transition: 0.2s; border: 1px solid var(--accent-gold);
        }
        .help-icon:hover { background-color: var(--accent-gold); color: #000; }

        .warning-box {
            display: none; background: rgba(255, 193, 7, 0.1); 
            border: 1px solid #ffc107; color: #ffc107;
            padding: 12px; border-radius: 4px; font-size: 0.85rem;
            margin-bottom: 20px; line-height: 1.4;
        }

        /* Modal Bantuan */
        .modal-overlay {
            display: none; position: fixed; top: 0; left: 0;
            width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7);
            z-index: 1000; justify-content: center; align-items: center;
            padding: 20px;
        }
        .modal-content {
            background: #111; border: 1px solid var(--accent-gold);
            border-radius: 8px; padding: 25px; max-width: 400px;
            text-align: center; animation: fadeIn 0.3s; position: relative;
        }
        .modal-title { color: var(--accent-gold); font-size: 1.2rem; margin-bottom: 10px; text-transform: uppercase;}
        .modal-text { color: #ccc; font-size: 0.9rem; line-height: 1.5; margin-bottom: 20px;}
        .btn-close {
            background: var(--primary-red); color: white; border: none;
            padding: 8px 20px; border-radius: 4px; cursor: pointer; font-weight: bold;
        }

        /* Hasil Kalkulator */
        .result-box {
            margin-top: 25px; padding: 20px; border-radius: 8px;
            background: #111; border: 1px dashed var(--accent-gold); display: none;
            text-align: center; animation: fadeIn 0.5s;
        }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

        .result-title { font-size: 1rem; color: var(--text-light); font-weight: bold; margin-bottom: 5px; }
        .result-value { font-size: 2.5rem; font-weight: bold; color: var(--accent-gold); margin-bottom: 5px; line-height: 1;}
        .result-desc { font-size: 0.85rem; color: #aaa; margin-bottom: 20px; line-height: 1.5;}
        
        .macro-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px; text-align: left;}
        .macro-item { background: #0a0a0a; padding: 15px; border-radius: 4px; border: 1px solid #222;}
        .macro-label { color: var(--accent-gold); font-size: 0.9rem; font-weight: bold; display: flex; align-items: center; margin-bottom: 5px; }
        .macro-number { color: var(--text-light); font-weight: bold; font-size: 1.4rem; display: block; margin-bottom: 5px; }
        .macro-note { font-size: 0.75rem; color: #777; display: block;}

        .bottom-nav-mobile { display: none !important; }

        /* =========================================
           MOBILE RESPONSIVE
           ========================================= */
        @media (max-width: 768px) {
            body { padding: 15px 10px 85px 10px; } /* 85px agar tidak tertutup nav bawah */
            .calc-container { padding: 15px 12px; }
            
            .nav-top { margin-bottom: 10px; }
            .btn-back-square { width: 32px; height: 32px; font-size: 1rem; }
            
            .form-header { margin-bottom: 15px; }
            .form-header h2 { font-size: 1.15rem; margin-bottom: 2px; }
            .form-header p { font-size: 0.75rem; line-height: 1.2; margin-top: 0;}
            
            .grid-2 { grid-template-columns: 1fr 1fr; gap: 8px; }
            
            .form-group { margin-bottom: 10px; }
            .form-group label { font-size: 0.75rem; margin-bottom: 4px; }
            
            .form-control { padding: 6px 10px; min-height: 34px; font-size: 0.8rem; }
            .btn-submit { font-size: 0.9rem; min-height: 38px; margin-top: 5px; }
            
            .result-box { margin-top: 15px; padding: 12px; }
            .result-title { font-size: 0.85rem; margin-bottom: 2px;}
            .result-value { font-size: 1.8rem; margin-bottom: 2px;}
            .result-value span { font-size: 0.9rem !important; }
            .result-desc { font-size: 0.75rem; margin-bottom: 12px; line-height: 1.3;}
            
            .macro-grid { grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 8px; }
            .macro-item { padding: 10px; }
            .macro-label { font-size: 0.75rem; margin-bottom: 2px;}
            .macro-number { font-size: 1.1rem; margin-bottom: 2px;}
            .macro-note { font-size: 0.7rem; }

            /* =========================================
               NAVIGASI BAWAH MOBILE (STANDAR)
               ========================================= */
            .bottom-nav-mobile {
                display: flex !important;
                position: fixed !important;
                bottom: 0 !important;
                left: 0 !important;
                width: 100vw !important;
                height: 70px !important;
                background-color: #0a0a0a !important;
                border-top: 1px solid #333 !important;
                justify-content: space-around !important;
                align-items: center !important;
                z-index: 2147483647 !important;
                box-shadow: 0 -5px 15px rgba(0,0,0,0.9) !important;
            }

            .bottom-nav-mobile .nav-item {
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center !important;
                color: #ccc !important;
                text-decoration: none !important;
                font-size: 10px !important;
                background: transparent !important;
                border: none !important;
                flex: 1 !important;
                gap: 4px !important;
                cursor: pointer !important;
                padding: 5px 0 !important;
                transition: 0.3s;
            }

            .bottom-nav-mobile .nav-item:hover, 
            .bottom-nav-mobile .nav-item:active {
                color: var(--accent-gold, #E8C999) !important;
            }

            .bottom-nav-mobile .nav-item svg {
                width: 22px !important;
                height: 22px !important;
                stroke: currentColor !important;
                fill: none !important;
                stroke-width: 2 !important;
                stroke-linecap: round !important;
                stroke-linejoin: round !important;
            }

            /* Menu Aktif / Highlight */
            .bottom-nav-mobile .nav-item.highlight {
                color: var(--accent-gold, #E8C999) !important;
                font-weight: bold !important;
            }
            .bottom-nav-mobile .nav-item.highlight svg {
                stroke: var(--accent-gold, #E8C999) !important;
                fill: none !important; 
            }
        }
        
        @media (max-width: 480px) {
            .calc-container { padding: 12px 10px; }
            .form-control { font-size: 0.75rem; padding: 5px 8px; }
        }

        /* =========================================
           TOMBOL WHATSAPP MELAYANG
           ========================================= */
        .wa-btn {
            position: fixed;
            bottom: 20px;
            left: 20px;
            width: 50px;
            height: 50px;
            background-color: #25D366;
            color: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.5);
            z-index: 1000;
            text-decoration: none;
            transition: transform 0.3s ease;
        }

        .wa-btn:hover {
            transform: scale(1.1);
        }

        .wa-btn svg {
            width: 28px;
            height: 28px;
        }

        @media (max-width: 768px) {
            .wa-btn {
                bottom: 85px !important; /* Diangkat agar tidak tertutup menu mobile */
                left: 15px !important;
                width: 45px !important;
                height: 45px !important;
            }
            .wa-btn svg {
                width: 24px !important;
                height: 24px !important;
            }
        }
    </style>
</head>
<body>

    <div class="calc-container">
        <div class="nav-top">
            <a href="<?= ($source === 'dasbor') ? 'member_dasbor.php' : 'index.php' ?>" id="btnBack" class="btn-back-square" title="Kembali">←</a>
        </div>

        <div class="form-header">
            <h2>Kalkulator <span>Gizi</span></h2>
            <p>Ketahui target kalori & protein harianmu.</p>
        </div>

        <div id="warningBox" class="warning-box"></div>

        <form id="formKalkulator" onsubmit="hitungGizi(event)">
            <div class="grid-2">
                <div class="form-group">
                    <label>Jenis Kelamin</label>
                    <select id="kalGender" class="form-control" required>
                        <option value="" disabled selected>-- Pilih --</option>
                        <option value="laki">Laki-laki</option>
                        <option value="perempuan">Perempuan</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Usia (Thn)</label>
                    <input type="number" id="kalUsia" class="form-control" required placeholder="Cth: 22">
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Berat (kg)</label>
                    <input type="number" id="kalBb" class="form-control" required step="0.1" placeholder="Cth: 65">
                </div>
                <div class="form-group">
                    <label>Tinggi (cm)</label>
                    <input type="number" id="kalTb" class="form-control" required step="0.1" placeholder="Cth: 170">
                </div>
            </div>

            <div class="form-group">
                <label>Aktivitas Fisik <span class="help-icon" onclick="showHelp('tdee')">?</span></label>
                <select id="kalAktivitas" class="form-control" required>
                    <option value="" disabled selected>-- Pilih Rutinitas --</option>
                    <option value="1.2">Jarang Gerak (Minim olahraga)</option>
                    <option value="1.375">Ringan (Olahraga 1-3 hari/mgg)</option>
                    <option value="1.55">Sedang (Olahraga 3-5 hari/mgg)</option>
                    <option value="1.725">Berat (Olahraga 6-7 hari/mgg)</option>
                    <option value="1.9">Sangat Berat (Pekerja fisik berat)</option>
                </select>
            </div>

            <div class="form-group">
                <label>Tujuan Anda?</label>
                <select id="kalTarget" class="form-control" required>
                    <option value="" disabled selected>-- Pilih Target Badan --</option>
                    <option value="weight_loss">Turun Berat Badan (Cutting)</option>
                    <option value="maintenance">Jaga Berat Badan (Maintenance)</option>
                    <option value="weight_gain">Naik Berat Badan (Bulking)</option>
                </select>
            </div>

            <button type="submit" class="btn-submit">Hitung Kebutuhan</button>
        </form>

        <div id="hasilGizi" class="result-box">
            <div class="result-title">Target Makan Harian Kamu:</div>
            <div class="result-value" id="resKalori">0 <span style="font-size: 1.2rem; color: #888;">Kkal</span></div>
            <div class="result-desc" id="resTargetDesc">Angka ini adalah panduan porsi makan yang harus kamu tuju.</div>
            
            <div class="macro-grid">
                <div class="macro-item">
                    <span class="macro-label">TDEE <span class="help-icon" onclick="showHelp('tdee')">?</span></span>
                    <span class="macro-number" id="resTdee">0 Kkal</span>
                    <span class="macro-note">Total Energi</span>
                </div>
                <div class="macro-item">
                    <span class="macro-label">BMR <span class="help-icon" onclick="showHelp('bmr')">?</span></span>
                    <span class="macro-number" id="resBmr">0 Kkal</span>
                    <span class="macro-note">Kalori Minimal</span>
                </div>
            </div>

            <div class="macro-grid" style="grid-template-columns: 1fr; margin-top: 8px;">
                <div class="macro-item" style="text-align: center;">
                    <span class="macro-label" style="justify-content: center;">Target Protein <span class="help-icon" onclick="showHelp('protein')">?</span></span>
                    <span class="macro-number" id="resProtein">0g</span>
                    <span class="macro-note">Asupan Otot Harian</span>
                </div>
            </div>
        </div>
    </div>

    <div id="infoModal" class="modal-overlay">
        <div class="modal-content">
            <h3 id="modalTitle" class="modal-title">Judul</h3>
            <p id="modalText" class="modal-text">Teks penjelasan akan muncul di sini.</p>
            <button class="btn-close" onclick="closeHelp()">Paham!</button>
        </div>
    </div>

    <?php if ($source === 'dasbor'): ?>
        <div class="bottom-nav-mobile">
            <a href="member_dasbor.php" class="nav-item">
                <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                <span>Dasbor</span>
            </a>
            <a href="kalkulator.php?source=dasbor" class="nav-item highlight">
                <svg viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="8" y1="6" x2="16" y2="6"></line><line x1="16" y1="14" x2="16.01" y2="14"></line><line x1="12" y1="14" x2="12.01" y2="14"></line><line x1="8" y1="14" x2="8.01" y2="14"></line></svg>
                <span>Gizi</span>
            </a>
            <a href="galeri_member.php" class="nav-item">
                <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                <span>Tutorial</span>
            </a>
            <a href="chatbot_member.php" class="nav-item">
                <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="10" rx="2"></rect><circle cx="12" cy="5" r="2"></circle><path d="M12 7v4"></path></svg>
                <span>AI Bot</span>
            </a>
            <a href="profil_member.php" class="nav-item">
                <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                <span>Profil</span>
            </a>
        </div>
    <?php else: ?>
        <div class="bottom-nav-mobile">
            <a href="index.php#paket" class="nav-item">
                <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                <span>Paket</span>
            </a>
            <a href="index.php#jadwal" class="nav-item">
                <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                <span>Jadwal</span>
            </a>
            <a href="index.php#galeri" class="nav-item">
                <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                <span>Galeri</span>
            </a>
            <a href="kalkulator.php" class="nav-item highlight">
                <svg viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="8" y1="6" x2="16" y2="6"></line><line x1="16" y1="14" x2="16.01" y2="14"></line><line x1="12" y1="14" x2="12.01" y2="14"></line><line x1="8" y1="14" x2="8.01" y2="14"></line><line x1="16" y1="18" x2="16.01" y2="18"></line><line x1="12" y1="18" x2="12.01" y2="18"></line><line x1="8" y1="18" x2="8.01" y2="18"></line></svg>
                <span>Gizi</span>
            </a>
            <a href="login.php" class="nav-item">
                <svg viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                <span>Login</span>
            </a>
            <a href="daftar.php" class="nav-item">
                <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                <span>Daftar</span>
            </a>
        </div>

        
    <?php endif; ?>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const source = urlParams.get('source');
        const backBtn = document.getElementById('btnBack');

        if (source === 'dasbor') {
            backBtn.href = 'member_dasbor.php';
        } else {
            backBtn.href = 'index.php';
        }

        function showHelp(tipe) {
            const modal = document.getElementById('infoModal');
            const title = document.getElementById('modalTitle');
            const text = document.getElementById('modalText');

            if (tipe === 'bmr') {
                title.innerText = 'Apa itu BMR?';
                text.innerHTML = '<strong>Basal Metabolic Rate (BMR)</strong> adalah kalori minimal agar organ tubuhmu tetap berfungsi normal meskipun kamu rebahan seharian. Jangan pernah makan di bawah angka ini agar metabolisme tidak rusak!';
            } else if (tipe === 'tdee') {
                title.innerText = 'Apa itu TDEE?';
                text.innerHTML = '<strong>Total Daily Energy Expenditure (TDEE)</strong> adalah total energi yang dibakar tubuhmu dalam 24 jam, sudah mempertimbangkan aktivitas harianmu.';
            } else if (tipe === 'protein') {
                title.innerText = 'Target Protein';
                text.innerHTML = 'Asupan protein disarankan antara <strong>1,6 - 2,2 gram per kg berat badan</strong> untuk memelihara dan membangun massa otot yang optimal.';
            }

            modal.style.display = 'flex';
        }

        function closeHelp() {
            document.getElementById('infoModal').style.display = 'none';
        }

        function hitungGizi(e) {
            e.preventDefault();

            const gender = document.getElementById('kalGender').value;
            const usia = parseInt(document.getElementById('kalUsia').value);
            const bb = parseFloat(document.getElementById('kalBb').value);
            const tb = parseFloat(document.getElementById('kalTb').value);
            const aktivitas = parseFloat(document.getElementById('kalAktivitas').value);
            const target = document.getElementById('kalTarget').value;

            // Validasi Input
            let warningMsg = [];
            if (usia > 100 || usia < 10) warningMsg.push("Usia kurang wajar.");
            if (bb > 250 || bb < 20) warningMsg.push("Berat badan kurang wajar.");
            if (tb > 250 || tb < 80) warningMsg.push("Tinggi badan kurang wajar.");

            const warnBox = document.getElementById('warningBox');
            if (warningMsg.length > 0) {
                warnBox.innerHTML = `<strong>⚠️ Peringatan:</strong> ${warningMsg.join(" ")} <br><em>Perhitungan tetap dilanjutkan.</em>`;
                warnBox.style.display = 'block';
            } else {
                warnBox.style.display = 'none';
            }

            // Hitung BMR
            let bmr = (10 * bb) + (6.25 * tb) - (5 * usia);
            if (gender === 'laki') {
                bmr += 5;
            } else {
                bmr -= 161; 
            }

            // Hitung TDEE
            let tdee = bmr * aktivitas;

            // Target Kalori
            let kaloriFinal = tdee;
            let targetDesc = "";

            if (target === 'weight_loss') {
                kaloriFinal -= 500;
                targetDesc = "Kamu menargetkan <strong>Penurunan Berat Badan</strong>. Makanlah lebih sedikit dari TDEE agar tubuh membakar lemak.";
            } else if (target === 'weight_gain') {
                kaloriFinal += 500;
                targetDesc = "Kamu menargetkan <strong>Kenaikan Berat Badan</strong>. Makanlah lebih banyak dari TDEE agar otot bisa tumbuh.";
            } else {
                targetDesc = "Kamu menargetkan <strong>Menjaga Berat Badan</strong>. Porsi ini pas untuk mempertahankan bentuk badanmu saat ini.";
            }

            // Protein
            let proteinMin = Math.round(bb * 1.6);
            let proteinMax = Math.round(bb * 2.2);

            // Tampil Hasil
            document.getElementById('hasilGizi').style.display = 'block';
            document.getElementById('resKalori').innerHTML = Math.round(kaloriFinal).toLocaleString('id-ID') + ' <span style="font-size: 1.2rem; color: #888;">Kkal</span>';
            document.getElementById('resTargetDesc').innerHTML = targetDesc;
            
            document.getElementById('resTdee').innerText = Math.round(tdee).toLocaleString('id-ID') + ' Kkal';
            document.getElementById('resBmr').innerText = Math.round(bmr).toLocaleString('id-ID') + ' Kkal';
            document.getElementById('resProtein').innerText = proteinMin + ' - ' + proteinMax + ' g';
            
            setTimeout(() => {
                document.getElementById('hasilGizi').scrollIntoView({ behavior: 'smooth', block: 'end' });
            }, 100);
        }
    </script>

    <!-- TOMBOL WA MELAYANG -->
    <a href="https://wa.me/6282148556601" target="_blank" class="wa-btn" title="Hubungi CS via WhatsApp">
        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
          <path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/>
        </svg>
    </a>
</body>
</html>