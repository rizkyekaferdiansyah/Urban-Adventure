<?php
require __DIR__ . '/bootstrap.php';

requireAdmin();
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$input = body();
$resource = $_GET['resource'] ?? 'dashboard';

function auditAdmin(PDO $pdo, int $adminId, string $action, string $target, ?int $targetId, string $description): void
{
    $statement = $pdo->prepare('INSERT INTO admin_audit_logs (admin_id, action, target_type, target_id, description) VALUES (?,?,?,?,?)');
    $statement->execute([$adminId, $action, $target, $targetId, $description]);
}

function uniqueProductSlug(PDO $pdo, string $name, int $ignoreId = 0): string
{
    $base = slugify($name) ?: 'produk';
    $slug = $base;
    $suffix = 2;
    while (true) {
        $query = 'SELECT id FROM products WHERE slug=?' . ($ignoreId ? ' AND id<>?' : '');
        $statement = $pdo->prepare($query);
        $statement->execute($ignoreId ? [$slug, $ignoreId] : [$slug]);
        if (!$statement->fetchColumn()) return $slug;
        $slug = $base . '-' . $suffix++;
    }
}

function validateProductInput(array $input, bool $create = false): array
{
    $name = trim((string) ($input['name'] ?? ''));
    $category = (int) ($input['category_id'] ?? 0);
    $price = (float) ($input['price_per_day'] ?? 0);
    $stock = (int) ($input['stock'] ?? 0);
    $minimum = (int) ($input['minimum_rental_days'] ?? 1);
    $maximum = ($input['maximum_rental_days'] ?? '') === '' ? null : (int) $input['maximum_rental_days'];
    if (($create || array_key_exists('name', $input)) && ($name === '' || mb_strlen($name) > 180)) jsonResponse(false, 'Nama produk wajib diisi dan maksimal 180 karakter.', null, 422);
    if (($create || array_key_exists('category_id', $input)) && $category <= 0) jsonResponse(false, 'Kategori produk wajib dipilih.', null, 422);
    if (($create || array_key_exists('price_per_day', $input)) && $price < 0) jsonResponse(false, 'Harga tidak boleh negatif.', null, 422);
    if (($create || array_key_exists('stock', $input)) && $stock < 0) jsonResponse(false, 'Stok tidak boleh negatif.', null, 422);
    if (($create || array_key_exists('minimum_rental_days', $input)) && $minimum < 1) jsonResponse(false, 'Minimal rental harus minimal 1 hari.', null, 422);
    if ($maximum !== null && $maximum < $minimum) jsonResponse(false, 'Maksimal rental harus sama atau lebih besar dari minimal rental.', null, 422);
    return [$name, $category, $price, $stock, $minimum, $maximum];
}

if ($resource === 'dashboard' && $method === 'GET') {
    $stats = [
        'products' => (int) $pdo->query("SELECT COUNT(*) FROM products WHERE status='active'")->fetchColumn(),
        'customers' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn(),
        'orders' => (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
        'pending' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn(),
        'running' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('paid','ongoing')")->fetchColumn(),
        'revenue' => (float) $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status='paid'")->fetchColumn(),
        'waiting_payments' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status='waiting_verification'")->fetchColumn(),
    ];
    $latest = $pdo->query('SELECT o.order_code, o.total, o.status, o.created_at, u.name customer FROM orders o JOIN users u ON u.id=o.user_id ORDER BY o.created_at DESC LIMIT 5')->fetchAll();
    $stats['latest'] = $latest;
    jsonResponse(true, '', $stats);
}

if ($resource === 'orders' && $method === 'GET') {
    $statement = $pdo->query('SELECT o.*,u.name customer,u.email FROM orders o JOIN users u ON u.id=o.user_id ORDER BY o.created_at DESC');
    jsonResponse(true, '', $statement->fetchAll());
}

if ($resource === 'customers' && $method === 'GET') {
    $statement = $pdo->query("SELECT u.id,u.name,u.email,u.phone,u.status,u.created_at,COUNT(o.id) orders_count FROM users u LEFT JOIN orders o ON o.user_id=u.id WHERE u.role='customer' GROUP BY u.id ORDER BY u.created_at DESC");
    jsonResponse(true, '', $statement->fetchAll());
}

if ($resource === 'orders' && $method === 'PUT') {
    requireCsrf($input);
    $id = (int) ($_GET['id'] ?? 0);
    $status = (string) ($input['status'] ?? '');
    $payment = (string) ($input['payment_status'] ?? '');
    $validStatuses = ['pending', 'approved', 'waiting_payment', 'paid', 'ongoing', 'completed', 'cancelled'];
    if ($status && !in_array($status, $validStatuses, true)) jsonResponse(false, 'Status tidak valid.', null, 422);
    if ($payment && !in_array($payment, ['unpaid', 'waiting_verification', 'paid', 'rejected'], true)) jsonResponse(false, 'Status pembayaran tidak valid.', null, 422);

    $current = $pdo->prepare('SELECT status FROM orders WHERE id=?');
    $current->execute([$id]);
    $oldStatus = $current->fetchColumn();
    if ($oldStatus === false) jsonResponse(false, 'Pesanan tidak ditemukan.', null, 404);

    $sql = 'UPDATE orders SET ';
    $values = [];
    if ($status) {
        $sql .= 'status=?';
        $values[] = $status;
    }
    if ($payment) {
        if ($status) $sql .= ',';
        $sql .= 'payment_status=?';
        $values[] = $payment;
    }
    $sql .= ' WHERE id=?';
    $values[] = $id;

    $pdo->beginTransaction();
    try {
        $pdo->prepare($sql)->execute($values);
        if ($status && !in_array($oldStatus, ['cancelled', 'completed'], true) && in_array($status, ['cancelled', 'completed'], true)) {
            $items = $pdo->prepare('SELECT product_id, quantity FROM order_items WHERE order_id=? AND product_id IS NOT NULL');
            $items->execute([$id]);
            $restore = $pdo->prepare('UPDATE products SET stock=stock+? WHERE id=?');
            foreach ($items as $item) $restore->execute([(int) $item['quantity'], (int) $item['product_id']]);
        }
        $pdo->commit();
    } catch (Throwable $error) {
        $pdo->rollBack();
        jsonResponse(false, $error->getMessage(), null, 422);
    }
    jsonResponse(true, 'Pesanan diperbarui.');
}

if ($resource === 'categories' && $method === 'GET') {
    $statement = $pdo->query('SELECT c.*, COUNT(p.id) product_count FROM categories c LEFT JOIN products p ON p.category_id=c.id GROUP BY c.id ORDER BY c.name');
    jsonResponse(true, '', $statement->fetchAll());
}

if ($resource === 'categories' && $method === 'POST') {
    requireCsrf($input);
    $name = trim((string) ($input['name'] ?? ''));
    if ($name === '' || mb_strlen($name) > 150) jsonResponse(false, 'Nama kategori tidak valid.', null, 422);
    $statement = $pdo->prepare('INSERT INTO categories (name, slug) VALUES (?,?)');
    try {
        $statement->execute([$name, slugify($name)]);
    } catch (PDOException) {
        jsonResponse(false, 'Kategori sudah ada.', null, 422);
    }
    jsonResponse(true, 'Kategori dibuat.', ['id' => (int) $pdo->lastInsertId()], 201);
}

if ($resource === 'categories' && $method === 'PUT') {
    requireCsrf($input);
    $id = (int) ($_GET['id'] ?? 0);
    $name = trim((string) ($input['name'] ?? ''));
    $status = (string) ($input['status'] ?? 'active');
    if ($name === '' || !in_array($status, ['active', 'inactive'], true)) jsonResponse(false, 'Data kategori tidak valid.', null, 422);
    $statement = $pdo->prepare('UPDATE categories SET name=?,slug=?,status=? WHERE id=?');
    $statement->execute([$name, slugify($name), $status, $id]);
    jsonResponse(true, 'Kategori diperbarui.');
}

if ($resource === 'products' && $method === 'GET') {
    $id = (int) ($_GET['id'] ?? 0);
    $search = trim((string) ($_GET['search'] ?? ''));
    $status = $_GET['status'] ?? 'all';
    $query = 'SELECT p.*,c.name AS category FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE 1=1';
    $params = [];
    if ($id) { $query .= ' AND p.id=?'; $params[] = $id; }
    if ($search !== '') { $query .= ' AND (p.name LIKE ? OR p.sku LIKE ? OR c.name LIKE ?)'; $params = array_merge($params, ["%{$search}%", "%{$search}%", "%{$search}%"]); }
    if (in_array($status, ['active', 'inactive'], true)) { $query .= ' AND p.status=?'; $params[] = $status; }
    $query .= ' ORDER BY p.status DESC,c.name,p.name';
    $statement = $pdo->prepare($query);
    $statement->execute($params);
    $products = $statement->fetchAll();
    foreach ($products as &$product) {
        $images = $pdo->prepare('SELECT id,image_path,is_primary,sort_order FROM product_images WHERE product_id=? ORDER BY is_primary DESC,sort_order,id');
        $images->execute([(int) $product['id']]);
        $product['images'] = $images->fetchAll();
    }
    unset($product);
    jsonResponse(true, '', $id ? ($products[0] ?? null) : $products);
}

if ($resource === 'products' && $method === 'POST') {
    requireCsrf($input);
    [$name, $category, $price, $stock, $minimum, $maximum] = validateProductInput($input, true);
    $slug = uniqueProductSlug($pdo, $name);
    $sku = trim((string) ($input['sku'] ?? '')) ?: 'UA-' . strtoupper(substr(slugify($name), 0, 3)) . '-' . str_pad((string) ((int) $pdo->query('SELECT COALESCE(MAX(id),0)+1 FROM products')->fetchColumn()), 4, '0', STR_PAD_LEFT);
    $statement = $pdo->prepare('INSERT INTO products (category_id,name,slug,sku,description,short_description,price_per_day,weekend_price,deposit,stock,unit,minimum_rental_days,maximum_rental_days,late_fee,damage_fee,lost_fee,rental_terms,return_terms,usage_terms,image,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    try {
        $statement->execute([$category, $name, $slug, $sku, $input['description'] ?? '', $input['short_description'] ?? '', $price, $input['weekend_price'] ?? null, $input['deposit'] ?? 0, $stock, $input['unit'] ?? 'unit', $minimum, $maximum, $input['late_fee'] ?? 0, $input['damage_fee'] ?? 0, $input['lost_fee'] ?? 0, $input['rental_terms'] ?? '', $input['return_terms'] ?? '', $input['usage_terms'] ?? '', $input['image'] ?? '', $input['status'] ?? 'active']);
    } catch (PDOException) {
        jsonResponse(false, 'SKU atau data produk sudah digunakan.', null, 422);
    }
    $id = (int) $pdo->lastInsertId();
    auditAdmin($pdo, (int) user()['id'], 'CREATE_PRODUCT', 'product', $id, "Produk {$name} dibuat.");
    jsonResponse(true, 'Produk dibuat.', ['id' => $id], 201);
}

if ($resource === 'products' && $method === 'PUT') {
    requireCsrf($input);
    $id = (int) ($_GET['id'] ?? 0);
    validateProductInput($input);
    $fields = [];
    $values = [];
    if (isset($input['category_id'])) { $fields[] = 'category_id=?'; $values[] = (int) $input['category_id']; }
    if (isset($input['name'])) { $fields[] = 'name=?'; $values[] = (string) $input['name']; $fields[] = 'slug=?'; $values[] = slugify((string) $input['name']); }
    if (isset($input['description'])) { $fields[] = 'description=?'; $values[] = (string) $input['description']; }
    foreach (['short_description', 'sku', 'unit', 'rental_terms', 'return_terms', 'usage_terms'] as $field) {
        if (array_key_exists($field, $input)) { $fields[] = "{$field}=?"; $values[] = (string) $input[$field]; }
    }
    if (isset($input['price_per_day'])) { $fields[] = 'price_per_day=?'; $values[] = (float) $input['price_per_day']; }
    foreach (['weekend_price', 'deposit', 'late_fee', 'damage_fee', 'lost_fee'] as $field) {
        if (array_key_exists($field, $input)) { $fields[] = "{$field}=?"; $values[] = $input[$field] === '' ? 0 : (float) $input[$field]; }
    }
    if (isset($input['stock'])) { $fields[] = 'stock=?'; $values[] = max(0, (int) $input['stock']); }
    if (isset($input['minimum_rental_days'])) { $fields[] = 'minimum_rental_days=?'; $values[] = max(1, (int) $input['minimum_rental_days']); }
    if (array_key_exists('maximum_rental_days', $input)) { $fields[] = 'maximum_rental_days=?'; $values[] = $input['maximum_rental_days'] === '' ? null : max(1, (int) $input['maximum_rental_days']); }
    if (isset($input['image'])) { $fields[] = 'image=?'; $values[] = (string) $input['image']; }
    if (isset($input['status']) && in_array($input['status'], ['active', 'inactive'], true)) { $fields[] = 'status=?'; $values[] = (string) $input['status']; }
    if (!$fields) jsonResponse(false, 'Tidak ada data produk yang diperbarui.', null, 422);
    $values[] = $id;
    try {
        $pdo->prepare('UPDATE products SET ' . implode(',', $fields) . ' WHERE id=?')->execute($values);
    } catch (PDOException) {
        jsonResponse(false, 'SKU atau slug sudah digunakan.', null, 422);
    }
    auditAdmin($pdo, (int) user()['id'], 'UPDATE_PRODUCT', 'product', $id, 'Data produk diperbarui.');
    jsonResponse(true, 'Produk diperbarui.');
}

if ($resource === 'products' && $method === 'DELETE') {
    requireCsrf($input);
    $id = (int) ($_GET['id'] ?? 0);
    $used = $pdo->prepare('SELECT COUNT(*) FROM order_items WHERE product_id=?');
    $used->execute([$id]);
    if ((int) $used->fetchColumn() > 0) {
        $pdo->prepare("UPDATE products SET status='inactive',deleted_at=NOW() WHERE id=?")->execute([$id]);
        jsonResponse(true, 'Produk sudah pernah ditransaksikan dan dinonaktifkan.');
    }
    $pdo->prepare('DELETE FROM products WHERE id=?')->execute([$id]);
    jsonResponse(true, 'Produk dihapus.');
}

if ($resource === 'product-images' && $method === 'POST') {
    requireCsrf($_POST);
    $productId = (int) ($_POST['product_id'] ?? 0);
    $productCheck = $pdo->prepare('SELECT id FROM products WHERE id=?');
    $productCheck->execute([$productId]);
    if (!$productCheck->fetchColumn()) jsonResponse(false, 'Produk tidak ditemukan.', null, 404);
    $file = $_FILES['image'] ?? null;
    if (!$productId || !$file || $file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) jsonResponse(false, 'File gambar tidak valid.', null, 422);
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime])) jsonResponse(false, 'Format gambar harus JPG, PNG, atau WEBP.', null, 422);
    $directory = __DIR__ . '/../uploads/products/original';
    if (!is_dir($directory)) mkdir($directory, 0750, true);
    $name = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $name)) jsonResponse(false, 'Gagal menyimpan gambar.', null, 500);
    $path = 'uploads/products/original/' . $name;
    $primary = (int) $pdo->query('SELECT COUNT(*) FROM product_images WHERE product_id=' . $productId)->fetchColumn() === 0 ? 1 : 0;
    $sort = $pdo->prepare('SELECT COALESCE(MAX(sort_order),0)+1 FROM product_images WHERE product_id=?');
    $sort->execute([$productId]);
    $statement = $pdo->prepare('INSERT INTO product_images (product_id,image_path,is_primary,sort_order) VALUES (?,?,?,?)');
    $statement->execute([$productId, $path, $primary, (int) $sort->fetchColumn()]);
    $imageId = (int) $pdo->lastInsertId();
    if ($primary) $pdo->prepare('UPDATE products SET image=? WHERE id=?')->execute([$path, $productId]);
    jsonResponse(true, 'Gambar berhasil diunggah.', ['id' => $imageId, 'path' => $path], 201);
}

if ($resource === 'product-images' && $method === 'DELETE') {
    requireCsrf($input);
    $imageId = (int) ($_GET['id'] ?? 0);
    $statement = $pdo->prepare('SELECT product_id,image_path,is_primary FROM product_images WHERE id=?');
    $statement->execute([$imageId]);
    $image = $statement->fetch();
    if (!$image) jsonResponse(false, 'Gambar tidak ditemukan.', null, 404);
    $pdo->prepare('DELETE FROM product_images WHERE id=?')->execute([$imageId]);
    $absolute = dirname(__DIR__) . '/' . ltrim($image['image_path'], '/');
    if (is_file($absolute)) unlink($absolute);
    if ($image['is_primary']) {
        $next = $pdo->prepare('SELECT image_path FROM product_images WHERE product_id=? ORDER BY sort_order,id LIMIT 1');
        $next->execute([(int) $image['product_id']]);
        $nextPath = $next->fetchColumn() ?: '';
        $pdo->prepare('UPDATE products SET image=? WHERE id=?')->execute([$nextPath, (int) $image['product_id']]);
        if ($nextPath) $pdo->prepare('UPDATE product_images SET is_primary=1 WHERE product_id=? AND image_path=? LIMIT 1')->execute([(int) $image['product_id'], $nextPath]);
    }
    jsonResponse(true, 'Gambar dihapus.');
}

jsonResponse(false, 'Resource admin tidak ditemukan.', null, 404);