<?php
/**
 * Halaman Transaksi Kasir (Point of Sale/POS)
 * Menggunakan AJAX untuk pencarian produk dan manipulasi keranjang belanja,
 * serta menampilkan modal cetak struk format thermal.
 */

// Memulai session dan database
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../auth/login.php?status=unauthorized");
    exit();
}

require_once '../config/koneksi.php';

// Inisialisasi Keranjang di Session jika belum ada
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// ============================================
//   AJAX HANDLERS (SEBELUM OUTPUT HTML)
// ============================================

// 1. AJAX: Pencarian Produk
if (isset($_GET['action']) && $_GET['action'] === 'search_produk') {
    header('Content-Type: application/json');
    $query = trim($_GET['q'] ?? '');
    try {
        if (!empty($query)) {
            $stmt = $pdo->prepare("SELECT p.*, k.nama_kategori 
                                   FROM produk p 
                                   LEFT JOIN kategori k ON p.kategori_id = k.id 
                                   WHERE p.nama_produk LIKE :q OR p.kode_produk LIKE :q 
                                   LIMIT 12");
            $stmt->execute([':q' => '%' . $query . '%']);
        } else {
            // Tampilkan 12 produk terbaru jika pencarian kosong
            $stmt = $pdo->query("SELECT p.*, k.nama_kategori 
                                 FROM produk p 
                                 LEFT JOIN kategori k ON p.kategori_id = k.id 
                                 ORDER BY p.id DESC LIMIT 12");
        }
        $products = $stmt->fetchAll();
        echo json_encode($products);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
}

// 2. AJAX: Tambah Produk ke Keranjang
if (isset($_POST['action']) && $_POST['action'] === 'add_cart') {
    header('Content-Type: application/json');
    $id = intval($_POST['id'] ?? 0);

    try {
        $stmt = $pdo->prepare("SELECT * FROM produk WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $product = $stmt->fetch();

        if ($product) {
            if ($product['stok'] <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Stok produk habis.']);
                exit();
            }

            // Cek jika produk sudah ada di keranjang
            if (isset($_SESSION['cart'][$id])) {
                $new_qty = $_SESSION['cart'][$id]['jumlah'] + 1;
                
                // Validasi stok
                if ($new_qty > $product['stok']) {
                    echo json_encode(['status' => 'error', 'message' => 'Stok tidak mencukupi untuk jumlah ini.']);
                    exit();
                }
                
                $_SESSION['cart'][$id]['jumlah'] = $new_qty;
                $_SESSION['cart'][$id]['subtotal'] = $new_qty * $product['harga_jual'];
            } else {
                $_SESSION['cart'][$id] = [
                    'id' => $product['id'],
                    'kode_produk' => $product['kode_produk'],
                    'nama_produk' => $product['nama_produk'],
                    'harga_jual' => floatval($product['harga_jual']),
                    'jumlah' => 1,
                    'stok' => intval($product['stok']),
                    'subtotal' => floatval($product['harga_jual'])
                ];
            }
            echo json_encode(['status' => 'success', 'cart' => array_values($_SESSION['cart'])]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Produk tidak ditemukan.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
}

// 3. AJAX: Ubah Jumlah Pembelian (Qty)
if (isset($_POST['action']) && $_POST['action'] === 'update_cart') {
    header('Content-Type: application/json');
    $id = intval($_POST['id'] ?? 0);
    $qty = intval($_POST['jumlah'] ?? 1);

    if (isset($_SESSION['cart'][$id])) {
        if ($qty <= 0) {
            // Jika qty 0 atau negatif, hapus item dari keranjang
            unset($_SESSION['cart'][$id]);
            echo json_encode(['status' => 'success', 'cart' => array_values($_SESSION['cart'])]);
            exit();
        }

        // Cek ketersediaan stok produk dari database
        try {
            $stmt = $pdo->prepare("SELECT stok FROM produk WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $stok = $stmt->fetchColumn();

            if ($qty > $stok) {
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Stok tersisa ' . $stok . ' item. Jumlah melebihi stok.',
                    'max_qty' => $stok
                ]);
                exit();
            }

            $_SESSION['cart'][$id]['jumlah'] = $qty;
            $_SESSION['cart'][$id]['subtotal'] = $qty * $_SESSION['cart'][$id]['harga_jual'];
            echo json_encode(['status' => 'success', 'cart' => array_values($_SESSION['cart'])]);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Produk tidak ada di keranjang.']);
    }
    exit();
}

// 4. AJAX: Hapus Item Keranjang
if (isset($_POST['action']) && $_POST['action'] === 'delete_cart') {
    header('Content-Type: application/json');
    $id = intval($_POST['id'] ?? 0);

    if (isset($_SESSION['cart'][$id])) {
        unset($_SESSION['cart'][$id]);
        echo json_encode(['status' => 'success', 'cart' => array_values($_SESSION['cart'])]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Item tidak ditemukan di keranjang.']);
    }
    exit();
}

// 5. AJAX: Kosongkan Keranjang
if (isset($_POST['action']) && $_POST['action'] === 'clear_cart') {
    header('Content-Type: application/json');
    $_SESSION['cart'] = [];
    echo json_encode(['status' => 'success', 'cart' => []]);
    exit();
}

// 6. AJAX: Get Detail Keranjang Saat Ini
if (isset($_GET['action']) && $_GET['action'] === 'get_cart') {
    header('Content-Type: application/json');
    echo json_encode(array_values($_SESSION['cart']));
    exit();
}

// 7. AJAX: Proses Checkout Transaksi
if (isset($_POST['action']) && $_POST['action'] === 'checkout') {
    header('Content-Type: application/json');
    
    if (empty($_SESSION['cart'])) {
        echo json_encode(['status' => 'error', 'message' => 'Keranjang kosong.']);
        exit();
    }

    $bayar = floatval($_POST['bayar'] ?? 0);
    $total_harga = 0;
    
    // Hitung total dari session cart untuk validasi keamanan backend
    foreach ($_SESSION['cart'] as $item) {
        $total_harga += $item['subtotal'];
    }

    if ($bayar < $total_harga) {
        echo json_encode(['status' => 'error', 'message' => 'Uang pembayaran kurang.']);
        exit();
    }

    $kembalian = $bayar - $total_harga;
    $user_id = $_SESSION['user']['id'];

    // Generate Nomor Transaksi Unik: TRX-YYYYMMDD-UUID/Random
    $nomor_transaksi = 'TRX-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

    try {
        $pdo->beginTransaction();

        // 1. Simpan Transaksi Utama
        $stmt_trx = $pdo->prepare("INSERT INTO transaksi (nomor_transaksi, total_harga, bayar, kembalian, user_id) 
                                   VALUES (:nomor, :total, :bayar, :kembalian, :user_id)");
        $stmt_trx->execute([
            ':nomor' => $nomor_transaksi,
            ':total' => $total_harga,
            ':bayar' => $bayar,
            ':kembalian' => $kembalian,
            ':user_id' => $user_id
        ]);

        $transaksi_id = $pdo->lastInsertId();

        // 2. Simpan Detail Transaksi & Kurangi Stok Produk
        foreach ($_SESSION['cart'] as $item) {
            // Double check ketersediaan stok
            $stmt_stock = $pdo->prepare("SELECT stok, nama_produk FROM produk WHERE id = :id FOR UPDATE");
            $stmt_stock->execute([':id' => $item['id']]);
            $current_product = $stmt_stock->fetch();

            if ($current_product['stok'] < $item['jumlah']) {
                throw new Exception("Stok untuk produk '" . $current_product['nama_produk'] . "' tidak cukup. Transaksi dibatalkan.");
            }

            // Simpan detail
            $stmt_detail = $pdo->prepare("INSERT INTO detail_transaksi (transaksi_id, produk_id, nama_produk, jumlah, harga_jual, subtotal) 
                                          VALUES (:trx_id, :prod_id, :nama, :jumlah, :harga, :subtotal)");
            $stmt_detail->execute([
                ':trx_id' => $transaksi_id,
                ':prod_id' => $item['id'],
                ':nama' => $item['nama_produk'],
                ':jumlah' => $item['jumlah'],
                ':harga' => $item['harga_jual'],
                ':subtotal' => $item['subtotal']
            ]);

            // Deduct stock
            $stmt_deduct = $pdo->prepare("UPDATE produk SET stok = stok - :qty WHERE id = :id");
            $stmt_deduct->execute([
                ':qty' => $item['jumlah'],
                ':id' => $item['id']
            ]);
        }

        $pdo->commit();
        
        // Simpan data struk ke temp var untuk ditampilkan di modal print
        $struk_data = [
            'nomor_transaksi' => $nomor_transaksi,
            'tanggal_transaksi' => date('d-m-Y H:i:s'),
            'total_harga' => $total_harga,
            'bayar' => $bayar,
            'kembalian' => $kembalian,
            'kasir' => $_SESSION['user']['nama_lengkap'],
            'items' => array_values($_SESSION['cart'])
        ];

        // Kosongkan keranjang setelah checkout berhasil
        $_SESSION['cart'] = [];

        echo json_encode([
            'status' => 'success', 
            'message' => 'Transaksi berhasil disimpan!', 
            'struk' => $struk_data
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
}

// ============================================
//   TAMPILAN UTAMA HALAMAN
// ============================================
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom border-secondary">
    <h1 class="h2 text-white">Transaksi Kasir</h1>
    <span class="text-muted"><i class="bi bi-clock me-2"></i><?= date('d-m-Y'); ?></span>
</div>

<div class="row">
    <!-- Kolom Kiri: Produk Selector -->
    <div class="col-lg-7">
        <div class="card-custom mb-4">
            <div class="input-group mb-3">
                <span class="input-group-text bg-dark border-secondary text-secondary"><i class="bi bi-search"></i></span>
                <input type="text" id="searchProduk" class="form-control bg-secondary text-white border-secondary" placeholder="Cari Produk berdasarkan Nama atau Kode (misal: P001)..." autocomplete="off">
            </div>

            <!-- Container Grid List Produk -->
            <div class="row row-cols-2 row-cols-md-3 g-3 overflow-y-auto" style="max-height: 480px; padding: 4px;" id="listProduk">
                <!-- Data produk akan dimuat oleh AJAX di sini -->
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Keranjang & Checkout -->
    <div class="col-lg-5">
        <div class="card-custom">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="text-white mb-0"><i class="bi bi-cart3 me-2 text-indigo"></i>Keranjang Belanja</h5>
                <button type="button" class="btn btn-sm btn-outline-danger" id="btnClearCart"><i class="bi bi-trash3 me-1"></i>Reset</button>
            </div>

            <!-- List Cart Item -->
            <div class="cart-container" id="cartList">
                <!-- Cart items load via JS -->
                <div class="text-center text-secondary py-5">
                    <i class="bi bi-cart-x" style="font-size: 3rem;"></i>
                    <p class="mt-2 small">Keranjang masih kosong</p>
                </div>
            </div>

            <!-- Panel Rincian Pembayaran -->
            <div class="mt-3">
                <div class="d-flex justify-content-between text-muted mb-2">
                    <span>Subtotal</span>
                    <span id="subtotalDisplay">Rp 0</span>
                </div>
                <div class="d-flex justify-content-between text-white fw-bold fs-5 mb-3 border-top border-secondary pt-2">
                    <span>Total</span>
                    <span id="totalDisplay" class="text-indigo">Rp 0</span>
                </div>

                <div class="mb-3">
                    <label for="bayar" class="form-label text-muted small">Uang Bayar (Rp)</label>
                    <input type="number" id="bayarInput" class="form-control form-control-lg bg-dark text-white border-secondary font-monospace" placeholder="0" min="0">
                </div>

                <div class="d-flex justify-content-between text-warning fw-semibold fs-6 mb-4">
                    <span>Kembalian</span>
                    <span id="kembalianDisplay">Rp 0</span>
                </div>

                <button type="button" class="btn btn-primary-custom w-100 py-3 d-flex justify-content-center align-items-center gap-2" id="btnCheckout" disabled>
                    <i class="bi bi-wallet2"></i>
                    <span class="fw-bold">PROSES PEMBAYARAN</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Struk Cetak (Thermal Format) -->
<div class="modal fade" id="modalStruk" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalStrukLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border-secondary text-white">
            <div class="modal-header border-secondary">
                <h5 class="modal-title" id="modalStrukLabel"><i class="bi bi-receipt me-2 text-success"></i> Transaksi Berhasil</h5>
            </div>
            <div class="modal-body bg-light text-dark text-center p-4">
                <!-- Kertas Struk Thermal Preview -->
                <div class="receipt-wrapper text-start shadow-none" id="thermal-receipt">
                    <div class="receipt-header">
                        <h5 class="fw-bold m-0 text-uppercase"><?= htmlspecialchars($toko['nama_toko']); ?></h5>
                        <p class="small m-0 text-muted" style="font-size: 11px;"><?= htmlspecialchars($toko['alamat']); ?></p>
                        <p class="small m-0 text-muted" style="font-size: 11px;">Telp: <?= htmlspecialchars($toko['telepon']); ?></p>
                    </div>
                    
                    <div class="receipt-line"></div>
                    
                    <div class="small text-muted" style="font-size: 11px;">
                        <div>No: <span id="strukNo"></span></div>
                        <div>Tgl: <span id="strukTgl"></span></div>
                        <div>Kasir: <span id="strukKasir"></span></div>
                    </div>
                    
                    <div class="receipt-line"></div>
                    
                    <div id="strukItems" class="small">
                        <!-- Items rendered via JS -->
                    </div>
                    
                    <div class="receipt-line"></div>
                    
                    <div class="small">
                        <div class="d-flex justify-content-between">
                            <span>TOTAL:</span>
                            <span id="strukTotal" class="fw-bold"></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>BAYAR:</span>
                            <span id="strukBayar"></span>
                        </div>
                        <div class="d-flex justify-content-between border-top border-dark border-dashed pt-1">
                            <span>KEMBALI:</span>
                            <span id="strukKembalian" class="fw-bold"></span>
                        </div>
                    </div>
                    
                    <div class="receipt-line"></div>
                    
                    <div class="receipt-footer small text-center mt-3">
                        <p class="fw-bold mb-1">TERIMA KASIH</p>
                        <p class="text-muted m-0" style="font-size: 10px;">Sudah Berbelanja di Toko Kami</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-secondary justify-content-between">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCloseStruk"><i class="bi bi-arrow-left me-1"></i>Transaksi Baru</button>
                <button type="button" class="btn btn-success btn-sm px-4" id="btnPrintStruk"><i class="bi bi-printer me-2"></i>Cetak Struk</button>
            </div>
        </div>
    </div>
</div>

<?php 
// Memasukkan footer layout
include '../includes/footer.php'; 
?>

<script>
$(document).ready(function() {
    let globalTotal = 0;

    // Load produk pertama kali
    loadProduk('');

    // Event input cari produk
    $('#searchProduk').on('input', function() {
        let q = $(this).val();
        loadProduk(q);
    });

    // Ambil data keranjang saat ini
    loadCart();

    // Fungsi mengambil daftar produk via AJAX
    function loadProduk(query) {
        $.ajax({
            url: 'transaksi.php',
            type: 'GET',
            data: { action: 'search_produk', q: query },
            dataType: 'json',
            success: function(response) {
                let html = '';
                if (response.length > 0) {
                    response.forEach(function(p) {
                        let fotoHtml = '';
                        if (p.foto) {
                            fotoHtml = `<img src="../assets/img/produk/${p.foto}" class="pos-product-img" alt="Foto">`;
                        } else {
                            fotoHtml = `<div class="pos-product-no-img"><i class="bi bi-image" style="font-size: 2rem;"></i></div>`;
                        }
                        
                        let disabled = p.stok <= 0 ? 'disabled' : '';
                        let stokText = p.stok <= 0 ? '<span class="text-danger">Habis</span>' : `Stok: ${p.stok}`;

                        html += `
                            <div class="col">
                                <div class="pos-product-card h-100 btn-add-cart" data-id="${p.id}">
                                    ${fotoHtml}
                                    <div class="p-2.5 card-body d-flex flex-column justify-content-between p-3" style="min-height: 100px;">
                                        <div>
                                            <h6 class="text-white text-truncate mb-1" style="font-size: 0.9rem;" title="${p.nama_produk}">${p.nama_produk}</h6>
                                            <span class="badge bg-secondary font-monospace" style="font-size: 0.7rem;">${p.kode_produk}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-2.5">
                                            <span class="fw-bold text-indigo small">${formatRupiah(p.harga_jual)}</span>
                                            <span class="text-muted small" style="font-size: 0.75rem;">${stokText}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    html = '<div class="col-12 py-5 text-center text-muted"><p>Produk tidak ditemukan</p></div>';
                }
                $('#listProduk').html(html);
            }
        });
    }

    // Fungsi memuat data keranjang belanja
    function loadCart() {
        $.ajax({
            url: 'transaksi.php',
            type: 'GET',
            data: { action: 'get_cart' },
            dataType: 'json',
            success: function(cart) {
                renderCart(cart);
            }
        });
    }

    // Render HTML Keranjang Belanja
    function renderCart(cart) {
        let html = '';
        let subtotal = 0;
        
        if (cart.length > 0) {
            cart.forEach(function(item) {
                subtotal += item.subtotal;
                html += `
                    <div class="cart-item d-flex justify-content-between align-items-center">
                        <div style="max-width: 60%;">
                            <div class="text-white fw-semibold small text-truncate" title="${item.nama_produk}">${item.nama_produk}</div>
                            <div class="text-muted small">${formatRupiah(item.harga_jual)} <span class="font-monospace">x${item.jumlah}</span></div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="input-group input-group-sm" style="width: 100px;">
                                <button class="btn btn-outline-secondary btn-qty-minus" type="button" data-id="${item.id}" data-qty="${item.jumlah}">-</button>
                                <input type="text" class="form-control bg-dark border-secondary text-white text-center font-monospace input-qty" value="${item.jumlah}" data-id="${item.id}" readonly>
                                <button class="btn btn-outline-secondary btn-qty-plus" type="button" data-id="${item.id}" data-qty="${item.jumlah}">+</button>
                            </div>
                            <button class="btn btn-sm btn-outline-danger btn-delete-item" data-id="${item.id}"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                `;
            });
            $('#btnCheckout').prop('disabled', false);
        } else {
            html = `
                <div class="text-center text-secondary py-5">
                    <i class="bi bi-cart-x" style="font-size: 3rem;"></i>
                    <p class="mt-2 small mb-0">Keranjang masih kosong</p>
                </div>
            `;
            $('#btnCheckout').prop('disabled', true);
        }

        $('#cartList').html(html);
        $('#subtotalDisplay').text(formatRupiah(subtotal));
        $('#totalDisplay').text(formatRupiah(subtotal));
        
        globalTotal = subtotal;
        hitungKembalian();
    }

    // Menghitung Kembalian Pembayaran
    function hitungKembalian() {
        let bayar = parseFloat($('#bayarInput').val()) || 0;
        let kembali = bayar - globalTotal;
        if (kembali < 0 || globalTotal === 0) {
            $('#kembalianDisplay').text('Rp 0').removeClass('text-success').addClass('text-warning');
        } else {
            $('#kembalianDisplay').text(formatRupiah(kembali)).removeClass('text-warning').addClass('text-success');
        }
    }

    // Input Bayar keyup handler
    $('#bayarInput').on('keyup input', function() {
        hitungKembalian();
    });

    // Event click Card Produk (Tambah ke keranjang)
    $(document).on('click', '.btn-add-cart', function() {
        let id = $(this).data('id');
        $.ajax({
            url: 'transaksi.php',
            type: 'POST',
            data: { action: 'add_cart', id: id },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    renderCart(response.cart);
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Oops!',
                        text: response.message,
                        confirmButtonColor: '#6366f1',
                        background: '#1e293b',
                        color: '#f8fafc'
                    });
                }
            }
        });
    });

    // Event Quantity Minus
    $(document).on('click', '.btn-qty-minus', function() {
        let id = $(this).data('id');
        let currentQty = parseInt($(this).data('qty'));
        let newQty = currentQty - 1;
        
        updateQty(id, newQty);
    });

    // Event Quantity Plus
    $(document).on('click', '.btn-qty-plus', function() {
        let id = $(this).data('id');
        let currentQty = parseInt($(this).data('qty'));
        let newQty = currentQty + 1;
        
        updateQty(id, newQty);
    });

    // Mengupdate kuantitas item
    function updateQty(id, qty) {
        $.ajax({
            url: 'transaksi.php',
            type: 'POST',
            data: { action: 'update_cart', id: id, jumlah: qty },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    renderCart(response.cart);
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Stok Terbatas',
                        text: response.message,
                        confirmButtonColor: '#6366f1',
                        background: '#1e293b',
                        color: '#f8fafc'
                    });
                }
            }
        });
    }

    // Event Delete Item
    $(document).on('click', '.btn-delete-item', function() {
        let id = $(this).data('id');
        $.ajax({
            url: 'transaksi.php',
            type: 'POST',
            data: { action: 'delete_cart', id: id },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    renderCart(response.cart);
                }
            }
        });
    });

    // Reset / Clear Cart
    $('#btnClearCart').on('click', function() {
        if (globalTotal === 0) return;
        
        Swal.fire({
            title: 'Hapus Keranjang?',
            text: "Seluruh daftar item belanja saat ini akan dikosongkan.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6366f1',
            confirmButtonText: 'Ya, Reset!',
            cancelButtonText: 'Batal',
            background: '#1e293b',
            color: '#f8fafc'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'transaksi.php',
                    type: 'POST',
                    data: { action: 'clear_cart' },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            renderCart(response.cart);
                            $('#bayarInput').val('');
                        }
                    }
                });
            }
        });
    });

    // Event Checkout / Bayar & Simpan
    $('#btnCheckout').on('click', function() {
        let bayar = parseFloat($('#bayarInput').val()) || 0;
        
        if (bayar <= 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Uang Bayar',
                text: 'Silakan isi jumlah uang pembayaran terlebih dahulu!',
                confirmButtonColor: '#6366f1',
                background: '#1e293b',
                color: '#f8fafc'
            });
            return;
        }

        if (bayar < globalTotal) {
            Swal.fire({
                icon: 'error',
                title: 'Pembayaran Kurang',
                text: 'Uang bayar yang dimasukkan kurang dari total belanja.',
                confirmButtonColor: '#6366f1',
                background: '#1e293b',
                color: '#f8fafc'
            });
            return;
        }

        // Jalankan checkout AJAX
        $.ajax({
            url: 'transaksi.php',
            type: 'POST',
            data: { action: 'checkout', bayar: bayar },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // Isi modal dengan detail transaksi struk
                    let struk = response.struk;
                    $('#strukNo').text(struk.nomor_transaksi);
                    $('#strukTgl').text(struk.tanggal_transaksi);
                    $('#strukKasir').text(struk.kasir);
                    $('#strukTotal').text(formatRupiah(struk.total_harga));
                    $('#strukBayar').text(formatRupiah(struk.bayar));
                    $('#strukKembalian').text(formatRupiah(struk.kembalian));

                    // Items list di struk
                    let itemsHtml = '';
                    struk.items.forEach(function(item) {
                        itemsHtml += `
                            <div class="mb-1">
                                <div class="fw-bold">${item.nama_produk}</div>
                                <div class="d-flex justify-content-between">
                                    <span>${item.jumlah} x ${formatRupiah(item.harga_jual, '')}</span>
                                    <span>${formatRupiah(item.subtotal, '')}</span>
                                </div>
                            </div>
                        `;
                    });
                    $('#strukItems').html(itemsHtml);

                    // Tampilkan Modal Struk
                    const modalStruk = new bootstrap.Modal(document.getElementById('modalStruk'));
                    modalStruk.show();
                    
                    // Bersihkan formulir input bayar
                    $('#bayarInput').val('');
                    loadCart(); // Reset view keranjang
                    loadProduk($('#searchProduk').val()); // Refresh list produk untuk update stok
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Transaksi Gagal',
                        text: response.message,
                        confirmButtonColor: '#6366f1',
                        background: '#1e293b',
                        color: '#f8fafc'
                    });
                }
            }
        });
    });

    // Tutup Struk Modal & Muat Ulang Transaksi Baru
    $('#btnCloseStruk').on('click', function() {
        $('#modalStruk').modal('hide');
    });

    // Cetak Struk (panggil print preview native browser)
    $('#btnPrintStruk').on('click', function() {
        window.print();
    });
});
</script>
