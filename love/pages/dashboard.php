<?php
/**
 * Halaman Dashboard Utama POS Kasir
 */

// Memasukkan header layout (memeriksa login & koneksi db)
include '../includes/header.php';
include '../includes/sidebar.php';

// Ambil Statistik
try {
    // 1. Total Produk
    $total_produk = $pdo->query("SELECT COUNT(*) FROM produk")->fetchColumn();

    // 2. Total Kategori
    $total_kategori = $pdo->query("SELECT COUNT(*) FROM kategori")->fetchColumn();

    // 3. Total Transaksi Hari Ini
    $trx_hari_ini = $pdo->query("SELECT COUNT(*) FROM transaksi WHERE DATE(tanggal_transaksi) = CURDATE()")->fetchColumn();

    // 4. Total Pendapatan Hari Ini
    $pendapatan_hari_ini = $pdo->query("SELECT SUM(total_harga) FROM transaksi WHERE DATE(tanggal_transaksi) = CURDATE()")->fetchColumn();
    $pendapatan_hari_ini = floatval($pendapatan_hari_ini ?? 0);

} catch (PDOException $e) {
    die("Gagal memuat statistik dashboard: " . $e->getMessage());
}

// --- DATA GRAFIK PENJUALAN (7 HARI TERAKHIR) ---
$sales_trend = [];
for ($i = 6; $i >= 0; $i--) {
    $date_key = date('Y-m-d', strtotime("-$i days"));
    $sales_trend[$date_key] = 0;
}

try {
    $stmt_chart = $pdo->query("SELECT DATE(tanggal_transaksi) AS tgl, SUM(total_harga) AS total 
                               FROM transaksi 
                               WHERE tanggal_transaksi >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) 
                               GROUP BY DATE(tanggal_transaksi)");
    
    while ($row = $stmt_chart->fetch()) {
        if (isset($sales_trend[$row['tgl']])) {
            $sales_trend[$row['tgl']] = floatval($row['total']);
        }
    }
} catch (PDOException $e) {
    // chart failed fallback
}

$labels = [];
$totals = [];
foreach ($sales_trend as $date => $val) {
    $labels[] = date('d M', strtotime($date));
    $totals[] = $val;
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom border-secondary">
    <h1 class="h2 text-white">Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <span class="text-muted"><i class="bi bi-person-workspace me-2 text-indigo"></i>Login sebagai: <strong class="text-capitalize text-white"><?= $_SESSION['user']['role']; ?></strong></span>
    </div>
</div>

<!-- Grid Card Statistik -->
<div class="row">
    <!-- Card Total Produk -->
    <div class="col-md-3 col-sm-6 mb-4">
        <div class="card-custom d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted d-block small mb-1">Total Produk</span>
                <h3 class="text-white fw-bold mb-0"><?= number_format($total_produk, 0, ',', '.'); ?></h3>
            </div>
            <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-3" style="font-size: 1.8rem;">
                <i class="bi bi-box-seam"></i>
            </div>
        </div>
    </div>

    <!-- Card Total Kategori -->
    <div class="col-md-3 col-sm-6 mb-4">
        <div class="card-custom d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted d-block small mb-1">Total Kategori</span>
                <h3 class="text-white fw-bold mb-0"><?= number_format($total_kategori, 0, ',', '.'); ?></h3>
            </div>
            <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-3" style="font-size: 1.8rem;">
                <i class="bi bi-tags"></i>
            </div>
        </div>
    </div>

    <!-- Card Transaksi Hari Ini -->
    <div class="col-md-3 col-sm-6 mb-4">
        <div class="card-custom d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted d-block small mb-1">Transaksi Hari Ini</span>
                <h3 class="text-white fw-bold mb-0"><?= number_format($trx_hari_ini, 0, ',', '.'); ?></h3>
            </div>
            <div class="bg-success bg-opacity-10 text-success p-3 rounded-3" style="font-size: 1.8rem;">
                <i class="bi bi-cart-check"></i>
            </div>
        </div>
    </div>

    <!-- Card Pendapatan Hari Ini -->
    <div class="col-md-3 col-sm-6 mb-4">
        <div class="card-custom d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted d-block small mb-1">Pendapatan Hari Ini</span>
                <h3 class="text-indigo fw-bold mb-0" style="font-size: 1.35rem;">Rp <?= number_format($pendapatan_hari_ini, 0, ',', '.'); ?></h3>
            </div>
            <div class="bg-indigo bg-opacity-10 text-indigo p-3 rounded-3" style="font-size: 1.8rem; color: #6366f1;">
                <i class="bi bi-cash-stack"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Panel Chart Grafik Penjualan -->
    <div class="col-lg-8 mb-4">
        <div class="card-custom h-100">
            <h5 class="text-white mb-4"><i class="bi bi-graph-up-arrow me-2 text-indigo"></i>Tren Penjualan (7 Hari Terakhir)</h5>
            <div style="position: relative; height: 280px;">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Panel Informasi Profil / Pintasan Cepat -->
    <div class="col-lg-4 mb-4">
        <div class="card-custom h-100 d-flex flex-column justify-content-between">
            <div>
                <h5 class="text-white mb-3"><i class="bi bi-person-bounding-box me-2 text-indigo"></i>Profil Kasir</h5>
                <div class="d-flex align-items-center gap-3 mb-4">
                    <span class="avatar-circle bg-primary text-white d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 55px; height: 55px; font-size: 1.5rem; font-weight: 700;">
                        <?= strtoupper(substr($_SESSION['user']['nama_lengkap'], 0, 1)); ?>
                    </span>
                    <div>
                        <h6 class="text-white mb-0"><?= htmlspecialchars($_SESSION['user']['nama_lengkap']); ?></h6>
                        <span class="text-muted small">Username: @<?= htmlspecialchars($_SESSION['user']['username']); ?></span>
                    </div>
                </div>
                <div class="receipt-line"></div>
                <div class="text-muted small mt-3">
                    <p class="mb-1"><i class="bi bi-building me-2"></i>Unit Toko: <strong><?= htmlspecialchars($toko['nama_toko']); ?></strong></p>
                    <p class="mb-0"><i class="bi bi-geo-alt me-2"></i>Alamat: <?= htmlspecialchars(strlen($toko['alamat']) > 40 ? substr($toko['alamat'], 0, 40) . '...' : $toko['alamat']); ?></p>
                </div>
            </div>
            
            <div class="mt-4">
                <a href="transaksi.php" class="btn btn-primary-custom w-100 py-2.5 d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-cart-plus"></i>
                    <span>Mulai Transaksi Baru</span>
                </a>
            </div>
        </div>
    </div>
</div>

<?php 
// Memasukkan footer layout
include '../includes/footer.php'; 
?>

<!-- Script Inisialisasi Grafik Chart.js -->
<script>
$(document).ready(function() {
    // Ambil data label dan value dari PHP
    const labels = <?= json_encode($labels); ?>;
    const totals = <?= json_encode($totals); ?>;

    const ctx = document.getElementById('salesChart').getContext('2d');
    
    // Inisialisasi Chart.js
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Pendapatan Harian (Rp)',
                data: totals,
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                borderColor: '#6366f1',
                borderWidth: 3,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#6366f1',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7,
                tension: 0.35, // Membuat garis melengkung smooth
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false // Sembunyikan legenda dataset
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Pendapatan: Rp ' + context.raw.toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                y: {
                    grid: {
                        color: 'rgba(51, 65, 85, 0.5)'
                    },
                    ticks: {
                        color: '#94a3b8',
                        font: {
                            family: 'Outfit'
                        },
                        callback: function(value, index, values) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#94a3b8',
                        font: {
                            family: 'Outfit'
                        }
                    }
                }
            }
        }
    });

    // Notifikasi SweetAlert jika ada status dari PHP Session
    <?php if (isset($_SESSION['swal_notif'])): ?>
        Swal.fire({
            icon: '<?= $_SESSION['swal_notif']['type']; ?>',
            title: '<?= $_SESSION['swal_notif']['title']; ?>',
            text: '<?= $_SESSION['swal_notif']['message']; ?>',
            confirmButtonColor: '#6366f1',
            background: '#1e293b',
            color: '#f8fafc'
        });
        <?php unset($_SESSION['swal_notif']); ?>
    <?php endif; ?>
});
</script>
