// ─── HTML escape helper ───────────────────────────────────────────────────────
function esc(str) {
  return String(str ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

// ─── API Base Path ────────────────────────────────────────────────────────────
// Dihitung sekali saat file dimuat berdasarkan lokasi halaman:
//   admin/          → ../api
//   admin/products/ → ../../api
const API_BASE = (function () {
  const path  = window.location.pathname;
  const depth = (path.match(/\/admin(\/[^/]+)+\//)?.[0].match(/\//g) || []).length - 1;
  return depth > 0 ? "../../api" : "../api";
})();

// ─── Core helpers ─────────────────────────────────────────────────────────────
let adminCsrf = "";

function money(value) {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    maximumFractionDigits: 0,
  }).format(Number(value || 0));
}

async function adminApi(url, options = {}) {
  const response = await fetch(url, options);
  const result   = await response.json();
  if (result.data?.csrf) adminCsrf = result.data.csrf;
  if (!result.success) throw new Error(result.message || "Terjadi kesalahan");
  return result.data || {};
}

async function adminInit() {
  const data = await adminApi(`${API_BASE}/session.php`);
  adminCsrf  = data.csrf || adminCsrf;
}

// ─── Dashboard ────────────────────────────────────────────────────────────────
async function loadDashboard(target = "stats") {
  await adminInit();
  try {
    const s = await adminApi(`${API_BASE}/admin.php?resource=dashboard`);
    const items = Object.entries({
      "Produk Aktif":        s.products,
      Pelanggan:             s.customers,
      "Total Pesanan":       s.orders,
      Pending:               s.pending,
      "Sedang Berjalan":     s.running,
      Pendapatan:            money(s.revenue),
      "Menunggu Verifikasi": s.waiting_payments,
    });

    const el = document.getElementById(target);
    if (el) {
      el.innerHTML = items
        .map(([key, value]) =>
          `<article class="stat-card"><small>${esc(key)}</small><strong>${esc(String(value))}</strong></article>`
        ).join("");
    }

    const latest = document.getElementById("latest");
    if (latest) {
      latest.innerHTML = s.latest.map((order) =>
        `<article class="order-card">
          <div>
            <small>${esc(order.order_code)}</small>
            <h3>${esc(order.customer)}</h3>
            <p>${esc(order.created_at)}</p>
          </div>
          <div>
            <b>${money(order.total)}</b>
            <span class="status">${esc(order.status)}</span>
          </div>
        </article>`
      ).join("") || '<p class="empty">Belum ada pesanan.</p>';
    }
  } catch (error) {
    const el = document.getElementById(target);
    if (el) el.innerHTML = `<p class="error">${esc(error.message)}</p>`;
  }
}

// ─── Orders admin ─────────────────────────────────────────────────────────────
const ORDER_STATUS_LABEL = {
  pending:              "Menunggu",
  approved:             "Disetujui",
  waiting_payment:      "Tunggu Bayar",
  paid:                 "Lunas",
  ongoing:              "Berlangsung",
  completed:            "Selesai",
  cancelled:            "Dibatalkan",
};

const PAYMENT_STATUS_LABEL = {
  unpaid:               "Belum Bayar",
  waiting_verification: "Menunggu Verif.",
  paid:                 "Lunas",
  rejected:             "Ditolak",
};

const ORDER_STATUS_COLOR = {
  pending:              "badge-yellow",
  approved:             "badge-blue",
  waiting_payment:      "badge-orange",
  paid:                 "badge-teal",
  ongoing:              "badge-green",
  completed:            "badge-gray",
  cancelled:            "badge-red",
};

const PAYMENT_STATUS_COLOR = {
  unpaid:               "badge-gray",
  waiting_verification: "badge-orange",
  paid:                 "badge-green",
  rejected:             "badge-red",
};

function getOrderActions(o) {
  const actions = [];
  if (o.status === "pending") {
    actions.push({ label: "✓ Setujui",  style: "primary",   confirm: null,                              update: { status: "approved" } });
    actions.push({ label: "✕ Tolak",    style: "danger",    confirm: "Tolak dan batalkan pesanan ini?", update: { status: "cancelled" } });
  }
  if (o.status === "approved") {
    actions.push({ label: "→ Minta Pembayaran", style: "secondary", confirm: null,                update: { status: "waiting_payment" } });
    actions.push({ label: "✕ Batalkan",          style: "danger",    confirm: "Batalkan pesanan?", update: { status: "cancelled" } });
  }
  if (o.payment_status === "waiting_verification" && !["completed","cancelled"].includes(o.status)) {
    actions.push({ label: "✓ Verifikasi Bayar", style: "primary", confirm: null,                        update: { payment_status: "paid", status: "paid" } });
    actions.push({ label: "✕ Tolak Bukti",       style: "danger",  confirm: "Tolak bukti pembayaran?", update: { payment_status: "rejected" } });
  }
  if (o.status === "paid")    actions.push({ label: "▶ Mulai Rental",   style: "secondary", confirm: null,                             update: { status: "ongoing" } });
  if (o.status === "ongoing") actions.push({ label: "✓ Tandai Selesai", style: "primary",   confirm: "Tandai pesanan ini sebagai selesai?", update: { status: "completed" } });
  return actions;
}

function renderOrderActions(o) {
  const actions = getOrderActions(o);
  if (!actions.length) return `<span class="text-muted" style="font-size:.8rem">—</span>`;
  return actions.map((a, idx) =>
    `<button class="order-action-btn btn-${a.style}"
      onclick="doOrderAction(${o.id},${idx},'${esc(o.order_code)}')">${a.label}</button>`
  ).join("");
}

const _orderActionsCache = {};

async function loadAdminOrders() {
  const container = document.getElementById("adminOrders");
  try {
    await adminInit();
    if (!document.getElementById("orderSearchInput")) {
      container.insertAdjacentHTML("beforebegin",
        `<div class="order-filter-bar">
          <input class="input" id="orderSearchInput" placeholder="Cari kode order / nama pelanggan…" style="max-width:280px">
          <select class="input" id="orderStatusFilter" style="max-width:200px">
            <option value="">Semua status</option>
            ${Object.entries(ORDER_STATUS_LABEL).map(([v,l]) => `<option value="${v}">${l}</option>`).join("")}
          </select>
        </div>`
      );
      document.getElementById("orderSearchInput").addEventListener("input", renderOrderTable);
      document.getElementById("orderStatusFilter").addEventListener("change", renderOrderTable);
    }
    const allOrders = await adminApi(`${API_BASE}/admin.php?resource=orders`);
    allOrders.forEach((o) => { _orderActionsCache[o.id] = getOrderActions(o); });
    window._allAdminOrders = allOrders;
    renderOrderTable();
  } catch (error) {
    container.innerHTML = `<p class="error">${esc(error.message)}</p>`;
  }
}

function renderOrderTable() {
  const container    = document.getElementById("adminOrders");
  const search       = (document.getElementById("orderSearchInput")?.value || "").toLowerCase();
  const statusFilter = document.getElementById("orderStatusFilter")?.value || "";
  let orders         = window._allAdminOrders || [];
  if (search)       orders = orders.filter((o) => o.order_code.toLowerCase().includes(search) || o.customer.toLowerCase().includes(search) || o.email.toLowerCase().includes(search));
  if (statusFilter) orders = orders.filter((o) => o.status === statusFilter);
  if (!orders.length) { container.innerHTML = '<p class="empty">Tidak ada pesanan ditemukan.</p>'; return; }

  container.innerHTML =
    '<div class="table-wrap"><table class="orders-table"><thead><tr>' +
    "<th>Pesanan</th><th>Pelanggan</th><th>Periode & Item</th><th>Total</th><th>Status</th><th>Pembayaran</th><th>Aksi</th>" +
    "</tr></thead><tbody>" +
    orders.map((o) => {
      const statusBadge  = `<span class="badge ${ORDER_STATUS_COLOR[o.status] || "badge-gray"}">${esc(ORDER_STATUS_LABEL[o.status] || o.status)}</span>`;
      const payBadge     = `<span class="badge ${PAYMENT_STATUS_COLOR[o.payment_status] || "badge-gray"}">${esc(PAYMENT_STATUS_LABEL[o.payment_status] || o.payment_status)}</span>`;
      const itemsSummary = o.items?.length ? o.items.map((i) => `${esc(i.product_name)} ×${i.quantity}`).join(", ") : "—";
      return `<tr>
        <td><strong class="order-code">${esc(o.order_code)}</strong><br><small class="text-muted">${esc(o.created_at?.substring(0,10)||"")}</small></td>
        <td>${esc(o.customer)}<br><small class="text-muted">${esc(o.email)}</small></td>
        <td><small>${esc(o.start_date)} – ${esc(o.end_date)}</small><br><small class="text-muted">${itemsSummary}</small></td>
        <td><strong>${money(o.total)}</strong></td>
        <td>${statusBadge}</td>
        <td>${payBadge}${o.payment?.proof_image ? `<br><a href="../${esc(o.payment.proof_image)}" target="_blank" class="text-button proof-link">🖼 Lihat bukti</a>` : ""}</td>
        <td class="action-cell">${renderOrderActions(o)}</td>
      </tr>`;
    }).join("") + "</tbody></table></div>";
}

async function doOrderAction(orderId, actionIdx, orderCode) {
  const actions = _orderActionsCache[orderId];
  if (!actions || !actions[actionIdx]) return;
  const action = actions[actionIdx];
  if (action.confirm && !confirm(`${action.confirm}\n\nPesanan: ${orderCode}`)) return;
  try {
    await adminApi(`${API_BASE}/admin.php?resource=orders&id=${orderId}`, {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ ...action.update, csrf: adminCsrf }),
    });
    await loadAdminOrders();
  } catch (error) {
    alert(error.message);
  }
}

// ─── Products admin ───────────────────────────────────────────────────────────
window.adjustProductStock = async function (id, delta) {
  try {
    await adminInit();
    const product = await adminApi(`${API_BASE}/admin.php?resource=products&id=${id}`);
    if (!product) return;
    const stock = Math.max(0, Number(product.stock) + Number(delta));
    await adminApi(`${API_BASE}/admin.php?resource=products&id=${id}`, {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ stock, csrf: adminCsrf }),
    });
    loadAdminProducts();
  } catch (error) {
    const el = document.getElementById("adminProducts");
    if (el) el.innerHTML = `<p class="error">${esc(error.message)}</p>`;
  }
};

window.loadAdminProducts = async function () {
  const container = document.getElementById("adminProducts");
  try {
    await adminInit();
    const search = document.getElementById("productSearch");
    const status = document.getElementById("productStatusFilter");
    const params = new URLSearchParams({ search: search?.value || "", status: status?.value || "all" });
    const products = await adminApi(`${API_BASE}/admin.php?resource=products&${params}`);

    if (search && !search.dataset.bound) { search.dataset.bound = "true"; search.addEventListener("input", () => loadAdminProducts()); }
    if (status && !status.dataset.bound) { status.dataset.bound = "true"; status.addEventListener("change", () => loadAdminProducts()); }

    container.innerHTML =
      '<div class="table-wrap"><table><thead><tr>' +
      "<th>Produk</th><th>SKU</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Status</th><th>Dibuat</th><th>Aksi</th>" +
      "</tr></thead><tbody>" +
      products.map((p) =>
        `<tr>
          <td>${esc(p.name)}</td>
          <td>${esc(p.sku || "-")}</td>
          <td>${esc(p.category || "-")}</td>
          <td>${money(p.price_per_day)}</td>
          <td>
            <div class="stock-control">
              <button class="button small outline" onclick="adjustProductStock(${p.id},-1)">-</button>
              <strong>${esc(p.stock)}</strong>
              <button class="button small" onclick="adjustProductStock(${p.id},1)">+</button>
            </div>
          </td>
          <td><span class="status">${esc(p.status)}</span></td>
          <td><small>${esc(p.created_at || "-")}</small></td>
          <td>
            <a class="button small outline" href="products/edit.php?id=${p.id}">Edit</a>
            <button class="button small" onclick="toggleProduct(${p.id},'${p.status === "active" ? "inactive" : "active"}')">
              ${p.status === "active" ? "Nonaktifkan" : "Aktifkan"}
            </button>
            <button class="text-button" onclick="deleteProduct(${p.id})">Hapus</button>
          </td>
        </tr>`
      ).join("") + "</tbody></table></div>";
  } catch (error) {
    container.innerHTML = `<p class="error">${esc(error.message)}</p>`;
  }
};

async function toggleProduct(id, status) {
  try {
    await adminApi(`${API_BASE}/admin.php?resource=products&id=${id}`, {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ status, csrf: adminCsrf }),
    });
    loadAdminProducts();
  } catch (error) { alert(error.message); }
}

async function deleteProduct(id) {
  if (!confirm("Apakah Anda yakin ingin menghapus produk ini?")) return;
  try {
    await adminApi(`${API_BASE}/admin.php?resource=products&id=${id}`, {
      method: "DELETE",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ csrf: adminCsrf }),
    });
    loadAdminProducts();
  } catch (error) { alert(error.message); }
}

// ─── Customers admin ──────────────────────────────────────────────────────────
async function loadAdminCustomers() {
  const container = document.getElementById("customers");
  try {
    await adminInit();
    const data = await adminApi(`${API_BASE}/admin.php?resource=customers`);
    container.innerHTML =
      '<div class="table-wrap"><table><thead><tr>' +
      "<th>Nama</th><th>Email</th><th>HP</th><th>Order</th><th>Status</th><th>Terdaftar</th><th>Aksi</th>" +
      "</tr></thead><tbody>" +
      data.map((c) =>
        `<tr>
          <td>${esc(c.name)}</td>
          <td>${esc(c.email)}</td>
          <td>${esc(c.phone)}</td>
          <td>${esc(c.orders_count)}</td>
          <td><span class="status">${esc(c.status)}</span></td>
          <td><small>${esc(c.created_at || "-")}</small></td>
          <td>
            <button class="button small${c.status === "active" ? " outline" : ""}"
              onclick="toggleCustomer(${c.id},'${c.status === "active" ? "inactive" : "active"}')">
              ${c.status === "active" ? "Nonaktifkan" : "Aktifkan"}
            </button>
          </td>
        </tr>`
      ).join("") + "</tbody></table></div>";
  } catch (error) {
    container.innerHTML = `<p class="error">${esc(error.message)}</p>`;
  }
}

async function toggleCustomer(id, status) {
  if (!confirm(`${status === "inactive" ? "Nonaktifkan" : "Aktifkan"} pelanggan ini?`)) return;
  try {
    await adminApi(`${API_BASE}/admin.php?resource=customers&id=${id}`, {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ status, csrf: adminCsrf }),
    });
    loadAdminCustomers();
  } catch (error) { alert(error.message); }
}

// ─── Reports ──────────────────────────────────────────────────────────────────
async function loadReports() {
  const container = document.getElementById("report");
  if (!container) return;
  try {
    await adminInit();
    if (!document.getElementById("reportFrom")) {
      const today    = new Date().toISOString().split("T")[0];
      const firstDay = today.substring(0, 8) + "01";
      container.insertAdjacentHTML("beforebegin",
        `<div class="report-filter" style="margin-bottom:1rem;display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap;">
          <label>Dari<input class="input" type="date" id="reportFrom" value="${firstDay}"></label>
          <label>Sampai<input class="input" type="date" id="reportTo" value="${today}"></label>
          <button class="button" onclick="loadReports()">Tampilkan</button>
        </div>`
      );
    }
    const from = document.getElementById("reportFrom")?.value || "";
    const to   = document.getElementById("reportTo")?.value   || "";
    const data = await adminApi(`${API_BASE}/admin.php?resource=reports&from=${from}&to=${to}`);
    const s    = data.summary;

    container.innerHTML = `
      <div class="stat-grid">
        <article class="stat-card"><small>Total Pesanan</small><strong>${esc(s.total_orders)}</strong></article>
        <article class="stat-card"><small>Selesai</small><strong>${esc(s.completed_orders)}</strong></article>
        <article class="stat-card"><small>Dibatalkan</small><strong>${esc(s.cancelled_orders)}</strong></article>
        <article class="stat-card"><small>Pending</small><strong>${esc(s.pending_orders)}</strong></article>
        <article class="stat-card"><small>Pendapatan</small><strong>${money(s.revenue)}</strong></article>
        <article class="stat-card"><small>Deposit Terkumpul</small><strong>${money(s.deposit_collected)}</strong></article>
        <article class="stat-card"><small>Total Hari Rental</small><strong>${esc(s.total_rental_days)}</strong></article>
      </div>
      <h3 style="margin-top:1.5rem">Produk terlaris</h3>
      ${data.top_products.length
        ? '<div class="table-wrap"><table><thead><tr><th>Produk</th><th>Qty Disewa</th><th>Pendapatan</th></tr></thead><tbody>' +
          data.top_products.map((p) => `<tr><td>${esc(p.product_name)}</td><td>${esc(p.total_qty)}</td><td>${money(p.total_revenue)}</td></tr>`).join("") +
          "</tbody></table></div>"
        : '<p class="empty">Belum ada data.</p>'}
      <h3 style="margin-top:1.5rem">Pesanan harian</h3>
      ${data.daily.length
        ? '<div class="table-wrap"><table><thead><tr><th>Tanggal</th><th>Pesanan</th><th>Revenue</th></tr></thead><tbody>' +
          data.daily.map((d) => `<tr><td>${esc(d.date)}</td><td>${esc(d.orders)}</td><td>${money(d.revenue)}</td></tr>`).join("") +
          "</tbody></table></div>"
        : '<p class="empty">Belum ada data.</p>'}`;
  } catch (error) {
    container.innerHTML = `<p class="error">${esc(error.message)}</p>`;
  }
}

// ─── Categories admin ─────────────────────────────────────────────────────────
async function loadAdminCategories() {
  const container = document.getElementById("adminCategories");
  const form      = document.getElementById("categoryForm");
  if (!container || !form) return;
  try {
    await adminInit();
    const categories = await adminApi(`${API_BASE}/admin.php?resource=categories`);
    window.adminCategoriesById = Object.fromEntries(categories.map((c) => [c.id, c]));

    container.innerHTML = categories.map((c) =>
      `<div class="category-row">
        <span>
          <strong>${esc(c.name)}</strong>
          <small>${esc(c.product_count)} produk</small>
        </span>
        <button class="text-button" onclick="toggleCategory(${c.id},'${c.status === "active" ? "inactive" : "active"}')">
          ${c.status === "active" ? "Nonaktifkan" : "Aktifkan"}
        </button>
      </div>`
    ).join("");

    form.onsubmit = async (event) => {
      event.preventDefault();
      const data = Object.fromEntries(new FormData(form).entries());
      data.csrf  = adminCsrf;
      try {
        await adminApi(`${API_BASE}/admin.php?resource=categories`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(data),
        });
        form.reset();
        loadAdminCategories();
      } catch (error) { alert(error.message); }
    };
  } catch (error) {
    container.innerHTML = `<p class="error">${esc(error.message)}</p>`;
  }
}

async function toggleCategory(id, status) {
  const cat = window.adminCategoriesById?.[id];
  if (!cat) { await loadAdminCategories(); }
  const name = window.adminCategoriesById?.[id]?.name;
  if (!name) return;
  try {
    await adminApi(`${API_BASE}/admin.php?resource=categories&id=${id}`, {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ name, status, csrf: adminCsrf }),
    });
    loadAdminCategories();
  } catch (error) { alert(error.message); }
}

// ─── Product form (create & edit) ────────────────────────────────────────────
async function loadProductForm() {
  const form = document.getElementById("productForm");
  if (!form) return;

  const productId = form.dataset.productId || "";
  const msgEl     = document.getElementById("productMessage");

  try {
    await adminInit();

    // ── 1. Load dan tampilkan pilihan kategori ────────────────────────────────
    const categories = await adminApi(`${API_BASE}/admin.php?resource=categories`);
    window.adminCategoriesById = Object.fromEntries(categories.map((c) => [c.id, c]));

    const catSelect = document.getElementById("category_id");
    if (catSelect) {
      catSelect.innerHTML =
        '<option value="">— Pilih kategori —</option>' +
        categories
          .filter((c) => c.status === "active" || productId)
          .map((c) => `<option value="${c.id}">${esc(c.name)}</option>`)
          .join("");
    }

    // ── 2. Mode edit: isi semua field ─────────────────────────────────────────
    if (productId) {
      const product = await adminApi(`${API_BASE}/admin.php?resource=products&id=${productId}`);

      // Isi field teks & number
      Object.entries(product).forEach(([key, value]) => {
        if (value === null || value === undefined) return;
        const field = form.elements.namedItem(key);
        if (!field) return;
        field.value = value;
      });

      // Setelah options sudah ada, set selected kategori
      if (catSelect && product.category_id) {
        catSelect.value = product.category_id;
      }

      // Render foto yang sudah ada
      const existingEl = document.getElementById("existingImages");
      if (existingEl) {
        existingEl.innerHTML = (product.images || []).length
          ? product.images.map((img) =>
              `<div class="image-thumb">
                <img src="../../${esc(img.image_path)}" alt="Foto produk" loading="lazy">
                <div class="image-thumb-info">
                  ${img.is_primary ? '<span class="badge badge-green" style="font-size:.7rem">Utama</span>' : ""}
                </div>
                <button type="button" class="btn-remove-image" onclick="deleteProductImage(${img.id})" title="Hapus foto">✕</button>
              </div>`
            ).join("")
          : '<p class="muted" style="font-size:.85rem">Belum ada foto. Upload di bawah.</p>';
      }
    }

    // ── 3. Preview file yang akan diupload ────────────────────────────────────
    const galleryInput = form.elements.namedItem("gallery");
    const preview      = document.getElementById("imagePreview");
    if (galleryInput && preview) {
      const freshGallery = galleryInput.cloneNode(true);
      galleryInput.parentNode.replaceChild(freshGallery, galleryInput);
      freshGallery.addEventListener("change", () => {
        const files = Array.from(freshGallery.files);
        preview.innerHTML = files.length
          ? files.map((f) => {
              const url = URL.createObjectURL(f);
              return `<div class="image-thumb"><img src="${url}" alt="${esc(f.name)}" loading="lazy"><small>${esc(f.name)}</small></div>`;
            }).join("")
          : "";
      });
    }

    // ── 4. Submit handler — daftarkan hanya sekali ────────────────────────────
    if (form.dataset.listenerAttached) return;
    form.dataset.listenerAttached = "true";

    form.addEventListener("submit", async (event) => {
      event.preventDefault();

      const submitBtn        = form.querySelector('[type="submit"]');
      const msg              = document.getElementById("productMessage");
      const currentProductId = form.dataset.productId || "";

      if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = "Menyimpan…"; }
      if (msg)       { msg.textContent = ""; msg.className = "form-message"; }

      try {
        // Kumpulkan payload (tanpa file gallery)
        const payload = {};
        new FormData(form).forEach((value, key) => {
          if (key !== "gallery") payload[key] = value;
        });
        payload.csrf = adminCsrf;

        // Validasi sisi client
        if (!payload.name?.trim())              throw new Error("Nama produk wajib diisi.");
        if (!payload.category_id)               throw new Error("Kategori wajib dipilih.");
        if (Number(payload.price_per_day) < 0)  throw new Error("Harga tidak boleh negatif.");
        if (Number(payload.stock) < 0)          throw new Error("Stok tidak boleh negatif.");

        // Simpan data produk (POST create / PUT edit)
        const response = await adminApi(
          `${API_BASE}/admin.php?resource=products${currentProductId ? `&id=${currentProductId}` : ""}`,
          {
            method: currentProductId ? "PUT" : "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload),
          }
        );

        const savedId = currentProductId || response.id;

        // Upload foto baru jika ada
        const currentGallery = form.elements.namedItem("gallery");
        if (currentGallery && savedId && currentGallery.files.length > 0) {
          if (msg) msg.textContent = "Mengunggah foto…";
          let errCount = 0;
          for (const file of currentGallery.files) {
            const fd = new FormData();
            fd.append("product_id", savedId);
            fd.append("image", file);
            fd.append("csrf", adminCsrf);
            const res    = await fetch(`${API_BASE}/admin.php?resource=product-images`, { method: "POST", body: fd });
            const result = await res.json();
            if (!result.success) errCount++;
          }
          if (errCount > 0 && msg) {
            msg.textContent = `Produk disimpan, tapi ${errCount} foto gagal diunggah.`;
            msg.classList.add("form-message--warn");
          }
        }

        if (msg && !msg.classList.contains("form-message--warn")) {
          msg.textContent = currentProductId ? "✓ Perubahan berhasil disimpan." : "✓ Produk berhasil dibuat.";
          msg.classList.add("form-message--success");
        }

        // Jika create, redirect ke edit page
        if (!currentProductId && response.id) {
          setTimeout(() => { window.location.href = `edit.php?id=${response.id}`; }, 800);
        } else {
          // Refresh galeri foto (reset flag dulu agar re-attach tidak double)
          delete form.dataset.listenerAttached;
          loadProductForm();
        }

      } catch (error) {
        if (msg) { msg.textContent = error.message; msg.classList.add("form-message--error"); }
      } finally {
        if (submitBtn) {
          submitBtn.disabled    = false;
          submitBtn.textContent = currentProductId ? "Simpan perubahan" : "Simpan produk";
        }
      }
    });

  } catch (error) {
    if (msgEl) msgEl.textContent = error.message;
    console.error("loadProductForm:", error);
  }
}

async function deleteProductImage(id) {
  if (!confirm("Hapus foto produk ini?")) return;
  try {
    await adminApi(`${API_BASE}/admin.php?resource=product-images&id=${id}`, {
      method: "DELETE",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ csrf: adminCsrf }),
    });
    // Reset flag agar submit handler tetap terdaftar saat reload
    const form = document.getElementById("productForm");
    if (form) delete form.dataset.listenerAttached;
    loadProductForm();
  } catch (error) { alert(error.message); }
}
