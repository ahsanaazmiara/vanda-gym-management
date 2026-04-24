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
        .form-group label { display: block; margin-bottom: 8px; color: #ccc; font-weight: 600; font-size: 0.9rem;}
        
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

        /* Hasil Kalkulator */
        .result-box {
            margin-top: 25px; padding: 20px; border-radius: 8px;
            background: #111; border: 1px dashed var(--accent-gold); display: none;
            text-align: center; animation: fadeIn 0.5s;
        }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

        .result-title { font-size: 1rem; color: var(--text-light); font-weight: bold; margin-bottom: 5px; }
        .result-value { font-size: 2.5rem; font-weight: bold; color: var(--accent-gold); margin-bottom: 5px; line-height: 1;}
        .result-desc { font-size: 0.85rem; color: #aaa; margin-bottom: 20px; }
        
        .macro-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px; text-align: left;}
        .macro-item { background: #0a0a0a; padding: 15px; border-radius: 4px; border: 1px solid #222;}
        .macro-label { color: var(--accent-gold); font-size: 0.9rem; font-weight: bold; display: block; margin-bottom: 5px; }
        .macro-number { color: var(--text-light); font-weight: bold; font-size: 1.4rem; display: block; margin-bottom: 5px;}
        .macro-note { font-size: 0.75rem; color: #777; line-height: 1.4; display: block;}

        .note { font-size: 0.8rem; color: #555; margin-top: 20px; text-align: left; line-height: 1.5; border-top: 1px solid #222; padding-top: 15px;}
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
                    <input type="number" id="kalUsia" class="form-control" min="15" max="80" required placeholder="Cth: 22">
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Berat Badan (kg)</label>
                    <input type="number" id="kalBb" class="form-control" min="30" max="200" required placeholder="Cth: 65">
                </div>
                <div class="form-group">
                    <label>Tinggi Badan (cm)</label>
                    <input type="number" id="kalTb" class="form-control" min="100" max="250" required placeholder="Cth: 170">
                </div>
            </div>

            <div class="form-group">
                <label>Tingkat Aktivitas Fisik (TDEE)</label>
                <select id="kalAktivitas" class="form-control" required>
                    <option value="" disabled selected>-- Pilih Rutinitas Anda --</option>
                    <option value="1.2">Sedentari (Aktivitas minimal / Jarang gerak)</option>
                    <option value="1.375">Ringan (Olahraga 1-3 hari/minggu)</option>
                    <option value="1.55">Moderat (Olahraga 3-5 hari/minggu)</option>
                    <option value="1.725">Berat (Olahraga 6-7 hari/minggu)</option>
                    <option value="1.9">Sangat Berat (Atlet / Kerja fisik berat)</option>
                </select>
            </div>

            <div class="form-group">
                <label>Target Manajemen Tubuh</label>
                <select id="kalTarget" class="form-control" required>
                    <option value="" disabled selected>-- Apa Tujuan Anda? --</option>
                    <option value="weight_loss">Defisit (Weight Loss / Menurunkan Berat)</option>
                    <option value="maintenance">Normal (Pemeliharaan Berat Badan)</option>
                    <option value="weight_gain">Surplus (Weight Gain / Menaikkan Berat)</option>
                </select>
            </div>

            <button type="submit" class="btn-submit">Hitung Kebutuhan</button>
        </form>

        <div id="hasilGizi" class="result-box">
            <div class="result-title">Target Asupan Kalori Harian:</div>
            <div class="result-value" id="resKalori">0 <span style="font-size: 1.2rem; color: #888;">Kkal</span></div>
            <div class="result-desc" id="resTargetDesc">Angka ini adalah batas makanan yang harus kamu capai setiap harinya.</div>
            
            <div class="macro-grid">
                <div class="macro-item">
                    <span class="macro-label">TDEE (Total Energi)</span>
                    <span class="macro-number" id="resTdee">0 Kkal</span>
                    <span class="macro-note">Total seluruh kalori yang dibakar tubuhmu dalam 24 jam penuh.</span>
                </div>
                <div class="macro-item">
                    <span class="macro-label">BMR (Kalori Basal)</span>
                    <span class="macro-number" id="resBmr">0 Kkal</span>
                    <span class="macro-note">Kalori wajib yang dibutuhkan organ tubuhmu agar tetap hidup meski sedang rebahan/tidur.</span>
                </div>
            </div>

            <div class="macro-grid" style="grid-template-columns: 1fr; margin-top: 15px;">
                <div class="macro-item">
                    <span class="macro-label">Saran Asupan Protein</span>
                    <span class="macro-number" id="resProtein">0g</span>
                    <span class="macro-note">Target protein harian untuk mencegah penyusutan otot (1.8g - 2.2g dikali berat badan).</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Logika untuk tombol Kembali berdasarkan parameter URL
        const urlParams = new URLSearchParams(window.location.search);
        const source = urlParams.get('source');
        const backBtn = document.getElementById('btnBack');

        if (source === 'dasbor') {
            backBtn.href = 'member_dasbor.php';
        } else {
            backBtn.href = 'index.php';
        }

        function hitungGizi(e) {
            e.preventDefault();

            const gender = document.getElementById('kalGender').value;
            const usia = parseInt(document.getElementById('kalUsia').value);
            const bb = parseFloat(document.getElementById('kalBb').value);
            const tb = parseFloat(document.getElementById('kalTb').value);
            const aktivitas = parseFloat(document.getElementById('kalAktivitas').value);
            const target = document.getElementById('kalTarget').value;

            // 1. Hitung BMR (Mifflin-St Jeor Equation)
            let bmr = (10 * bb) + (6.25 * tb) - (5 * usia);
            if (gender === 'laki') {
                bmr += 5;
            } else {
                bmr -= 161; // perempuan
            }

            // 2. Hitung TDEE (Total Daily Energy Expenditure)
            let tdee = bmr * aktivitas;

            // 3. Sesuaikan Kalori dengan Target Manajemen Tubuh
            let kaloriFinal = tdee;
            let targetDesc = "";

            if (target === 'weight_loss') {
                kaloriFinal -= 500; 
                targetDesc = "Kamu dalam fase <strong>Defisit Kalori</strong>. Konsumsi makanan lebih sedikit dari energi yang dibakar untuk memangkas lemak.";
            } else if (target === 'weight_gain') {
                kaloriFinal += 300; 
                targetDesc = "Kamu dalam fase <strong>Surplus Kalori</strong>. Makanlah lebih banyak untuk mendukung pertumbuhan massa otot (Weight Gain).";
            } else {
                targetDesc = "Kamu di fase <strong>Maintenance</strong>. Konsumsi kalori ini untuk menjaga berat badanmu tetap stabil.";
            }

            // 4. Estimasi Kebutuhan Protein (1.8g - 2.2g per kg)
            let proteinMin = Math.round(bb * 1.8);
            let proteinMax = Math.round(bb * 2.2);

            // Tampilkan Hasil
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