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
// FUNGSI PROSES NOTIFIKASI
// Mencegah email yang sama terkirim berkali-kali
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

    // -------------------------------------------------
    // SIMPAN PENANDA TERLEBIH DAHULU
    // UNIQUE KEY mencegah email yang sama diproses lagi
    // -------------------------------------------------
    $jenisSafe = mysqli_real_escape_string($koneksi, $jenis);

    $simpanLog = mysqli_query($koneksi, "
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

    if (!$simpanLog) {
        return false;
    }


    // Jika affected_rows = 0,
    // berarti notifikasi ini sudah pernah tercatat/dikirim
    if (mysqli_affected_rows($koneksi) === 0) {
        return false;
    }


    // -------------------------------------------------
    // KIRIM EMAIL
    // -------------------------------------------------
    $berhasil = kirimEmailMembership(
        $email,
        $nama,
        $jenis,
        $tglBerakhir
    );


    // -------------------------------------------------
    // JIKA EMAIL GAGAL:
    // hapus log supaya cron berikutnya boleh mencoba lagi
    // -------------------------------------------------
    if (!$berhasil) {

        mysqli_query($koneksi, "
            DELETE FROM log_notifikasi_email
            WHERE id_membership = $idMembership
            AND jenis_notif = '$jenisSafe'
        ");

        return false;
    }


    return true;
}



// =====================================================
// COUNTER UNTUK OUTPUT CRON
// =====================================================
$jumlah7Hari = 0;
$jumlah1Hari = 0;
$jumlahGagal = 0;


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

    -- Hanya membership aktif terbaru milik user
    AND m.id_membership = (
        SELECT MAX(m2.id_membership)
        FROM membership m2
        WHERE m2.id_user = m.id_user
        AND m2.status = 'aktif'
    )

    -- Jangan kirim jika perpanjangan sedang diverifikasi
    AND NOT EXISTS (
        SELECT 1
        FROM membership p
        WHERE p.id_user = m.id_user
        AND p.status = 'pending'
        AND p.jenis_pengajuan = 'perpanjang'
    )

    -- Jangan proses jika sebelumnya sudah pernah dikirim
    AND NOT EXISTS (
        SELECT 1
        FROM log_notifikasi_email l
        WHERE l.id_membership = m.id_membership
        AND l.jenis_notif = '7_hari'
    )
");


if ($query7Hari) {

    while ($data = mysqli_fetch_assoc($query7Hari)) {

        $hasil = prosesNotifikasi(
            $koneksi,
            $data['id_membership'],
            $data['email'],
            $data['nama_lengkap'],
            '7_hari',
            $data['tgl_berakhir']
        );


        if ($hasil) {
            $jumlah7Hari++;
        } else {
            $jumlahGagal++;
        }
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

    -- Hanya membership aktif terbaru milik user
    AND m.id_membership = (
        SELECT MAX(m2.id_membership)
        FROM membership m2
        WHERE m2.id_user = m.id_user
        AND m2.status = 'aktif'
    )

    -- Jangan kirim jika perpanjangan sedang diverifikasi
    AND NOT EXISTS (
        SELECT 1
        FROM membership p
        WHERE p.id_user = m.id_user
        AND p.status = 'pending'
        AND p.jenis_pengajuan = 'perpanjang'
    )

    -- Jangan proses jika sebelumnya sudah pernah dikirim
    AND NOT EXISTS (
        SELECT 1
        FROM log_notifikasi_email l
        WHERE l.id_membership = m.id_membership
        AND l.jenis_notif = '1_hari'
    )
");


if ($query1Hari) {

    while ($data = mysqli_fetch_assoc($query1Hari)) {

        $hasil = prosesNotifikasi(
            $koneksi,
            $data['id_membership'],
            $data['email'],
            $data['nama_lengkap'],
            '1_hari',
            $data['tgl_berakhir']
        );


        if ($hasil) {
            $jumlah1Hari++;
        } else {
            $jumlahGagal++;
        }
    }
}



// =====================================================
// 3. UPDATE STATUS MEMBERSHIP YANG SUDAH HABIS
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

echo "Pengecekan notifikasi membership selesai.\n";
echo "Email H-7 dikirim: $jumlah7Hari\n";
echo "Email H-1 dikirim: $jumlah1Hari\n";
echo "Gagal diproses: $jumlahGagal\n";

?>