<?php
require __DIR__ . '/bootstrap.php';

$user = requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method tidak diizinkan.', null, 405);
}

// CSRF via POST field (multipart form)
if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) {
    jsonResponse(false, 'CSRF token tidak valid.', null, 419);
}

$orderId = (int) ($_POST['order_id'] ?? 0);
$file    = $_FILES['image'] ?? null;

if (!$orderId) {
    jsonResponse(false, 'ID pesanan tidak valid.', null, 422);
}

if (!$file || $file['error'] !== UPLOAD_ERR_OK || $file['size'] > 3 * 1024 * 1024) {
    jsonResponse(false, 'File bukti pembayaran tidak valid (maks. 3 MB).', null, 422);
}

$mime       = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
$extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
if (!isset($extensions[$mime])) {
    jsonResponse(false, 'Format file harus JPG, PNG, atau WEBP.', null, 422);
}

$pdo = db();

// Validasi: order harus milik user dan dalam status yang bisa upload bukti
$stmt = $pdo->prepare(
    "SELECT id, status, payment_status FROM orders WHERE id=? AND user_id=?"
);
$stmt->execute([$orderId, $user['id']]);
$order = $stmt->fetch();

if (!$order) {
    jsonResponse(false, 'Pesanan tidak ditemukan.', null, 404);
}

if (!in_array($order['payment_status'], ['unpaid', 'waiting_verification', 'rejected'], true)) {
    jsonResponse(false, 'Bukti pembayaran tidak dapat diunggah untuk status ini.', null, 422);
}

// Pastikan ada row di tabel payments untuk order ini
$paymentRow = $pdo->prepare(
    "SELECT id FROM payments WHERE order_id=? AND payment_status != 'paid' ORDER BY id DESC LIMIT 1"
);
$paymentRow->execute([$orderId]);
$payment = $paymentRow->fetch();

if (!$payment) {
    jsonResponse(false, 'Pilih metode pembayaran terlebih dahulu.', null, 422);
}

// Simpan file ke uploads/payments/
$directory = __DIR__ . '/../uploads/payments';
if (!is_dir($directory)) mkdir($directory, 0750, true);

$filename = 'proof-' . $orderId . '-' . bin2hex(random_bytes(8)) . '.' . $extensions[$mime];
$filePath = $directory . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    jsonResponse(false, 'Gagal menyimpan file.', null, 500);
}

// Simpan path relatif dari root project
$relativePath = 'uploads/payments/' . $filename;

// Update payment row yang spesifik (berdasarkan ID, bukan ORDER BY LIMIT)
$pdo->prepare(
    "UPDATE payments SET proof_image=?, payment_status='waiting_verification' WHERE id=?"
)->execute([$relativePath, (int) $payment['id']]);

$pdo->prepare(
    "UPDATE orders SET payment_status='waiting_verification' WHERE id=?"
)->execute([$orderId]);

// Notifikasi admin (insert ke notif user admin pertama, atau cukup ke user sendiri)
$pdo->prepare(
    'INSERT INTO notifications (user_id,title,message) VALUES (?,?,?)'
)->execute([
    $user['id'],
    'Bukti pembayaran diunggah',
    "Bukti pembayaran untuk pesanan #{$orderId} sedang menunggu verifikasi admin.",
]);

jsonResponse(true, 'Bukti pembayaran berhasil diunggah. Menunggu verifikasi admin.', [
    'path' => $relativePath,
]);
