<?php
include "koneksi.php";

/* =========================
   PROSES TAMBAH DATA
========================= */
if (isset($_POST['kirim'])) {
    $id       = $_POST['id'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $nama     = $_POST['nama'];
    $email    = $_POST['email'];

    $sql = "INSERT INTO user (id, username, password, nama, email)
            VALUES ('$id','$username','$password','$nama','$email')";

    if ($koneksi->query($sql) === TRUE) {
        echo "<p>Data berhasil ditambahkan</p>";
    } else {
        echo "<p>Error: ".$koneksi->error."</p>";
    }
}

/* =========================
   PROSES HAPUS DATA
========================= */
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $koneksi->query("DELETE FROM user WHERE id='$id'");
    header("location:materi5.php");
}

/* =========================
   PROSES UPDATE DATA
========================= */
if (isset($_POST['update'])) {
    $id       = $_POST['id'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $nama     = $_POST['nama'];
    $email    = $_POST['email'];

    $sql = "UPDATE user SET
            username='$username',
            password='$password',
            nama='$nama',
            email='$email'
            WHERE id='$id'";

    if ($koneksi->query($sql) === TRUE) {
        echo "<p>Data berhasil diupdate</p>";
    } else {
        echo "<p>Error: ".$koneksi->error."</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Materi 5 - Database</title>
</head>
<body>

<h1>Materi 5 : Database</h1>

<!-- =========================
     FORM TAMBAH DATA
========================= -->
<form method="POST">
    ID User : <input type="number" name="id" required><br><br>
    Username : <input type="text" name="username" required><br><br>
    Password : <input type="text" name="password" required><br><br>
    Nama : <input type="text" name="nama" required><br><br>
    Email : <input type="email" name="email" required><br><br>
    <button type="submit" name="kirim">Kirim</button>
</form>

<hr>

<!-- =========================
     TABEL DATA USER
========================= -->
<table border="1" cellpadding="10">
<tr>
    <th>ID</th>
    <th>Username</th>
    <th>Password</th>
    <th>Nama</th>
    <th>Email</th>
    <th>Aksi</th>
</tr>

<?php
$result = $koneksi->query("SELECT * FROM user");
while ($row = $result->fetch_assoc()) {
    echo "<tr>
            <td>{$row['id']}</td>
            <td>{$row['username']}</td>
            <td>{$row['password']}</td>
            <td>{$row['nama']}</td>
            <td>{$row['email']}</td>
            <td>
                <a href='materi5.php?hapus={$row['id']}' 
                   onclick='return confirm(\"Yakin hapus?\")'>Hapus</a> |
                <a href='materi5.php?edit={$row['id']}'>Edit</a>
            </td>
          </tr>";
}
?>
</table>

<!-- =========================
     FORM EDIT DATA
========================= -->
<?php
if (isset($_GET['edit'])) {
    $id   = $_GET['edit'];
    $data = $koneksi->query("SELECT * FROM user WHERE id='$id'")->fetch_assoc();
?>
<hr>
<h3>Edit Data</h3>
<form method="POST">
    <input type="hidden" name="id" value="<?= $data['id']; ?>">
    Username : <input type="text" name="username" value="<?= $data['username']; ?>"><br><br>
    Password : <input type="text" name="password" value="<?= $data['password']; ?>"><br><br>
    Nama : <input type="text" name="nama" value="<?= $data['nama']; ?>"><br><br>
    Email : <input type="email" name="email" value="<?= $data['email']; ?>"><br><br>
    <button type="submit" name="update">Update</button>
</form>
<?php } ?>

</body>
</html>