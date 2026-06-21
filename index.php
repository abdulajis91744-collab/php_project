<?php
/**
 * Root Router POS Kasir
 */

session_start();

// Cek apakah user telah login
if (!isset($_SESSION['user'])) {
    // Jika belum login, alihkan ke halaman login
    header("Location: auth/login.php");
    exit();
} else {
    // Jika sudah login, alihkan ke halaman dashboard
    header("Location: pages/dashboard.php");
    exit();
}
