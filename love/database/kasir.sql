CREATE DATABASE IF NOT EXISTS db_kasir;
USE db_kasir;

-- Table: users
DROP TABLE IF EXISTS detail_transaksi;
DROP TABLE IF EXISTS transaksi;
DROP TABLE IF EXISTS produk;
DROP TABLE IF EXISTS kategori;
DROP TABLE IF EXISTS pengaturan_toko;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('admin', 'kasir') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: kategori
CREATE TABLE kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: produk
CREATE TABLE produk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_produk VARCHAR(50) NOT NULL UNIQUE,
    nama_produk VARCHAR(150) NOT NULL,
    kategori_id INT,
    harga_beli DECIMAL(10,2) NOT NULL,
    harga_jual DECIMAL(10,2) NOT NULL,
    stok INT NOT NULL DEFAULT 0,
    foto VARCHAR(255) NULL,
    tanggal_input TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: transaksi
CREATE TABLE transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nomor_transaksi VARCHAR(50) NOT NULL UNIQUE,
    tanggal_transaksi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_harga DECIMAL(10,2) NOT NULL,
    bayar DECIMAL(10,2) NOT NULL,
    kembalian DECIMAL(10,2) NOT NULL,
    user_id INT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: detail_transaksi
CREATE TABLE detail_transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaksi_id INT NOT NULL,
    produk_id INT NULL,
    nama_produk VARCHAR(150) NOT NULL,
    jumlah INT NOT NULL,
    harga_jual DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (transaksi_id) REFERENCES transaksi(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: pengaturan_toko
CREATE TABLE pengaturan_toko (
    id INT PRIMARY KEY,
    nama_toko VARCHAR(150) NOT NULL,
    alamat TEXT NOT NULL,
    telepon VARCHAR(20) NOT NULL,
    logo VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Seed Data
-- Seed Users (Passwords: admin123, kasir123)
INSERT INTO users (id, username, password, nama_lengkap, role) VALUES 
(1, 'admin', '$2y$10$hZ1oPcNBRJgFUiLVe8DYl.4ylZqs2yAYPz6TNi5BPyIdC0htrJse.', 'Administrator POS', 'admin'),
(2, 'kasir', '$2y$10$M158zKWXi3ZI6CsuE/4b7OtQFWQZnsmomDv7mt.MUtzD5/1LmHhBG', 'Kasir Utama', 'kasir');

-- Seed Kategori
INSERT INTO kategori (id, nama_kategori) VALUES 
(1, 'Makanan'),
(2, 'Minuman'),
(3, 'Snack'),
(4, 'Lain-lain');

-- Seed Produk
INSERT INTO produk (id, kode_produk, nama_produk, kategori_id, harga_beli, harga_jual, stok, foto) VALUES
(1, 'P001', 'Kopi Susu Gula Aren', 2, 8000.00, 15000.00, 50, 'kopi_susu.png'),
(2, 'P002', 'Nasi Goreng Spesial', 1, 12000.00, 20000.00, 30, 'nasi_goreng.png'),
(3, 'P003', 'Kentang Goreng', 3, 6000.00, 12000.00, 40, 'kentang_goreng.png'),
(4, 'P004', 'steak', 1, 10000.00, 10000.00, 5, 'steak.png'),
(5, 'P005', 'Nasi Goreng Ayam', 1, 10000.00, 15000.00, 35, 'nasi_goreng.png'),
(6, 'P006', 'Nasi Goreng Gila', 1, 12000.00, 18000.00, 20, 'nasi_goreng.png'),
(7, 'P007', 'Nasi Uduk Spesial', 1, 9000.00, 14000.00, 25, 'nasi_goreng.png'),
(8, 'P008', 'Mie Goreng Jawa', 1, 8000.00, 13000.00, 40, 'nasi_goreng.png'),
(9, 'P009', 'Mie Rebus Spesial', 1, 8000.00, 13000.00, 30, 'nasi_goreng.png'),
(10, 'P010', 'Ayam Goreng Lengkuas', 1, 12000.00, 18000.00, 20, 'nasi_goreng.png'),
(11, 'P011', 'Ayam Bakar Madu', 1, 13000.00, 20000.00, 15, 'nasi_goreng.png'),
(12, 'P012', 'Beef Burger Deluxe', 1, 15000.00, 25000.00, 25, 'burger.png'),
(13, 'P013', 'Cheese Burger Supreme', 1, 18000.00, 28000.00, 20, 'burger.png'),
(14, 'P014', 'Pizza Pepperoni Small', 1, 25000.00, 45000.00, 10, 'pizza.png'),
(15, 'P015', 'Pizza Meat Lovers', 1, 30000.00, 55000.00, 8, 'pizza.png'),
(16, 'P016', 'Steak Ayam Crispy', 1, 15000.00, 25000.00, 18, 'steak.png'),
(17, 'P017', 'Sirloin Beef Steak', 1, 45000.00, 75000.00, 12, 'steak.png'),
(18, 'P018', 'T-Bone Beef Steak', 1, 55000.00, 85000.00, 10, 'steak.png'),
(19, 'P019', 'Kopi Hitam / Americano', 2, 5000.00, 10000.00, 100, 'kopi_susu.png'),
(20, 'P020', 'Kopi Latte Creamy', 2, 9000.00, 16000.00, 60, 'kopi_susu.png'),
(21, 'P021', 'Kopi Cappuccino Hot', 2, 9000.00, 16000.00, 50, 'kopi_susu.png'),
(22, 'P022', 'Matcha Latte Ice', 2, 10000.00, 18000.00, 45, 'kopi_susu.png'),
(23, 'P023', 'Chocolate Signature Ice', 2, 10000.00, 18000.00, 40, 'kopi_susu.png'),
(24, 'P024', 'Teh Manis Dingin (Ice Tea)', 2, 2000.00, 5000.00, 150, 'kopi_susu.png'),
(25, 'P025', 'Lemon Tea Segar', 2, 4000.00, 8000.00, 80, 'kopi_susu.png'),
(26, 'P026', 'Orange Juice Murni', 2, 7000.00, 12000.00, 50, 'kopi_susu.png'),
(27, 'P027', 'Juice Alpukat Mentega', 2, 8000.00, 15000.00, 30, 'kopi_susu.png'),
(28, 'P028', 'Kentang Goreng Keju', 3, 8000.00, 15000.00, 40, 'kentang_goreng.png'),
(29, 'P029', 'Cireng Bumbu Rujak', 3, 5000.00, 10000.00, 35, 'kentang_goreng.png'),
(30, 'P030', 'Singkong Keju Merekah', 3, 6000.00, 12000.00, 30, 'kentang_goreng.png'),
(31, 'P031', 'Pisang Goreng Pasir Madu', 3, 6000.00, 12000.00, 45, 'kentang_goreng.png'),
(32, 'P032', 'Otak-Otak Goreng Crispy', 3, 5000.00, 10000.00, 50, 'kentang_goreng.png'),
(33, 'P033', 'Tahu Bakso Goreng', 3, 7000.00, 12000.00, 30, 'kentang_goreng.png'),
(34, 'P034', 'Tempe Mendoan Hangat', 3, 4000.00, 8000.00, 60, 'kentang_goreng.png');

-- Seed Pengaturan Toko
INSERT INTO pengaturan_toko (id, nama_toko, alamat, telepon, logo) VALUES
(1, 'Love POS', 'Jl. Merdeka Raya No. 45, Jakarta Selatan', '081234567890', NULL);
