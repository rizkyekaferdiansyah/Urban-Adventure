<?php
require_once __DIR__ . '/../config/database.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
?><!doctype html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title><?= htmlspecialchars($title ?? 'Urban Adventure') ?></title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="../assets/css/app.css">
    </head>
    <body>
        <header class="site-header">
            <a class="brand" href="index.php">
                <b>UA</b>
                <span>URBAN ADVENTURE<small>OUTDOOR RENTAL · JOGJA</small></span>
            </a>
            <nav>
                <a href="index.php">Katalog</a>
                <a href="cart.php">Keranjang</a>
                <?php if(isset($_SESSION['user'])): ?>
                    <a href="orders.php">Pesanan</a>
                    <a href="profile.php">Profil</a>
                    <a class="button small" href="../api/auth.php?action=logout">Keluar</a>
                <?php else: ?>
                    <a class="button small" href="login.php">Masuk</a>
                <?php endif; ?>
            </nav>
        </header>
        <main class="page-shell">
