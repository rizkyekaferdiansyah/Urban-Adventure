<?php require_once __DIR__.'/../config/database.php'; 
if(session_status()!==PHP_SESSION_ACTIVE)session_start(); 
if(!isset($_SESSION['user'])||$_SESSION['user']['role']!=='admin'){header('Location: ../public/login.php');exit;} ?><!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin | Urban Adventure</title><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/app.css">
</head>
<body>
    <header class="site-header">
        <a class="brand" href="index.php">
            <b>UA</b>
            <span>ADMIN CONSOLE<small>URBAN ADVENTURE</small>
        </span></a><button class="menu-toggle" type="button" aria-label="Buka menu admin" aria-controls="admin-nav" aria-expanded="false">Menu</button><nav id="admin-nav"><a href="index.php">Dashboard</a>
        <a href="orders.php">Pesanan</a>
        <a href="products.php">Produk</a>
        <a href="customers.php">Pelanggan</a>
        <a href="reports.php">Laporan</a>
        <a class="button small" href="../api/auth.php?action=logout">Keluar</a>
    </nav></header><main class="page-shell"><script src="../assets/js/nav.js"></script>
