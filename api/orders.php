<?php
require __DIR__ . '/bootstrap.php';

$user = requireLogin();
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$input = body();
$id = (int) ($_GET['id'] ?? 0);

if ($method === 'GET') {
    $sql = 'SELECT o.*,u.name customer FROM orders o JOIN users u ON u.id=o.user_id WHERE o.user_id=?';
    $params = [$user['id']];
    if ($id) {
        $sql .= ' AND o.id=?';
        $params[] = $id;
    }
    $sql .= ' ORDER BY o.created_at DESC';
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $orders = $statement->fetchAll();
    foreach ($orders as &$order) {
        $items = $pdo->prepare('SELECT * FROM order_items WHERE order_id=?');
        $items->execute([$order['id']]);
        $order['items'] = $items->fetchAll();
    }
    jsonResponse(true, '', $id ? ($orders[0] ?? null) : $orders);
}

if ($method === 'POST') {
    requireCsrf($input);
    $cart = $pdo->prepare('SELECT c.*,p.name,p.price_per_day,p.stock FROM cart c JOIN products p ON p.id=c.product_id WHERE c.user_id=?');
    $cart->execute([$user['id']]);
    $items = $cart->fetchAll();
    if (!$items) jsonResponse(false, 'Keranjang kosong.', null, 422);

    $start = $items[0]['start_date'];
    $end = $items[0]['end_date'];
    foreach ($items as $item) {
        if ($item['start_date'] !== $start || $item['end_date'] !== $end || !checkAvailability($pdo, (int) $item['product_id'], $start, $end, (int) $item['quantity'])) {
            jsonResponse(false, 'Stok tidak mencukupi untuk tanggal tersebut.', null, 422);
        }
    }

    $days = rentalDays($start, $end);
    $subtotal = 0;
    foreach ($items as $item) $subtotal += (float) $item['price_per_day'] * $days * (int) $item['quantity'];

    $pdo->beginTransaction();
    try {
        foreach ($items as $item) {
            $reserve = $pdo->prepare('UPDATE products SET stock=stock-? WHERE id=? AND status="active" AND stock>=?');
            $reserve->execute([(int) $item['quantity'], (int) $item['product_id'], (int) $item['quantity']]);
            if ($reserve->rowCount() !== 1) throw new RuntimeException('Stok berubah. Silakan coba lagi.');
        }

        $code = 'UA-' . date('Ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        $order = $pdo->prepare('INSERT INTO orders (order_code,user_id,start_date,end_date,total_days,subtotal,total,notes) VALUES (?,?,?,?,?,?,?,?)');
        $order->execute([$code, $user['id'], $start, $end, $days, $subtotal, $subtotal, trim((string) ($input['notes'] ?? ''))]);
        $orderId = (int) $pdo->lastInsertId();

        $line = $pdo->prepare('INSERT INTO order_items (order_id,product_id,product_name,price_per_day,quantity,subtotal) VALUES (?,?,?,?,?,?)');
        foreach ($items as $item) $line->execute([$orderId, $item['product_id'], $item['name'], $item['price_per_day'], $item['quantity'], $item['price_per_day'] * $days * $item['quantity']]);
        $pdo->prepare('DELETE FROM cart WHERE user_id=?')->execute([$user['id']]);
        $pdo->prepare('INSERT INTO notifications (user_id,title,message) VALUES (?,?,?)')->execute([$user['id'], 'Pesanan berhasil dibuat', "Pesanan {$code} menunggu persetujuan admin."]);
        $pdo->commit();
    } catch (Throwable $error) {
        $pdo->rollBack();
        jsonResponse(false, $error->getMessage(), null, 422);
    }

    jsonResponse(true, 'Pesanan berhasil dibuat.', ['id' => $orderId, 'order_code' => $code], 201);
}

jsonResponse(false, 'Permintaan order tidak valid.', null, 400);