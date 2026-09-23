<?php
require __DIR__ . '/bootstrap.php';
$user=requireLogin();$pdo=db();if($_SERVER['REQUEST_METHOD']==='GET'){$stmt=$pdo->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 50');$stmt->execute([$user['id']]);jsonResponse(true,'',$stmt->fetchAll());}$id=(int)($_GET['id']??0);$input=body();requireCsrf($input);$stmt=$pdo->prepare('UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?');$stmt->execute([$id,$user['id']]);jsonResponse(true,'Notifikasi ditandai telah dibaca.');
