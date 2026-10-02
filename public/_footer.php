</main>
<footer class="site-footer">
    <div><strong>URBAN ADVENTURE</strong><small>Rental tenda &amp; perlengkapan outdoor di Yogyakarta.</small></div>
    <div class="footer-links"><a href="index.php#katalog">Katalog</a><a href="index.php#kenapa">Kenapa Kami</a><a href="https://wa.me/6282225708380" target="_blank" rel="noopener">WhatsApp</a></div>
    <div><span>Wirobrajan · Yogyakarta</span><small>© 2026 Urban Adventure</small></div>
</footer>

<!-- ── Live update polling ── -->
<script>
(function () {
  if (typeof LivePoll === "undefined" || typeof window.__pollStarted !== "undefined") return;
  window.__pollStarted = true;

  const page = location.pathname.split("/").pop() || "index.php";

  // Load notifikasi pertama kali saat halaman siap
  // (setelah app.js selesai — gunakan DOMContentLoaded sudah lewat, jadi langsung)
  if (typeof loadNotifications === "function") {
    loadNotifications();
  }

  LivePoll
    // ── Event notifikasi: HANYA update bell + panel ────────────────────────
    // Tidak memanggil loadOrders() dari sini
    .on("notification", function () {
      if (typeof loadNotifications === "function") loadNotifications();
    })

    // ── Event order: HANYA refresh daftar pesanan ──────────────────────────
    // Dipanggil hanya jika last_order_update berubah (dihandle poll.js)
    .on("order", function () {
      if (page === "orders.php" && typeof loadOrders === "function") {
        loadOrders();
      }
    })

    // ── Event cart: update badge di nav ───────────────────────────────────
    .on("cart", function (count) {
      _updateCartBadge(count);
    })

    .start();

  function _updateCartBadge(count) {
    const link = document.querySelector('nav a[href="cart.php"]');
    if (!link) return;
    const badge = link.querySelector(".cart-badge");
    if (count > 0) {
      if (badge) badge.textContent = count;
      else link.insertAdjacentHTML("beforeend", `<sup class="cart-badge notif-badge">${count}</sup>`);
    } else {
      if (badge) badge.remove();
    }
  }
})();
</script>
</body>
</html>
