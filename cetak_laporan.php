<?php
session_start();
require 'includes/koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    exit('Akses ditolak.');
}

$bulan = $_GET['bulan'] ?? date('m');
$tahun = $_GET['tahun'] ?? date('Y');

$nama_bulan = [
    '01'=>'Januari', '02'=>'Februari', '03'=>'Maret', '04'=>'April', '05'=>'Mei', '06'=>'Juni',
    '07'=>'Juli', '08'=>'Agustus', '09'=>'September', '10'=>'Oktober', '11'=>'November', '12'=>'Desember'
];

// Ambil data transaksi aktif pada bulan & tahun tersebut
$query = "SELECT m.*, u.nama_lengkap 
          FROM membership m 
          JOIN users u ON m.id_user = u.id_user 
          WHERE m.status = 'aktif' 
          AND MONTH(m.created_at) = '$bulan' 
          AND YEAR(m.created_at) = '$tahun'
          ORDER BY m.created_at ASC";
$result = mysqli_query($koneksi, $query);

$total_pendapatan = 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan_Penghasilan_<?= $bulan ?>_<?= $tahun ?></title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; text-transform: uppercase; }
        .header p { margin: 5px 0 0; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #999; padding: 10px; text-align: left; font-size: 12px; }
        th { background-color: #f2f2f2; }
        .total-row { font-weight: bold; background-color: #eee; }
        .footer { margin-top: 30px; text-align: right; font-size: 12px; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="background: #fff3cd; padding: 10px; margin-bottom: 20px; border: 1px solid #ffeeba; font-size: 13px;">
        <strong>Petunjuk:</strong> Gunakan menu <b>"Save as PDF"</b> di tujuan printer untuk mengunduh laporan ini.
    </div>

    <div class="header">
        <h1>VANDA GYM CLASSIC ROOM</h1>
        <p>Laporan Pendapatan Membership Bulanan</p>
        <p>Periode: <?= $nama_bulan[$bulan] ?> <?= $tahun ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Nama Member</th>
                <th>Paket</th>
                <th>Metode Bayar</th>
                <th>Jumlah (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            if(mysqli_num_rows($result) > 0):
                while($row = mysqli_fetch_assoc($result)): 
                    $total_pendapatan += $row['total_harga'];
            ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
                    <td><?= $row['nama_lengkap'] ?></td>
                    <td><?= $row['paket_bulan'] ?> Bulan</td>
                    <td><?= strtoupper($row['metode_bayar']) ?></td>
                    <td><?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                </tr>
            <?php 
                endwhile; 
            else:
                echo "<tr><td colspan='6' style='text-align:center;'>Tidak ada data transaksi bulan ini.</td></tr>";
            endif;
            ?>
            <tr class="total-row">
                <td colspan="5" style="text-align: right;">TOTAL PENDAPATAN :</td>
                <td>Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>Dicetak pada: <?= date('d/m/Y H:i') ?></p>
        <p style="margin-top: 50px;">(_________________________)</p>
        <p>Admin Vanda Gym</p>
    </div>
</body>
</html>