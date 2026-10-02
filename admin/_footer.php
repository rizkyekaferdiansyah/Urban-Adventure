    </main>
    <footer class="site-footer">
        <div><strong>URBAN ADVENTURE</strong><small>Admin Panel</small></div>
        <div><span>Data operasional rental</span><small>© 2026 Urban Adventure</small></div>
    </footer>

    <!-- ── Live update polling (admin) ── -->
    <script>
    (function () {
      if (typeof LivePoll === "undefined" || typeof window.__pollStarted !== "undefined") return;
      window.__pollStarted = true;

      const page = location.pathname.split("/").pop() || "index.php";

      LivePoll
        .on("order", function (data) {
          if (page === "orders.php" && typeof loadAdminOrders === "function") {
            loadAdminOrders();

            // Pesan toast berdasarkan kondisi terkini
            let msg = "Ada aktivitas pesanan baru.";
            if (data && data.waiting_payment > 0) {
              msg = `${data.waiting_payment} pesanan menunggu verifikasi pembayaran.`;
            } else if (data && data.pending_orders > 0) {
              msg = `${data.pending_orders} pesanan menunggu tindakan.`;
            }
            _showAdminToast(msg);
          }

          if (page === "index.php" && typeof loadDashboard === "function") {
            loadDashboard();
          }
        })

        .start();

      function _showAdminToast(msg) {
        const existing = document.getElementById("admin-toast");
        if (existing) existing.remove();

        const toast = document.createElement("div");
        toast.id        = "admin-toast";
        toast.className = "admin-toast";
        toast.textContent = "🔔 " + msg;
        document.body.appendChild(toast);

        setTimeout(() => toast.classList.add("admin-toast--hide"), 3500);
        setTimeout(() => { if (toast.parentNode) toast.remove(); }, 4000);
      }
    })();
    </script>
</body>
</html>
