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

        @media (max-width: 768px) {
            body { padding: 20px 15px; }
            .calc-container { padding: 25px 20px; }
            .grid-2 { grid-template-columns: 1fr; gap: 12px; }
            .macro-grid { grid-template-columns: 1fr; gap: 12px; }
            .form-header h2 { font-size: 1.4rem; }
            .result-value { font-size: 2.2rem; }
            .macro-number { font-size: 1.2rem; }
            .btn-submit { font-size: 1rem; min-height: 44px; }
        }
        @media (max-width: 480px) {
            .calc-container { padding: 20px 15px; }
            .result-box { padding: 15px; }
        }
    </style>
</head>
<body>

    <div class="calc-container">
        <div class="nav-top">
            <a href="index.php" id="btnBack" class="btn-back-square" title="Kembali">←</a>
        </div>

        <div class="form-header">
            <h2>Kalkulator <span>Gizi</span></h2>
            <p>Ketahui secara pasti target kalori dan protein harianmu.</p>
        </div>

        <!-- Kotak Peringatan Validasi -->
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
                    <label>Usia (Tahun)</label>
                    <input type="number" id="kalUsia" class="form-control" required placeholder="Cth: 22">
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Berat Badan (kg)</label>
                    <input type="number" id="kalBb" class="form-control" required step="0.1" placeholder="Cth: 65">
                </div>
                <div class="form-group">
                    <label>Tinggi Badan (cm)</label>
                    <input type="number" id="kalTb" class="form-control" required step="0.1" placeholder="Cth: 170">
                </div>
            </div>

            <div class="form-group">
                <label>Tingkat Aktivitas Fisik <span class="help-icon" onclick="showHelp('tdee')">?</span></label>
                <select id="kalAktivitas" class="form-control" required>
                    <option value="" disabled selected>-- Pilih Rutinitas Anda --</option>
                    <option value="1.2">Jarang Gerak (Sedentari / Minim olahraga)</option>
                    <option value="1.375">Ringan (Olahraga 1-3 hari/minggu)</option>
                    <option value="1.55">Sedang (Olahraga 3-5 hari/minggu)</option>
                    <option value="1.725">Berat (Olahraga 6-7 hari/minggu)</option>
                    <option value="1.9">Sangat Berat (Atlet / Pekerja fisik berat)</option>
                </select>
            </div>

            <div class="form-group">
                <label>Apa Tujuan Anda?</label>
                <select id="kalTarget" class="form-control" required>
                    <option value="" disabled selected>-- Pilih Target Badan --</option>
                    <option value="weight_loss">Menurunkan Berat Badan (Cutting)</option>
                    <option value="maintenance">Menjaga Berat Badan (Maintenance)</option>
                    <option value="weight_gain">Menaikkan Berat Badan (Bulking)</option>
                </select>
            </div>

            <button type="submit" class="btn-submit">Hitung Kebutuhan</button>
        </form>

        <div id="hasilGizi" class="result-box">
            <div class="result-title">Target Makanan Harian Kamu:</div>
            <div class="result-value" id="resKalori">0 <span style="font-size: 1.2rem; color: #888;">Kkal</span></div>
            <div class="result-desc" id="resTargetDesc">Angka ini adalah panduan porsi makan yang harus kamu tuju setiap harinya agar target tercapai.</div>
            
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

            <div class="macro-grid" style="grid-template-columns: 1fr; margin-top: 15px;">
                <div class="macro-item">
                    <span class="macro-label">Target Protein Harian <span class="help-icon" onclick="showHelp('protein')">?</span></span>
                    <span class="macro-number" id="resProtein">0g</span>
                    <span class="macro-note">Asupan Otot</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Penjelasan Singkat -->
    <div id="infoModal" class="modal-overlay">
        <div class="modal-content">
            <h3 id="modalTitle" class="modal-title">Judul</h3>
            <p id="modalText" class="modal-text">Teks penjelasan akan muncul di sini.</p>
            <button class="btn-close" onclick="closeHelp()">Paham!</button>
        </div>
    </div>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const source = urlParams.get('source');
        const backBtn = document.getElementById('btnBack');

        if (source === 'dasbor') {
            backBtn.href = 'member_dasbor.php';
        } else {
            backBtn.href = 'index.php';
        }

        // Fungsi Menampilkan Modal Penjelasan
        function showHelp(tipe) {
            const modal = document.getElementById('infoModal');
            const title = document.getElementById('modalTitle');
            const text = document.getElementById('modalText');

            if (tipe === 'bmr') {
                title.innerText = 'Apa itu BMR?';
                text.innerHTML = '<strong>Basal Metabolic Rate (BMR)</strong> adalah kalori minimal agar organ tubuhmu tetap berfungsi normal meskipun kamu rebahan seharian. Jangan pernah makan di bawah angka ini agar metabolisme tidak rusak!';
            } else if (tipe === 'tdee') {
                title.innerText = 'Apa itu TDEE?';
                text.innerHTML = '<strong>Total Daily Energy Expenditure (TDEE)</strong> adalah total energi yang dibakar tubuhmu dalam 24 jam, sudah mempertimbangkan seluruh aktivitas dari berjalan hingga olahraga (mengacu pada tabel faktor aktivitas).';
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

            // 1. Validasi Input Tidak Masuk Akal
            let warningMsg = [];
            if (usia > 100 || usia < 10) warningMsg.push("Usia tampak kurang wajar.");
            if (bb > 250 || bb < 20) warningMsg.push("Berat badan tampak kurang wajar.");
            if (tb > 250 || tb < 80) warningMsg.push("Tinggi badan tampak kurang wajar.");

            const warnBox = document.getElementById('warningBox');
            if (warningMsg.length > 0) {
                warnBox.innerHTML = `<strong>⚠️ Peringatan:</strong> ${warningMsg.join(" ")} <br><em>Namun, perhitungan tetap dilanjutkan. Pastikan data yang diketik sudah benar.</em>`;
                warnBox.style.display = 'block';
            } else {
                warnBox.style.display = 'none';
            }

            // 2. Hitung BMR (Algoritma Mifflin-St Jeor)
            // Laki-laki: (10 × BB) + (6.25 × TB) - (5 × Usia) + 5
            // Perempuan: (10 × BB) + (6.25 × TB) - (5 × Usia) - 161
            let bmr = (10 * bb) + (6.25 * tb) - (5 * usia);
            if (gender === 'laki') {
                bmr += 5;
            } else {
                bmr -= 161; 
            }

            // 3. Hitung TDEE (Total Daily Energy Expenditure)
            let tdee = bmr * aktivitas;

            // 4. Sesuaikan Kalori dengan Kategori Manajemen Komposisi Tubuh
            let kaloriFinal = tdee;
            let targetDesc = "";

            if (target === 'weight_loss') {
                kaloriFinal -= 500; // Defisit Kalori
                targetDesc = "Kamu sedang menargetkan <strong>Penurunan Berat Badan</strong>. Makanlah lebih sedikit dari total energi harianmu agar tubuh membakar lemak sebagai tenaga.";
            } else if (target === 'weight_gain') {
                kaloriFinal += 500; // Surplus Kalori
                targetDesc = "Kamu sedang menargetkan <strong>Kenaikan Berat Badan</strong>. Makanlah lebih banyak dari biasanya agar otot dan badanmu bisa tumbuh membesar.";
            } else {
                // Maintenance
                targetDesc = "Kamu sedang menargetkan <strong>Menjaga Berat Badan</strong>. Porsi ini pas untuk mempertahankan bentuk badanmu yang sekarang agar tidak naik dan tidak turun.";
            }

            // 5. Estimasi Kebutuhan Protein (1.6g - 2.2g dikali berat badan)
            let proteinMin = Math.round(bb * 1.6);
            let proteinMax = Math.round(bb * 2.2);

            // 6. Tampilkan Hasil
            document.getElementById('hasilGizi').style.display = 'block';
            document.getElementById('resKalori').innerHTML = Math.round(kaloriFinal).toLocaleString('id-ID') + ' <span style="font-size: 1.2rem; color: #888;">Kkal</span>';
            document.getElementById('resTargetDesc').innerHTML = targetDesc;
            
            document.getElementById('resTdee').innerText = Math.round(tdee).toLocaleString('id-ID') + ' Kkal';
            document.getElementById('resBmr').innerText = Math.round(bmr).toLocaleString('id-ID') + ' Kkal';
            document.getElementById('resProtein').innerText = proteinMin + ' - ' + proteinMax + ' gram';
            
            setTimeout(() => {
                document.getElementById('hasilGizi').scrollIntoView({ behavior: 'smooth', block: 'end' });
            }, 100);
        }
    </script>
</body>
</html>