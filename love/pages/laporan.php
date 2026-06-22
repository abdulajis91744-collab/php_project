<?php
/**
 * Halaman Laporan Penjualan POS Kasir
 * Menyediakan filter Harian, Mingguan, Bulanan, Tahunan.
 * Dilengkapi fitur cetak laporan, export Excel, dan export PDF (Save as PDF).
 */

// Memulai session dan database
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../auth/login.php?status=unauthorized");
    exit();
}

require_once '../config/koneksi.php';

// Tentukan filter tipe (Default: harian)
$filter_type = $_GET['filter'] ?? 'harian';
$sql_cond = "";
$filter_label = "";

switch ($filter_type) {
    case 'mingguan':
        $sql_cond = "t.tanggal_transaksi >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)";
        $filter_label = "7 Hari Terakhir";
        break;
    case 'bulanan':
        $sql_cond = "MONTH(t.tanggal_transaksi) = MONTH(CURDATE()) AND YEAR(t.tanggal_transaksi) = YEAR(CURDATE())";
        $filter_label = "Bulan Ini (" . date('M Y') . ")";
        break;
    case 'tahunan':
        $sql_cond = "YEAR(t.tanggal_transaksi) = YEAR(CURDATE())";
        $filter_label = "Tahun Ini (" . date('Y') . ")";
        break;
    case 'harian':
    default:
        $sql_cond = "DATE(t.tanggal_transaksi) = CURDATE()";
        $filter_label = "Hari Ini (" . date('d-m-Y') . ")";
        break;
}

// --- AMBIL DETAIL TRANSAKSI UNTUK LAPORAN ---
try {
    // 1. Data Ringkasan (Total Pendapatan & Total Transaksi)
    $stmt_summary = $pdo->query("SELECT COUNT(*) AS total_trx, SUM(total_harga) AS total_omset 
                                 FROM transaksi t 
                                 WHERE $sql_cond");
    $summary = $stmt_summary->fetch();
    $total_trx = intval($summary['total_trx']);
    $total_omset = floatval($summary['total_omset'] ?? 0);

    // 2. Daftar Produk Terlaris (Top Selling)
    $stmt_best = $pdo->query("SELECT dt.nama_produk, SUM(dt.jumlah) AS qty_terjual, SUM(dt.subtotal) AS omset_produk 
                              FROM detail_transaksi dt 
                              JOIN transaksi t ON dt.transaksi_id = t.id 
                              WHERE $sql_cond 
                              GROUP BY dt.produk_id, dt.nama_produk 
                              ORDER BY qty_terjual DESC LIMIT 5");
    $best_products = $stmt_best->fetchAll();

    // 3. Daftar Transaksi untuk Tabel Detail
    $stmt_list = $pdo->query("SELECT t.*, u.nama_lengkap AS nama_kasir 
                              FROM transaksi t 
                              LEFT JOIN users u ON t.user_id = u.id 
                              WHERE $sql_cond 
                              ORDER BY t.id DESC");
    $transactions = $stmt_list->fetchAll();

    // 4. Data Toko
    $toko = $pdo->query("SELECT * FROM pengaturan_toko WHERE id = 1")->fetch();

} catch (PDOException $e) {
    die("Error Database: " . $e->getMessage());
}

// ============================================
//   EXPORT EXCEL HANDLER
// ============================================
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $filename = "Laporan_Penjualan_" . ucfirst($filter_type) . "_" . date('Ymd') . ".xls";
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=$filename");
    header("Pragma: no-cache");
    header("Expires: 0");
    ?>
    <table border="1">
        <thead>
            <tr>
                <th colspan="6" style="font-size: 16px; font-weight: bold; text-align: center;">LAPORAN PENJUALAN - <?= strtoupper($toko['nama_toko']); ?></th>
            </tr>
            <tr>
                <th colspan="6" style="text-align: center;">Periode: <?= $filter_label; ?></th>
            </tr>
            <tr>
                <th>No</th>
                <th>No. Transaksi</th>
                <th>Tanggal</th>
                <th>Kasir</th>
                <th>Total Belanja</th>
                <th>Bayar</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1; 
            foreach ($transactions as $row): 
            ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= $row['nomor_transaksi']; ?></td>
                    <td><?= date('d-m-Y H:i', strtotime($row['tanggal_transaksi'])); ?></td>
                    <td><?= $row['nama_kasir']; ?></td>
                    <td><?= $row['total_harga']; ?></td>
                    <td><?= $row['bayar']; ?></td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="4" style="text-align: right; font-weight: bold;">TOTAL PENDAPATAN:</td>
                <td colspan="2" style="font-weight: bold;"><?= $total_omset; ?></td>
            </tr>
        </tbody>
    </table>
    <?php
    exit();
}

// ============================================
//   PRINT LAYOUT HANDLER (FOR PDF & PAPER PRINT)
// ============================================
if (isset($_GET['print']) && $_GET['print'] === 'true') {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Cetak Laporan - <?= htmlspecialchars($toko['nama_toko']); ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { font-family: 'Courier New', Courier, monospace; color: #000; background: #fff; padding: 20px; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px double #000; padding-bottom: 10px; }
            table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            th, td { border: 1px solid #000; padding: 8px; font-size: 13px; }
            th { background-color: #f2f2f2; }
            .total-row { font-weight: bold; }
        </style>
    </head>
    <body onload="window.print();">
        <div class="header">
            <h3 class="m-0 text-uppercase"><?= htmlspecialchars($toko['nama_toko']); ?></h3>
            <p class="m-0 small"><?= htmlspecialchars($toko['alamat']); ?> | Telp: <?= htmlspecialchars($toko['telepon']); ?></p>
            <h4 class="mt-3">LAPORAN PENJUALAN (<?= strtoupper($filter_type); ?>)</h4>
            <p class="small">Periode: <?= $filter_label; ?></p>
        </div>

        <h5>Ringkasan:</h5>
        <ul>
            <li>Total Transaksi: <strong><?= $total_trx; ?> Kali</strong></li>
            <li>Total Pendapatan: <strong>Rp <?= number_format($total_omset, 0, ',', '.'); ?></strong></li>
        </ul>

        <h5 class="mt-4">Detail Transaksi:</h5>
        <table>
            <thead>
                <tr>
                    <th width="50">No</th>
                    <th>No. Transaksi</th>
                    <th>Tanggal</th>
                    <th>Kasir</th>
                    <th class="text-end">Total Harga</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                foreach ($transactions as $row): 
                ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($row['nomor_transaksi']); ?></td>
                        <td><?= date('d-m-Y H:i:s', strtotime($row['tanggal_transaksi'])); ?></td>
                        <td><?= htmlspecialchars($row['nama_kasir'] ?? 'System'); ?></td>
                        <td class="text-end">Rp <?= number_format($row['total_harga'], 0, ',', '.'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="4" class="text-end">Total Pendapatan:</td>
                    <td class="text-end">Rp <?= number_format($total_omset, 0, ',', '.'); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="mt-5 d-flex justify-content-between text-center small" style="margin-top: 100px !important;">
            <div style="width: 200px;">
                <p>Dilaporkan Oleh,</p>
                <div style="margin-top: 60px;"></div>
                <p class="border-top border-dark pt-1"><?= htmlspecialchars($_SESSION['user']['nama_lengkap']); ?></p>
            </div>
            <div style="width: 200px;">
                <p>Pimpinan,</p>
                <div style="margin-top: 60px;"></div>
                <p class="border-top border-dark pt-1">....................</p>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Render Header & Sidebar untuk Tampilan Browser
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom border-secondary">
    <h1 class="h2 text-white">Laporan Penjualan</h1>
    
    <!-- Group Button Export dan Filter -->
    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
        <a href="laporan.php?filter=<?= $filter_type; ?>&print=true" target="_blank" class="btn btn-sm btn-outline-info">
            <i class="bi bi-printer me-2"></i>Cetak / PDF
        </a>
        <a href="laporan.php?filter=<?= $filter_type; ?>&export=excel" class="btn btn-sm btn-outline-success">
            <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
        </a>
    </div>
</div>

<!-- Row Box Pilihan Filter Laporan -->
<div class="card-custom mb-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-funnel text-primary fs-5"></i>
            <span class="text-white fw-semibold">Pilih Periode:</span>
        </div>
        <div class="btn-group" role="group" aria-label="Filter Laporan">
            <a href="laporan.php?filter=harian" class="btn btn-outline-secondary py-2 px-3 <?= $filter_type === 'harian' ? 'active' : ''; ?>">Harian</a>
            <a href="laporan.php?filter=mingguan" class="btn btn-outline-secondary py-2 px-3 <?= $filter_type === 'mingguan' ? 'active' : ''; ?>">Mingguan</a>
            <a href="laporan.php?filter=bulanan" class="btn btn-outline-secondary py-2 px-3 <?= $filter_type === 'bulanan' ? 'active' : ''; ?>">Bulanan</a>
            <a href="laporan.php?filter=tahunan" class="btn btn-outline-secondary py-2 px-3 <?= $filter_type === 'tahunan' ? 'active' : ''; ?>">Tahunan</a>
        </div>
    </div>
</div>

<!-- Summary Cards Laporan -->
<div class="row mb-2">
    <div class="col-md-6 mb-4">
        <div class="card-custom d-flex align-items-center justify-content-between py-4">
            <div>
                <span class="text-muted d-block small mb-1">Total Pendapatan Penjualan</span>
                <h2 class="text-indigo fw-bold mb-0">Rp <?= number_format($total_omset, 0, ',', '.'); ?></h2>
                <small class="text-muted text-capitalize">Periode: <?= $filter_label; ?></small>
            </div>
            <div class="bg-indigo bg-opacity-10 text-indigo p-3.5 rounded-3" style="font-size: 2.2rem; color: #6366f1;">
                <i class="bi bi-cash-coin"></i>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card-custom d-flex align-items-center justify-content-between py-4">
            <div>
                <span class="text-muted d-block small mb-1">Total Transaksi Penjualan</span>
                <h2 class="text-white fw-bold mb-0"><?= $total_trx; ?> Kali</h2>
                <small class="text-muted text-capitalize">Periode: <?= $filter_label; ?></small>
            </div>
            <div class="bg-success bg-opacity-10 text-success p-3.5 rounded-3" style="font-size: 2.2rem;">
                <i class="bi bi-receipt"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Tabel Rincian Penjualan -->
    <div class="col-lg-8 mb-4">
        <div class="card-custom">
            <h5 class="text-white mb-4"><i class="bi bi-list-columns-reverse me-2 text-indigo"></i>Rincian Transaksi Penjualan</h5>
            <div class="table-responsive">
                <table class="table table-dark table-hover w-100" id="tableLaporan">
                    <thead>
                        <tr>
                            <th width="50">No</th>
                            <th>No. Transaksi</th>
                            <th>Tanggal</th>
                            <th>Kasir</th>
                            <th class="text-end">Total Belanja</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach ($transactions as $row): 
                        ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><span class="badge bg-secondary font-monospace"><?= htmlspecialchars($row['nomor_transaksi']); ?></span></td>
                                <td><?= date('d-m-Y H:i:s', strtotime($row['tanggal_transaksi'])); ?></td>
                                <td><?= htmlspecialchars($row['nama_kasir'] ?? 'System'); ?></td>
                                <td class="text-end text-white fw-semibold">Rp <?= number_format($row['total_harga'], 0, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Panel Produk Terlaris -->
    <div class="col-lg-4 mb-4">
        <div class="card-custom">
            <h5 class="text-white mb-4"><i class="bi bi-fire me-2 text-warning"></i>5 Produk Terlaris</h5>
            
            <?php if (count($best_products) > 0): ?>
                <div class="list-group list-group-flush bg-transparent">
                    <?php 
                    $rank = 1;
                    foreach ($best_products as $prod): 
                    ?>
                        <div class="list-group-item bg-transparent text-white border-secondary px-0 py-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary rounded-circle" style="width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem;"><?= $rank++; ?></span>
                                    <span class="fw-semibold text-truncate" style="max-width: 170px;" title="<?= htmlspecialchars($prod['nama_produk']); ?>"><?= htmlspecialchars($prod['nama_produk']); ?></span>
                                </div>
                                <span class="badge bg-success font-monospace"><?= $prod['qty_terjual']; ?> Terjual</span>
                            </div>
                            <div class="d-flex justify-content-between text-muted small ps-4">
                                <span>Omset Penjualan</span>
                                <span>Rp <?= number_format($prod['omset_produk'], 0, ',', '.'); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center text-secondary py-5">
                    <i class="bi bi-clipboard-x" style="font-size: 2.5rem;"></i>
                    <p class="mt-2 small mb-0">Belum ada penjualan di periode ini</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
// Memasukkan footer layout
include '../includes/footer.php'; 
?>

<script>
$(document).ready(function() {
    // Inisialisasi DataTable untuk Laporan
    $('#tableLaporan').DataTable({
        "order": [[0, "asc"]],
        "language": {
            "search": "Cari Transaksi:",
            "lengthMenu": "Tampilkan _MENU_ data per halaman",
            "zeroRecords": "Tidak ada data penjualan",
            "info": "Menampilkan halaman _PAGE_ dari _PAGES_",
            "infoEmpty": "Tidak ada data tersedia",
            "infoFiltered": "(difilter dari _MAX_ total data)",
            "paginate": {
                "first": "Pertama",
                "last": "Terakhir",
                "next": "Lanjut",
                "previous": "Kembali"
            }
        }
    });
});
</script>
