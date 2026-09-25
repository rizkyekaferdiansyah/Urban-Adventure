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
        <link rel="stylesheet" href="../assets/css/app.css?v=20260924">
    </head>
    <body>
        <header class="site-header">
            <a class="brand" href="index.php">
                <b>UA</b>
                <span>URBAN ADVENTURE<small>OUTDOOR RENTAL · JOGJA</small></span>
            </a>
            <button class="menu-toggle" type="button" aria-label="Buka menu" aria-controls="public-nav" aria-expanded="false">Menu</button>
            <nav id="public-nav">
                <a href="index.php">Beranda</a>
                <a href="index.php#katalog">Katalog</a>
                <a href="index.php#kenapa">Kenapa Kami</a>
                <a href="cart.php">Keranjang</a>
                <?php if(isset($_SESSION['user'])): ?>
                    <a href="orders.php">Pesanan</a>
                    <a href="profile.php">Profil</a>
                    <div class="notif-wrapper">
                        <button id="notifBell" class="notif-bell" aria-label="Notifikasi" aria-haspopup="true" aria-expanded="false" onclick="toggleNotifPanel()">🔔</button>
                        <div id="notifPanel" class="notif-panel" hidden>
                            <div class="notif-header"><strong>Notifikasi</strong></div>
                            <div id="notifList"><p class="muted">Memuat...</p></div>
                        </div>
                    </div>
                    <a class="button small" href="../api/auth.php?action=logout">Keluar</a>
                <?php else: ?>
                    <a class="button small" href="login.php">Masuk</a>
                <?php endif; ?>
            </nav>
        </header>
        <main class="page-shell"><script src="../assets/js/nav.js"></script>
<script>
function toggleNotifPanel() {
  const panel = document.getElementById("notifPanel");
  const bell  = document.getElementById("notifBell");
  if (!panel) return;
  const isHidden = panel.hasAttribute("hidden");
  if (isHidden) {
    panel.removeAttribute("hidden");
    bell.setAttribute("aria-expanded", "true");
  } else {
    panel.setAttribute("hidden", "");
    bell.setAttribute("aria-expanded", "false");
  }
}
// Tutup panel saat klik di luar
document.addEventListener("click", function(e) {
  const wrapper = document.querySelector(".notif-wrapper");
  if (wrapper && !wrapper.contains(e.target)) {
    const panel = document.getElementById("notifPanel");
    const bell  = document.getElementById("notifBell");
    if (panel && !panel.hasAttribute("hidden")) {
      panel.setAttribute("hidden", "");
      if (bell) bell.setAttribute("aria-expanded", "false");
    }
  }
});
</script>
