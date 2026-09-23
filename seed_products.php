<?php
declare(strict_types=1);
require __DIR__ . '/config/database.php';

function slugify(string $value): string
{
    return trim((string) preg_replace('/[^a-z0-9]+/i', '-', strtolower($value)), '-');
}

$source = file_get_contents(__DIR__ . '/data.js');
if (!preg_match('/const products\s*=\s*(\[.*\]);\s*$/s', $source, $match)) exit("Format data.js tidak dikenali.\n");
$products = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
$pdo = db();
$pdo->beginTransaction();
$category = $pdo->prepare('INSERT INTO categories (name, slug) VALUES (?, ?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)');
$product = $pdo->prepare('INSERT INTO products (category_id, name, slug, description, price_per_day, stock, image) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE price_per_day=VALUES(price_per_day), image=VALUES(image), status="active"');
foreach ($products as $item) {
    $name = trim((string) $item['category']);
    $name = str_replace('Lain – Lain', 'Lain-Lain', $name);
    $category->execute([$name, slugify($name)]);
    $categoryId = (int) $pdo->lastInsertId();
    $product->execute([$categoryId, $item['name'], slugify($item['name']), 'Perlengkapan outdoor Urban Adventure yang bersih dan terawat.', $item['price'], 5, $item['image']]);
}
$pdo->commit();
echo count($products) . " produk berhasil diimpor.\n";
