<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) return $pdo;

    $host = getenv('UA_DB_HOST') ?: '127.0.0.1';
    $port = getenv('UA_DB_PORT') ?: '8889';
    $name = getenv('UA_DB_NAME') ?: 'urban_adventure';
    $user = getenv('UA_DB_USER') ?: 'root';
    $pass = getenv('UA_DB_PASS') ?: 'root';

    // Security #12: warn jika masih menggunakan kredensial default di environment non-development
    if (
        $user === 'root'
        && $pass === 'root'
        && !in_array($host, ['127.0.0.1', 'localhost'], true) === false
        && getenv('UA_ENV') === 'production'
    ) {
        error_log('[Urban Adventure] PERINGATAN: Menggunakan kredensial database default di lingkungan production!');
    }

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        return $pdo;
    } catch (PDOException) {
        http_response_code(503);
        exit('Database belum terhubung. Atur UA_DB_HOST, UA_DB_NAME, UA_DB_USER, UA_DB_PASS, dan UA_DB_PORT lalu import database.sql.');
    }
}
