<?php
require __DIR__ . '/bootstrap.php';

$user = requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method tidak diizinkan.', null, 405);
}

$input   = body();
requireCsrf($input);

$orderId = (int) ($input['order_id'] ?? 0);
$method  = (string) ($input['payment_method'] ?? '');
$allowed = ['bank_transfer', 'ewallet', 'cod'];

if (!$orderId || !in_array($method, $allowed, true)) {
    jsonResponse(false, 'Metode pembayaran tidak valid.', null, 422);
}

$pdo  = db();
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id=? AND user_id=?');
$stmt->execute([$orderId, $user['id']]);
$order = $stmt->fetch();

if (!$order) jsonResponse(false, 'Pesanan tidak ditemukan.', null, 404);

// Bug #8: validasi status order — hanya boleh bayar jika sudah approved
$payableStatuses = ['approved', 'waiting_payment'];
if (!in_array($order['status'], $payableStatuses, true)) {
    jsonResponse(false, 'Pesanan belum disetujui admin atau sudah tidak dapat dibayar.', null, 422);
}

// Bug #8: cek apakah sudah ada payment aktif (bukan rejected)
$existing = $pdo->prepare(
    "SELECT id FROM payments WHERE order_id=? AND payment_status != 'rejected'"
);
$existing->execute([$orderId]);
if ($existing->fetchColumn()) {
    jsonResponse(false, 'Pembayaran untuk pesanan ini sudah ada. Tunggu verifikasi admin.', null, 422);
}

$paymentStatus = $method === 'cod' ? 'paid' : 'waiting_verification';
$orderStatus   = $method === 'cod' ? 'paid' : 'waiting_payment';

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare(
        'INSERT INTO payments (order_id,payment_method,payment_status,amount) VALUES (?,?,?,?)'
    );
    $stmt->execute([$orderId, $method, $paymentStatus, $order['total']]);

    $pdo->prepare(
        'UPDATE orders SET payment_status=?,status=? WHERE id=?'
    )->execute([$paymentStatus, $orderStatus, $orderId]);

    // Notifikasi ke customer
    $msg = $method === 'cod'
        ? "Pembayaran COD untuk pesanan #{$orderId} dikonfirmasi."
        : "Bukti pembayaran pesanan #{$orderId} sedang diverifikasi admin.";
    $pdo->prepare(
        'INSERT INTO notifications (user_id,title,message) VALUES (?,?,?)'
    )->execute([$user['id'], 'Status pembayaran', $msg]);

    $pdo->commit();
} catch (Throwable $error) {
    $pdo->rollBack();
    jsonResponse(false, $error->getMessage(), null, 500);
}

jsonResponse(true, 'Metode pembayaran disimpan.', ['payment_status' => $paymentStatus]);
