<?php
require __DIR__ . '/bootstrap.php';

// ─── GET: ambil session + CSRF ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    jsonResponse(true, '', ['user' => user(), 'csrf' => csrfToken()]);
}

// ─── PUT: update profile (nama, phone, password) ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $user  = requireLogin();
    $input = body();
    requireCsrf($input);

    $pdo    = db();
    $fields = [];
    $values = [];

    $name  = trim((string) ($input['name'] ?? ''));
    $phone = trim((string) ($input['phone'] ?? ''));

    if ($name !== '') {
        if (mb_strlen($name) > 120) jsonResponse(false, 'Nama maksimal 120 karakter.', null, 422);
        $fields[] = 'name=?';
        $values[] = $name;
    }
    if ($phone !== '') {
        $fields[] = 'phone=?';
        $values[] = $phone;
    }

    // Ganti password jika disertakan
    $newPassword = (string) ($input['new_password'] ?? '');
    if ($newPassword !== '') {
        if (strlen($newPassword) < 6) {
            jsonResponse(false, 'Password baru minimal 6 karakter.', null, 422);
        }
        // Verifikasi password lama
        $currentPassword = (string) ($input['current_password'] ?? '');
        $stmt = $pdo->prepare('SELECT password FROM users WHERE id=?');
        $stmt->execute([$user['id']]);
        $hash = $stmt->fetchColumn();
        if (!$hash || !password_verify($currentPassword, $hash)) {
            jsonResponse(false, 'Password saat ini tidak benar.', null, 422);
        }
        $fields[] = 'password=?';
        $values[] = password_hash($newPassword, PASSWORD_DEFAULT);
    }

    if (!$fields) jsonResponse(false, 'Tidak ada data yang diubah.', null, 422);

    $values[] = $user['id'];
    $pdo->prepare('UPDATE users SET ' . implode(',', $fields) . ' WHERE id=?')->execute($values);

    // Refresh data session
    $stmt = $pdo->prepare('SELECT id,name,email,phone,role FROM users WHERE id=?');
    $stmt->execute([$user['id']]);
    $updated = $stmt->fetch();
    $_SESSION['user'] = $updated;

    jsonResponse(true, 'Profil berhasil diperbarui.', $updated);
}

jsonResponse(false, 'Method tidak diizinkan.', null, 405);
