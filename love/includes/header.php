<?php
/**
 * Header Layout POS Kasir
 */
session_start();

// Validasi session login
if (!isset($_SESSION['user'])) {
    header("Location: ../auth/login.php?status=unauthorized");
    exit();
}

// Hubungkan ke database
require_once '../config/koneksi.php';

// Ambil info detail toko untuk nama dan logo
try {
    $stmt_toko = $pdo->prepare("SELECT * FROM pengaturan_toko WHERE id = 1");
    $stmt_toko->execute();
    $toko = $stmt_toko->fetch();
} catch (PDOException $e) {
    $toko = [
        'nama_toko' => 'Love POS',
        'telepon' => '',
        'alamat' => '',
        'logo' => null
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($toko['nama_toko']); ?> - System POS</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- DataTables Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <!-- Custom Style CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<!-- Top Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom fixed-top px-3">
    <div class="container-fluid">
        <!-- Sidebar Toggle (Mobile) -->
        <button class="btn btn-outline-secondary me-2 d-lg-none" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>

        <a class="navbar-brand d-flex align-items-center gap-2" href="dashboard.php">
            <?php if (!empty($toko['logo']) && file_exists('../assets/img/logo/' . $toko['logo'])): ?>
                <img src="../assets/img/logo/<?= $toko['logo']; ?>" alt="Logo" width="30" height="30" class="rounded-circle object-fit-cover">
            <?php else: ?>
                <i class="bi bi-wallet2 text-primary"></i>
            <?php endif; ?>
            <span class="fw-bold text-white"><?= htmlspecialchars($toko['nama_toko']); ?></span>
        </a>

        <div class="ms-auto d-flex align-items-center gap-3">
            <div class="text-end d-none d-sm-block">
                <div class="text-white fw-semibold small"><?= htmlspecialchars($_SESSION['user']['nama_lengkap']); ?></div>
                <div class="text-muted small text-capitalize" style="font-size: 0.75rem;"><?= htmlspecialchars($_SESSION['user']['role']); ?></div>
            </div>
            <div class="dropdown">
                <a href="#" class="d-block link-light text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="avatar-circle bg-primary text-white d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 38px; height: 38px; font-weight: 600;">
                        <?= strtoupper(substr($_SESSION['user']['nama_lengkap'], 0, 1)); ?>
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark bg-dark border-secondary text-small shadow" aria-labelledby="dropdownUser">
                    <li><a class="dropdown-item py-2 small" href="pengaturan.php"><i class="bi bi-gear me-2"></i> Pengaturan Toko</a></li>
                    <li><hr class="dropdown-divider border-secondary"></li>
                    <li><a class="dropdown-item py-2 text-danger small" href="../auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Keluar</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
