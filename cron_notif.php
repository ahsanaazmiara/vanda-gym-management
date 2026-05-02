<?php
// Pastikan path ke koneksi database benar
require 'includes/koneksi.php';

$log_file = 'cron_log.txt';
function tulisLog($pesan) {
    global $log_file;
    $waktu = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$waktu] $pesan\n", FILE_APPEND);
}

// Token API Fonnte Kamu
$api_token = "7JKot34wdeZqaYNcUjoy"; 
$is_simulasi = false;

// =========================================================
// CEK APAKAH INI MODE SIMULASI (TRIGGER MANUAL) ATAU OTOMATIS
// =========================================================
if (isset($_GET['simulasi_id'])) {
    // JIKA MODE SIMULASI: Abaikan syarat H-7, paksa kirim ke ID tertentu
    $is_simulasi = true;
    $simulasi_id = (int)$_GET['simulasi_id'];
    
    tulisLog("Memulai MODE SIMULASI Cron Job untuk User ID: $simulasi_id...");
    
    $query = "SELECT u.id_user, u.nama_lengkap, u.no_wa, m.tgl_berakhir 
              FROM users u 
              LEFT JOIN membership m ON u.id_user = m.id_user 
              WHERE u.id_user = $simulasi_id 
              ORDER BY m.id_membership DESC LIMIT 1";

} else {
    // JIKA MODE OTOMATIS (Dijalankan Windows jam 00:00)
    tulisLog("Memulai eksekusi Cron Job H-7 NORMAL...");
    
    $query = "SELECT u.id_user, u.nama_lengkap, u.no_wa, m.tgl_berakhir 
              FROM users u 
              JOIN membership m ON u.id_user = m.id_user 
              WHERE m.status = 'aktif' 
              AND u.notif_wa = 1 
              AND DATEDIFF(m.tgl_berakhir, CURDATE()) = 7";
}

$result = mysqli_query($koneksi, $query);

if (mysqli_num_rows($result) > 0) {
    if (!$is_simulasi) {
        tulisLog("Ditemukan " . mysqli_num_rows($result) . " member yang masa aktifnya sisa 7 hari.");
    }

    // LOOPING PENGIRIMAN
    while ($row = mysqli_fetch_assoc($result)) {
        
        // Bersihkan dan format nomor WA
        $no_wa = preg_replace('/[^0-9]/', '', $row['no_wa']);
        if(substr($no_wa, 0, 2) != '62') {
            if(substr($no_wa, 0, 1) == '0') {
                $no_wa = '62' . substr($no_wa, 1);
            } elseif(substr($no_wa, 0, 1) == '8') {
                $no_wa = '62' . $no_wa;
            }
        }

        if (empty($no_wa) || strlen($no_wa) < 9) {
            $err_msg = "Gagal kirim ke " . $row['nama_lengkap'] . ": Nomor WA tidak valid.";
            tulisLog($err_msg);
            if($is_simulasi) echo $err_msg;
            continue; 
        }

        $tanggal_habis = $row['tgl_berakhir'] ? date('d F Y', strtotime($row['tgl_berakhir'])) : '(Tidak ada paket aktif)';

        // Teks ditambahkan label [SIMULASI] agar penguji tahu ini sedang demo
        $pesan_wa = ($is_simulasi ? "[SIMULASI SISTEM]\n\n" : "") . "🔔 *PENGINGAT MASA AKTIF VANDA GYM*\n\n";
        $pesan_wa .= "Halo *" . $row['nama_lengkap'] . "*,\n\n";
        $pesan_wa .= "Ini adalah pesan otomatis dari sistem. Masa aktif paket Gym Anda akan berakhir " . ($is_simulasi ? "*(Ini adalah test pengiriman)*" : "dalam *7 HARI* lagi") . ", yaitu pada tanggal:\n";
        $pesan_wa .= "📅 *" . $tanggal_habis . "*\n\n";
        $pesan_wa .= "Agar rutinitas latihan Anda tidak terputus, segera lakukan perpanjangan.\n\n";
        $pesan_wa .= "_Tetap semangat bentuk karakter dan bangun kekuatanmu!_ 💪";

        // Kirim cURL
        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://api.fonnte.com/send',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS => array(
            'target' => $no_wa,
            'message' => $pesan_wa,
            'countryCode' => '62',
          ),
          CURLOPT_HTTPHEADER => array(
            "Authorization: $api_token"
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            $msg = "Error kirim ke $no_wa: " . $err;
            tulisLog($msg);
            if($is_simulasi) echo $msg;
        } else {
            $res = json_decode($response, true);
            if(isset($res['status']) && $res['status'] == true) {
                $msg = "SUKSES kirim notif WA ke " . $row['nama_lengkap'] . " ($no_wa)";
                tulisLog($msg);
                if($is_simulasi) echo "<h3 style='color:green;'>$msg</h3><p>Cek aplikasi WhatsApp Anda sekarang.</p>";
            } else {
                $msg = "GAGAL kirim ke " . $row['nama_lengkap'] . " ($no_wa) - " . ($res['reason'] ?? 'Unknown');
                tulisLog($msg);
                if($is_simulasi) echo "<h3 style='color:red;'>$msg</h3>";
            }
        }
        
        if (!$is_simulasi) sleep(2); // Jangan sleep jika simulasi biar cepat
    }
} else {
    $msg = "Tidak ada data yang diproses. (ID tidak ditemukan / Tidak ada member H-7).";
    tulisLog($msg);
    if($is_simulasi) echo $msg;
}

if(!$is_simulasi) tulisLog("Eksekusi Cron Job Selesai.\n-----------------------------------");
?>

// http://localhost/vanda-gym-web/cron_notif.php?simulasi_id=2 //