<?php
/**
 * Proses Autentikasi Login POS Kasir
 */

session_start();
require_once '../config/koneksi.php';

// Cek apakah data login dikirim melalui POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validasi data input kosong
    if (empty($username) || empty($password)) {
        header("Location: login.php?status=empty");
        exit();
    }

    try {
        // Query mencari user berdasarkan username dengan prepared statement
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        // Verifikasi keberadaan user dan password hash
        if ($user && password_verify($password, $user['password'])) {
            // Set session data user
            $_SESSION['user'] = [
                'id'           => $user['id'],
                'username'     => $user['username'],
                'nama_lengkap' => $user['nama_lengkap'],
                'role'         => $user['role']
            ];

            // Redirect ke halaman index/dashboard utama
            header("Location: ../index.php");
            exit();
        } else {
            // Login gagal (username atau password salah)
            header("Location: login.php?status=failed");
            exit();
        }

    } catch (PDOException $e) {
        // Tangani kesalahan sistem database
        die("Terjadi kesalahan sistem: " . $e->getMessage());
    }
} else {
    // Jika diakses langsung tanpa POST, arahkan ke login
    header("Location: login.php");
    exit();
}
