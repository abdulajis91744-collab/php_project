<?php
session_start();

// Jika user sudah login, arahkan ke dashboard
if (isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Love POS</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Custom Style CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: radial-gradient(circle at top right, #1e1b4b, #090514);
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(16px);
            border-radius: 20px;
            padding: 40px 30px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
        }
        .login-header h2 {
            font-weight: 700;
            letter-spacing: -0.5px;
            background: linear-gradient(to right, #a5b4fc, #6366f1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .form-control-custom {
            background-color: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border-color);
            color: var(--text-main) !important;
            padding: 12px 16px;
            border-radius: 10px;
            transition: var(--transition);
        }
        .form-control-custom:focus {
            background-color: rgba(15, 23, 42, 0.8);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25);
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header text-center mb-4">
        <i class="bi bi-wallet2 text-indigo" style="font-size: 3.5rem; color: #6366f1;"></i>
        <h2 class="mt-2 mb-1">Love POS</h2>
        <p class="text-secondary small">Point of Sale System - Silakan masuk</p>
    </div>

    <form action="proses_login.php" method="POST">
        <div class="mb-3">
            <label for="username" class="form-label text-muted small">Username</label>
            <div class="input-group">
                <span class="input-group-text bg-dark border-secondary text-secondary"><i class="bi bi-person"></i></span>
                <input type="text" name="username" id="username" class="form-control form-control-custom" placeholder="Masukkan username" required autocomplete="off">
            </div>
        </div>

        <div class="mb-4">
            <label for="password" class="form-label text-muted small">Password</label>
            <div class="input-group">
                <span class="input-group-text bg-dark border-secondary text-secondary"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" id="password" class="form-control form-control-custom" placeholder="Masukkan password" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary-custom w-100 py-2.5 d-flex justify-content-center align-items-center gap-2">
            <span>Masuk</span>
            <i class="bi bi-box-arrow-in-right"></i>
        </button>
    </form>
</div>

<!-- SweetAlert2 & JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Membaca status login dari URL query
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');

    if (status === 'failed') {
        Swal.fire({
            icon: 'error',
            title: 'Login Gagal',
            text: 'Username atau Password salah!',
            confirmButtonColor: '#6366f1'
        });
    } else if (status === 'empty') {
        Swal.fire({
            icon: 'warning',
            title: 'Isi Semua Kolom',
            text: 'Username dan password wajib diisi!',
            confirmButtonColor: '#6366f1'
        });
    } else if (status === 'unauthorized') {
        Swal.fire({
            icon: 'warning',
            title: 'Akses Ditolak',
            text: 'Silakan login terlebih dahulu untuk mengakses halaman.',
            confirmButtonColor: '#6366f1'
        });
    } else if (status === 'loggedout') {
        Swal.fire({
            icon: 'success',
            title: 'Berhasil Keluar',
            text: 'Anda telah keluar dari aplikasi.',
            confirmButtonColor: '#6366f1',
            timer: 2000,
            showConfirmButton: false
        });
    }
</script>
</body>
</html>
