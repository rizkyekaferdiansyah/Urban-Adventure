<?php
require __DIR__ . '/bootstrap.php';

$user = requireCustomer();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method tidak diizinkan.', null, 405);
}

$input  = body();
requireCsrf($input);

$orderId       = (int) ($input['order_id'] ?? 0);
$paymentMethod = (string) ($input['payment_method'] ?? '');
$allowed       = ['bank_transfer', 'qris', 'cod'];

if (!$orderId || !in_array($paymentMethod, $allowed, true)) {
    jsonResponse(false, 'Metode pembayaran tidak valid.', null, 422);
}

$pdo  = db();
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id=? AND user_id=?');
$stmt->execute([$orderId, $user['id']]);
$order = $stmt->fetch();

if (!$order) jsonResponse(false, 'Pesanan tidak ditemukan.', null, 404);

// Order sudah approved otomatis — izinkan bayar dari pending/approved/waiting_payment
$payableStatuses = ['pending', 'approved', 'waiting_payment'];
if (!in_array($order['status'], $payableStatuses, true)) {
    jsonResponse(false, 'Pesanan sudah tidak dapat dibayar.', null, 422);
}

// Cegah duplikat — jika sudah ada payment aktif (bukan rejected), tolak
$existing = $pdo->prepare(
    "SELECT id FROM payments WHERE order_id=? AND payment_status NOT IN ('rejected')"
);
$existing->execute([$orderId]);
if ($existing->fetchColumn()) {
    jsonResponse(false, 'Pembayaran untuk pesanan ini sudah tercatat. Tunggu verifikasi admin.', null, 422);
}

$paymentStatus = $paymentMethod === 'cod' ? 'paid' : 'waiting_verification';
$orderStatus   = $paymentMethod === 'cod' ? 'paid'  : 'waiting_payment';

$pdo->beginTransaction();
try {
    $pdo->prepare(
        'INSERT INTO payments (order_id,payment_method,payment_status,amount) VALUES (?,?,?,?)'
    )->execute([$orderId, $paymentMethod, $paymentStatus, $order['total']]);

    $pdo->prepare(
        'UPDATE orders SET payment_status=?,status=? WHERE id=?'
    )->execute([$paymentStatus, $orderStatus, $orderId]);

    // Notifikasi ke customer
    $methodLabel = ['bank_transfer' => 'Transfer Bank', 'qris' => 'QRIS', 'cod' => 'Bayar di Tempat'];
    $label = $methodLabel[$paymentMethod] ?? $paymentMethod;

    if ($paymentMethod === 'cod') {
        $notifTitle = 'Pembayaran COD dikonfirmasi';
        $notifMsg   = "Pesanan {$order['order_code']}: pembayaran COD senilai Rp " . number_format((float)$order['total'], 0, ',', '.') . " telah dikonfirmasi.";
        $eventType  = 'payment_cod_confirmed';
    } else {
        $notifTitle = 'Menunggu bukti pembayaran';
        $notifMsg   = "Pesanan {$order['order_code']}: metode {$label} dipilih. Silakan upload bukti transfer ke rekening admin.";
        $eventType  = 'payment_method_selected';
    }

    insertNotification(
        $pdo, $user['id'],
        $notifTitle,
        $notifMsg,
        $orderId, $eventType
    );

    $pdo->commit();
} catch (Throwable $error) {
    $pdo->rollBack();
    jsonResponse(false, $error->getMessage(), null, 500);
}

jsonResponse(true, 'Metode pembayaran disimpan.', [
    'payment_status' => $paymentStatus,
    'order_status'   => $orderStatus,
    'need_upload'    => $paymentMethod !== 'cod',
]);
