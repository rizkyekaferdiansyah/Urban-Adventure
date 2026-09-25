<?php
require __DIR__ . '/bootstrap.php';
$action = $_GET['action'] ?? $_SERVER['REQUEST_METHOD'];
$input  = body();

// ─── Rate-limit helper (session-based, 5 attempts per 15 min) ────────────────
function checkLoginRateLimit(): void
{
    $now     = time();
    $window  = 15 * 60; // 15 minutes
    $maxTries = 5;

    $_SESSION['login_attempts'] ??= [];
    // Prune attempts older than the window
    $_SESSION['login_attempts'] = array_filter(
        $_SESSION['login_attempts'],
        fn($t) => ($now - $t) < $window
    );

    if (count($_SESSION['login_attempts']) >= $maxTries) {
        $wait = $window - ($now - min($_SESSION['login_attempts']));
        jsonResponse(false, "Terlalu banyak percobaan login. Coba lagi dalam " . ceil($wait / 60) . " menit.", null, 429);
    }
}

function recordLoginFailure(): void
{
    $_SESSION['login_attempts'][] = time();
}

function clearLoginAttempts(): void
{
    $_SESSION['login_attempts'] = [];
}

// ─── Register ─────────────────────────────────────────────────────────────────
if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf($input);
    $name     = trim((string) ($input['name'] ?? ''));
    $email    = strtolower(trim((string) ($input['email'] ?? '')));
    $phone    = trim((string) ($input['phone'] ?? ''));
    $password = (string) ($input['password'] ?? '');

    if (
        $name === ''
        || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || $phone === ''
        || strlen($password) < 6
        || $password !== ($input['password_confirmation'] ?? '')
    ) {
        jsonResponse(false, 'Data registrasi tidak valid.', null, 422);
    }

    try {
        $stmt = db()->prepare('INSERT INTO users (name,email,phone,password) VALUES (?,?,?,?)');
        $stmt->execute([$name, $email, $phone, password_hash($password, PASSWORD_DEFAULT)]);
    } catch (PDOException) {
        jsonResponse(false, 'Email sudah terdaftar.', null, 422);
    }

    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'    => (int) db()->lastInsertId(),
        'name'  => $name,
        'email' => $email,
        'phone' => $phone,
        'role'  => 'customer',
    ];
    jsonResponse(true, 'Registrasi berhasil.', $_SESSION['user'], 201);
}

// ─── Login ────────────────────────────────────────────────────────────────────
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf($input);
    checkLoginRateLimit();

    $stmt = db()->prepare('SELECT id,name,email,phone,password,role,status FROM users WHERE email=?');
    $stmt->execute([strtolower(trim((string) ($input['email'] ?? '')))]);
    $record = $stmt->fetch();

    if (!$record || !password_verify((string) ($input['password'] ?? ''), $record['password'])) {
        recordLoginFailure();
        jsonResponse(false, 'Email atau password salah.', null, 422);
    }

    if ($record['status'] !== 'active') {
        jsonResponse(false, 'Akun Anda telah dinonaktifkan. Hubungi admin.', null, 403);
    }

    clearLoginAttempts();
    unset($record['password']);
    session_regenerate_id(true);
    $_SESSION['user'] = $record;
    jsonResponse(true, 'Login berhasil.', $record);
}

// ─── Logout ───────────────────────────────────────────────────────────────────
if ($action === 'logout') {
    session_destroy();
    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'text/html')) {
        header('Location: ../public/login.php');
        exit;
    }
    jsonResponse(true, 'Logout berhasil.');
}

jsonResponse(false, 'Endpoint auth tidak ditemukan.', null, 404);
