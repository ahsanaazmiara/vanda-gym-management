<?php

date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/includes/koneksi.php';


// =====================================================
// KONFIGURASI
// =====================================================

$namaGym = 'Vanda Gym Classic';
$urlWebsite = 'https://vandagym.my.id';
$urlPerpanjang = 'https://vandagym.my.id/perpanjang.php';


// =====================================================
// FUNGSI KIRIM EMAIL MEMBERSHIP
// =====================================================

function kirimEmailMembership(
    $emailTujuan,
    $nama,
    $jenis,
    $tglBerakhir,
    $idMembership
) {
    global $namaGym, $urlWebsite, $urlPerpanjang;

    // Validasi email
    if (!filter_var($emailTujuan, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    // Amankan data untuk HTML
    $namaAman = htmlspecialchars(
        $nama,
        ENT_QUOTES,
        'UTF-8'
    );

    $tanggal = date(
        'd-m-Y',
        strtotime($tglBerakhir)
    );


    // =================================================
    // NOTIFIKASI H-7
    // =================================================

    if ($jenis === '7_hari') {

        $jumlahHari = 7;

        $subjek =
            "Pengingat Membership H-7 • Berakhir $tanggal • Vanda Gym";

        $judul =
            "Masa Aktif Tersisa 7 Hari";

        $deskripsi =
            "Masa aktif membership Anda akan berakhir dalam "
            . "<strong>7 hari</strong>.";


    // =================================================
    // NOTIFIKASI H-1
    // =================================================

    } elseif ($jenis === '1_hari') {

        $jumlahHari = 1;

        $subjek =
            "Pengingat Membership H-1 • Berakhir $tanggal • Vanda Gym";

        $judul =
            "Masa Aktif Tersisa 1 Hari";

        $deskripsi =
            "Masa aktif membership Anda akan berakhir dalam "
            . "<strong>1 hari</strong>.";

    } else {

        return false;
    }


    // =================================================
    // ISI EMAIL HTML
    // =================================================

    $pesan = "
    <!DOCTYPE html>
    <html lang='id'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport'
              content='width=device-width, initial-scale=1.0'>

        <title>$judul</title>
    </head>

    <body style='
        margin:0;
        padding:0;
        background-color:#f4f4f4;
        font-family:Arial, Helvetica, sans-serif;
        color:#222222;
    '>

        <div style='
            width:100%;
            padding:30px 15px;
            box-sizing:border-box;
        '>

            <div style='
                max-width:600px;
                margin:0 auto;
                background:#ffffff;
                border-radius:10px;
                overflow:hidden;
                border:1px solid #e0e0e0;
            '>

                <!-- HEADER -->
                <div style='
                    background:#111111;
                    padding:24px;
                    text-align:center;
                    border-top:5px solid #E8C999;
                '>

                    <div style='
                        color:#E8C999;
                        font-size:22px;
                        font-weight:bold;
                    '>
                        Vanda Gym Classic
                    </div>

                    <div style='
                        color:#aaaaaa;
                        font-size:12px;
                        margin-top:5px;
                    '>
                        Membership Reminder
                    </div>

                </div>


                <!-- CONTENT -->
                <div style='
                    padding:30px;
                    line-height:1.6;
                '>

                    <p style='
                        margin-top:0;
                        font-size:16px;
                    '>
                        Halo <strong>$namaAman</strong>,
                    </p>


                    <!-- ALERT -->
                    <div style='
                        background:#fff4f4;
                        border:1px solid #dc3545;
                        border-left:5px solid #dc3545;
                        border-radius:6px;
                        padding:18px;
                        margin:22px 0;
                    '>

                        <div style='
                            color:#b5202d;
                            font-size:20px;
                            font-weight:bold;
                            margin-bottom:6px;
                        '>
                            $judul
                        </div>

                        <div style='
                            color:#444444;
                            font-size:14px;
                        '>
                            $deskripsi
                        </div>

                    </div>


                    <!-- DETAIL -->
                    <div style='
                        background:#f8f8f8;
                        padding:18px;
                        border-radius:6px;
                        margin-bottom:25px;
                    '>

                        <div style='
                            color:#777777;
                            font-size:12px;
                            margin-bottom:5px;
                        '>
                            TANGGAL BERAKHIR
                        </div>

                        <div style='
                            font-size:18px;
                            font-weight:bold;
                            color:#222222;
                        '>
                            $tanggal
                        </div>

                    </div>


                    <p style='
                        font-size:14px;
                        color:#555555;
                    '>
                        Silakan melakukan perpanjangan membership
                        agar masa aktif Anda dapat dilanjutkan.
                    </p>


                    <!-- BUTTON -->
                    <div style='
                        text-align:center;
                        margin:30px 0;
                    '>

                        <a href='$urlPerpanjang'
                           style='
                                display:inline-block;
                                background:#b51f24;
                                color:#ffffff;
                                text-decoration:none;
                                padding:13px 25px;
                                border-radius:6px;
                                font-size:14px;
                                font-weight:bold;
                           '>

                            Perpanjang Membership

                        </a>

                    </div>


                    <p style='
                        color:#777777;
                        font-size:12px;
                        margin-bottom:0;
                    '>
                        Jika Anda sudah melakukan pengajuan
                        perpanjangan, silakan abaikan email ini.
                    </p>

                </div>


                <!-- FOOTER -->
                <div style='
                    background:#111111;
                    padding:20px;
                    text-align:center;
                '>

                    <div style='
                        color:#E8C999;
                        font-size:13px;
                        font-weight:bold;
                    '>
                        Vanda Gym Classic
                    </div>

                    <div style='
                        color:#777777;
                        font-size:11px;
                        margin-top:5px;
                    '>
                        Email ini dikirim secara otomatis.
                        Mohon tidak membalas email ini.
                    </div>

                    <div style='
                        margin-top:8px;
                    '>

                        <a href='$urlWebsite'
                           style='
                                color:#aaaaaa;
                                font-size:11px;
                                text-decoration:none;
                           '>
                            vandagym.my.id
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </body>
    </html>
    ";


    // =================================================
    // HEADER EMAIL
    // =================================================

    $headers =
        "MIME-Version: 1.0\r\n" .
        "From: Vanda Gym Classic <no-reply@vandagym.com>\r\n" .
        "Content-Type: text/html; charset=UTF-8\r\n" .
        "X-Mailer: PHP/" . phpversion() . "\r\n" .

        // ID berbeda untuk membantu membedakan setiap notifikasi
        "X-Vanda-Notification: membership-" .
        $idMembership . "-" .
        $jenis . "\r\n";


    // =================================================
    // KIRIM
    // =================================================

    return @mail(
        $emailTujuan,
        $subjek,
        $pesan,
        $headers
    );
}



// =====================================================
// FUNGSI PROSES NOTIFIKASI
// =====================================================

function prosesNotifikasi(
    $koneksi,
    $idMembership,
    $email,
    $nama,
    $jenis,
    $tglBerakhir
) {

    $idMembership = (int)$idMembership;

    $jenisSafe = mysqli_real_escape_string(
        $koneksi,
        $jenis
    );


    // =================================================
    // CEK APAKAH SUDAH PERNAH DIKIRIM
    // =================================================

    $cekLog = mysqli_query($koneksi, "
        SELECT id_log
        FROM log_notifikasi_email

        WHERE id_membership = $idMembership

        AND jenis_notif = '$jenisSafe'

        LIMIT 1
    ");


    if (
        $cekLog &&
        mysqli_num_rows($cekLog) > 0
    ) {

        return 'sudah_dikirim';
    }


    // =================================================
    // RESERVASI LOG
    // =================================================
    // Disimpan sebelum mail() supaya apabila cron
    // berjalan bersamaan, email tidak dikirim dua kali.
    // =================================================

    $insertLog = mysqli_query($koneksi, "
        INSERT IGNORE INTO log_notifikasi_email
        (
            id_membership,
            jenis_notif,
            tanggal_kirim
        )

        VALUES
        (
            $idMembership,
            '$jenisSafe',
            NOW()
        )
    ");


    if (!$insertLog) {

        return 'gagal_log';
    }


    // Jika INSERT IGNORE tidak menambah baris,
    // berarti sudah diproses oleh eksekusi lain.
    if (mysqli_affected_rows($koneksi) === 0) {

        return 'sudah_dikirim';
    }


    // =================================================
    // KIRIM EMAIL
    // =================================================

    $berhasil = kirimEmailMembership(
        $email,
        $nama,
        $jenis,
        $tglBerakhir,
        $idMembership
    );


    // =================================================
    // JIKA EMAIL GAGAL
    // =================================================

    if (!$berhasil) {

        // Hapus log supaya cron berikutnya
        // masih boleh mencoba mengirim lagi.

        mysqli_query($koneksi, "
            DELETE FROM log_notifikasi_email

            WHERE id_membership = $idMembership

            AND jenis_notif = '$jenisSafe'
        ");

        return 'gagal_email';
    }


    return 'berhasil';
}



// =====================================================
// COUNTER OUTPUT CRON
// =====================================================

$jumlah7Hari = 0;
$jumlah1Hari = 0;
$jumlahLewati = 0;
$jumlahGagal = 0;



// =====================================================
// AMBIL MEMBER H-7 DAN H-1 SEKALIGUS
// =====================================================

$queryNotif = mysqli_query($koneksi, "

    SELECT

        m.id_membership,
        m.id_user,
        m.tgl_berakhir,

        DATEDIFF(
            m.tgl_berakhir,
            CURDATE()
        ) AS sisa_hari,

        u.nama_lengkap,
        u.email

    FROM membership m

    INNER JOIN users u
        ON u.id_user = m.id_user


    WHERE m.status = 'aktif'


    -- Hanya H-7 dan H-1
    AND DATEDIFF(
        m.tgl_berakhir,
        CURDATE()
    ) IN (7, 1)


    -- User memilih notifikasi email
    AND u.notif_email = 1


    -- Email wajib tersedia
    AND u.email IS NOT NULL

    AND u.email != ''


    -- =================================================
    -- HANYA MEMBERSHIP AKTIF TERBARU
    -- =================================================

    AND m.id_membership = (

        SELECT MAX(m2.id_membership)

        FROM membership m2

        WHERE m2.id_user = m.id_user

        AND m2.status = 'aktif'

    )


    -- =================================================
    -- JANGAN KIRIM JIKA PERPANJANGAN SEDANG PENDING
    -- =================================================

    AND NOT EXISTS (

        SELECT 1

        FROM membership p

        WHERE p.id_user = m.id_user

        AND p.status = 'pending'

        AND p.jenis_pengajuan = 'perpanjang'

    )

");



// =====================================================
// PROSES HASIL QUERY
// =====================================================

if ($queryNotif) {

    while (
        $data = mysqli_fetch_assoc($queryNotif)
    ) {


        // =============================================
        // TENTUKAN JENIS EMAIL
        // =============================================

        if ((int)$data['sisa_hari'] === 7) {

            $jenis = '7_hari';

        } elseif ((int)$data['sisa_hari'] === 1) {

            $jenis = '1_hari';

        } else {

            continue;
        }


        // =============================================
        // PROSES EMAIL
        // =============================================

        $hasil = prosesNotifikasi(

            $koneksi,

            $data['id_membership'],

            $data['email'],

            $data['nama_lengkap'],

            $jenis,

            $data['tgl_berakhir']

        );


        // =============================================
        // HITUNG OUTPUT
        // =============================================

        if ($hasil === 'berhasil') {

            if ($jenis === '7_hari') {

                $jumlah7Hari++;

            } elseif ($jenis === '1_hari') {

                $jumlah1Hari++;
            }


        } elseif ($hasil === 'sudah_dikirim') {

            $jumlahLewati++;


        } else {

            $jumlahGagal++;
        }

    }

} else {

    echo "Query notifikasi gagal: "
       . mysqli_error($koneksi)
       . "\n";
}



// =====================================================
// UPDATE MEMBERSHIP YANG SUDAH KEDALUWARSA
// =====================================================

mysqli_query($koneksi, "

    UPDATE membership

    SET status = 'kedaluwarsa'

    WHERE status = 'aktif'

    AND tgl_berakhir < CURDATE()

");



// =====================================================
// OUTPUT CRON
// =====================================================

echo "=====================================\n";
echo "VANDA GYM - CRON NOTIFIKASI\n";
echo "=====================================\n";

echo "Waktu: "
   . date('d-m-Y H:i:s')
   . "\n";

echo "Email H-7 dikirim : "
   . $jumlah7Hari
   . "\n";

echo "Email H-1 dikirim : "
   . $jumlah1Hari
   . "\n";

echo "Sudah pernah dikirim : "
   . $jumlahLewati
   . "\n";

echo "Gagal : "
   . $jumlahGagal
   . "\n";

echo "=====================================\n";
echo "Pengecekan selesai.\n";

?>