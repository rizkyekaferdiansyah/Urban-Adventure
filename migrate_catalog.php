<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';

$pdo = db();

$columns = [
    'sku' => 'VARCHAR(80) NULL UNIQUE AFTER slug',
    'short_description' => 'VARCHAR(500) NULL AFTER description',
    'weekend_price' => 'DECIMAL(12,2) NULL AFTER price_per_day',
    'deposit' => 'DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER weekend_price',
    'unit' => "VARCHAR(40) NOT NULL DEFAULT 'unit' AFTER stock",
    'minimum_rental_days' => 'INT UNSIGNED NOT NULL DEFAULT 1 AFTER unit',
    'maximum_rental_days' => 'INT UNSIGNED NULL AFTER minimum_rental_days',
    'late_fee' => 'DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER maximum_rental_days',
    'damage_fee' => 'DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER late_fee',
    'lost_fee' => 'DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER damage_fee',
    'rental_terms' => 'TEXT NULL AFTER lost_fee',
    'return_terms' => 'TEXT NULL AFTER rental_terms',
    'usage_terms' => 'TEXT NULL AFTER return_terms',
    'deleted_at' => 'TIMESTAMP NULL AFTER updated_at',
];

$existing = $pdo->prepare('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
$existing->execute(['products']);
$known = array_fill_keys($existing->fetchAll(PDO::FETCH_COLUMN), true);

foreach ($columns as $name => $definition) {
    if (!isset($known[$name])) $pdo->exec("ALTER TABLE products ADD COLUMN {$name} {$definition}");
}

$existing->execute(['categories']);
$categoryColumns = array_fill_keys($existing->fetchAll(PDO::FETCH_COLUMN), true);
if (!isset($categoryColumns['status'])) {
    $pdo->exec("ALTER TABLE categories ADD COLUMN status ENUM('active','inactive') NOT NULL DEFAULT 'active'");
}
$pdo->exec("CREATE TABLE IF NOT EXISTS product_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_product_images_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_images_product (product_id, sort_order)
) ENGINE=InnoDB");
$pdo->exec("CREATE TABLE IF NOT EXISTS admin_audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id INT UNSIGNED NOT NULL,
    action VARCHAR(80) NOT NULL,
    target_type VARCHAR(80) NOT NULL,
    target_id INT UNSIGNED NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_admin FOREIGN KEY (admin_id) REFERENCES users(id)
) ENGINE=InnoDB");

$pdo->exec("UPDATE products SET sku=CONCAT('UA-', LPAD(id, 5, '0')) WHERE sku IS NULL OR sku=''");
$pdo->exec("ALTER TABLE products MODIFY sku VARCHAR(80) NOT NULL UNIQUE");

echo "Catalog migration completed.\n";
