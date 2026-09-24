window.adjustProductStock = async function (id, delta) {
  try {
    const products = await adminApi("../api/admin.php?resource=products");
    const product = products.find((item) => Number(item.id) === Number(id));
    if (!product) return;
    const stock = Math.max(0, Number(product.stock) + Number(delta));
    await adminApi("../api/admin.php?resource=products&id=" + id, {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ stock, csrf: adminCsrf }),
    });
    loadAdminProducts();
  } catch (error) {
    document.getElementById("adminProducts").innerHTML =
      `<p class="error">${error.message}</p>`;
  }
};
window.loadAdminProducts = async function () {
  const container = document.getElementById("adminProducts");
  try {
    await adminInit();
    const search = document.getElementById("productSearch");
    const status = document.getElementById("productStatusFilter");
    const params = new URLSearchParams({ search: search?.value || "", status: status?.value || "all" });
    const products = await adminApi(`../api/admin.php?resource=products&${params}`);
    if (search && !search.dataset.bound) { search.dataset.bound = "true"; search.addEventListener("input", () => loadAdminProducts()); }
    if (status && !status.dataset.bound) { status.dataset.bound = "true"; status.addEventListener("change", () => loadAdminProducts()); }
    container.innerHTML =
      '<div class="table-wrap"><table><thead><tr><th>Produk</th><th>SKU</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Status</th><th>Dibuat</th><th>Aksi</th></tr></thead><tbody>' +
      products
        .map(
          (p) =>
              `<tr><td>${p.name}</td><td>${p.sku || "-"}</td><td>${p.category || "-"}</td><td>${money(p.price_per_day)}</td><td><div class="stock-control"><button class="button small outline" onclick="adjustProductStock(${p.id},-1)" title="Kurangi stok">-</button><strong>${p.stock}</strong><button class="button small" onclick="adjustProductStock(${p.id},1)" title="Tambah stok">+</button></div></td><td><span class="status">${p.status}</span></td><td><small>${p.created_at || "-"}</small></td><td><a class="button small outline" href="products/edit.php?id=${p.id}">Edit</a> <button class="button small" onclick="toggleProduct(${p.id}, '${p.status === "active" ? "inactive" : "active"}')">${p.status === "active" ? "Nonaktifkan" : "Aktifkan"}</button> <button class="text-button" onclick="deleteProduct(${p.id})">Hapus</button></td></tr>`,
        )
        .join("") +
      "</tbody></table></div>";
  } catch (error) {
    container.innerHTML = `<p class="error">${error.message}</p>`;
  }
};
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
  const result = await response.json();
  if (result.data?.csrf) adminCsrf = result.data.csrf;
  if (!result.success) throw new Error(result.message || "Terjadi kesalahan");
  return result.data || {};
}
async function adminInit() {
  const data = await adminApi("../api/session.php");
  adminCsrf = data.csrf || adminCsrf;
}
async function loadDashboard(target = "stats") {
  await adminInit();
  try {
    const s = await adminApi("../api/admin.php?resource=dashboard");
    const items = Object.entries({
      "Produk Aktif": s.products,
      Pelanggan: s.customers,
      "Total Pesanan": s.orders,
      Pending: s.pending,
      "Sedang Berjalan": s.running,
      Pendapatan: money(s.revenue),
      "Menunggu Verifikasi": s.waiting_payments,
    });
    const el = document.getElementById(target);
    if (el)
      el.innerHTML = items
        .map(
          ([key, value]) =>
            `<article class="stat-card"><small>${key}</small><strong>${value}</strong></article>`,
        )
        .join("");
    const latest = document.getElementById("latest");
    if (latest) {
      latest.innerHTML =
        s.latest
          .map(
            (order) =>
              `<article class="order-card"><div><small>${order.order_code}</small><h3>${order.customer}</h3><p>${order.created_at}</p></div><div><b>${money(order.total)}</b><span class="status">${order.status}</span></div></article>`,
          )
          .join("") || '<p class="empty">Belum ada pesanan.</p>';
    }
  } catch (error) {
    const el = document.getElementById(target);
    if (el) el.innerHTML = `<p class="error">${error.message}</p>`;
  }
}
async function loadAdminOrders() {
  await adminInit();
  const orders = await adminApi("../api/admin.php?resource=orders");
  document.getElementById("adminOrders").innerHTML =
    '<div class="table-wrap"><table><thead><tr><th>Order</th><th>Pelanggan</th><th>Periode</th><th>Total</th><th>Status</th><th>Aksi</th></tr></thead><tbody>' +
    orders
      .map(
        (o) =>
          `<tr><td>${o.order_code}</td><td>${o.customer}<br><small>${o.email}</small></td><td>${o.start_date} - ${o.end_date}</td><td>${money(o.total)}</td><td>${o.status}<br>${o.payment_status}</td><td><select onchange="updateOrder(${o.id},this.value)"><option value="">Pilih status</option><option>approved</option><option>waiting_payment</option><option>paid</option><option>ongoing</option><option>completed</option><option>cancelled</option></select></td></tr>`,
      )
      .join("") +
    "</tbody></table></div>";
}
async function updateOrder(id, status) {
  if (!status) return;
  await adminApi("../api/admin.php?resource=orders&id=" + id, {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ status, csrf: adminCsrf }),
  });
  loadAdminOrders();
}
async function toggleProduct(id, status) {
  await adminApi("../api/admin.php?resource=products&id=" + id, {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ status, csrf: adminCsrf }),
  });
  loadAdminProducts();
}

async function deleteProduct(id) {
  if (!window.confirm("Apakah Anda yakin ingin menghapus produk ini?")) return;
  await adminApi(`../api/admin.php?resource=products&id=${id}`, { method: "DELETE", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ csrf: adminCsrf }) });
  loadAdminProducts();
}
async function loadAdminCustomers() {
  await adminInit();
  const data = await adminApi("../api/admin.php?resource=customers");
  document.getElementById("customers").innerHTML =
    '<div class="table-wrap"><table><thead><tr><th>Nama</th><th>Email</th><th>HP</th><th>Order</th><th>Status</th></tr></thead><tbody>' +
    data
      .map(
        (c) =>
          `<tr><td>${c.name}</td><td>${c.email}</td><td>${c.phone}</td><td>${c.orders_count}</td><td>${c.status}</td></tr>`,
      )
      .join("") +
    "</tbody></table></div>";
}
async function loadReports() {
  await loadDashboard("report");
}

async function loadProductForm() {
  const form = document.getElementById("productForm");
  const categorySelect = document.getElementById("category_id");
  const productId = form.dataset.productId || "";
  try {
    await adminInit();
    const categories = await adminApi("../api/admin.php?resource=categories");
    window.adminCategoriesById = Object.fromEntries(categories.map((category) => [category.id, category]));
    categorySelect.innerHTML = categories
      .filter((category) => category.status === "active" || productId)
      .map((category) => `<option value="${category.id}">${category.name}</option>`)
      .join("");
    if (productId) {
      const product = await adminApi(`../api/admin.php?resource=products&id=${productId}`);
      Object.entries(product).forEach(([key, value]) => {
        const field = form.elements.namedItem(key);
        if (field && value !== null) field.value = value;
      });
      const existing = document.getElementById("existingImages");
      if (existing) {
        existing.innerHTML = (product.images || []).map((image) => `<img src="../../${image.image_path}" alt="Foto produk"><button type="button" class="text-button" onclick="deleteProductImage(${image.id})">Hapus</button>`).join("");
      }
    }
    const gallery = form.elements.namedItem("gallery");
    const preview = document.getElementById("imagePreview");
    if (gallery && preview) gallery.addEventListener("change", () => {
      preview.innerHTML = Array.from(gallery.files).map((file) => `<span>${file.name}</span>`).join("");
    });
    form.addEventListener("submit", async (event) => {
      event.preventDefault();
      const message = document.getElementById("productMessage");
      const payload = Object.fromEntries(new FormData(form).entries());
      delete payload.gallery;
      payload.csrf = adminCsrf;
      try {
        const response = await adminApi(
          `../api/admin.php?resource=products${productId ? `&id=${productId}` : ""}`,
          { method: productId ? "PUT" : "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(payload) },
        );
        message.textContent = "Produk berhasil disimpan.";
        const savedId = productId || response.id;
        if (gallery && savedId) {
          for (const file of gallery.files) {
            const upload = new FormData();
            upload.append("product_id", savedId);
            upload.append("image", file);
            upload.append("csrf", adminCsrf);
            await fetch("../api/admin.php?resource=product-images", { method: "POST", body: upload });
          }
        }
        if (!productId && response.id) window.location.href = `edit.php?id=${response.id}`;
      } catch (error) {
        message.textContent = error.message;
      }
    });
  } catch (error) {
    document.getElementById("productMessage").textContent = error.message;
  }
}

async function deleteProductImage(id) {
  if (!window.confirm("Hapus foto produk ini?")) return;
  await adminApi(`../api/admin.php?resource=product-images&id=${id}`, { method: "DELETE", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ csrf: adminCsrf }) });
  loadProductForm();
}

async function loadAdminCategories() {
  const container = document.getElementById("adminCategories");
  const form = document.getElementById("categoryForm");
  if (!container || !form) return;
  try {
    await adminInit();
    const categories = await adminApi("../api/admin.php?resource=categories");
    container.innerHTML = categories.map((category) => `<div class="category-row"><span><strong>${category.name}</strong><small>${category.product_count} produk</small></span><button class="text-button" onclick="toggleCategory(${category.id}, '${category.status === "active" ? "inactive" : "active"}')">${category.status === "active" ? "Nonaktifkan" : "Aktifkan"}</button></div>`).join("");
    form.onsubmit = async (event) => {
      event.preventDefault();
      const data = Object.fromEntries(new FormData(form).entries());
      data.csrf = adminCsrf;
      await adminApi("../api/admin.php?resource=categories", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(data) });
      form.reset();
      loadAdminCategories();
    };
  } catch (error) {
    container.innerHTML = `<p class="error">${error.message}</p>`;
  }
}

async function toggleCategory(id, status) {
  const name = window.adminCategoriesById?.[id]?.name;
  if (!name) return;
  await adminApi(`../api/admin.php?resource=categories&id=${id}`, { method: "PUT", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ name, status, csrf: adminCsrf }) });
  loadAdminCategories();
}
