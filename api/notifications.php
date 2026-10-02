<?php
require __DIR__ . '/bootstrap.php';

$user   = requireCustomer();
$pdo    = db();
$method = $_SERVER['REQUEST_METHOD'];

// ─── GET: Ambil daftar notifikasi ─────────────────────────────────────────────
if ($method === 'GET') {
    // Ambil langsung — tidak perlu GROUP BY lagi karena
    // insertNotification() sudah mencegah duplikat via ON DUPLICATE KEY UPDATE
    $stmt = $pdo->prepare(
        'SELECT id, title, message, is_read, created_at
         FROM notifications
         WHERE user_id=?
         ORDER BY created_at DESC
         LIMIT 30'
    );
    $stmt->execute([$user['id']]);
    jsonResponse(true, '', $stmt->fetchAll());
}

// ─── PUT: Tandai sudah dibaca ─────────────────────────────────────────────────
if ($method === 'PUT') {
    $input = body();
    requireCsrf($input);
    $id = (int) ($_GET['id'] ?? 0);

    if ($id) {
        $pdo->prepare('UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?')
            ->execute([$id, $user['id']]);
    } else {
        // Tandai semua sebagai dibaca
        $pdo->prepare('UPDATE notifications SET is_read=1 WHERE user_id=? AND is_read=0')
            ->execute([$user['id']]);
    }

    jsonResponse(true, 'Notifikasi ditandai telah dibaca.');
}

// ─── DELETE: Hapus notifikasi (satu atau semua) ───────────────────────────────
if ($method === 'DELETE') {
    $input = body();
    requireCsrf($input);
    $id = (int) ($_GET['id'] ?? 0);

    if ($id) {
        // Hapus satu notifikasi milik user ini
        $pdo->prepare('DELETE FROM notifications WHERE id=? AND user_id=?')
            ->execute([$id, $user['id']]);
        jsonResponse(true, 'Notifikasi dihapus.');
    }

    // Hapus semua notifikasi user
    $pdo->prepare('DELETE FROM notifications WHERE user_id=?')->execute([$user['id']]);
    jsonResponse(true, 'Semua notifikasi dihapus.');
}

jsonResponse(false, 'Method tidak diizinkan.', null, 405);
