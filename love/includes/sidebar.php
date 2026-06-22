<?php
/**
 * Sidebar Layout POS Kasir
 */

// Menentukan halaman aktif untuk highlight menu
$active_page = basename($_SERVER['PHP_SELF']);
$user_role = $_SESSION['user']['role'] ?? 'kasir';
?>
<!-- Sidebar Navigation -->
<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column gap-1">
            <li class="nav-item">
                <a class="nav-link <?= ($active_page === 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($active_page === 'transaksi.php') ? 'active' : ''; ?>" href="transaksi.php">
                    <i class="bi bi-cart3"></i>
                    <span>Transaksi Kasir</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($active_page === 'produk.php') ? 'active' : ''; ?>" href="produk.php">
                    <i class="bi bi-box-seam"></i>
                    <span>Manajemen Produk</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($active_page === 'kategori.php') ? 'active' : ''; ?>" href="kategori.php">
                    <i class="bi bi-tags"></i>
                    <span>Manajemen Kategori</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($active_page === 'laporan.php') ? 'active' : ''; ?>" href="laporan.php">
                    <i class="bi bi-file-earmark-bar-graph"></i>
                    <span>Laporan Penjualan</span>
                </a>
            </li>
            
            <?php if ($user_role === 'admin'): ?>
                <li class="nav-item">
                    <hr class="my-3 border-secondary mx-3">
                    <div class="text-uppercase text-secondary px-3 mb-2 small fw-bold" style="font-size: 0.7rem;">Administrasi</div>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($active_page === 'users.php') ? 'active' : ''; ?>" href="users.php">
                        <i class="bi bi-people"></i>
                        <span>Kelola Pengguna</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($active_page === 'pengaturan.php') ? 'active' : ''; ?>" href="pengaturan.php">
                        <i class="bi bi-sliders"></i>
                        <span>Pengaturan Toko</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>

        <div style="position: absolute; bottom: 20px; width: 100%;" class="px-3">
            <a href="../auth/logout.php" class="btn btn-outline-danger w-100 d-flex align-items-center justify-content-center gap-2 py-2 small">
                <i class="bi bi-box-arrow-left"></i>
                <span>Keluar</span>
            </a>
        </div>
    </div>
</nav>

<!-- Main content panel container opener -->
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content" id="mainContent">
    <div class="pt-5 mt-3">
