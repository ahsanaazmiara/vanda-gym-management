<?php
session_start();
require 'includes/koneksi.php'; 
require 'includes/api_key.php'; // Panggil file rahasia di sini

// Proteksi Keamanan: Pastikan member sudah login
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'member') {
    // Jika diakses via AJAX tapi belum login
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
        echo json_encode(['status' => 'error', 'message' => 'Sesi login habis. Silakan muat ulang halaman.']);
        exit;
    }
    header("Location: login.php");
    exit;
}

// =========================================================
// BLOK PHP: HANDLING AJAX REQUEST KE API GEMINI
// =========================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'chat_ai') {
    header('Content-Type: application/json');

    $pesan = $_POST['pesan'] ?? '';
    $gambarBase64 = $_POST['gambar'] ?? '';

    // 🔴 Ambil API Key dari file rahasia yang tidak di-push ke Git
    $api_key = $gemini_api_key; 
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $api_key;
    
    $parts = [];

    // 1. Instruksi Sistem (System Prompt) agar AI berperan sebagai Trainer Vanda Gym
    $prompt_sistem = "Kamu adalah Vanda AI, asisten virtual dan personal trainer untuk Vanda Gym Classic di Palangka Raya. Jawablah dengan singkat, padat, ramah, dan gunakan bahasa Indonesia yang santai tapi profesional. Jangan gunakan bahasa pemrograman dalam jawabanmu. Fokus pada fitness, kesehatan, nutrisi, dan alat gym. Jika user mengirim foto makanan, berikan estimasi kalori dan nutrisinya. Jika user mengirim foto alat gym, jelaskan nama dan cara pakainya secara singkat. Pertanyaan/Pernyataan member: " . $pesan;

    if (!empty($pesan)) {
        $parts[] = ['text' => $prompt_sistem];
    } else if (!empty($gambarBase64)) {
        $parts[] = ['text' => $prompt_sistem . "\n\nTolong analisis gambar yang saya lampirkan ini."];
    }

    // 2. Masukkan Gambar (Jika member mengunggah foto)
    if (!empty($gambarBase64)) {
        // Ekstrak tipe file dan data mentah dari string base64 Javascript
        $image_parts = explode(";base64,", $gambarBase64);
        $mime_type = explode("data:", $image_parts[0])[1]; // contoh: image/jpeg
        $base64_data = $image_parts[1];

        $parts[] = [
            'inline_data' => [
                'mime_type' => $mime_type,
                'data' => $base64_data
            ]
        ];
    }

    // Bungkus sesuai format JSON yang diminta Google Gemini
    $data = [
        'contents' => [
            [
                'parts' => $parts
            ]
        ]
    ];

    // Eksekusi cURL ke server Google
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Matikan verifikasi SSL lokal XAMPP

    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal terhubung ke Server AI: ' . $err]);
        exit;
    }

    $result = json_decode($response, true);

    // Ambil teks balasan dari Google Gemini
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $balasan = $result['candidates'][0]['content']['parts'][0]['text'];
        
        // Bersihkan sedikit format Markdown bawaan AI agar rapi di HTML
        $balasanHTML = nl2br(htmlspecialchars($balasan));
        // Ubah format **Teks** menjadi <strong>Teks</strong>
        $balasanHTML = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $balasanHTML);
        // Ubah format *Teks* menjadi <em>Teks</em>
        $balasanHTML = preg_replace('/(?<!\*)\*(?!\*)(.*?)(?<!\*)\*(?!\*)/', '<em>$1</em>', $balasanHTML);

        echo json_encode(['status' => 'success', 'message' => $balasanHTML]);
    } else {
        // Jika limit habis atau ditolak Google
        $error_msg = $result['error']['message'] ?? 'AI tidak dapat memproses permintaan ini. Mungkin gambar tidak jelas atau di luar topik.';
        echo json_encode(['status' => 'error', 'message' => $error_msg]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot AI Vanda Gym</title>
    <style>
        :root {
            --bg-dark: #000000;
            --primary-red: #8E1616;
            --accent-gold: #E8C999;
            --text-light: #F8EEDF;
            --chat-bg: #0a0a0a;
            --bubble-member: #E8C999;
            --bubble-ai: #1a1a1a;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background-color: var(--bg-dark); 
            color: var(--text-light); 
            display: flex; justify-content: center; align-items: center; 
            min-height: 100vh; padding: 20px;
        }

        .chat-container {
            width: 100%; max-width: 450px; 
            height: 85vh; max-height: 800px;
            background-color: var(--chat-bg);
            border: 1px solid #333; border-top: 4px solid var(--primary-red);
            border-radius: 12px;
            display: flex; flex-direction: column;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.8);
            position: relative;
        }

        header { 
            background-color: #050505; padding: 15px 20px; 
            display: flex; align-items: center; border-bottom: 1px solid #222;
            z-index: 10;
        }
        .btn-back { 
            text-decoration: none; color: var(--accent-gold); font-weight: bold; 
            font-size: 1.2rem; margin-right: 15px; width: 40px; height: 40px;
            display: flex; align-items: center; justify-content: center;
            background: #111; border-radius: 8px; border: 1px solid #333; transition: 0.3s;
        }
        .btn-back:hover { background-color: var(--primary-red); color: white; border-color: var(--primary-red); }
        .ai-info h2 { font-size: 1.1rem; color: var(--accent-gold); margin-bottom: 2px;}
        .ai-info p { font-size: 0.75rem; color: #888; }

        #chatContent { 
            flex: 1; overflow-y: auto; padding: 20px; 
            display: flex; flex-direction: column; gap: 15px; scroll-behavior: smooth;
        }
        #chatContent::-webkit-scrollbar { width: 6px; }
        #chatContent::-webkit-scrollbar-thumb { background: #333; border-radius: 4px; }

        .bubble { max-width: 85%; padding: 12px 16px; border-radius: 12px; font-size: 0.9rem; line-height: 1.5; position: relative; word-wrap: break-word; }
        .member { align-self: flex-end; background-color: var(--bubble-member); color: #000; border-bottom-right-radius: 2px; font-weight: 500; }
        .vanda-ai { align-self: flex-start; background-color: var(--bubble-ai); color: var(--text-light); border-bottom-left-radius: 2px; border: 1px solid #222; }
        
        .bubble img { max-width: 100%; border-radius: 8px; display: block; border: 1px solid rgba(0,0,0,0.2); margin-bottom: 8px; }
        .bubble p { margin-top: 0; }

        .typing-container { padding: 0 20px 10px; display: none; }
        .typing { font-style: italic; font-size: 0.8rem; color: var(--accent-gold); animation: blink 1.5s infinite; }
        @keyframes blink { 0% { opacity: 0.4; } 50% { opacity: 1; } 100% { opacity: 0.4; } }

        .chat-footer { background: #050505; padding: 15px; border-top: 1px solid #222; display: flex; flex-direction: column; gap: 10px; position: relative; }
        
        .attach-menu {
            display: none; position: absolute; bottom: 85px; left: 15px;
            background: #111; border: 1px solid #333; border-radius: 8px;
            padding: 5px; box-shadow: 0 5px 15px rgba(0,0,0,0.8); z-index: 20;
            width: 200px;
        }
        .attach-menu button {
            width: 100%; text-align: left; background: transparent; border: none;
            color: var(--text-light); padding: 12px 15px; cursor: pointer;
            font-size: 0.9rem; transition: 0.3s; border-radius: 4px;
            display: flex; align-items: center; gap: 10px;
        }
        .attach-menu button svg { width: 18px; height: 18px; fill: var(--text-light); transition: 0.3s; }
        .attach-menu button:hover { background: #222; color: var(--accent-gold); }
        .attach-menu button:hover svg { fill: var(--accent-gold); }

        .preview-container {
            display: none; background: #111; border: 1px solid #333; border-radius: 8px;
            padding: 10px; position: relative; margin-bottom: 5px; width: fit-content;
        }
        .preview-container img { height: 80px; border-radius: 4px; border: 1px solid #222; }
        .btn-remove-img {
            position: absolute; top: -5px; right: -5px; background: var(--primary-red); color: white;
            border: none; border-radius: 50%; width: 22px; height: 22px; font-size: 14px;
            cursor: pointer; display: flex; justify-content: center; align-items: center;
        }

        .input-wrapper { display: flex; gap: 8px; align-items: center; }
        
        .btn-attach {
            background: #111; border: 1px solid #333; width: 44px; height: 44px;
            border-radius: 50%; color: var(--accent-gold); cursor: pointer; transition: 0.3s;
            display: flex; justify-content: center; align-items: center; flex-shrink: 0;
        }
        .btn-attach:hover { background: #222; border-color: var(--accent-gold); }
        .btn-attach svg { width: 20px; height: 20px; fill: currentColor; }

        .chat-input { 
            flex: 1; background: #111; border: 1px solid #333; padding: 12px 15px; 
            border-radius: 25px; color: white; outline: none; font-size: 0.95rem;
        }
        .chat-input:focus { border-color: var(--accent-gold); }
        
        .btn-send { 
            background: var(--primary-red); border: none; width: 44px; height: 44px; 
            border-radius: 50%; color: white; cursor: pointer; transition: 0.3s; 
            display: flex; justify-content: center; align-items: center; flex-shrink: 0;
        }
        .btn-send:hover { background: #a81a1a; transform: scale(1.05); }
        .btn-send svg { width: 18px; height: 18px; fill: white; margin-left: 2px; }
        .btn-send:disabled { background: #555; cursor: not-allowed; transform: none; }

        .disclaimer { font-size: 0.65rem; color: #555; text-align: center; }
    </style>
</head>
<body>

    <div class="chat-container">
        <header>
            <a href="member_dasbor.php" class="btn-back">←</a>
            <div class="ai-info">
                <h2>Vanda AI Assistant</h2>
                <p>Aktif • Didukung oleh Gemini AI</p>
            </div>
        </header>

        <div id="chatContent">
            <div class="bubble vanda-ai">
                Halo Ahsana! Saya Vanda AI. Anda bisa bertanya tentang nutrisi, mengirim foto makanan untuk cek kalori, atau mengirim foto alat gym untuk tau cara pakainya. Ada yang bisa saya bantu hari ini? 💪
            </div>
        </div>

        <div id="typingContainer" class="typing-container">
            <div class="typing">Vanda AI sedang memikirkan jawaban...</div>
        </div>

        <div class="chat-footer">
            
            <div id="attachMenu" class="attach-menu">
                <button onclick="document.getElementById('fileCamera').click()">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3.2"/><path d="M9 2L7.17 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2h-3.17L15 2H9zm3 15c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5z"/></svg> Ambil Foto
                </button>
                <button onclick="document.getElementById('fileGallery').click()">
                    <svg viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg> Pilih dari Galeri
                </button>
            </div>

            <!-- Batasi ukuran file max 4MB untuk mencegah API timeout -->
            <input type="file" id="fileCamera" accept="image/*" capture="environment" style="display: none;" onchange="previewGambar(this)">
            <input type="file" id="fileGallery" accept="image/*" style="display: none;" onchange="previewGambar(this)">
            
            <div id="previewContainer" class="preview-container">
                <img id="imgPreview" src="" alt="Preview">
                <button class="btn-remove-img" onclick="hapusPreview()">×</button>
            </div>

            <div class="input-wrapper">
                <button class="btn-attach" onclick="toggleAttachMenu()" title="Lampirkan Gambar">
                    <svg viewBox="0 0 24 24"><path d="M16.5 6v11.5c0 2.21-1.79 4-4 4s-4-1.79-4-4V5a2.5 2.5 0 0 1 5 0v10.5c0 .55-.45 1-1 1s-1-.45-1-1V6H10v9.5a2.5 2.5 0 0 0 5 0V5c0-3.04-2.46-5.5-5.5-5.5S4 1.96 4 5v12.5c0 3.87 3.13 7 7 7s7-3.13 7-7V6h-1.5z"/></svg>
                </button>

                <input type="text" id="userInput" class="chat-input" placeholder="Ketik pesan atau caption..." autocomplete="off">
                
                <button class="btn-send" id="btnSend" onclick="kirimChat()" title="Kirim Pesan">
                    <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                </button>
            </div>
            <div class="disclaimer">
                AI dapat memberikan informasi yang tidak akurat. Kebijakan Privasi Google berlaku untuk pemrosesan data. Bukan pengganti saran ahli gizi profesional.
            </div>
        </div>
    </div>

    <script>
        const chatContent = document.getElementById('chatContent');
        const userInput = document.getElementById('userInput');
        const typingContainer = document.getElementById('typingContainer');
        const attachMenu = document.getElementById('attachMenu');
        const previewContainer = document.getElementById('previewContainer');
        const imgPreview = document.getElementById('imgPreview');
        const btnSend = document.getElementById('btnSend');

        let base64ImageTemp = null;

        function toggleAttachMenu() {
            attachMenu.style.display = attachMenu.style.display === 'block' ? 'none' : 'block';
        }

        // Kompresi ringan gambar ke canvas sebelum dikirim agar tidak terlalu berat untuk API
        function previewGambar(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                // Cek ukuran (max 4MB)
                if(file.size > 4 * 1024 * 1024) {
                    alert('Ukuran gambar terlalu besar. Maksimal 4MB.');
                    input.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = new Image();
                    img.onload = function() {
                        const canvas = document.createElement('canvas');
                        const ctx = canvas.getContext('2d');
                        
                        // Resize max width 800px untuk menghemat bandwidth API
                        let width = img.width;
                        let height = img.height;
                        const MAX_WIDTH = 800;
                        if (width > MAX_WIDTH) {
                            height = Math.round((height * MAX_WIDTH) / width);
                            width = MAX_WIDTH;
                        }
                        
                        canvas.width = width;
                        canvas.height = height;
                        ctx.drawImage(img, 0, 0, width, height);
                        
                        base64ImageTemp = canvas.toDataURL('image/jpeg', 0.8);
                        
                        imgPreview.src = base64ImageTemp;
                        previewContainer.style.display = 'block';
                        attachMenu.style.display = 'none'; 
                        userInput.focus(); 
                    }
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
            input.value = ''; 
        }

        function hapusPreview() {
            base64ImageTemp = null;
            imgPreview.src = "";
            previewContainer.style.display = 'none';
        }

        function kirimChat() {
            const pesan = userInput.value.trim();
            const imgKirim = base64ImageTemp; 
            
            if (pesan === "" && !imgKirim) return;

            let isiBubble = "";
            if (imgKirim) {
                isiBubble += `<img src="${imgKirim}" alt="Foto Upload">`;
            }
            if (pesan !== "") {
                isiBubble += `<p>${pesan.replace(/</g, "&lt;").replace(/>/g, "&gt;")}</p>`; // Keamanan anti XSS
            }

            tambahBubble(isiBubble, 'member');
            
            // Reset input
            userInput.value = "";
            hapusPreview();
            attachMenu.style.display = 'none';

            // Panggil API PHP secara Asinkron
            prosesTanyaAPI(pesan, imgKirim);
        }

        function prosesTanyaAPI(pesanTeks, base64Data) {
            typingContainer.style.display = 'block';
            chatContent.scrollTop = chatContent.scrollHeight;
            
            // Kunci tombol kirim dan input saat memproses
            userInput.disabled = true;
            btnSend.disabled = true;

            const formData = new FormData();
            formData.append('action', 'chat_ai');
            formData.append('pesan', pesanTeks);
            if (base64Data) {
                formData.append('gambar', base64Data);
            }

            fetch('chatbot_member.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                typingContainer.style.display = 'none';
                userInput.disabled = false;
                btnSend.disabled = false;
                userInput.focus();

                if (data.status === 'success') {
                    tambahBubble(data.message, 'vanda-ai');
                } else {
                    tambahBubble('❌ <strong style="color:var(--primary-red)">Terjadi Masalah:</strong><br>' + data.message, 'vanda-ai');
                }
            })
            .catch(error => {
                typingContainer.style.display = 'none';
                userInput.disabled = false;
                btnSend.disabled = false;
                tambahBubble('❌ <strong style="color:var(--primary-red)">Gagal terhubung ke Server.</strong> Pastikan koneksi internet Anda stabil dan coba lagi.', 'vanda-ai');
            });
        }

        function tambahBubble(isiHTML, tipe) {
            const div = document.createElement('div');
            div.className = `bubble ${tipe}`;
            div.innerHTML = isiHTML;
            chatContent.appendChild(div);
            chatContent.scrollTop = chatContent.scrollHeight;
        }

        userInput.addEventListener("keypress", function(event) {
            if (event.key === "Enter" && !userInput.disabled) {
                kirimChat();
            }
        });

        document.addEventListener('click', function(event) {
            const isClickInside = attachMenu.contains(event.target) || event.target.closest('.btn-attach');
            if (!isClickInside) {
                attachMenu.style.display = 'none';
            }
        });
    </script>
</body>
</html>