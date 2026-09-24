<?php
require __DIR__ . '/bootstrap.php';
$action = $_GET['action'] ?? $_SERVER['REQUEST_METHOD'];
$input = body();
if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf($input);
    $name = trim((string)($input['name'] ?? '')); $email = strtolower(trim((string)($input['email'] ?? ''))); $phone = trim((string)($input['phone'] ?? '')); $password = (string)($input['password'] ?? '');
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '' || strlen($password) < 6 || $password !== ($input['password_confirmation'] ?? '')) jsonResponse(false, 'Data registrasi tidak valid.', null, 422);
    try { $stmt = db()->prepare('INSERT INTO users (name,email,phone,password) VALUES (?,?,?,?)'); $stmt->execute([$name,$email,$phone,password_hash($password,PASSWORD_DEFAULT)]); } catch (PDOException $e) { jsonResponse(false, 'Email sudah terdaftar.', null, 422); }
    session_regenerate_id(true);
    $_SESSION['user'] = ['id'=>(int)db()->lastInsertId(),'name'=>$name,'email'=>$email,'phone'=>$phone,'role'=>'customer'];
    jsonResponse(true, 'Registrasi berhasil.', $_SESSION['user'], 201);
}
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf($input); $stmt=db()->prepare('SELECT id,name,email,phone,password,role FROM users WHERE email=? AND status="active"'); $stmt->execute([strtolower(trim((string)($input['email'] ?? '')))]); $record=$stmt->fetch();
    if (!$record || !password_verify((string)($input['password'] ?? ''), $record['password'])) jsonResponse(false, 'Email atau password salah.', null, 422);
    unset($record['password']); session_regenerate_id(true); $_SESSION['user']=$record; jsonResponse(true, 'Login berhasil.', $record);
}
if ($action === 'logout') { session_destroy(); if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'text/html')) { header('Location: ../public/login.php'); exit; } jsonResponse(true, 'Logout berhasil.'); }
jsonResponse(false, 'Endpoint auth tidak ditemukan.', null, 404);
