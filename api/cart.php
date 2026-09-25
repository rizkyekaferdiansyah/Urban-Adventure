<?php
require __DIR__ . '/bootstrap.php';

$user   = requireLogin();
$pdo    = db();
$input  = body();
$method = $_SERVER['REQUEST_METHOD'];
$id     = (int) ($_GET['id'] ?? 0);

// ─── GET: Ambil isi keranjang ─────────────────────────────────────────────────
if ($method === 'GET') {
    $stmt = $pdo->prepare(
        'SELECT c.*,p.name,p.image,p.price_per_day,p.stock,p.minimum_rental_days,p.maximum_rental_days
         FROM cart c
         JOIN products p ON p.id=c.product_id
         WHERE c.user_id=?
         ORDER BY c.created_at DESC'
    );
    $stmt->execute([$user['id']]);
    jsonResponse(true, '', $stmt->fetchAll());
}

requireCsrf($input);

// ─── Helper: validasi durasi terhadap aturan produk ───────────────────────────
function validateRentalDuration(PDO $pdo, int $productId, string $start, string $end): void
{
    $stmt = $pdo->prepare('SELECT minimum_rental_days, maximum_rental_days FROM products WHERE id=? AND status="active"');
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    if (!$product) jsonResponse(false, 'Produk tidak ditemukan.', null, 422);

    $days = rentalDays($start, $end);
    $min  = (int) $product['minimum_rental_days'];
    $max  = $product['maximum_rental_days'] !== null ? (int) $product['maximum_rental_days'] : null;

    if ($days < $min) {
        jsonResponse(false, "Minimal rental produk ini adalah {$min} hari.", null, 422);
    }
    if ($max !== null && $days > $max) {
        jsonResponse(false, "Maksimal rental produk ini adalah {$max} hari.", null, 422);
    }
}

// ─── POST: Tambah ke keranjang ────────────────────────────────────────────────
if ($method === 'POST') {
    $product = (int) ($input['product_id'] ?? 0);
    $qty     = max(1, (int) ($input['quantity'] ?? 1));
    $start   = (string) ($input['start_date'] ?? '');
    $end     = (string) ($input['end_date'] ?? '');

    if (!$product || !validDates($start, $end)) {
        jsonResponse(false, 'Tanggal tidak valid.', null, 422);
    }

    // Bug #7: cek minimum_rental_days
    validateRentalDuration($pdo, $product, $start, $end);

    if (!checkAvailability($pdo, $product, $start, $end, $qty)) {
        jsonResponse(false, 'Stok tidak mencukupi.', null, 422);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO cart (user_id,product_id,quantity,start_date,end_date) VALUES (?,?,?,?,?)
         ON DUPLICATE KEY UPDATE quantity=VALUES(quantity),start_date=VALUES(start_date),end_date=VALUES(end_date)'
    );
    $stmt->execute([$user['id'], $product, $qty, $start, $end]);
    jsonResponse(true, 'Produk ditambahkan ke keranjang.');
}

// ─── PUT: Update item keranjang ───────────────────────────────────────────────
if ($method === 'PUT' && $id) {
    $qty   = max(1, (int) ($input['quantity'] ?? 1));
    $start = (string) ($input['start_date'] ?? '');
    $end   = (string) ($input['end_date'] ?? '');

    $stmt = $pdo->prepare('SELECT product_id FROM cart WHERE id=? AND user_id=?');
    $stmt->execute([$id, $user['id']]);
    $row = $stmt->fetch();

    if (!$row || !validDates($start, $end)) {
        jsonResponse(false, 'Data cart tidak valid.', null, 422);
    }

    // Bug #7: cek minimum_rental_days pada update
    validateRentalDuration($pdo, (int) $row['product_id'], $start, $end);

    if (!checkAvailability($pdo, (int) $row['product_id'], $start, $end, $qty)) {
        jsonResponse(false, 'Stok tidak mencukupi.', null, 422);
    }

    $stmt = $pdo->prepare('UPDATE cart SET quantity=?,start_date=?,end_date=? WHERE id=? AND user_id=?');
    $stmt->execute([$qty, $start, $end, $id, $user['id']]);
    jsonResponse(true, 'Keranjang diperbarui.');
}

// ─── DELETE: Hapus item keranjang ─────────────────────────────────────────────
if ($method === 'DELETE' && $id) {
    $stmt = $pdo->prepare('DELETE FROM cart WHERE id=? AND user_id=?');
    $stmt->execute([$id, $user['id']]);
    jsonResponse(true, 'Produk dihapus.');
}

jsonResponse(false, 'Permintaan cart tidak valid.', null, 400);
