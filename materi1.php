<form method="POST">
    masukan sebuah angka : <imput type = "number" nama = "angka"
    <input type = "sutmit" value = "lirim"
<form

<?php

if (isset($_POST['angka'])) {
    $angka = $_POST['angka'];

    for ($i = 1;$i <= $amgka; $i++) [
        echo "<br>nilai anda : $i";
    ]
}