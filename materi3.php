<?php
function salam()
{
    echo "Assalamu'alaikum <br>";
}

salam();

function tambah($a, $b)
{
    $jumlah = $a + $b;
    return $jumlah;
}

$hasil = tambah(4, 6);

echo "Hasil penjumlahan: " . $hasil;

function kali($a, $b)
{
    $jumlah = $a * $b;
    return $jumlah;
}

$hasil = kali(4, 6);

echo "Hasil perkalian: " . $hasil;
?>

<form method="POST">
    <input type="number" name="angka1" required>
    <input type="number" name="angka2" required>
    <button type="submit" name="kirim">Kirim</button>
</form>

<?php

if (isset($_POST['kirim'])) {
    $angka1 = $_POST['angka1'];
    $angka2 = $_POST['angka2'];
    

    $hasil = tambah($angka1, $angka2);

    echo "Hasil: " . $hasil;
}
?>