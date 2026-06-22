<?php
/**
 * Konfigurasi Database POS Kasir
 * Menggunakan PDO (PHP Data Objects) untuk keamanan prepared statements.
 */

// Hindari akses langsung ke file ini
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    header("HTTP/1.1 403 Forbidden");
    exit("Akses langsung tidak diizinkan.");
}

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'db_kasir';

try {
    // Inisialisasi koneksi PDO dengan opsi keamanan
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Menampilkan error sebagai exception
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch data sebagai array asosiatif
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Menonaktifkan emulasi prepared statements untuk keamanan SQL Injection
    ]);
} catch (PDOException $e) {
    // Jika koneksi gagal, hentikan proses dan tampilkan pesan error
    die("Koneksi database gagal: " . $e->getMessage());
}
?>
