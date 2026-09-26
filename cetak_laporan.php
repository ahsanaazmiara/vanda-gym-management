<?php

// ============================================================
// SESSION & DATABASE
// ============================================================

require_once __DIR__ . '/session_init.php';
require_once __DIR__ . '/includes/koneksi.php';


// ============================================================
// CEK LOGIN ADMIN
// ============================================================

if (
    !isset($_SESSION['id_user']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    http_response_code(403);
    die("Akses ditolak.");
}


// ============================================================
// AMBIL PARAMETER LAPORAN
// ============================================================

$jenis = $_GET['jenis'] ?? 'bulanan';
$bulan = $_GET['bulan'] ?? date('m');
$tahun = $_GET['tahun'] ?? date('Y');


// ============================================================
// VALIDASI JENIS LAPORAN
// ============================================================

if (!in_array($jenis, ['bulanan', 'tahunan'], true)) {
    $jenis = 'bulanan';
}


// ============================================================
// VALIDASI BULAN
// ============================================================

$bulan_int = (int) $bulan;

if ($bulan_int < 1 || $bulan_int > 12) {
    $bulan_int = (int) date('m');
}

$bulan = str_pad($bulan_int, 2, '0', STR_PAD_LEFT);


// ============================================================
// VALIDASI TAHUN
// ============================================================

$tahun = (int) $tahun;

if ($tahun < 2000 || $tahun > 2100) {
    $tahun = (int) date('Y');
}


// ============================================================
// NAMA BULAN INDONESIA
// ============================================================

$bulan_indo = [
    '01' => 'Januari',
    '02' => 'Februari',
    '03' => 'Maret',
    '04' => 'April',
    '05' => 'Mei',
    '06' => 'Juni',
    '07' => 'Juli',
    '08' => 'Agustus',
    '09' => 'September',
    '10' => 'Oktober',
    '11' => 'November',
    '12' => 'Desember'
];


// ============================================================
// TENTUKAN PERIODE LAPORAN
// ============================================================

if ($jenis === 'tahunan') {

    $nama_periode = "Tahun " . $tahun;

    $kondisi_waktu = "
        YEAR(m.created_at) = $tahun
    ";

} else {

    $nama_periode = "Bulan " . $bulan_indo[$bulan] . " " . $tahun;

    $kondisi_waktu = "
        MONTH(m.created_at) = $bulan_int
        AND YEAR(m.created_at) = $tahun
    ";
}


// ============================================================
// AMBIL PENGATURAN WEBSITE
// ============================================================

$wa_cs = '08xxx';

$query_pengaturan = mysqli_query(
    $koneksi,
    "SELECT wa_cs FROM pengaturan_web WHERE id = 1 LIMIT 1"
);

if ($query_pengaturan && mysqli_num_rows($query_pengaturan) > 0) {

    $pengaturan = mysqli_fetch_assoc($query_pengaturan);

    if (!empty($pengaturan['wa_cs'])) {
        $wa_cs = $pengaturan['wa_cs'];
    }
}


// ============================================================
// QUERY TRANSAKSI MEMBERSHIP
// ============================================================

$sql = "
    SELECT
        u.nama_lengkap,
        m.jenis_pengajuan,
        m.paket_bulan,
        m.total_harga,
        m.created_at,
        m.metode_bayar

    FROM membership m

    INNER JOIN users u
        ON m.id_user = u.id_user

    WHERE
        m.status IN ('aktif', 'kedaluwarsa')
        AND $kondisi_waktu

    ORDER BY
        m.created_at ASC
";

$query = mysqli_query($koneksi, $sql);


// ============================================================
// CEK QUERY
// ============================================================

if (!$query) {

    die(
        "Gagal mengambil data laporan: " .
        htmlspecialchars(mysqli_error($koneksi))
    );
}


// ============================================================
// TOTAL PENDAPATAN
// ============================================================

$total_pendapatan = 0;


// ============================================================
// FUNGSI FORMAT TANGGAL INDONESIA
// ============================================================

function tanggalIndonesia($tanggal)
{
    $nama_bulan = [
        1 => 'Jan',
        2 => 'Feb',
        3 => 'Mar',
        4 => 'Apr',
        5 => 'Mei',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Agu',
        9 => 'Sep',
        10 => 'Okt',
        11 => 'Nov',
        12 => 'Des'
    ];

    $timestamp = strtotime($tanggal);

    if (!$timestamp) {
        return '-';
    }

    $hari = date('d', $timestamp);
    $bulan = (int) date('m', $timestamp);
    $tahun = date('Y', $timestamp);

    return $hari . ' ' . $nama_bulan[$bulan] . ' ' . $tahun;
}

?>
<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Laporan_Pendapatan_<?= htmlspecialchars(
            str_replace(' ', '_', $nama_periode)
        ) ?>
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            color: #000;
            background: #ffffff;
            margin: 0;
            padding: 20px;
        }


        /* ===================================================
           TOMBOL CETAK
        =================================================== */

        .btn-print {
            width: 240px;
            display: block;
            margin: 0 auto 25px auto;

            padding: 12px 20px;

            background: #8E1616;
            color: #ffffff;

            border: none;
            border-radius: 5px;

            cursor: pointer;

            font-family: Arial, sans-serif;
            font-size: 14px;
            font-weight: bold;

            text-align: center;
        }

        .btn-print:hover {
            background: #741212;
        }


        /* ===================================================
           KOP LAPORAN
        =================================================== */

        .kop-surat {
            text-align: center;

            border-bottom: 3px solid #000;

            padding-bottom: 12px;
            margin-bottom: 25px;
        }

        .kop-surat h1 {
            margin: 0;

            font-size: 25px;
            font-weight: bold;

            text-transform: uppercase;
        }

        .kop-surat p {
            margin: 6px 0 0 0;

            font-size: 14px;
            line-height: 1.4;
        }


        /* ===================================================
           JUDUL LAPORAN
        =================================================== */

        .judul-laporan {
            text-align: center;
            margin-bottom: 25px;
        }

        .judul-laporan h2 {
            margin: 0 0 12px 0;

            font-size: 19px;

            text-decoration: underline;
        }

        .judul-laporan p {
            margin: 0;

            font-size: 15px;
        }


        /* ===================================================
           TABEL
        =================================================== */

        table {

            width: 100%;

            border-collapse: collapse;

            margin-bottom: 30px;

            font-size: 14px;
        }

        table,
        th,
        td {
            border: 1px solid #000;
        }

        th,
        td {
            padding: 8px 10px;
        }

        th {
            background-color: #f2f2f2;

            text-align: center;

            font-weight: bold;
        }

        td {
            vertical-align: middle;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }


        /* ===================================================
           TOTAL
        =================================================== */

        tfoot th {
            font-weight: bold;
            background: #f2f2f2;
        }


        /* ===================================================
           PRINT / PDF
        =================================================== */

        @media print {

            @page {
                size: A4 portrait;
                margin: 1.5cm;
            }

            body {
                margin: 0;
                padding: 0;
            }

            .btn-print {
                display: none !important;
            }

            table {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            thead {
                display: table-header-group;
            }

            tfoot {
                display: table-footer-group;
            }

        }


        /* ===================================================
           RESPONSIVE
        =================================================== */

        @media screen and (max-width: 768px) {

            body {
                padding: 10px;
            }

            .kop-surat h1 {
                font-size: 20px;
            }

            .kop-surat p {
                font-size: 12px;
            }

            .judul-laporan h2 {
                font-size: 16px;
            }

            table {
                font-size: 11px;
            }

            th,
            td {
                padding: 6px;
            }

        }

    </style>

</head>

<body>


<!-- ========================================================
     TOMBOL CETAK
========================================================= -->

<button
    type="button"
    class="btn-print"
    onclick="window.print()"
>
    🖨️ Cetak / Simpan PDF
</button>



<!-- ========================================================
     KOP SURAT
========================================================= -->

<div class="kop-surat">

    <h1>
        Vanda Gym Classic Room
    </h1>

    <p>
        Jl. Kapten Pierre Tendean No.17
        Palangka Raya, Kalimantan Tengah
        <br>

        Email: cs@vandagym.com

        |

        Telp/WA:
        <?= htmlspecialchars($wa_cs) ?>
    </p>

</div>



<!-- ========================================================
     JUDUL LAPORAN
========================================================= -->

<div class="judul-laporan">

    <h2>
        LAPORAN PENDAPATAN MEMBERSHIP
    </h2>

    <p>
        Periode:
        <strong>
            <?= htmlspecialchars($nama_periode) ?>
        </strong>
    </p>

</div>



<!-- ========================================================
     TABEL LAPORAN
========================================================= -->

<table>

    <thead>

        <tr>

            <th style="width:5%;">
                No
            </th>

            <th style="width:15%;">
                Tanggal
            </th>

            <th style="width:25%;">
                Nama Member
            </th>

            <th style="width:15%;">
                Jenis
            </th>

            <th style="width:10%;">
                Paket
            </th>

            <th style="width:15%;">
                Metode
            </th>

            <th style="width:15%;">
                Nominal
            </th>

        </tr>

    </thead>


    <tbody>

    <?php

    $no = 1;

    if (mysqli_num_rows($query) === 0):

    ?>

        <tr>

            <td
                colspan="7"
                class="text-center"
            >
                <em>
                    Tidak ada transaksi aktif pada periode ini.
                </em>
            </td>

        </tr>


    <?php

    else:

        while ($row = mysqli_fetch_assoc($query)):

            $total_pendapatan += (int) $row['total_harga'];

    ?>

        <tr>

            <td class="text-center">

                <?= $no++ ?>

            </td>


            <td>

                <?= htmlspecialchars(
                    tanggalIndonesia($row['created_at'])
                ) ?>

            </td>


            <td>

                <?= htmlspecialchars(
                    $row['nama_lengkap']
                ) ?>

            </td>


            <td class="text-center">

                <?= htmlspecialchars(
                    ucfirst($row['jenis_pengajuan'])
                ) ?>

            </td>


            <td class="text-center">

                <?= (int) $row['paket_bulan'] ?>
                Bln

            </td>


            <td class="text-center">

                <?= htmlspecialchars(
                    strtoupper($row['metode_bayar'])
                ) ?>

            </td>


            <td class="text-right">

                Rp
                <?= number_format(
                    (int) $row['total_harga'],
                    0,
                    ',',
                    '.'
                ) ?>

            </td>

        </tr>


    <?php

        endwhile;

    endif;

    ?>

    </tbody>


    <tfoot>

        <tr>

            <th
                colspan="6"
                class="text-right"
            >
                TOTAL PENDAPATAN
            </th>

            <th class="text-right">

                Rp
                <?= number_format(
                    $total_pendapatan,
                    0,
                    ',',
                    '.'
                ) ?>

            </th>

        </tr>

    </tfoot>

</table>


</body>
</html>