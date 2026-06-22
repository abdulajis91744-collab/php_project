<?php
/**
 * Halaman Pengaturan Toko POS Kasir
 */

// Memasukkan header layout (memeriksa login & koneksi db)
include '../includes/header.php';
include '../includes/sidebar.php';

// Proteksi Halaman - Hanya Admin yang dapat mengakses pengaturan toko
if ($_SESSION['user']['role'] !== 'admin') {
    $_SESSION['swal_notif'] = [
        'type' => 'error',
        'title' => 'Akses Ditolak',
        'message' => 'Halaman ini hanya dapat diakses oleh Administrator.'
    ];
    echo "<script>window.location.href = 'dashboard.php';</script>";
    exit();
}

// Buat direktori logo jika belum ada
$logo_dir = '../assets/img/logo/';
if (!file_exists($logo_dir)) {
    mkdir($logo_dir, 0777, true);
}

// --- PROSES ACTION ---
$message = '';
$msg_type = '';

// Aksi Update Pengaturan
if (isset($_POST['action']) && $_POST['action'] === 'update') {
    $nama_toko = trim($_POST['nama_toko'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');
    $logo_name = $toko['logo']; // Gunakan logo lama sebagai fallback

    if (!empty($nama_toko) && !empty($alamat) && !empty($telepon)) {
        // Proses upload logo baru jika ada
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['logo']['tmp_name'];
            $file_name = $_FILES['logo']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($file_ext, $allowed_ext)) {
                // Hapus logo lama dari server jika ada
                if (!empty($toko['logo']) && file_exists($logo_dir . $toko['logo'])) {
                    unlink($logo_dir . $toko['logo']);
                }
                
                // Berikan nama unik untuk file logo baru
                $logo_name = 'logo_' . time() . '_' . uniqid() . '.' . $file_ext;
                move_uploaded_file($file_tmp, $logo_dir . $logo_name);
            } else {
                $_SESSION['swal_notif'] = [
                    'type' => 'warning',
                    'title' => 'Format Salah',
                    'message' => 'Logo harus berformat JPG, JPEG, PNG, atau WEBP.'
                ];
                header("Location: pengaturan.php");
                exit();
            }
        }

        try {
            // Update data ke database
            $stmt = $pdo->prepare("UPDATE pengaturan_toko SET nama_toko = :nama, alamat = :alamat, telepon = :telepon, logo = :logo WHERE id = 1");
            $stmt->execute([
                ':nama' => $nama_toko,
                ':alamat' => $alamat,
                ':telepon' => $telepon,
                ':logo' => $logo_name
            ]);

            $_SESSION['swal_notif'] = [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Pengaturan toko berhasil diperbarui.'
            ];
            header("Location: pengaturan.php");
            exit();

        } catch (PDOException $e) {
            $message = 'Gagal memperbarui pengaturan toko: ' . $e->getMessage();
            $msg_type = 'danger';
        }
    } else {
        $message = 'Nama toko, alamat, dan telepon wajib diisi!';
        $msg_type = 'warning';
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom border-secondary">
    <h1 class="h2 text-white">Pengaturan Toko</h1>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?= $msg_type; ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Kolom Form Pengaturan -->
    <div class="col-lg-8">
        <div class="card-custom">
            <h4 class="mb-4 text-white"><i class="bi bi-gear-fill me-2 text-indigo"></i> Detail Toko</h4>
            <form action="pengaturan.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update">
                
                <div class="mb-3">
                    <label for="nama_toko" class="form-label text-muted">Nama Toko</label>
                    <input type="text" name="nama_toko" id="nama_toko" class="form-control bg-secondary text-white border-secondary" value="<?= htmlspecialchars($toko['nama_toko']); ?>" required autocomplete="off">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="telepon" class="form-label text-muted">Nomor Telepon Toko</label>
                        <input type="text" name="telepon" id="telepon" class="form-control bg-secondary text-white border-secondary" value="<?= htmlspecialchars($toko['telepon']); ?>" required autocomplete="off">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="logo" class="form-label text-muted">Ganti Logo Toko (JPG/PNG/WEBP)</label>
                        <input type="file" name="logo" id="logo" class="form-control bg-secondary text-white border-secondary" accept="image/*">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="alamat" class="form-label text-muted">Alamat Toko</label>
                    <textarea name="alamat" id="alamat" rows="4" class="form-control bg-secondary text-white border-secondary" required><?= htmlspecialchars($toko['alamat']); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary-custom px-4 py-2">
                    <i class="bi bi-save me-2"></i> Simpan Pengaturan
                </button>
            </form>
        </div>
    </div>

    <!-- Kolom Preview Logo Saat Ini -->
    <div class="col-lg-4">
        <div class="card-custom text-center">
            <h5 class="mb-3 text-muted">Logo Saat Ini</h5>
            <div class="d-flex align-items-center justify-content-center p-4 rounded-3 bg-dark border border-secondary mb-3" style="min-height: 200px;">
                <?php if (!empty($toko['logo']) && file_exists($logo_dir . $toko['logo'])): ?>
                    <img src="<?= $logo_dir . $toko['logo']; ?>" alt="Logo Toko" class="img-fluid rounded shadow-sm object-fit-contain" style="max-height: 180px;">
                <?php else: ?>
                    <div class="text-center text-secondary">
                        <i class="bi bi-wallet2 text-primary d-block mb-2" style="font-size: 4rem;"></i>
                        <span class="small">Belum ada logo terunggah</span>
                    </div>
                <?php endif; ?>
            </div>
            <p class="small text-secondary mb-0">Nama file: <?= htmlspecialchars($toko['logo'] ?? 'Tidak ada'); ?></p>
        </div>
    </div>
</div>

<?php 
// Memasukkan footer layout
include '../includes/footer.php'; 
?>

<script>
$(document).ready(function() {
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
