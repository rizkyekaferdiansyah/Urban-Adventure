<?php
require_once __DIR__ . '/../config/database.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../public/login.php');
    exit;
}

// Hitung kedalaman path relatif terhadap root (admin/ vs admin/products/)
$depth      = substr_count(str_replace($_SERVER['DOCUMENT_ROOT'], '', __DIR__), '/');
$scriptDepth = substr_count(str_replace($_SERVER['DOCUMENT_ROOT'], '', $_SERVER['SCRIPT_FILENAME']), '/');
// Jika halaman ada di admin/products/, perlu prefix ../../ untuk root asset
$isSubpage  = str_contains($_SERVER['SCRIPT_FILENAME'], '/admin/products/');
$assetBase  = $isSubpage ? '../../assets' : '../assets';
$adminBase  = $isSubpage ? '../..' : '..';
?><!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin | Urban Adventure</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $assetBase ?>/css/app.css?v=20260925">
</head>
<body>
    <header class="site-header">
        <a class="brand" href="<?= $adminBase ?>/admin/index.php">
            <b>UA</b>
            <span>ADMIN CONSOLE<small>URBAN ADVENTURE</small></span>
        </a>
        <button class="menu-toggle" type="button" aria-label="Buka menu admin" aria-controls="admin-nav" aria-expanded="false">Menu</button>
        <nav id="admin-nav">
            <a href="<?= $adminBase ?>/admin/index.php">Dashboard</a>
            <a href="<?= $adminBase ?>/admin/orders.php">Pesanan</a>
            <a href="<?= $adminBase ?>/admin/products.php">Produk</a>
            <a href="<?= $adminBase ?>/admin/customers.php">Pelanggan</a>
            <a href="<?= $adminBase ?>/admin/reports.php">Laporan</a>
            <a class="button small" href="<?= $adminBase ?>/api/auth.php?action=logout">Keluar</a>
        </nav>
    </header>
    <main class="page-shell">
        <script src="<?= $assetBase ?>/js/nav.js"></script>
