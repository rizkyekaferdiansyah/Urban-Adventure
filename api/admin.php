<?php
require __DIR__ . '/bootstrap.php';

requireAdmin();
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$input = body();
$resource = $_GET['resource'] ?? 'dashboard';

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

if ($resource === 'products' && $method === 'GET') {
    $statement = $pdo->query('SELECT p.*,c.name AS category FROM products p LEFT JOIN categories c ON c.id=p.category_id ORDER BY p.status DESC, c.name, p.name');
    jsonResponse(true, '', $statement->fetchAll());
}

if ($resource === 'products' && $method === 'POST') {
    requireCsrf($input);
    $category = (int) ($input['category_id'] ?? 0);
    $name = trim((string) ($input['name'] ?? ''));
    $price = (float) ($input['price_per_day'] ?? 0);
    $stock = (int) ($input['stock'] ?? 0);
    if ($name === '' || $category <= 0 || $price <= 0 || $stock <= 0) jsonResponse(false, 'Data produk tidak valid.', null, 422);
    $statement = $pdo->prepare('INSERT INTO products (category_id,name,slug,description,price_per_day,stock,image) VALUES (?,?,?,?,?,?,?)');
    $statement->execute([$category, $name, slugify($name), $input['description'] ?? '', $price, $stock, $input['image'] ?? '']);
    jsonResponse(true, 'Produk dibuat.', null, 201);
}

if ($resource === 'products' && $method === 'PUT') {
    requireCsrf($input);
    $id = (int) ($_GET['id'] ?? 0);
    $fields = [];
    $values = [];
    if (isset($input['category_id'])) { $fields[] = 'category_id=?'; $values[] = (int) $input['category_id']; }
    if (isset($input['name'])) { $fields[] = 'name=?'; $values[] = (string) $input['name']; $fields[] = 'slug=?'; $values[] = slugify((string) $input['name']); }
    if (isset($input['description'])) { $fields[] = 'description=?'; $values[] = (string) $input['description']; }
    if (isset($input['price_per_day'])) { $fields[] = 'price_per_day=?'; $values[] = (float) $input['price_per_day']; }
    if (isset($input['stock'])) { $fields[] = 'stock=?'; $values[] = max(0, (int) $input['stock']); }
    if (isset($input['image'])) { $fields[] = 'image=?'; $values[] = (string) $input['image']; }
    if (isset($input['status'])) { $fields[] = 'status=?'; $values[] = (string) $input['status']; }
    if (!$fields) jsonResponse(false, 'Tidak ada data produk yang diperbarui.', null, 422);
    $values[] = $id;
    $pdo->prepare('UPDATE products SET ' . implode(',', $fields) . ' WHERE id=?')->execute($values);
    jsonResponse(true, 'Produk diperbarui.');
}

if ($resource === 'products' && $method === 'DELETE') {
    requireCsrf($input);
    $id = (int) ($_GET['id'] ?? 0);
    $pdo->prepare("UPDATE products SET status='inactive' WHERE id=?")->execute([$id]);
    jsonResponse(true, 'Produk dinonaktifkan.');
}

jsonResponse(false, 'Resource admin tidak ditemukan.', null, 404);