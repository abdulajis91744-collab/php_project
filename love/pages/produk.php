<?php
/**
 * Halaman Manajemen Produk POS Kasir
 */

// Memasukkan header layout (memeriksa login & koneksi db)
include '../includes/header.php';
include '../includes/sidebar.php';

// Buat direktori upload foto jika belum ada
$upload_dir = '../assets/img/produk/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// --- PROSES ACTION ---
$message = '';
$msg_type = '';

// 1. Aksi Tambah Produk
if (isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $kode_produk = trim($_POST['kode_produk'] ?? '');
    $nama_produk = trim($_POST['nama_produk'] ?? '');
    $kategori_id = intval($_POST['kategori_id'] ?? 0);
    $harga_beli = floatval($_POST['harga_beli'] ?? 0);
    $harga_jual = floatval($_POST['harga_jual'] ?? 0);
    $stok = intval($_POST['stok'] ?? 0);
    $foto_name = null;

    // Cek duplikasi kode produk
    try {
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM produk WHERE kode_produk = :kode");
        $stmt_check->execute([':kode' => $kode_produk]);
        if ($stmt_check->fetchColumn() > 0) {
            $_SESSION['swal_notif'] = [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Kode produk sudah terdaftar, gunakan kode lain.'
            ];
            header("Location: produk.php");
            exit();
        }
    } catch (PDOException $e) {
        die("Error database: " . $e->getMessage());
    }

    // Proses upload foto jika ada
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['foto']['tmp_name'];
        $file_name = $_FILES['foto']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($file_ext, $allowed_ext)) {
            $foto_name = time() . '_' . uniqid() . '.' . $file_ext;
            move_uploaded_file($file_tmp, $upload_dir . $foto_name);
        } else {
            $_SESSION['swal_notif'] = [
                'type' => 'warning',
                'title' => 'Format Salah',
                'message' => 'Foto harus berformat JPG, JPEG, PNG, atau WEBP.'
            ];
            header("Location: produk.php");
            exit();
        }
    }

    // Simpan ke database
    try {
        $stmt = $pdo->prepare("INSERT INTO produk (kode_produk, nama_produk, kategori_id, harga_beli, harga_jual, stok, foto) 
                               VALUES (:kode, :nama, :kategori, :beli, :jual, :stok, :foto)");
        $stmt->execute([
            ':kode' => $kode_produk,
            ':nama' => $nama_produk,
            ':kategori' => $kategori_id > 0 ? $kategori_id : null,
            ':beli' => $harga_beli,
            ':jual' => $harga_jual,
            ':stok' => $stok,
            ':foto' => $foto_name
        ]);

        $_SESSION['swal_notif'] = [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => 'Produk baru berhasil ditambahkan.'
        ];
        header("Location: produk.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['swal_notif'] = [
            'type' => 'error',
            'title' => 'Error!',
            'message' => 'Gagal menyimpan produk: ' . $e->getMessage()
        ];
        header("Location: produk.php");
        exit();
    }
}

// 2. Aksi Edit Produk
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = intval($_POST['id'] ?? 0);
    $kode_produk = trim($_POST['kode_produk'] ?? '');
    $nama_produk = trim($_POST['nama_produk'] ?? '');
    $kategori_id = intval($_POST['kategori_id'] ?? 0);
    $harga_beli = floatval($_POST['harga_beli'] ?? 0);
    $harga_jual = floatval($_POST['harga_jual'] ?? 0);
    $stok = intval($_POST['stok'] ?? 0);

    try {
        // Ambil info foto lama
        $stmt_old = $pdo->prepare("SELECT foto, kode_produk FROM produk WHERE id = :id");
        $stmt_old->execute([':id' => $id]);
        $old_data = $stmt_old->fetch();
        $foto_name = $old_data['foto'];

        // Cek duplikasi kode produk jika kode diubah
        if ($old_data['kode_produk'] !== $kode_produk) {
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM produk WHERE kode_produk = :kode AND id != :id");
            $stmt_check->execute([':kode' => $kode_produk, ':id' => $id]);
            if ($stmt_check->fetchColumn() > 0) {
                $_SESSION['swal_notif'] = [
                    'type' => 'error',
                    'title' => 'Gagal!',
                    'message' => 'Kode produk sudah terdaftar pada produk lain.'
                ];
                header("Location: produk.php");
                exit();
            }
        }

        // Proses upload foto baru jika ada
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['foto']['tmp_name'];
            $file_name = $_FILES['foto']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($file_ext, $allowed_ext)) {
                // Hapus foto lama jika ada
                if (!empty($foto_name) && file_exists($upload_dir . $foto_name)) {
                    unlink($upload_dir . $foto_name);
                }
                $foto_name = time() . '_' . uniqid() . '.' . $file_ext;
                move_uploaded_file($file_tmp, $upload_dir . $foto_name);
            } else {
                $_SESSION['swal_notif'] = [
                    'type' => 'warning',
                    'title' => 'Format Salah',
                    'message' => 'Foto harus berformat JPG, JPEG, PNG, atau WEBP.'
                ];
                header("Location: produk.php");
                exit();
            }
        }

        // Update database
        $stmt = $pdo->prepare("UPDATE produk SET 
                                kode_produk = :kode,
                                nama_produk = :nama,
                                kategori_id = :kategori,
                                harga_beli = :beli,
                                harga_jual = :jual,
                                stok = :stok,
                                foto = :foto
                               WHERE id = :id");
        $stmt->execute([
            ':kode' => $kode_produk,
            ':nama' => $nama_produk,
            ':kategori' => $kategori_id > 0 ? $kategori_id : null,
            ':beli' => $harga_beli,
            ':jual' => $harga_jual,
            ':stok' => $stok,
            ':foto' => $foto_name,
            ':id' => $id
        ]);

        $_SESSION['swal_notif'] = [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => 'Produk berhasil diperbarui.'
        ];
        header("Location: produk.php");
        exit();

    } catch (PDOException $e) {
        $_SESSION['swal_notif'] = [
            'type' => 'error',
            'title' => 'Error!',
            'message' => 'Gagal memperbarui produk: ' . $e->getMessage()
        ];
        header("Location: produk.php");
        exit();
    }
}

// 3. Aksi Hapus Produk (GET request)
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = intval($_GET['id'] ?? 0);

    if ($id > 0) {
        try {
            // Ambil info foto untuk dihapus dari folder
            $stmt_old = $pdo->prepare("SELECT foto FROM produk WHERE id = :id");
            $stmt_old->execute([':id' => $id]);
            $foto = $stmt_old->fetchColumn();

            if (!empty($foto) && file_exists($upload_dir . $foto)) {
                unlink($upload_dir . $foto);
            }

            // Hapus dari database
            $stmt = $pdo->prepare("DELETE FROM produk WHERE id = :id");
            $stmt->execute([':id' => $id]);

            $_SESSION['swal_notif'] = [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Produk berhasil dihapus.'
            ];
            header("Location: produk.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['swal_notif'] = [
                'type' => 'error',
                'title' => 'Error!',
                'message' => 'Gagal menghapus produk: ' . $e->getMessage()
            ];
            header("Location: produk.php");
            exit();
        }
    }
}

// Ambil data kategori untuk select option dropdown
try {
    $stmt_cat = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");
    $kategori_list = $stmt_cat->fetchAll();
} catch (PDOException $e) {
    die("Error mengambil kategori: " . $e->getMessage());
}

// Ambil semua data produk beserta nama kategori menggunakan JOIN
try {
    $stmt_prod = $pdo->query("SELECT p.*, k.nama_kategori 
                              FROM produk p 
                              LEFT JOIN kategori k ON p.kategori_id = k.id 
                              ORDER BY p.id DESC");
    $produk_list = $stmt_prod->fetchAll();
} catch (PDOException $e) {
    die("Error mengambil data produk: " . $e->getMessage());
}

// Auto generate kode produk baru untuk form input
$next_kode = 'P' . str_pad(count($produk_list) + 1, 3, '0', STR_PAD_LEFT);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom border-secondary">
    <h1 class="h2 text-white">Manajemen Produk</h1>
    <button type="button" class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-plus-lg me-2"></i> Tambah Produk
    </button>
</div>

<!-- Card DataTables -->
<div class="card-custom">
    <div class="table-responsive">
        <table class="table table-dark table-hover w-100" id="tableProduk">
            <thead>
                <tr>
                    <th width="50">Foto</th>
                    <th width="100">Kode</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Harga Beli</th>
                    <th>Harga Jual</th>
                    <th width="80">Stok</th>
                    <th width="120" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($produk_list as $row): ?>
                    <tr>
                        <td>
                            <?php if (!empty($row['foto']) && file_exists($upload_dir . $row['foto'])): ?>
                                <img src="<?= $upload_dir . $row['foto']; ?>" alt="Foto" width="40" height="40" class="rounded object-fit-cover">
                            <?php else: ?>
                                <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="bi bi-image" style="font-size: 1.2rem;"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-secondary font-monospace"><?= htmlspecialchars($row['kode_produk']); ?></span></td>
                        <td><?= htmlspecialchars($row['nama_produk']); ?></td>
                        <td><?= htmlspecialchars($row['nama_kategori'] ?? 'Tanpa Kategori'); ?></td>
                        <td>Rp <?= number_format($row['harga_beli'], 0, ',', '.'); ?></td>
                        <td>Rp <?= number_format($row['harga_jual'], 0, ',', '.'); ?></td>
                        <td>
                            <?php if ($row['stok'] <= 5): ?>
                                <span class="badge bg-danger"><?= $row['stok']; ?> (Kritis)</span>
                            <?php else: ?>
                                <span class="badge bg-success"><?= $row['stok']; ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="btn-group gap-1">
                                <button type="button" class="btn btn-sm btn-warning text-white rounded btn-edit" 
                                        data-id="<?= $row['id']; ?>" 
                                        data-kode="<?= htmlspecialchars($row['kode_produk']); ?>"
                                        data-nama="<?= htmlspecialchars($row['nama_produk']); ?>"
                                        data-kategori="<?= $row['kategori_id']; ?>"
                                        data-beli="<?= $row['harga_beli']; ?>"
                                        data-jual="<?= $row['harga_jual']; ?>"
                                        data-stok="<?= $row['stok']; ?>"
                                        data-foto="<?= htmlspecialchars($row['foto'] ?? ''); ?>"
                                        data-bs-toggle="modal" data-bs-target="#modalEdit">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger rounded btn-hapus" 
                                        data-id="<?= $row['id']; ?>"
                                        data-nama="<?= htmlspecialchars($row['nama_produk']); ?>">
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

<!-- Modal Tambah Produk -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark border-secondary text-white">
            <div class="modal-header border-secondary">
                <h5 class="modal-title" id="modalTambahLabel"><i class="bi bi-plus-lg me-2"></i> Tambah Produk Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="produk.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="tambah">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tambah_kode" class="form-label">Kode Produk</label>
                            <input type="text" name="kode_produk" id="tambah_kode" class="form-control bg-secondary text-white border-secondary font-monospace" value="<?= $next_kode; ?>" required autocomplete="off">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="tambah_nama" class="form-label">Nama Produk</label>
                            <input type="text" name="nama_produk" id="tambah_nama" class="form-control bg-secondary text-white border-secondary" placeholder="Contoh: Kopi Cappuccino" required autocomplete="off">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tambah_kategori" class="form-label">Kategori</label>
                            <select name="kategori_id" id="tambah_kategori" class="form-select bg-secondary text-white border-secondary">
                                <option value="0">Pilih Kategori</option>
                                <?php foreach ($kategori_list as $cat): ?>
                                    <option value="<?= $cat['id']; ?>"><?= htmlspecialchars($cat['nama_kategori']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="tambah_stok" class="form-label">Stok</label>
                            <input type="number" name="stok" id="tambah_stok" min="0" class="form-control bg-secondary text-white border-secondary" value="0" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tambah_beli" class="form-label">Harga Beli (Rp)</label>
                            <input type="number" name="harga_beli" id="tambah_beli" min="0" step="100" class="form-control bg-secondary text-white border-secondary" placeholder="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="tambah_jual" class="form-label">Harga Jual (Rp)</label>
                            <input type="number" name="harga_jual" id="tambah_jual" min="0" step="100" class="form-control bg-secondary text-white border-secondary" placeholder="0" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="tambah_foto" class="form-label">Foto Produk (Maks 2MB, JPG/PNG/WEBP)</label>
                        <input type="file" name="foto" id="tambah_foto" class="form-control bg-secondary text-white border-secondary" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-custom">Simpan Produk</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Produk -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark border-secondary text-white">
            <div class="modal-header border-secondary">
                <h5 class="modal-title" id="modalEditLabel"><i class="bi bi-pencil-square me-2"></i> Edit Produk</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="produk.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_kode" class="form-label">Kode Produk</label>
                            <input type="text" name="kode_produk" id="edit_kode" class="form-control bg-secondary text-white border-secondary font-monospace" required autocomplete="off">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_nama" class="form-label">Nama Produk</label>
                            <input type="text" name="nama_produk" id="edit_nama" class="form-control bg-secondary text-white border-secondary" required autocomplete="off">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_kategori" class="form-label">Kategori</label>
                            <select name="kategori_id" id="edit_kategori" class="form-select bg-secondary text-white border-secondary">
                                <option value="0">Pilih Kategori</option>
                                <?php foreach ($kategori_list as $cat): ?>
                                    <option value="<?= $cat['id']; ?>"><?= htmlspecialchars($cat['nama_kategori']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_stok" class="form-label">Stok</label>
                            <input type="number" name="stok" id="edit_stok" min="0" class="form-control bg-secondary text-white border-secondary" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_beli" class="form-label">Harga Beli (Rp)</label>
                            <input type="number" name="harga_beli" id="edit_beli" min="0" step="100" class="form-control bg-secondary text-white border-secondary" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_jual" class="form-label">Harga Jual (Rp)</label>
                            <input type="number" name="harga_jual" id="edit_jual" min="0" step="100" class="form-control bg-secondary text-white border-secondary" required>
                        </div>
                    </div>

                    <div class="row align-items-center">
                        <div class="col-md-9 mb-3">
                            <label for="edit_foto" class="form-label">Ganti Foto Produk (Biarkan kosong jika tidak diubah)</label>
                            <input type="file" name="foto" id="edit_foto" class="form-control bg-secondary text-white border-secondary" accept="image/*">
                        </div>
                        <div class="col-md-3 mb-3 text-center">
                            <label class="form-label d-block text-muted small">Foto Saat Ini</label>
                            <img src="" id="edit_foto_preview" alt="Preview" width="70" height="70" class="rounded object-fit-cover border border-secondary d-none">
                            <div id="edit_no_foto" class="bg-secondary text-white rounded d-inline-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                                <i class="bi bi-image" style="font-size: 1.5rem;"></i>
                            </div>
                        </div>
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
    $('#tableProduk').DataTable({
        "language": {
            "search": "Cari Produk:",
            "lengthMenu": "Tampilkan _MENU_ data per halaman",
            "zeroRecords": "Produk tidak ditemukan",
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
        const kode = $(this).data('kode');
        const nama = $(this).data('nama');
        const kategori = $(this).data('kategori');
        const beli = $(this).data('beli');
        const jual = $(this).data('jual');
        const stok = $(this).data('stok');
        const foto = $(this).data('foto');

        $('#edit_id').val(id);
        $('#edit_kode').val(kode);
        $('#edit_nama').val(nama);
        $('#edit_kategori').val(kategori ? kategori : 0);
        $('#edit_beli').val(beli);
        $('#edit_jual').val(jual);
        $('#edit_stok').val(stok);

        if (foto) {
            $('#edit_foto_preview').attr('src', '<?= $upload_dir; ?>' + foto).removeClass('d-none');
            $('#edit_no_foto').addClass('d-none');
        } else {
            $('#edit_foto_preview').addClass('d-none');
            $('#edit_no_foto').removeClass('d-none');
        }
    });

    // Handle Hapus Button Click (SweetAlert2 Confirmation)
    $('.btn-hapus').on('click', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        const nama = $(this).data('nama');

        Swal.fire({
            title: 'Apakah Anda yakin?',
            html: `Produk <b>${nama}</b> akan dihapus secara permanen.`,
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
                window.location.href = `produk.php?action=delete&id=${id}`;
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
