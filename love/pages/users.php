<?php
/**
 * Halaman Kelola Pengguna POS Kasir
 */

// Memasukkan header layout (memeriksa login & koneksi db)
include '../includes/header.php';
include '../includes/sidebar.php';

// Proteksi Halaman - Hanya Admin yang dapat mengakses kelola pengguna
if ($_SESSION['user']['role'] !== 'admin') {
    $_SESSION['swal_notif'] = [
        'type' => 'error',
        'title' => 'Akses Ditolak',
        'message' => 'Halaman ini hanya dapat diakses oleh Administrator.'
    ];
    echo "<script>window.location.href = 'dashboard.php';</script>";
    exit();
}

// --- PROSES ACTION ---
$message = '';
$msg_type = '';

// 1. Aksi Tambah User
if (isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $role = $_POST['role'] ?? 'kasir';

    if (!empty($username) && !empty($password) && !empty($nama_lengkap)) {
        try {
            // Cek keunikan username
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
            $stmt_check->execute([':username' => $username]);
            
            if ($stmt_check->fetchColumn() > 0) {
                $_SESSION['swal_notif'] = [
                    'type' => 'error',
                    'title' => 'Gagal!',
                    'message' => 'Username sudah digunakan. Silakan pilih username lain.'
                ];
                header("Location: users.php");
                exit();
            }

            // Enkripsi password menggunakan password_hash()
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);

            // Simpan ke database
            $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (:username, :password, :nama, :role)");
            $stmt->execute([
                ':username' => $username,
                ':password' => $password_hashed,
                ':nama' => $nama_lengkap,
                ':role' => $role
            ]);

            $_SESSION['swal_notif'] = [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Pengguna baru berhasil ditambahkan.'
            ];
            header("Location: users.php");
            exit();

        } catch (PDOException $e) {
            $message = 'Gagal menambahkan pengguna: ' . $e->getMessage();
            $msg_type = 'danger';
        }
    } else {
        $message = 'Mohon lengkapi semua field!';
        $msg_type = 'warning';
    }
}

// 2. Aksi Edit User
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = intval($_POST['id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $role = $_POST['role'] ?? 'kasir';

    if ($id > 0 && !empty($username) && !empty($nama_lengkap)) {
        try {
            // Cek keunikan username (tidak termasuk user yang sedang diedit)
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username AND id != :id");
            $stmt_check->execute([':username' => $username, ':id' => $id]);
            
            if ($stmt_check->fetchColumn() > 0) {
                $_SESSION['swal_notif'] = [
                    'type' => 'error',
                    'title' => 'Gagal!',
                    'message' => 'Username sudah digunakan oleh akun lain.'
                ];
                header("Location: users.php");
                exit();
            }

            // Update data user dasar
            $sql = "UPDATE users SET username = :username, nama_lengkap = :nama, role = :role WHERE id = :id";
            $params = [
                ':username' => $username,
                ':nama' => $nama_lengkap,
                ':role' => $role,
                ':id' => $id
            ];
            
            // Eksekusi update data dasar
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            // Jika password diisi, update password-nya juga
            if (!empty($password)) {
                $password_hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt_pw = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
                $stmt_pw->execute([
                    ':password' => $password_hashed,
                    ':id' => $id
                ]);
            }

            // Update session jika mengedit akun sendiri
            if ($id === $_SESSION['user']['id']) {
                $_SESSION['user']['username'] = $username;
                $_SESSION['user']['nama_lengkap'] = $nama_lengkap;
                $_SESSION['user']['role'] = $role;
            }

            $_SESSION['swal_notif'] = [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Data pengguna berhasil diperbarui.'
            ];
            header("Location: users.php");
            exit();

        } catch (PDOException $e) {
            $message = 'Gagal memperbarui pengguna: ' . $e->getMessage();
            $msg_type = 'danger';
        }
    } else {
        $message = 'Mohon lengkapi data wajib!';
        $msg_type = 'warning';
    }
}

// 3. Aksi Hapus User (GET request)
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = intval($_GET['id'] ?? 0);

    if ($id > 0) {
        // Cek jika menghapus akun sendiri
        if ($id === $_SESSION['user']['id']) {
            $_SESSION['swal_notif'] = [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Anda tidak bisa menghapus akun yang sedang Anda gunakan saat ini.'
            ];
            header("Location: users.php");
            exit();
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $id]);

            $_SESSION['swal_notif'] = [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Pengguna berhasil dihapus.'
            ];
            header("Location: users.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['swal_notif'] = [
                'type' => 'error',
                'title' => 'Error!',
                'message' => 'Gagal menghapus pengguna: ' . $e->getMessage()
            ];
            header("Location: users.php");
            exit();
        }
    }
}

// Ambil semua data pengguna untuk tabel
try {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
    $users_list = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error mengambil data pengguna: " . $e->getMessage());
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom border-secondary">
    <h1 class="h2 text-white">Kelola Pengguna</h1>
    <button type="button" class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-person-plus me-2"></i> Tambah Pengguna
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
        <table class="table table-dark table-hover w-100" id="tableUsers">
            <thead>
                <tr>
                    <th width="80">No</th>
                    <th>Nama Lengkap</th>
                    <th>Username</th>
                    <th>Hak Akses / Role</th>
                    <th width="150" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                foreach ($users_list as $row): 
                ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($row['nama_lengkap']); ?></td>
                        <td><span class="font-monospace text-indigo"><?= htmlspecialchars($row['username']); ?></span></td>
                        <td>
                            <?php if ($row['role'] === 'admin'): ?>
                                <span class="badge bg-primary text-uppercase">Admin</span>
                            <?php else: ?>
                                <span class="badge bg-secondary text-uppercase">Kasir</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="btn-group gap-1">
                                <button type="button" class="btn btn-sm btn-warning text-white rounded btn-edit" 
                                        data-id="<?= $row['id']; ?>" 
                                        data-username="<?= htmlspecialchars($row['username']); ?>"
                                        data-nama="<?= htmlspecialchars($row['nama_lengkap']); ?>"
                                        data-role="<?= $row['role']; ?>"
                                        data-bs-toggle="modal" data-bs-target="#modalEdit">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger rounded btn-hapus" 
                                        data-id="<?= $row['id']; ?>"
                                        data-nama="<?= htmlspecialchars($row['nama_lengkap']); ?>">
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

<!-- Modal Tambah Pengguna -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark border-secondary text-white">
            <div class="modal-header border-secondary">
                <h5 class="modal-title" id="modalTambahLabel"><i class="bi bi-person-plus me-2"></i> Tambah Pengguna Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="users.php" method="POST">
                <input type="hidden" name="action" value="tambah">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="tambah_nama" class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" id="tambah_nama" class="form-control bg-secondary text-white border-secondary" placeholder="Masukkan nama lengkap" required autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label for="tambah_username" class="form-label">Username</label>
                        <input type="text" name="username" id="tambah_username" class="form-control bg-secondary text-white border-secondary" placeholder="Masukkan username" required autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label for="tambah_password" class="form-label">Password</label>
                        <input type="password" name="password" id="tambah_password" class="form-control bg-secondary text-white border-secondary" placeholder="Masukkan password" required>
                    </div>
                    <div class="mb-3">
                        <label for="tambah_role" class="form-label">Hak Akses / Role</label>
                        <select name="role" id="tambah_role" class="form-select bg-secondary text-white border-secondary">
                            <option value="kasir">Kasir (Staff)</option>
                            <option value="admin">Administrator (Owner/Manager)</option>
                        </select>
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

<!-- Modal Edit Pengguna -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark border-secondary text-white">
            <div class="modal-header border-secondary">
                <h5 class="modal-title" id="modalEditLabel"><i class="bi bi-pencil-square me-2"></i> Edit Pengguna</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="users.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_nama" class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" id="edit_nama" class="form-control bg-secondary text-white border-secondary" required autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Username</label>
                        <input type="text" name="username" id="edit_username" class="form-control bg-secondary text-white border-secondary" required autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">Password Baru (Kosongkan jika tidak diganti)</label>
                        <input type="password" name="password" id="edit_password" class="form-control bg-secondary text-white border-secondary" placeholder="Masukkan password baru jika ingin diganti">
                    </div>
                    <div class="mb-3">
                        <label for="edit_role" class="form-label">Hak Akses / Role</label>
                        <select name="role" id="edit_role" class="form-select bg-secondary text-white border-secondary">
                            <option value="kasir">Kasir (Staff)</option>
                            <option value="admin">Administrator (Owner/Manager)</option>
                        </select>
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
    $('#tableUsers').DataTable({
        "language": {
            "search": "Cari Pengguna:",
            "lengthMenu": "Tampilkan _MENU_ data per halaman",
            "zeroRecords": "Pengguna tidak ditemukan",
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

    // Handle Edit Button Click (Load data ke modal)
    $('.btn-edit').on('click', function() {
        const id = $(this).data('id');
        const username = $(this).data('username');
        const nama = $(this).data('nama');
        const role = $(this).data('role');

        $('#edit_id').val(id);
        $('#edit_username').val(username);
        $('#edit_nama').val(nama);
        $('#edit_role').val(role);
        $('#edit_password').val(''); // Selalu kosongkan password input di awal
    });

    // Handle Hapus Button Click (SweetAlert2 Confirmation)
    $('.btn-hapus').on('click', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        const nama = $(this).data('nama');

        Swal.fire({
            title: 'Apakah Anda yakin?',
            html: `Pengguna <b>${nama}</b> akan dihapus secara permanen.`,
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
                window.location.href = `users.php?action=delete&id=${id}`;
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
