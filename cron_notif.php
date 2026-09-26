<?php

date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/includes/koneksi.php';


// =====================================================
// FUNGSI KIRIM EMAIL NOTIFIKASI MEMBERSHIP
// =====================================================
function kirimEmailMembership($emailTujuan, $nama, $jenis, $tglBerakhir)
{
    $tanggal = date('d-m-Y', strtotime($tglBerakhir));

    // =================================================
    // NOTIFIKASI SISA 7 HARI
    // =================================================
    if ($jenis === '7_hari') {

        $subjek = 'Masa Aktif Membership Anda Tersisa 7 Hari - Vanda Gym';

        $pesan =
            "Halo $nama,\n\n" .
            "Masa aktif membership Vanda Gym Classic Anda akan berakhir dalam 7 hari.\n\n" .
            "Tanggal berakhir: $tanggal\n\n" .
            "Silakan lakukan perpanjangan membership melalui website Vanda Gym Classic jika Anda ingin melanjutkan membership.\n\n" .
            "Terima kasih,\n" .
            "Vanda Gym Classic";


    // =================================================
    // NOTIFIKASI SISA 1 HARI
    // =================================================
    } elseif ($jenis === '1_hari') {

        $subjek = 'Masa Aktif Membership Anda Tersisa 1 Hari - Vanda Gym';

        $pesan =
            "Halo $nama,\n\n" .
            "Masa aktif membership Vanda Gym Classic Anda akan berakhir dalam 1 hari.\n\n" .
            "Tanggal berakhir: $tanggal\n\n" .
            "Silakan segera melakukan perpanjangan membership melalui website Vanda Gym Classic jika Anda ingin melanjutkan membership.\n\n" .
            "Terima kasih,\n" .
            "Vanda Gym Classic";

    } else {

        return false;
    }


    // =================================================
    // HEADER EMAIL
    // =================================================
    $headers =
        "From: Vanda Gym Classic <no-reply@vandagym.com>\r\n" .
        "Reply-To: no-reply@vandagym.com\r\n" .
        "Content-Type: text/plain; charset=UTF-8\r\n";


    return @mail(
        $emailTujuan,
        $subjek,
        $pesan,
        $headers
    );
}



// =====================================================
// 1. NOTIFIKASI SISA MASA AKTIF 7 HARI
// =====================================================

$query7Hari = mysqli_query($koneksi, "
    SELECT
        m.id_membership,
        m.id_user,
        m.tgl_berakhir,
        u.nama_lengkap,
        u.email

    FROM membership m

    JOIN users u
        ON u.id_user = m.id_user

    WHERE m.status = 'aktif'

    AND DATEDIFF(m.tgl_berakhir, CURDATE()) = 7

    AND u.notif_email = 1

    AND u.email IS NOT NULL
    AND u.email != ''

    AND NOT EXISTS (
        SELECT 1
        FROM membership p

        WHERE p.id_user = m.id_user
        AND p.status = 'pending'
        AND p.jenis_pengajuan = 'perpanjang'
    )
");


if ($query7Hari) {

    while ($data = mysqli_fetch_assoc($query7Hari)) {

        kirimEmailMembership(
            $data['email'],
            $data['nama_lengkap'],
            '7_hari',
            $data['tgl_berakhir']
        );
    }
}



// =====================================================
// 2. NOTIFIKASI SISA MASA AKTIF 1 HARI
// =====================================================

$query1Hari = mysqli_query($koneksi, "
    SELECT
        m.id_membership,
        m.id_user,
        m.tgl_berakhir,
        u.nama_lengkap,
        u.email

    FROM membership m

    JOIN users u
        ON u.id_user = m.id_user

    WHERE m.status = 'aktif'

    AND DATEDIFF(m.tgl_berakhir, CURDATE()) = 1

    AND u.notif_email = 1

    AND u.email IS NOT NULL
    AND u.email != ''

    AND NOT EXISTS (
        SELECT 1
        FROM membership p

        WHERE p.id_user = m.id_user
        AND p.status = 'pending'
        AND p.jenis_pengajuan = 'perpanjang'
    )
");


if ($query1Hari) {

    while ($data = mysqli_fetch_assoc($query1Hari)) {

        kirimEmailMembership(
            $data['email'],
            $data['nama_lengkap'],
            '1_hari',
            $data['tgl_berakhir']
        );
    }
}



// =====================================================
// 3. UPDATE STATUS JIKA MASA AKTIF SUDAH HABIS
// =====================================================

mysqli_query($koneksi, "
    UPDATE membership

    SET status = 'kedaluwarsa'

    WHERE status = 'aktif'

    AND tgl_berakhir < CURDATE()
");



echo "Pengecekan notifikasi membership selesai.";
?>