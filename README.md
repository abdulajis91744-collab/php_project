# Aplikasi Point of Sale (POS) Kasir - PHP Native & Bootstrap 5

Aplikasi POS Kasir berbasis website yang dibangun menggunakan PHP Native (PDO MySQL), Bootstrap 5, AJAX, DataTables, SweetAlert2, dan Chart.js tanpa menggunakan framework (Laravel, CodeIgniter, dll).

---

## 🛠️ Langkah Instalasi di XAMPP (Windows)

Berikut adalah panduan instalasi dari awal sampai aplikasi siap dijalankan di komputer Anda:

### Langkah 1: Persiapan Folder Project
1. Pastikan Anda telah menginstal **XAMPP** di komputer Anda (Disarankan versi PHP 8.0 ke atas).
2. Salin folder project ini ke dalam direktori web server XAMPP, biasanya berada di:
   `C:\xampp\htdocs\love`
   *(Catatan: Pastikan nama folder di htdocs adalah `love` agar path routing berjalan lancar di browser).*

### Langkah 2: Mengaktifkan Apache dan MySQL
1. Buka aplikasi **XAMPP Control Panel**.
2. Klik tombol **Start** pada modul **Apache** dan **MySQL** hingga indikator berwarna hijau.

### Langkah 3: Import Database MySQL
1. Buka browser Anda (Google Chrome, Edge, Firefox, dll) lalu buka tautan berikut untuk masuk ke phpMyAdmin:
   [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/)
2. Klik tab **Database** di bagian atas, lalu buat database baru dengan nama:
   `db_kasir`
3. Pilih database `db_kasir` yang baru saja dibuat pada bilah menu sebelah kiri.
4. Klik tab **Import** di menu navigasi atas.
5. Klik **Choose File** (Pilih File) dan arahkan ke file SQL yang berada di dalam folder project Anda:
   `C:\xampp\htdocs\love\database\kasir.sql`
6. Gulir ke bawah lalu klik tombol **Import** (atau **Go**) untuk memulai proses impor struktur tabel dan data awal.
7. Pastikan proses impor berhasil tanpa error dan seluruh tabel (`users`, `kategori`, `produk`, `transaksi`, `detail_transaksi`, `pengaturan_toko`) telah terbuat.

### Langkah 4: Menjalankan Aplikasi
1. Buka browser Anda dan akses aplikasi melalui URL berikut:
   [http://localhost/love/](http://localhost/love/)
2. Anda akan otomatis dialihkan ke halaman Login.
3. Masuk menggunakan akun bawaan (seeder) berikut:
   * **Akun Administrator**:
     * Username: `admin`
     * Password: `admin123`
   * **Akun Kasir**:
     * Username: `kasir`
     * Password: `kasir123`

---

## 📂 Struktur Folder Project

```
love/
│
├── assets/
│   ├── css/
│   │   └── style.css            # Desain kustom premium, layout responsif & print struk
│   ├── js/
│   │   └── main.js              # Fungsi JS umum (format rupiah & responsive sidebar)
│   └── img/
│       ├── logo/                # Penyimpanan file upload logo toko
│       └── produk/              # Penyimpanan file upload foto produk
│
├── auth/
│   ├── login.php                # Tampilan halaman login
│   ├── proses_login.php         # Proses pengecekan otentikasi user & enkripsi password
│   └── logout.php               # Mengakhiri session dan keluar aplikasi
│
├── config/
│   └── koneksi.php              # Pengaturan koneksi database MySQL menggunakan PDO
│
├── database/
│   └── kasir.sql                # File skema database & data awal (seeder)
│
├── includes/
│   ├── header.php               # Validasi login session, link CSS CDN, navbar atas
│   ├── sidebar.php              # Navigasi menu utama berdasarkan hak akses/role
│   └── footer.php               # Kumpulan library JS CDN (Bootstrap, jQuery, DataTables, Chart.js)
│
├── pages/
│   ├── dashboard.php            # Tampilan statistik penjualan & grafik Chart.js
│   ├── produk.php               # CRUD data produk & fitur upload gambar
│   ├── kategori.php             # CRUD kategori produk
│   ├── transaksi.php            # POS transaksi kasir (AJAX keranjang, kalkulasi, struk thermal)
│   ├── laporan.php              # Laporan filter harian-tahunan, cetak PDF & export Excel
│   └── users.php                # CRUD data pengguna / manajemen user (khusus Admin)
│   └── pengaturan.php           # Pengaturan info nama toko, alamat, no telp & logo (khusus Admin)
│
└── index.php                    # Router utama (mengalihkan session ke login/dashboard)
```

---

## 📝 Penjelasan Fungsi Setiap File

1. **`index.php`**: Berfungsi sebagai pintu masuk utama aplikasi. Melakukan pemeriksaan session. Jika pengguna belum login, akan langsung dialihkan ke `auth/login.php`, dan jika sudah login akan dialihkan ke `pages/dashboard.php`.
2. **`config/koneksi.php`**: Mengatur koneksi PHP ke database MySQL melalui PDO. Dilengkapi perlindungan `emulate prepares => false` guna menjamin keamanan statements dari celah SQL Injection.
3. **`database/kasir.sql`**: Berisi seluruh kode SQL DDL pembuatan tabel-tabel, relasi foreign key, constraint cascade, serta penambahan data awal (seperti akun default, kategori produk, produk, dan profil toko).
4. **`auth/login.php`**: Menyediakan formulir input username dan password dengan antarmuka modern glassmorphism. Menangkap status URL untuk memicu pop-up notifikasi login gagal/akses ditolak menggunakan SweetAlert2.
5. **`auth/proses_login.php`**: Menerima request POST login dari user, mengecek keberadaan akun lewat prepared statements, serta mencocokkan password terenkripsi via `password_verify()`. Jika lolos, data user disimpan ke `$_SESSION['user']`.
6. **`auth/logout.php`**: Menghapus seluruh array data session, menghancurkan session id, dan mengalihkan kembali ke halaman login.
7. **`includes/header.php`**: Memvalidasi session aktif di awal agar halaman tidak bisa ditembus bypass URL. Memuat CDN stylesheet serta bagian navbar atas.
8. **`includes/sidebar.php`**: Menyediakan menu navigasi samping. Memiliki logika pengecekan role `admin` atau `kasir` agar menu sensitif (seperti Kelola Pengguna dan Pengaturan Toko) hanya tampil di akun Administrator.
9. **`includes/footer.php`**: Menutup tag HTML layout body dan mengimpor seluruh library Javascript penting (jQuery, Bootstrap 5 Bundle, DataTables, Chart.js, dan SweetAlert2).
10. **`assets/css/style.css`**: File stylesheet utama yang berisi modifikasi palet warna slate modern, modifikasi gaya scrollbar, modifikasi tema DataTables & SweetAlert2 agar menyatu dengan nuansa gelap/slate, grid POS, serta print media query khusus cetak struk thermal.
11. **`assets/js/main.js`**: Menyimpan fungsi responsive toggle sidebar di device mobile/tablet serta format helper angka desimal ke Rupiah (`Rp 1.000`).
12. **`pages/dashboard.php`**: Menyajikan widget ringkasan statistik (jumlah produk, kategori, transaksi hari ini, dan total omset hari ini) dan melacak data penjualan 7 hari ke belakang untuk diplot menjadi grafik garis melalui Chart.js.
13. **`pages/kategori.php`**: Mengelola kategori produk (tambah, edit, dan hapus). Didukung modal Bootstrap 5 untuk form input tanpa pindah halaman, dan konfirmasi hapus interaktif menggunakan SweetAlert2.
14. **`pages/produk.php`**: Mengelola data produk (tambah, edit, hapus) lengkap dengan validasi harga beli/jual, generator kode produk otomatis, upload foto produk dengan pembatasan ukuran, dan pembersihan otomatis foto lama di folder server saat produk dihapus atau diganti gambarnya.
15. **`pages/transaksi.php`**: Layar kasir (Point of Sale/POS).
    * Menggunakan **AJAX** untuk pencarian produk secara langsung tanpa reload halaman.
    * Menggunakan **AJAX** untuk menambah, mengurangi jumlah, reset, dan menghapus item keranjang belanja (disimpan aman di session).
    * Menghitung subtotal, total belanja, dan sisa uang kembalian secara instan dengan Javascript.
    * Melakukan validasi batas stok produk saat kasir bertransaksi.
    * Menyimpan data transaksi ke tabel database (`transaksi` & `detail_transaksi`) menggunakan mekanisme database **Transaction (Commit / Rollback)** untuk integritas data.
    * Membuka modal cetak struk berformat thermal 80mm dan memicu printer pencetak struk via window print native.
16. **`pages/laporan.php`**: Menampilkan summary omset penjualan dengan filter rentang waktu (Harian, Mingguan, Bulanan, Tahunan). Menyediakan tabel transaksi, daftar 5 produk terlaris (top selling), fitur cetak laporan langsung (Cetak / PDF), dan unduh file spreadsheet Microsoft Excel.
17. **`pages/users.php`**: Mengelola data akun pengguna (tambah, edit, hapus). Memproteksi diri agar kasir biasa tidak dapat masuk halaman ini, melakukan hashing password aman (`password_hash()`), serta menolak aksi apabila admin mencoba menghapus akunnya sendiri yang sedang aktif digunakan.
18. **`pages/pengaturan.php`**: Memungkinkan administrator memperbarui profil toko seperti Nama Toko, Alamat, No Telepon, serta mengunggah file Logo Toko baru.

---

## 🔒 Fitur Keamanan Sistem
1. **SQL Injection Prevention**: Seluruh query database di aplikasi ini menggunakan **Prepared Statements (PDO)** dengan parameter binding, sehingga parameter input dinilai terpisah dari struktur kode SQL.
2. **Session Hijacking Guard**: Pembatasan akses langsung di tiap folder, serta verifikasi login session (`$_SESSION['user']`) di semua header halaman utama.
3. **Role Authorization**: Menu administrasi pengguna (`users.php`) dan pengaturan toko (`pengaturan.php`) diberi pagar pengaman validasi role di sisi backend PHP. Jika kasir mencoba mengakses via ketik manual URL, mereka akan diblokir dan dialihkan kembali.
4. **Password Encryption**: Password disimpan dalam database menggunakan enkripsi satu arah yang aman via `password_hash()` menggunakan algoritma default bcrypt dan diverifikasi dengan `password_verify()`.
