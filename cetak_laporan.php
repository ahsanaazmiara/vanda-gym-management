<?php
session_start();
require 'includes/koneksi.php';

// Pastikan hanya admin yang bisa mencetak laporan
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak.");
}

// Tangkap parameter dari form
$jenis = $_GET['jenis'] ?? 'bulanan';
$bulan = $_GET['bulan'] ?? date('m');
$tahun = $_GET['tahun'] ?? date('Y');

// Array nama bulan
$bulan_indo = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
    '04' => 'April', '05' => 'Mei', '06' => 'Juni', '07' => 'Juli',
    '08' => 'Agustus', '09' => 'September', '10' => 'Oktober',
    '11' => 'November', '12' => 'Desember'
];

// Tentukan judul periode dan kondisi pencarian query berdasarkan jenis laporan
if ($jenis === 'tahunan') {
    $nama_periode = "Tahun " . $tahun;
    $kondisi_waktu = "YEAR(m.created_at) = '$tahun'";
} else {
    $nama_periode = "Bulan " . $bulan_indo[$bulan] . " " . $tahun;
    $kondisi_waktu = "MONTH(m.created_at) = '$bulan' AND YEAR(m.created_at) = '$tahun'";
}

// Query mengambil transaksi membership sesuai filter periode
$query = mysqli_query($koneksi, "
    SELECT u.nama_lengkap, m.jenis_pengajuan, m.paket_bulan, m.total_harga, m.created_at, m.metode_bayar 
    FROM membership m 
    JOIN users u ON m.id_user = u.id_user 
    WHERE m.status IN ('aktif', 'kedaluwarsa') AND $kondisi_waktu
    ORDER BY m.created_at ASC
");

$total_pendapatan = 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan_Pendapatan_<?= str_replace(' ', '_', $nama_periode) ?></title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 20px;
        }
        .kop-surat {
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .kop-surat h1 { margin: 0; font-size: 24px; text-transform: uppercase; }
        .kop-surat p { margin: 5px 0 0; font-size: 14px; }
        .judul-laporan {
            text-align: center;
            margin-bottom: 20px;
        }
        .judul-laporan h2 { margin: 0; font-size: 18px; text-decoration: underline; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            font-size: 14px;
        }
        table, th, td {
            border: 1px solid #000;
        }
        th, td {
            padding: 8px 12px;
            text-align: left;
        }
        th { background-color: #f2f2f2; text-align: center; }
        td.text-right { text-align: right; }
        td.text-center { text-align: center; }
        
        /* Pengaturan Kertas Saat Print PDF melalui Browser */
        @media print {
            @page { margin: 2cm; }
            body { padding: 0; }
            .btn-print { display: none !important; }
        }
        
        .btn-print {
            padding: 10px 20px; background: #8E1616; color: white; border: none; 
            border-radius: 4px; cursor: pointer; display: block; margin: 0 auto 20px auto; 
            font-weight: bold; width: 200px; text-align: center; font-family: Arial, sans-serif;
        }
    </style>
</head>
<body>

    <button class="btn-print" onclick="window.print()">🖨️ Cetak / Simpan PDF</button>

    <div class="kop-surat">
        <h1>Vanda Gym Classic Room</h1>
        <p>Jl. Kapten Pierre Tendean No.17 Palangka Raya, Kalimantan Tengah<br>Email: cs@vandagym.com | Telp/WA: <?= htmlspecialchars($_SESSION['wa_cs'] ?? '08xxx') ?></p>
    </div>

    <div class="judul-laporan">
        <h2>LAPORAN PENDAPATAN MEMBERSHIP</h2>
        <p>Periode: <strong><?= $nama_periode ?></strong></p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">Tanggal</th>
                <th width="25%">Nama Member</th>
                <th width="15%">Jenis</th>
                <th width="10%">Paket</th>
                <th width="15%">Metode</th>
                <th width="15%">Nominal</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            if(mysqli_num_rows($query) == 0): ?>
                <tr><td colspan="7" class="text-center"><em>Tidak ada transaksi aktif pada periode ini.</em></td></tr>
            <?php else:
                while($row = mysqli_fetch_assoc($query)): 
                    $total_pendapatan += $row['total_harga'];
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                <td><?= $row['nama_lengkap'] ?></td>
                <td class="text-center"><?= ucfirst($row['jenis_pengajuan']) ?></td>
                <td class="text-center"><?= $row['paket_bulan'] ?> Bln</td>
                <td class="text-center"><?= strtoupper($row['metode_bayar']) ?></td>
                <td class="text-right">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
            </tr>
            <?php endwhile; endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="6" class="text-right">TOTAL PENDAPATAN</th>
                <th class="text-right">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></th>
            </tr>
        </tfoot>
    </table>

</body>
</html>