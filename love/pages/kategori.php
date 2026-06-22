<?php
/**
 * Halaman Manajemen Kategori POS Kasir
 */

// Memasukkan header layout (memeriksa login & koneksi db)
include '../includes/header.php';
include '../includes/sidebar.php';

// Proteksi Aksi - Hanya admin/kasir yang boleh mengakses. (Kedua role diizinkan untuk Kategori)
$user_role = $_SESSION['user']['role'];

// --- PROSES ACTION ---
$message = '';
$msg_type = '';

// 1. Aksi Tambah Kategori
if (isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $nama_kategori = trim($_POST['nama_kategori'] ?? '');

    if (!empty($nama_kategori)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO kategori (nama_kategori) VALUES (:nama_kategori)");
            $stmt->execute([':nama_kategori' => $nama_kategori]);
            
            $_SESSION['swal_notif'] = [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Kategori baru berhasil ditambahkan.'
            ];
            header("Location: kategori.php");
            exit();
        } catch (PDOException $e) {
            $message = 'Gagal menambahkan kategori: ' . $e->getMessage();
            $msg_type = 'danger';
        }
    } else {
        $message = 'Nama kategori tidak boleh kosong!';
        $msg_type = 'warning';
    }
}

// 2. Aksi Edit Kategori
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = intval($_POST['id'] ?? 0);
    $nama_kategori = trim($_POST['nama_kategori'] ?? '');

    if ($id > 0 && !empty($nama_kategori)) {
        try {
            $stmt = $pdo->prepare("UPDATE kategori SET nama_kategori = :nama_kategori WHERE id = :id");
            $stmt->execute([
                ':nama_kategori' => $nama_kategori,
                ':id' => $id
            ]);
            
            $_SESSION['swal_notif'] = [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Kategori berhasil diperbarui.'
            ];
            header("Location: kategori.php");
            exit();
        } catch (PDOException $e) {
            $message = 'Gagal memperbarui kategori: ' . $e->getMessage();
            $msg_type = 'danger';
        }
    } else {
        $message = 'Nama kategori tidak boleh kosong!';
        $msg_type = 'warning';
    }
}

// 3. Aksi Hapus Kategori (GET request)
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = intval($_GET['id'] ?? 0);

    if ($id > 0) {
        try {
            // Check if there are products using this category
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM produk WHERE kategori_id = :id");
            $stmt_check->execute([':id' => $id]);
            $count = $stmt_check->fetchColumn();

            if ($count > 0) {
                $_SESSION['swal_notif'] = [
                    'type' => 'error',
                    'title' => 'Gagal!',
                    'message' => 'Kategori ini tidak dapat dihapus karena sedang digunakan oleh beberapa produk.'
                ];
            } else {
                $stmt = $pdo->prepare("DELETE FROM kategori WHERE id = :id");
                $stmt->execute([':id' => $id]);
                
                $_SESSION['swal_notif'] = [
                    'type' => 'success',
                    'title' => 'Berhasil!',
                    'message' => 'Kategori berhasil dihapus.'
                ];
            }
            header("Location: kategori.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['swal_notif'] = [
                'type' => 'error',
                'title' => 'Error!',
                'message' => 'Gagal menghapus kategori: ' . $e->getMessage()
            ];
            header("Location: kategori.php");
            exit();
        }
    }
}

// Ambil semua data kategori untuk tabel
try {
    $stmt = $pdo->prepare("SELECT * FROM kategori ORDER BY id DESC");
    $stmt->execute();
    $kategori_list = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Gagal mengambil data kategori: " . $e->getMessage());
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom border-secondary">
    <h1 class="h2 text-white">Manajemen Kategori</h1>
    <button type="button" class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-plus-lg me-2"></i> Tambah Kategori
    </button>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?= $msg_type; ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Card DataTables -->
<div class="card-custom">
    <div class="table-responsive">
        <table class="table table-dark table-hover w-100" id="tableKategori">
            <thead>
                <tr>
                    <th width="80">No</th>
                    <th>Nama Kategori</th>
                    <th width="150" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                foreach ($kategori_list as $row): 
                ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($row['nama_kategori']); ?></td>
                        <td class="text-center">
                            <div class="btn-group gap-1">
                                <button type="button" class="btn btn-sm btn-warning text-white rounded btn-edit" 
                                        data-id="<?= $row['id']; ?>" 
                                        data-nama="<?= htmlspecialchars($row['nama_kategori']); ?>"
                                        data-bs-toggle="modal" data-bs-target="#modalEdit">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger rounded btn-hapus" 
                                        data-id="<?= $row['id']; ?>"
                                        data-nama="<?= htmlspecialchars($row['nama_kategori']); ?>">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Kategori -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark border-secondary text-white">
            <div class="modal-header border-secondary">
                <h5 class="modal-title" id="modalTambahLabel"><i class="bi bi-plus-lg me-2"></i> Tambah Kategori Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="kategori.php" method="POST">
                <input type="hidden" name="action" value="tambah">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="tambah_nama" class="form-label">Nama Kategori</label>
                        <input type="text" name="nama_kategori" id="tambah_nama" class="form-control bg-secondary text-white border-secondary" placeholder="Contoh: Makanan Berat" required autocomplete="off">
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-custom">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Kategori -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark border-secondary text-white">
            <div class="modal-header border-secondary">
                <h5 class="modal-title" id="modalEditLabel"><i class="bi bi-pencil-square me-2"></i> Edit Kategori</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="kategori.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_nama" class="form-label">Nama Kategori</label>
                        <input type="text" name="nama_kategori" id="edit_nama" class="form-control bg-secondary text-white border-secondary" required autocomplete="off">
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-custom">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
// Memasukkan footer layout
include '../includes/footer.php'; 
?>

<script>
$(document).ready(function() {
    // Inisialisasi DataTable
    $('#tableKategori').DataTable({
        "language": {
            "search": "Cari Kategori:",
            "lengthMenu": "Tampilkan _MENU_ data per halaman",
            "zeroRecords": "Kategori tidak ditemukan",
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

    // Handle Edit Button Click (Load data ke dalam modal)
    $('.btn-edit').on('click', function() {
        const id = $(this).data('id');
        const nama = $(this).data('nama');
        $('#edit_id').val(id);
        $('#edit_nama').val(nama);
    });

    // Handle Hapus Button Click (SweetAlert2 Confirmation)
    $('.btn-hapus').on('click', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        const nama = $(this).data('nama');

        Swal.fire({
            title: 'Apakah Anda yakin?',
            html: `Kategori <b>${nama}</b> akan dihapus secara permanen.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6366f1',
            confirmButtonText: '<i class="bi bi-trash me-2"></i>Ya, Hapus!',
            cancelButtonText: 'Batal',
            background: '#1e293b',
            color: '#f8fafc'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `kategori.php?action=delete&id=${id}`;
            }
        });
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
