<?php
/**
 * Lightweight polling endpoint.
 * Mengembalikan "fingerprint" state terkini.
 * Untuk admin: fingerprint berdasarkan semua order dari semua user.
 * Untuk customer: fingerprint berdasarkan order dan notifikasi milik sendiri.
 */
require __DIR__ . '/bootstrap.php';

$user = user();

// Tidak login → kembalikan state kosong
if (!$user) {
    jsonResponse(true, '', [
        'fingerprint'          => 'guest',
        'unread_notifications' => 0,
        'cart_count'           => 0,
        'role'                 => 'guest',
    ]);
}

$pdo  = db();
$role = $user['role'];

if ($role === 'admin') {
    // ── Admin: pantau semua pesanan dari semua user ───────────────────────────

    // Timestamp pesanan terbaru yang masuk atau berubah status (semua user)
    $stmt = $pdo->prepare(
        "SELECT COALESCE(MAX(updated_at), '1970-01-01') FROM orders"
    );
    $stmt->execute();
    $lastOrderUpdate = $stmt->fetchColumn();

    // Jumlah pesanan pending yang perlu perhatian admin
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM orders WHERE status IN ('pending','approved','waiting_payment')"
    );
    $stmt->execute();
    $pendingCount = (int) $stmt->fetchColumn();

    // Jumlah pembayaran menunggu verifikasi
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM orders WHERE payment_status = 'waiting_verification'"
    );
    $stmt->execute();
    $waitingPayment = (int) $stmt->fetchColumn();

    $fingerprint = md5($lastOrderUpdate . '|' . $pendingCount . '|' . $waitingPayment);

    jsonResponse(true, '', [
        'fingerprint'          => $fingerprint,
        'unread_notifications' => 0,     // admin tidak pakai notif bell
        'cart_count'           => 0,
        'pending_orders'       => $pendingCount,
        'waiting_payment'      => $waitingPayment,
        'last_order_update'    => $lastOrderUpdate,
        'role'                 => 'admin',
    ]);

} else {
    // ── Customer: pantau hanya data milik sendiri ─────────────────────────────

    // Notifikasi belum dibaca
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0'
    );
    $stmt->execute([$user['id']]);
    $unread = (int) $stmt->fetchColumn();

    // Timestamp pesanan customer ini yang terakhir berubah
    $stmt = $pdo->prepare(
        "SELECT COALESCE(MAX(updated_at), '1970-01-01') FROM orders WHERE user_id=?"
    );
    $stmt->execute([$user['id']]);
    $lastOrderUpdate = $stmt->fetchColumn();

    // Jumlah item keranjang
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM cart WHERE user_id=?');
    $stmt->execute([$user['id']]);
    $cartCount = (int) $stmt->fetchColumn();

    $fingerprint = md5($unread . '|' . $lastOrderUpdate . '|' . $cartCount);

    jsonResponse(true, '', [
        'fingerprint'          => $fingerprint,
        'unread_notifications' => $unread,
        'cart_count'           => $cartCount,
        'last_order_update'    => $lastOrderUpdate,
        'role'                 => 'customer',
    ]);
}
