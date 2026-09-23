<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
session_start();

function jsonResponse(bool $success, string $message = '', mixed $data = null, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function body(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '', true);
    return is_array($data) ? $data : $_POST;
}

function csrfToken(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function requireCsrf(array $input): void
{
    $token = $input['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals($_SESSION['csrf'] ?? '', (string) $token)) jsonResponse(false, 'CSRF token tidak valid.', null, 419);
}

function user(): ?array { return $_SESSION['user'] ?? null; }
function requireLogin(): array { $user = user(); if (!$user) jsonResponse(false, 'Silakan login terlebih dahulu.', null, 401); return $user; }
function requireAdmin(): array { $user = requireLogin(); if ($user['role'] !== 'admin') jsonResponse(false, 'Akses admin diperlukan.', null, 403); return $user; }
function slugify(string $value): string { return trim(preg_replace('/[^a-z0-9]+/i', '-', strtolower($value)), '-'); }
function validDates(string $start, string $end): bool
{
    $from = DateTime::createFromFormat('Y-m-d', $start);
    $to = DateTime::createFromFormat('Y-m-d', $end);
    return $from && $to && $from->format('Y-m-d') === $start && $to->format('Y-m-d') === $end && $to >= $from;
}
function rentalDays(string $start, string $end): int { return max(1, (int) (new DateTime($start))->diff(new DateTime($end))->days); }
function checkAvailability(PDO $pdo, int $productId, string $start, string $end, int $quantity, ?int $ignoreOrder = null): bool
{
        $statement = $pdo->prepare("SELECT stock AS available FROM products WHERE id = ? AND status = 'active'");
        $statement->execute([$productId]);
    return (int) ($statement->fetchColumn() ?: 0) >= $quantity;
}
