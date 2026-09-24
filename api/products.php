<?php
require __DIR__ . '/bootstrap.php';
$pdo=db(); $id=(int)($_GET['id'] ?? 0); $search=trim((string)($_GET['search'] ?? '')); $category=trim((string)($_GET['category'] ?? ''));
if ($id) {
	$stmt=$pdo->prepare('SELECT p.*,c.name category FROM products p JOIN categories c ON c.id=p.category_id WHERE p.id=? AND p.status="active" AND p.deleted_at IS NULL');
	$stmt->execute([$id]);
	$item=$stmt->fetch();
	if (!$item) jsonResponse(false,'Produk tidak ditemukan.',null,404);
	$images=$pdo->prepare('SELECT id,image_path,is_primary,sort_order FROM product_images WHERE product_id=? ORDER BY is_primary DESC,sort_order,id');
	$images->execute([$id]);
	$item['images']=$images->fetchAll();
	jsonResponse(true,'', $item);
}
$query='SELECT p.*,c.name category FROM products p JOIN categories c ON c.id=p.category_id WHERE p.status="active" AND p.deleted_at IS NULL AND c.status="active"'; $params=[];
if ($search !== '') { $query.=' AND (p.name LIKE ? OR c.name LIKE ?)'; $params=["%$search%","%$search%"]; }
if ($category !== '') { $query.=' AND c.slug=?'; $params[]=$category; }
$query.=' ORDER BY c.name,p.name'; $stmt=$pdo->prepare($query); $stmt->execute($params); jsonResponse(true,'', ['products'=>$stmt->fetchAll(),'categories'=>$pdo->query('SELECT id,name,slug FROM categories ORDER BY name')->fetchAll()]);
