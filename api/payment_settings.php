<?php
/**
 * api/payment_settings.php
 *
 * GET  — Publik. Mengembalikan semua setting sebagai key→value map.
 * PUT  — Admin. Update satu atau lebih key (JSON body).
 * POST — Admin. Upload gambar QRIS (multipart, resource=qris-image).
 */
require __DIR__ . '/bootstrap.php';

$pdo    = db();
$method = $_SERVER['REQUEST_METHOD'];

// ─── Helper: ambil semua setting sebagai key→value ────────────────────────────
function allSettings(PDO $pdo): array
{
    $rows = $pdo->query('SELECT `key`, value FROM payment_settings')->fetchAll();
    $map  = [];
    foreach ($rows as $row) {
        $map[$row['key']] = $row['value'];
    }
    return $map;
}

// ─── Helper: upsert satu key ──────────────────────────────────────────────────
function setSetting(PDO $pdo, string $key, string $value): void
{
    $pdo->prepare(
        'INSERT INTO payment_settings (`key`, value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE value = VALUES(value)'
    )->execute([$key, $value]);
}

// ─── GET: publik, tidak perlu login ──────────────────────────────────────────
if ($method === 'GET') {
    try {
        jsonResponse(true, '', allSettings($pdo));
    } catch (PDOException $e) {
        // Tabel belum ada — kembalikan default kosong agar UI tidak crash
        jsonResponse(true, '', [
            'bank_name'           => '',
            'bank_account_number' => '',
            'bank_account_name'   => '',
            'bank_is_active'      => '1',
            'qris_image_path'     => '',
            'qris_is_active'      => '0',
            '_error'              => 'Tabel payment_settings belum ada. Jalankan migrate_payment_settings.php',
        ]);
    }
}

// ─── PUT: update setting (admin only) ────────────────────────────────────────
if ($method === 'PUT') {
    requireAdmin();
    $input = body();
    requireCsrf($input);

    $allowed = [
        'bank_name', 'bank_account_number', 'bank_account_name',
        'bank_is_active', 'qris_is_active',
    ];

    $updated = 0;
    foreach ($allowed as $key) {
        if (array_key_exists($key, $input)) {
            $val = match ($key) {
                'bank_is_active', 'qris_is_active' => $input[$key] ? '1' : '0',
                default => trim((string) $input[$key]),
            };
            setSetting($pdo, $key, $val);
            $updated++;
        }
    }

    if (!$updated) {
        jsonResponse(false, 'Tidak ada data yang diperbarui.', null, 422);
    }

    jsonResponse(true, 'Pengaturan pembayaran disimpan.', allSettings($pdo));
}

// ─── POST: upload gambar QRIS (admin only) ────────────────────────────────────
if ($method === 'POST') {
    requireAdmin();

    // CSRF via POST field (multipart)
    if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) {
        jsonResponse(false, 'CSRF token tidak valid.', null, 419);
    }

    $file = $_FILES['qris_image'] ?? null;

    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(false, 'File QRIS tidak valid.', null, 422);
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        jsonResponse(false, 'Ukuran file maksimal 2 MB.', null, 422);
    }

    $mime       = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    if (!isset($extensions[$mime])) {
        jsonResponse(false, 'Format file harus JPG atau PNG.', null, 422);
    }

    $dir = __DIR__ . '/../uploads/qris';
    if (!is_dir($dir)) mkdir($dir, 0750, true);

    // Hapus file lama jika ada
    $current = allSettings($pdo)['qris_image_path'] ?? '';
    if ($current && is_file(__DIR__ . '/../' . $current)) {
        unlink(__DIR__ . '/../' . $current);
    }

    $filename = 'qris-' . bin2hex(random_bytes(8)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
        jsonResponse(false, 'Gagal menyimpan gambar QRIS.', null, 500);
    }

    $relativePath = 'uploads/qris/' . $filename;
    setSetting($pdo, 'qris_image_path', $relativePath);

    jsonResponse(true, 'Gambar QRIS berhasil diperbarui.', [
        'qris_image_path' => $relativePath,
        'settings'        => allSettings($pdo),
    ]);
}

jsonResponse(false, 'Method tidak diizinkan.', null, 405);
