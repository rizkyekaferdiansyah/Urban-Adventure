<?php
declare(strict_types=1);
require __DIR__ . '/config/database.php';
if (PHP_SAPI !== 'cli') exit("Jalankan dari terminal.\n");
[$script, $name, $email, $password] = array_pad($argv, 4, null);
if (!$name || !$email || !$password) exit("Usage: php create_admin.php \"Admin\" admin@example.com password\n");
$statement = db()->prepare("INSERT INTO users (name,email,phone,password,role) VALUES (?,?,?,?, 'admin')");
$statement->execute([$name, $email, '-', password_hash($password, PASSWORD_DEFAULT)]);
echo "Admin berhasil dibuat.\n";
