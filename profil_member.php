<?php
session_start();
require 'includes/koneksi.php'; 
require_once 'includes/api_key.php'; 

// Proteksi Keamanan: Pastikan member sudah login
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'member') {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// =========================================================
// BLOK PHP: HANDLING AJAX UPDATE DATA
// =========================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    // --- A. UPDATE PROFIL (NAMA & WA) ---
    if ($action === 'update_profil') {
        $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
        $wa   = mysqli_real_escape_string($koneksi, $_POST['wa']);
        $q = mysqli_query($koneksi, "UPDATE users SET nama_lengkap='$nama', no_wa='$wa' WHERE id_user='$id_user'");
        if ($q) {
            $_SESSION['nama'] = $nama;
            echo json_encode(['status' => 'success', 'message' => 'Profil berhasil diperbarui!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui database.']);
        }
        exit;
    }

    // --- B. UPDATE PASSWORD DENGAN PASS LAMA ---
    if ($action === 'update_password') {
        $passLama = $_POST['passLama'];
        $passBaru = $_POST['passBaru'];
        $res = mysqli_query($koneksi, "SELECT password FROM users WHERE id_user='$id_user'");
        $user = mysqli_fetch_assoc($res);
        if (password_verify($passLama, $user['password'])) {
            $hashBaru = password_hash($passBaru, PASSWORD_DEFAULT);
            mysqli_query($koneksi, "UPDATE users SET password='$hashBaru' WHERE id_user='$id_user'");
            echo json_encode(['status' => 'success', 'message' => 'Password berhasil diubah!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Password lama salah!']);
        }
        exit;
    }

    // --- C. KIRIM LINK LUPA PASSWORD VIA WHATSAPP (FONNTE) ---
    if ($action === 'forgot_password') {
        $input_wa = mysqli_real_escape_string($koneksi, trim($_POST['resetWa']));
        $clean_wa = preg_replace('/[^0-9]/', '', $input_wa);
        $wa_62 = '';
        if(substr($clean_wa, 0, 1) == '0') {
            $wa_62 = '62' . substr($clean_wa, 1);
        } elseif(substr($clean_wa, 0, 1) == '8') {
            $wa_62 = '62' . $clean_wa;
        } else {
            $wa_62 = $clean_wa;
        }
        $cek_akun = mysqli_query($koneksi, "SELECT id_user, nama_lengkap, no_wa FROM users WHERE id_user='$id_user' AND (no_wa='$wa_62' OR no_wa='$clean_wa' OR no_wa='$input_wa')");
        if(mysqli_num_rows($cek_akun) > 0) {
            $user_reset = mysqli_fetch_assoc($cek_akun);
            $no_wa_tujuan = $user_reset['no_wa'];
            if(substr($no_wa_tujuan, 0, 1) == '0') {
                $no_wa_tujuan = '62' . substr($no_wa_tujuan, 1);
            }
            $token = bin2hex(random_bytes(16));
            $simpan_token = mysqli_query($koneksi, "UPDATE users SET reset_token='$token', reset_token_exp=DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id_user='$id_user'");
            if(!$simpan_token) {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan token ke database. Hubungi Admin.']);
                exit;
            }
            $base_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
            $reset_link = $base_url . "/login.php?token=" . $token;
            $pesan_wa  = "*Vanda Gym Classic - Reset Password*\n\n";
            $pesan_wa .= "Halo *" . $user_reset['nama_lengkap'] . "*,\n\n";
            $pesan_wa .= "Anda meminta reset password melalui halaman Profil Akun.\n\n";
            $pesan_wa .= "Klik link di bawah ini untuk membuat password baru Anda:\n";
            $pesan_wa .= $reset_link . "\n\n";
            $pesan_wa .= "_Link ini kedaluwarsa dalam 1 jam._\n";
            $pesan_wa .= "Abaikan pesan ini jika Anda tidak merasa memintanya.";
            $api_token = $fonnte_api_key;
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://api.fonnte.com/send',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => ['target' => $no_wa_tujuan, 'message' => $pesan_wa, 'countryCode' => '62'],
                CURLOPT_HTTPHEADER => ["Authorization: $api_token"],
            ]);
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
                echo json_encode(['status' => 'error', 'message' => 'Gagal mengirim pesan WhatsApp.']);
            } else {
                $res = json_decode($response, true);
                if(isset($res['status']) && $res['status'] == true) {
                    echo json_encode(['status' => 'success', 'message' => 'Tautan reset telah berhasil dikirim ke WhatsApp Anda.']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'WhatsApp Gateway Gagal: ' . ($res['reason'] ?? 'Device Offline')]);
                }
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Nomor WhatsApp tidak cocok dengan data profil Anda saat ini.']);
        }
        exit;
    }
}

// 2. AMBIL DATA USER & STATUS MEMBERSHIP
$query  = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = '$id_user'");
$u      = mysqli_fetch_assoc($query);

$q_member = mysqli_query($koneksi, "SELECT status FROM membership WHERE id_user = $id_user ORDER BY id_membership DESC LIMIT 1");
$d_member  = mysqli_fetch_assoc($q_member);
$status_member = $d_member['status'] ?? 'belum_daftar';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Vanda Gym Classic</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* =========================================
           SEMBUNYIKAN BOTTOM NAV DI PC DEFAULT
           ========================================= */
        .bottom-nav-mobile { display: none !important; }

        /* =========================================
           HEADER & KANAN HEADER
           ========================================= */
        .header-right {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-left: auto;
        }

        /* =========================================
           HAMBURGER TOGGLE
           ========================================= */
        header .menu-toggle {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            width: 42px;
            height: 42px;
            background-color: #1a1a1a;
            border: 1px solid #333;
            border-radius: 10px;
            cursor: pointer;
            z-index: 1001;
            padding: 0;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }
        header .menu-toggle:hover { border-color: var(--accent-gold); }
        header .menu-toggle .bar {
            display: block;
            width: 20px;
            height: 2px;
            background-color: #E8C999;
            border-radius: 2px;
            transition: all 0.3s ease-in-out;
            margin: 2px 0;
        }
        header .menu-toggle.active .bar:nth-child(1) { transform: translateY(6px) rotate(45deg); }
        header .menu-toggle.active .bar:nth-child(2) { opacity: 0; }
        header .menu-toggle.active .bar:nth-child(3) { transform: translateY(-6px) rotate(-45deg); }

        /* =========================================
           DROPDOWN NAV MENU
           ========================================= */
        #nav-menu {
            display: none;
            position: absolute;
            top: 70px;
            right: 20px;
            left: auto;
            width: 220px;
            box-sizing: border-box;
            background-color: #1a1a1a;
            padding: 12px;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.8);
            border: 1px solid #333;
            flex-direction: column;
            z-index: 1000;
        }
        #nav-menu.active {
            display: flex;
            animation: slideDownFade 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }
        @keyframes slideDownFade {
            from { opacity: 0; transform: translateY(-15px) scale(0.95); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        #nav-menu a.menu-link {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 10px;
            width: 100%;
            box-sizing: border-box;
            color: #ccc;
            text-decoration: none;
            font-weight: 600;
            padding: 8px 10px;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-size: 0.85rem;
            text-transform: none;
            letter-spacing: normal;
            margin-left: 0;
            min-height: auto;
        }
        #nav-menu a.menu-link:hover { background-color: rgba(232, 201, 153, 0.1); color: var(--accent-gold); }
        #nav-menu a.menu-link.active-link { color: var(--accent-gold); background-color: rgba(232, 201, 153, 0.07); }
        #nav-menu a.menu-link.locked-link { opacity: 0.45; cursor: not-allowed; pointer-events: none; }

        .menu-divider { height: 1px; background-color: #333; margin: 4px 0; width: 100%; }

        .nav-actions-dasbor { display: flex; flex-direction: column; width: 100%; gap: 8px; margin-top: 4px; }
        .nav-actions-dasbor .btn-keluar-menu {
            display: block;
            box-sizing: border-box;
            padding: 9px 15px;
            border: 1px solid var(--primary-red);
            background-color: transparent;
            color: var(--primary-red);
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s ease;
            font-size: 0.6rem;
            text-decoration: none;
        }
        .nav-actions-dasbor .btn-keluar-menu:hover { background-color: var(--primary-red); color: white; }

        /* =========================================
           KONTEN PROFIL
           ========================================= */
        .profil-container {
            max-width: 500px;
            margin: 30px auto;
            padding: 0 20px;
            width: 100%;
        }

        .profil-card {
            background-color: #0a0a0a;
            border: 1px solid #333;
            border-top: 4px solid var(--primary-red);
            border-radius: 8px;
            padding: 35px 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.8);
        }

        .profil-nav-top { display: flex; align-items: center; justify-content: space-between;  }
        .btn-back-square {
            width: 40px; height: 40px;
            background-color: #1a1a1a; border: 1px solid #333;
            color: var(--accent-gold); border-radius: 4px;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; font-weight: bold; font-size: 1.2rem;
            transition: 0.3s;
        }
        .btn-back-square:hover { background-color: var(--primary-red); color: #fff; border-color: var(--primary-red); }

        .section-header {
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid #333; padding-bottom: 8px; margin: 25px 0 15px;
        }
        .section-header h3 { color: var(--accent-gold); text-transform: uppercase; font-size: 1.05rem; margin: 0; }

        .btn-profil-action {
            padding: 6px 12px; border-radius: 4px; font-weight: bold;
            cursor: pointer; transition: 0.3s; font-size: 0.8rem;
            border: 1px solid var(--accent-gold); background: transparent;
            color: var(--accent-gold); width: auto; margin: 0;
        }
        .btn-profil-action:hover { background: var(--accent-gold); color: #000; }

        .form-group { margin-bottom: 15px; position: relative; }
        .form-group label { display: block; margin-bottom: 6px; color: #ccc; font-size: 0.85rem; font-weight: 600; }
        .form-control {
            width: 100%; padding: 10px 12px; min-height: 42px;
            background-color: #111111; border: 1px solid #333;
            border-radius: 4px; color: white; font-size: 0.95rem; transition: 0.3s;
        }
        .form-control:focus { outline: none; border-color: var(--accent-gold); }
        .form-control:disabled { background-color: #050505; color: #666; cursor: not-allowed; border-color: #222; }

        .action-buttons { display: none; gap: 10px; margin-top: 20px; }
        .btn-save, .btn-save-wa {
            background-color: #28a745; color: white;
            border: none; width: 100%; min-height: 44px; text-transform: uppercase;
            cursor: pointer; font-weight: bold; border-radius: 4px; transition: 0.3s; font-size: 0.9rem;
        }
        .btn-save-wa { display: block; margin-top: 20px; }
        .btn-save:hover, .btn-save-wa:hover { background-color: #218838; }

        .btn-cancel {
            background-color: var(--primary-red); color: white;
            border: none; width: 100%; min-height: 44px; text-transform: uppercase;
            cursor: pointer; font-weight: bold; border-radius: 4px; transition: 0.3s; font-size: 0.9rem;
        }
        .btn-cancel:hover { background-color: #b01c1c; }

        .toast {
            position: fixed; top: 20px; right: 20px; background: #28a745;
            color: white; padding: 15px 25px; border-radius: 4px; font-weight: bold;
            display: none; z-index: 9999; box-shadow: 0 5px 15px rgba(0,0,0,0.5);
        }

        .eye-toggle {
            position: absolute; right: 5px; top: 28px;
            cursor: pointer; min-height: 44px; min-width: 44px;
            display: flex; align-items: center; justify-content: center; z-index: 10;
        }

        .text-link { color: var(--accent-gold); text-decoration: none; font-size: 0.8rem; transition: 0.3s; }
        .text-link:hover { text-decoration: underline; }
        .error-msg { color: var(--primary-red); font-size: 0.75rem; margin-top: 5px; display: none; }
        .success-msg-profil { color: #28a745; background: rgba(40,167,69,0.1); border: 1px solid #28a745; padding: 10px; border-radius: 4px; font-size: 0.85rem; margin-bottom: 15px; display: none; text-align: center; }

        .bell-icon {
    position: relative;
    color: #888;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 4px;
    transition: 0.3s;
    text-decoration: none;
}
.bell-icon:hover { color: var(--text-light); }
.bell-icon.active { color: var(--text-light); animation: ring 2s infinite ease-in-out; }
.bell-badge {
    position: absolute; top: 4px; right: 4px;
    background: var(--primary-red); color: white;
    font-size: 10px; font-weight: bold;
    width: 14px; height: 14px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
}
@keyframes ring {
    0%  { transform: rotate(0); }
    10% { transform: rotate(15deg); }
    20% { transform: rotate(-10deg); }
    30% { transform: rotate(5deg); }
    40% { transform: rotate(-5deg); }
    50% { transform: rotate(0); }
    100%{ transform: rotate(0); }
}
        /* =========================================
           MOBILE RESPONSIVE
           ========================================= */
        @media screen and (max-width: 768px) {
            body { padding-bottom: 85px !important; }

            /* Sembunyikan tombol CS kiri bawah di mobile */
            .wa-btn-cs { display: none !important; }

            header .menu-toggle { width: 36px; height: 36px; }
            header .menu-toggle .bar { width: 16px; }

            #nav-menu { top: 55px; right: 10px; left: auto; width: 195px; padding: 10px; }
            #nav-menu a.menu-link { font-size: 0.75rem; padding: 5px 6px; white-space: nowrap; }
            .nav-actions-dasbor .btn-keluar-menu { font-size: 0.75rem; padding: 7px 8px; }

            .profil-container { padding: 0 12px; margin: 10px auto; }

            .profil-card {
                padding: 14px 12px;
                border-radius: 6px;
            }

            .profil-nav-top { margin-bottom: 12px; }
            .btn-back-square { width: 32px; height: 32px; font-size: 0.9rem; }

            .section-header { margin: 12px 0 8px; padding-bottom: 5px; }
            .section-header h3 { font-size: 0.8rem; }
            .btn-profil-action { font-size: 0.68rem; padding: 3px 7px; }

            .form-group { margin-bottom: 9px; }
            .form-group label { font-size: 0.72rem; margin-bottom: 3px; }
            .form-control { font-size: 0.78rem; padding: 6px 9px; min-height: 34px; }

            .action-buttons { flex-direction: column; gap: 7px; margin-top: 9px; }
            .btn-save, .btn-save-wa, .btn-cancel { min-height: 36px; font-size: 0.78rem; }

            .eye-toggle { top: 20px; min-width: 34px; min-height: 34px; }
            .eye-toggle svg { width: 15px; height: 15px; }

            .text-link { font-size: 0.7rem; }
            .error-msg, .success-msg-profil { font-size: 0.68rem; }

            /* Reset blok */
            #blokResetPassword h2 { font-size: 1rem !important; margin-bottom: 6px !important; }
            #blokResetPassword p  { font-size: 0.72rem !important; }

            /* ============================================
               NAVIGASI BAWAH MOBILE
               ============================================ */
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
            .bottom-nav-mobile .nav-item:active { color: var(--accent-gold, #E8C999) !important; }

            .bottom-nav-mobile .nav-item svg {
                width: 22px !important;
                height: 22px !important;
                stroke: currentColor !important;
                fill: none !important;
                stroke-width: 2 !important;
                stroke-linecap: round !important;
                stroke-linejoin: round !important;
            }

            .bottom-nav-mobile .nav-item.highlight {
                color: var(--accent-gold, #E8C999) !important;
                font-weight: bold !important;
            }
            .bottom-nav-mobile .nav-item.highlight svg {
                stroke: var(--accent-gold, #E8C999) !important;
            }

            @keyframes jelly {
                0%, 100% { transform: scale(1, 1); }
                25% { transform: scale(0.8, 1.2); }
                50% { transform: scale(1.2, 0.8); }
                75% { transform: scale(0.95, 1.05); }
            }
            .bottom-nav-mobile .nav-item:active svg { animation: jelly 0.5s ease; }
        }

        @media (max-width: 480px) {
            .profil-container { padding: 15px 25px; margin: 8px auto; }
            .profil-card { padding: 12px 10px; }
        }
    </style>
</head>
<body>

    <div id="toastNotif" class="toast">Data berhasil disimpan!</div>

    <header>
        <div class="logo">
            <img src="assets/logo.png" alt="Logo Vanda Gym">
        </div>

        <div class="header-right">
            <a href="perpanjang.php" class="bell-icon" title="Tagihan Perpanjangan Membership">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
        </svg>
    </a>
            <!-- Hamburger Toggle -->
            <button class="menu-toggle" id="mobile-menu" aria-label="Toggle Menu">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>
        </div>

        <!-- Dropdown Nav Menu -->
        <nav id="nav-menu">
            <a href="member_dasbor.php" class="menu-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                Dasbor
            </a>
            <a href="https://instagram.com/vandagympky_classic" target="_blank" class="menu-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
                Hubungi Kami
            </a>
            <a href="galeri_member.php" class="menu-link <?= ($status_member !== 'aktif') ? 'locked-link' : '' ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><path d="M21 15l-5-5L5 21"></path></svg>
                Galeri Gym
            </a>
            <a href="chatbot_member.php" class="menu-link <?= ($status_member !== 'aktif') ? 'locked-link' : '' ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"></rect><circle cx="12" cy="5" r="2"></circle><path d="M12 7v4"></path><line x1="8" y1="16" x2="8.01" y2="16"></line><line x1="16" y1="16" x2="16.01" y2="16"></line></svg>
                Chatbot AI
            </a>
            <a href="kalkulator.php?source=dasbor" class="menu-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="8" y1="6" x2="16" y2="6"></line><line x1="16" y1="14" x2="16.01" y2="14"></line><line x1="12" y1="14" x2="12.01" y2="14"></line><line x1="8" y1="14" x2="8.01" y2="14"></line></svg>
                Kalkulator Gizi
            </a>
            <a href="profil_member.php" class="menu-link active-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                Profil Saya
            </a>

            <div class="menu-divider"></div>

            <div class="nav-actions-dasbor">
                <a href="index.php" class="btn-keluar-menu">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline;vertical-align:middle;margin-right:5px;"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    Keluar dari Akun
                </a>
            </div>
        </nav>
    </header>

    <!-- KONTEN PROFIL -->
    <div class="profil-container">
        <div class="profil-card">

            <div class="profil-nav-top">
                <a href="member_dasbor.php" id="btnBackTop" class="btn-back-square" title="Kembali ke Dasbor">←</a>
            </div>

            <div id="blokProfilUtama">
                <h2 style="text-align:center; color:var(--text-light); text-transform:uppercase; letter-spacing:1px; margin-bottom: 5px; font-size:1.3rem;">Pengaturan Akun</h2>

                <!-- BAGIAN DATA PRIBADI -->
                <div class="section-header">
                    <h3>Data Pribadi</h3>
                    <button type="button" class="btn-profil-action" id="btnEditProfil" onclick="toggleEdit('profil')">Edit Profil</button>
                </div>

                <form id="formProfil" onsubmit="handleSimpan(event, 'profil')">
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" id="profNama" class="form-control" value="<?= htmlspecialchars($u['nama_lengkap']) ?>" disabled required>
                    </div>
                    <div class="form-group">
                        <label>Nomor WhatsApp</label>
                        <input type="text" id="profHp" class="form-control" value="<?= htmlspecialchars($u['no_wa']) ?>" disabled required oninput="validasiAngka(this)">
                        <div id="errorHp" class="error-msg">Wajib angka saja.</div>
                    </div>
                    <div id="actionProfil" class="action-buttons">
                        <button type="submit" id="saveProfil" class="btn-save">Simpan Perubahan</button>
                        <button type="button" id="cancelProfil" class="btn-cancel" onclick="toggleEdit('profil')">Batal</button>
                    </div>
                </form>

                <!-- BAGIAN KEAMANAN AKUN -->
                <div class="section-header">
                    <h3>Keamanan Akun</h3>
                    <button type="button" class="btn-profil-action" id="btnEditKeamanan" onclick="toggleEdit('keamanan')">Ubah Password</button>
                </div>

                <form id="formKeamanan" onsubmit="handleSimpan(event, 'keamanan')">
                    <div class="form-group">
                        <label>Email Login</label>
                        <input type="email" id="profEmail" class="form-control" value="<?= htmlspecialchars($u['email']) ?>" disabled style="background-color: #050505;">
                        <small style="color: #666; font-size: 0.7rem; margin-top: 5px; display: block;">Hubungi Admin jika ingin mengubah email.</small>
                    </div>

                    <div id="groupPassDummy">
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" class="form-control" value="********" disabled>
                        </div>
                    </div>

                    <div id="groupEditPass" style="display: none;">
                        <div class="form-group" style="position: relative;">
                            <label>Password Lama</label>
                            <input type="password" id="profPassLama" class="form-control" placeholder="Masukkan password saat ini" disabled required oninput="cekPassword(this, 'errorPassLama')" style="padding-right: 45px;">
                            <span class="eye-toggle" onclick="toggleVisibility('profPassLama', 'eyeIconLama')">
                                <svg id="eyeIconLama" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </span>
                            <div id="errorPassLama" class="error-msg">Format salah (Kombinasi huruf & angka).</div>
                            <div style="text-align: right; margin-top: 8px;">
                                <a href="#" class="text-link" onclick="toggleResetForm(true, event)">Lupa password lama?</a>
                            </div>
                        </div>
                        <div class="form-group" style="position: relative;">
                            <label>Password Baru</label>
                            <input type="password" id="profPass" class="form-control" placeholder="Min. 6 karakter (Huruf & Angka)" disabled required oninput="cekPassword(this, 'errorPassBaru')" style="padding-right: 45px;">
                            <span class="eye-toggle" onclick="toggleVisibility('profPass', 'eyeIconBaru')">
                                <svg id="eyeIconBaru" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </span>
                            <div id="errorPassBaru" class="error-msg">Gunakan huruf & angka (min 6).</div>
                        </div>
                    </div>

                    <div id="actionKeamanan" class="action-buttons">
                        <button type="submit" id="saveKeamanan" class="btn-save">Simpan Password Baru</button>
                        <button type="button" id="cancelKeamanan" class="btn-cancel" onclick="toggleEdit('keamanan')">Batal</button>
                    </div>
                </form>
            </div>

            <!-- BAGIAN RESET PASSWORD -->
            <div id="blokResetPassword" style="display: none; padding-top: 10px;">
                <div style="display: flex; justify-content: center; margin-bottom: 10px; color: var(--accent-gold);">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                </div>
                <div style="text-align: center; margin-bottom: 20px;">
                    <h2 style="color: var(--accent-gold); text-transform: uppercase; font-size: 1.25rem; margin-bottom: 5px;">Lupa Password?</h2>
                    <p style="color: #888; font-size: 0.8rem; line-height: 1.4;">Kami akan mengirimkan tautan pengaturan ulang password <strong>ke nomor WhatsApp</strong> Anda.</p>
                </div>
                <div id="pesanSuksesReset" class="success-msg-profil"></div>
                <form id="formResetPass" onsubmit="kirimLinkReset(event)">
                    <div class="form-group">
                        <label>Nomor WhatsApp Terdaftar</label>
                        <input type="tel" id="resetWaProf" class="form-control" value="<?= htmlspecialchars($u['no_wa']) ?>" required oninput="validasiAngka(this)">
                    </div>
                    <div style="display: flex; gap: 8px; flex-direction: column; margin-top: 15px;">
                        <button type="submit" id="btnKirimReset" class="btn-save" style="display: block; margin: 0;">Kirim Tautan Reset</button>
                        <button type="button" class="btn-cancel" onclick="toggleResetForm(false, event)" style="margin: 0;">Batal</button>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <!-- BOTTOM NAV MOBILE -->
    <div class="bottom-nav-mobile">
        <a href="member_dasbor.php" class="nav-item">
            <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span>Dasbor</span>
        </a>
        <a href="kalkulator.php?source=dasbor" class="nav-item">
            <svg viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="8" y1="6" x2="16" y2="6"></line><line x1="16" y1="14" x2="16.01" y2="14"></line><line x1="12" y1="14" x2="12.01" y2="14"></line><line x1="8" y1="14" x2="8.01" y2="14"></line></svg>
            <span>Gizi</span>
        </a>
        <a href="galeri_member.php" class="nav-item <?= ($status_member !== 'aktif') ? 'locked-nav' : '' ?>" <?= ($status_member !== 'aktif') ? 'onclick="event.preventDefault(); alert(\'Galeri terkunci!\')"' : '' ?>>
            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
            <span>Galeri</span>
        </a>
        <a href="chatbot_member.php" class="nav-item <?= ($status_member !== 'aktif') ? 'locked-nav' : '' ?>" <?= ($status_member !== 'aktif') ? 'onclick="event.preventDefault(); alert(\'Terkunci!\')"' : '' ?>>
            <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="10" rx="2"></rect><circle cx="12" cy="5" r="2"></circle><path d="M12 7v4"></path></svg>
            <span>AI Bot</span>
        </a>
        <a href="profil_member.php" class="nav-item highlight">
            <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            <span>Profil</span>
        </a>
    </div>

    <!-- Tombol CS Instagram Floating (Hanya PC) -->
    <a href="https://instagram.com/vandagympky_classic" target="_blank" class="wa-btn-cs" title="Hubungi CS via Instagram"
       style="position: fixed; bottom: 20px; left: 20px; z-index: 9999; color: #ffffff; background: var(--primary-red, #ff4d4d); border-radius: 50%; padding: 12px; box-shadow: 0 4px 15px rgba(255,77,77,0.4); border: 2px solid #E8C999; transition: all 0.3s; display:flex; align-items:center; justify-content:center;">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
        </svg>
    </a>

    <script>
        // HAMBURGER TOGGLE
        const menuToggle = document.getElementById('mobile-menu');
        const navMenu    = document.getElementById('nav-menu');

        menuToggle.addEventListener('click', () => {
            menuToggle.classList.toggle('active');
            navMenu.classList.toggle('active');
        });

        document.addEventListener('click', function(event) {
            const isClickInside = navMenu.contains(event.target) || menuToggle.contains(event.target);
            if (!isClickInside && navMenu.classList.contains('active')) {
                navMenu.classList.remove('active');
                menuToggle.classList.remove('active');
            }
        });

        // ==========================================
        // FUNGSI PROFIL
        // ==========================================
        function toggleResetForm(tampilkanLupaPass, e) {
            if (e) e.preventDefault();
            const btnBack = document.getElementById('btnBackTop');
            document.getElementById('blokProfilUtama').style.display   = tampilkanLupaPass ? 'none' : 'block';
            document.getElementById('blokResetPassword').style.display = tampilkanLupaPass ? 'block' : 'none';
            if (tampilkanLupaPass) {
                btnBack.onclick = function(e) { e.preventDefault(); toggleResetForm(false); };
            } else {
                btnBack.onclick = null;
                btnBack.href = "member_dasbor.php";
            }
        }

        function kirimLinkReset(e) {
            e.preventDefault();
            const btn = document.getElementById('btnKirimReset');
            const originalText = btn.innerText;
            const waInput = document.getElementById('resetWaProf').value.trim();
            btn.innerText = "Mengirim..."; btn.disabled = true;
            const formData = new FormData();
            formData.append('action', 'forgot_password');
            formData.append('resetWa', waInput);
            fetch('profil_member.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    document.getElementById('formResetPass').style.display = 'none';
                    const msg = document.getElementById('pesanSuksesReset');
                    msg.style.display = 'block';
                    msg.innerHTML = data.message;
                } else {
                    alert('❌ ' + data.message);
                    btn.innerText = originalText; btn.disabled = false;
                }
            })
            .catch(() => {
                alert('Terjadi kesalahan jaringan atau server.');
                btn.innerText = originalText; btn.disabled = false;
            });
        }

        function toggleEdit(tipe) {
            if (tipe === 'profil') {
                const isDis = document.getElementById('profNama').disabled;
                document.getElementById('profNama').disabled = !isDis;
                document.getElementById('profHp').disabled   = !isDis;
                document.getElementById('btnEditProfil').style.display   = isDis ? 'none' : 'block';
                document.getElementById('actionProfil').style.display    = isDis ? 'flex' : 'none';
            } else {
                const isDis = document.getElementById('profPassLama').disabled;
                document.getElementById('profPassLama').disabled = !isDis;
                document.getElementById('profPass').disabled     = !isDis;
                document.getElementById('groupPassDummy').style.display  = isDis ? 'none' : 'block';
                document.getElementById('groupEditPass').style.display   = isDis ? 'block' : 'none';
                document.getElementById('btnEditKeamanan').style.display = isDis ? 'none' : 'block';
                document.getElementById('actionKeamanan').style.display  = isDis ? 'flex' : 'none';
            }
        }

        function validasiAngka(input) {
            const error = document.getElementById('errorHp');
            input.value = input.value.replace(/\D/g, '');
            if(error) error.style.display = (/\D/g.test(input.value)) ? 'block' : 'none';
        }

        function cekPassword(input, errorId) {
            const error = document.getElementById(errorId);
            const regex = /^(?=.*[0-9])(?=.*[a-zA-Z])([a-zA-Z0-9]{6,})$/;
            error.style.display = (!regex.test(input.value) && input.value.length > 0) ? 'block' : 'none';
        }

        function toggleVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon  = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>`;
            } else {
                input.type = 'password';
                icon.innerHTML = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>`;
            }
        }

        function handleSimpan(e, tipe) {
            e.preventDefault();
            const btn = (tipe === 'profil') ? document.getElementById('saveProfil') : document.getElementById('saveKeamanan');
            const originalText = btn.innerText;
            btn.innerText = "Menyimpan..."; btn.disabled = true;
            const formData = new FormData();
            formData.append('action', (tipe === 'profil' ? 'update_profil' : 'update_password'));
            if (tipe === 'profil') {
                formData.append('nama', document.getElementById('profNama').value);
                formData.append('wa',   document.getElementById('profHp').value);
            } else {
                formData.append('passLama', document.getElementById('profPassLama').value);
                formData.append('passBaru', document.getElementById('profPass').value);
            }
            fetch('profil_member.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast(data.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert('❌ ' + data.message);
                    btn.innerText = originalText; btn.disabled = false;
                }
            })
            .catch(() => {
                alert('Terjadi kesalahan jaringan.');
                btn.innerText = originalText; btn.disabled = false;
            });
        }

        function showToast(pesan) {
            const toast = document.getElementById('toastNotif');
            toast.innerText = pesan; toast.style.display = 'block';
            setTimeout(() => { toast.style.display = 'none'; }, 3000);
        }
    </script>
</body>
</html>