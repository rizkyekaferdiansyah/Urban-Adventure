<?php
require __DIR__ . '/bootstrap.php';

$user   = requireLogin();
$pdo    = db();
$method = $_SERVER['REQUEST_METHOD'];
$input  = body();
$id     = (int) ($_GET['id'] ?? 0);

// ─── GET: Ambil pesanan user ──────────────────────────────────────────────────
if ($method === 'GET') {
    $sql    = 'SELECT o.*,u.name customer FROM orders o JOIN users u ON u.id=o.user_id WHERE o.user_id=?';
    $params = [$user['id']];
    if ($id) {
        $sql    .= ' AND o.id=?';
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

        // Sertakan data payment jika ada
        $payment = $pdo->prepare(
            'SELECT id,payment_method,payment_status,amount,proof_image,paid_at,created_at
             FROM payments WHERE order_id=? ORDER BY id DESC LIMIT 1'
        );
        $payment->execute([$order['id']]);
        $order['payment'] = $payment->fetch() ?: null;
    }
    unset($order);

    jsonResponse(true, '', $id ? ($orders[0] ?? null) : $orders);
}

// ─── POST: Buat pesanan baru ──────────────────────────────────────────────────
if ($method === 'POST') {
    requireCsrf($input);

    // Ambil cart + data produk termasuk deposit
    $cart = $pdo->prepare(
        'SELECT c.*,p.name,p.price_per_day,p.stock,p.deposit
         FROM cart c
         JOIN products p ON p.id=c.product_id
         WHERE c.user_id=?'
    );
    $cart->execute([$user['id']]);
    $items = $cart->fetchAll();

    if (!$items) jsonResponse(false, 'Keranjang kosong.', null, 422);

    $start = $items[0]['start_date'];
    $end   = $items[0]['end_date'];

    foreach ($items as $item) {
        if (
            $item['start_date'] !== $start
            || $item['end_date'] !== $end
            || !checkAvailability($pdo, (int) $item['product_id'], $start, $end, (int) $item['quantity'])
        ) {
            jsonResponse(false, 'Stok tidak mencukupi untuk tanggal tersebut.', null, 422);
        }
    }

    $days     = rentalDays($start, $end);
    $subtotal = 0;
    // Bug #4: hitung deposit dari setiap produk
    $totalDeposit = 0;
    foreach ($items as $item) {
        $subtotal     += (float) $item['price_per_day'] * $days * (int) $item['quantity'];
        $totalDeposit += (float) $item['deposit'] * (int) $item['quantity'];
    }
    $grandTotal = $subtotal + $totalDeposit;

    $pdo->beginTransaction();
    try {
        foreach ($items as $item) {
            $reserve = $pdo->prepare(
                'UPDATE products SET stock=stock-? WHERE id=? AND status="active" AND stock>=?'
            );
            $reserve->execute([(int) $item['quantity'], (int) $item['product_id'], (int) $item['quantity']]);
            if ($reserve->rowCount() !== 1) {
                throw new RuntimeException('Stok berubah. Silakan coba lagi.');
            }
        }

        // Bug #5: generate kode dulu dengan placeholder, update setelah dapat orderId
        $order = $pdo->prepare(
            'INSERT INTO orders
             (order_code,user_id,start_date,end_date,total_days,subtotal,deposit,total,notes)
             VALUES (?,?,?,?,?,?,?,?,?)'
        );
        $placeholderCode = 'UA-TEMP-' . bin2hex(random_bytes(4));
        $order->execute([
            $placeholderCode,
            $user['id'],
            $start,
            $end,
            $days,
            $subtotal,
            $totalDeposit,
            $grandTotal,
            trim((string) ($input['notes'] ?? '')),
        ]);
        $orderId = (int) $pdo->lastInsertId();

        // Kode unik berbasis orderId — tidak mungkin collision
        $code = 'UA-' . date('Ymd') . '-' . str_pad((string) $orderId, 5, '0', STR_PAD_LEFT);
        $pdo->prepare('UPDATE orders SET order_code=? WHERE id=?')->execute([$code, $orderId]);

        $line = $pdo->prepare(
            'INSERT INTO order_items (order_id,product_id,product_name,price_per_day,quantity,subtotal)
             VALUES (?,?,?,?,?,?)'
        );
        foreach ($items as $item) {
            $line->execute([
                $orderId,
                $item['product_id'],
                $item['name'],
                $item['price_per_day'],
                $item['quantity'],
                (float) $item['price_per_day'] * $days * (int) $item['quantity'],
            ]);
        }

        $pdo->prepare('DELETE FROM cart WHERE user_id=?')->execute([$user['id']]);
        $pdo->prepare(
            'INSERT INTO notifications (user_id,title,message) VALUES (?,?,?)'
        )->execute([
            $user['id'],
            'Pesanan berhasil dibuat',
            "Pesanan {$code} senilai " . number_format($grandTotal, 0, ',', '.') . " menunggu persetujuan admin tombol untuk membayar akan muncul jikan admin sudah menyetujui pesanan.",
        ]);

        $pdo->commit();
    } catch (Throwable $error) {
        $pdo->rollBack();
        jsonResponse(false, $error->getMessage(), null, 422);
    }

    jsonResponse(true, 'Pesanan berhasil dibuat.', ['id' => $orderId, 'order_code' => $code], 201);
}

// ─── DELETE: Customer membatalkan pesanan sendiri ─────────────────────────────
if ($method === 'DELETE' && $id) {
    requireCsrf($input);

    $stmt = $pdo->prepare('SELECT status FROM orders WHERE id=? AND user_id=?');
    $stmt->execute([$id, $user['id']]);
    $order = $stmt->fetch();

    if (!$order) jsonResponse(false, 'Pesanan tidak ditemukan.', null, 404);

    // Hanya boleh cancel jika masih pending atau approved (belum bayar)
    $cancellable = ['pending', 'approved'];
    if (!in_array($order['status'], $cancellable, true)) {
        jsonResponse(false, 'Pesanan tidak dapat dibatalkan pada status ini.', null, 422);
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE orders SET status='cancelled' WHERE id=?")->execute([$id]);

        // Kembalikan stok
        $items = $pdo->prepare(
            'SELECT product_id, quantity FROM order_items WHERE order_id=? AND product_id IS NOT NULL'
        );
        $items->execute([$id]);
        $restore = $pdo->prepare('UPDATE products SET stock=stock+? WHERE id=?');
        foreach ($items as $item) {
            $restore->execute([(int) $item['quantity'], (int) $item['product_id']]);
        }

        $pdo->prepare(
            'INSERT INTO notifications (user_id,title,message) VALUES (?,?,?)'
        )->execute([$user['id'], 'Pesanan dibatalkan', "Pesanan #{$id} telah dibatalkan."]);

        $pdo->commit();
    } catch (Throwable $error) {
        $pdo->rollBack();
        jsonResponse(false, $error->getMessage(), null, 422);
    }

    jsonResponse(true, 'Pesanan berhasil dibatalkan.');
}

jsonResponse(false, 'Permintaan order tidak valid.', null, 400);
