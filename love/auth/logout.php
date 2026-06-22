<?php
/**
 * Proses Logout POS Kasir
 */

session_start();

// Hapus semua data session
$_SESSION = [];

// Jika session menggunakan cookie, hapus juga cookie-nya
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hancurkan session secara keseluruhan
session_destroy();

// Alihkan kembali ke halaman login dengan parameter status
header("Location: login.php?status=loggedout");
exit();
