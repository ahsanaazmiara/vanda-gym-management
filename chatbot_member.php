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

        /* Container Chat */
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

        /* Header Chat */
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

        /* Area Percakapan */
        #chatContent { 
            flex: 1; overflow-y: auto; padding: 20px; 
            display: flex; flex-direction: column; gap: 15px; scroll-behavior: smooth;
        }
        #chatContent::-webkit-scrollbar { width: 6px; }
        #chatContent::-webkit-scrollbar-thumb { background: #333; border-radius: 4px; }

        /* Bubble Chat */
        .bubble { max-width: 85%; padding: 12px 16px; border-radius: 12px; font-size: 0.9rem; line-height: 1.5; position: relative; word-wrap: break-word; }
        .member { align-self: flex-end; background-color: var(--bubble-member); color: #000; border-bottom-right-radius: 2px; font-weight: 500; }
        .vanda-ai { align-self: flex-start; background-color: var(--bubble-ai); color: var(--text-light); border-bottom-left-radius: 2px; border: 1px solid #222; }
        
        /* Gambar & Teks di dalam Bubble */
        .bubble img { max-width: 100%; border-radius: 8px; display: block; border: 1px solid rgba(0,0,0,0.2); }
        .bubble p { margin-top: 8px; }
        .bubble img:last-child { margin-bottom: 0; }

        /* Indikator Mengetik */
        .typing-container { padding: 0 20px 10px; display: none; }
        .typing { font-style: italic; font-size: 0.8rem; color: var(--accent-gold); }

        /* Footer Input & Attachment Menu */
        .chat-footer { background: #050505; padding: 15px; border-top: 1px solid #222; display: flex; flex-direction: column; gap: 10px; position: relative; }
        
        /* Menu Pilihan Upload dengan SVG Ikon Solid */
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
            display: flex; align-items: center; gap: 10px; /* Merapikan ikon dan teks */
        }
        .attach-menu button svg {
            width: 18px; height: 18px; fill: var(--text-light); transition: 0.3s;
        }
        .attach-menu button:hover { background: #222; color: var(--accent-gold); }
        .attach-menu button:hover svg { fill: var(--accent-gold); }

        /* Area Preview Gambar sebelum dikirim */
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
        
        /* Tombol Klip (Attachment) */
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
                Halo Ahsana! Saya Vanda AI. Anda bisa bertanya tentang jadwal latihan, mengirim foto makanan untuk cek kalori, atau mengirim foto alat gym untuk tau cara pakainya. Ada yang bisa saya bantu?
            </div>
        </div>

        <div id="typingContainer" class="typing-container">
            <div class="typing">Vanda AI sedang menganalisis pesan...</div>
        </div>

        <div class="chat-footer">
            
            <div id="attachMenu" class="attach-menu">
                <button onclick="document.getElementById('fileCamera').click()">
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="3.2"/>
                        <path d="M9 2L7.17 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2h-3.17L15 2H9zm3 15c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5z"/>
                    </svg>
                    Ambil Foto
                </button>
                <button onclick="document.getElementById('fileGallery').click()">
                    <svg viewBox="0 0 24 24">
                        <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
                    </svg>
                    Pilih dari Galeri
                </button>
            </div>

            <input type="file" id="fileCamera" accept="image/*" capture="environment" style="display: none;" onchange="previewGambar(this)">
            <input type="file" id="fileGallery" accept="image/*" style="display: none;" onchange="previewGambar(this)">
            
            <div id="previewContainer" class="preview-container">
                <img id="imgPreview" src="" alt="Preview">
                <button class="btn-remove-img" onclick="hapusPreview()">×</button>
            </div>

            <div class="input-wrapper">
                <button class="btn-attach" onclick="toggleAttachMenu()" title="Lampirkan Gambar">
                    <svg viewBox="0 0 24 24">
                        <path d="M16.5 6v11.5c0 2.21-1.79 4-4 4s-4-1.79-4-4V5a2.5 2.5 0 0 1 5 0v10.5c0 .55-.45 1-1 1s-1-.45-1-1V6H10v9.5a2.5 2.5 0 0 0 5 0V5c0-3.04-2.46-5.5-5.5-5.5S4 1.96 4 5v12.5c0 3.87 3.13 7 7 7s7-3.13 7-7V6h-1.5z"/>
                    </svg>
                </button>

                <input type="text" id="userInput" class="chat-input" placeholder="Ketik pesan atau caption..." autocomplete="off">
                
                <button class="btn-send" onclick="kirimChat()" title="Kirim Pesan">
                    <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                </button>
            </div>
            <div class="disclaimer">
                AI dapat memberikan informasi yang tidak akurat. Bukan pengganti saran medis profesional.
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

        let base64ImageTemp = null;

        // Menampilkan/Menyembunyikan menu lampiran
        function toggleAttachMenu() {
            attachMenu.style.display = attachMenu.style.display === 'block' ? 'none' : 'block';
        }

        // Memproses gambar yang dipilih untuk ditampilkan sebagai preview
        function previewGambar(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    base64ImageTemp = e.target.result;
                    imgPreview.src = base64ImageTemp;
                    previewContainer.style.display = 'block';
                    attachMenu.style.display = 'none'; 
                    userInput.focus(); 
                };
                reader.readAsDataURL(input.files[0]);
            }
            input.value = ''; 
        }

        // Menghapus gambar dari preview jika batal dikirim
        function hapusPreview() {
            base64ImageTemp = null;
            imgPreview.src = "";
            previewContainer.style.display = 'none';
        }

        function kirimChat() {
            const pesan = userInput.value.trim();
            
            // Cek apakah ada teks ATAU gambar yang akan dikirim
            if (pesan === "" && !base64ImageTemp) return;

            // Rangkai isi bubble
            let isiBubble = "";
            if (base64ImageTemp) {
                isiBubble += `<img src="${base64ImageTemp}" alt="Foto Upload">`;
            }
            if (pesan !== "") {
                isiBubble += `<p>${pesan}</p>`;
            }

            // Tampilkan pesan member
            tambahBubble(isiBubble, 'member');
            
            // Reset input form
            userInput.value = "";
            hapusPreview();
            attachMenu.style.display = 'none';

            // Kirim ke simulasi AI
            prosesLoadingAI(base64ImageTemp ? "GAMBAR_DIKIRIM" : pesan);
        }

        function prosesLoadingAI(trigger) {
            typingContainer.style.display = 'block';
            chatContent.scrollTop = chatContent.scrollHeight;
            
            setTimeout(() => {
                typingContainer.style.display = 'none';
                let respon = "";
                
                if (trigger === "GAMBAR_DIKIRIM") {
                    respon = "📸 Gambar diterima! Nanti API Gemini akan menganalisis makanan atau alat gym yang ada di foto ini beserta konteks dari teks yang kamu kirimkan. 💪🍱";
                } else if (trigger.toLowerCase().includes("jadwal")) {
                    respon = "Jadwal operasional Vanda Gym adalah Senin-Jumat pukul 06.00-10.30 dan 14.15-19.45 WIB. Ada kelas senam juga lho!";
                } else if (trigger.toLowerCase().includes("protein") || trigger.toLowerCase().includes("makan")) {
                    respon = "Untuk membentuk otot, disarankan mengonsumsi protein 1.6g - 2.2g per kilogram berat badan Anda.";
                } else {
                    respon = "Siap! Menunggu integrasi API Gemini untuk menjawab instruksi ini dengan detail program latihan dan diet. 😉";
                }

                tambahBubble(respon, 'vanda-ai');
            }, 1800);
        }

        function tambahBubble(isiHTML, tipe) {
            const div = document.createElement('div');
            div.className = `bubble ${tipe}`;
            div.innerHTML = isiHTML;
            chatContent.appendChild(div);
            chatContent.scrollTop = chatContent.scrollHeight;
        }

        // Jalankan tombol kirim jika menekan Enter
        userInput.addEventListener("keypress", function(event) {
            if (event.key === "Enter") {
                kirimChat();
            }
        });

        // Tutup menu attach jika klik di luar area menu
        document.addEventListener('click', function(event) {
            const isClickInside = attachMenu.contains(event.target) || event.target.closest('.btn-attach');
            if (!isClickInside) {
                attachMenu.style.display = 'none';
            }
        });
    </script>
</body>
</html>