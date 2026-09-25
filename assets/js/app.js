// ─── Core API helper ──────────────────────────────────────────────────────────
let csrf = "";

async function api(url, options = {}) {
  const response = await fetch(url, options);
  const result = await response.json();
  if (result.data?.csrf) csrf = result.data.csrf;
  if (!result.success) throw new Error(result.message || "Terjadi kesalahan");
  return result.data;
}

async function init() {
  try {
    await api("../api/session.php");
  } catch (error) {
    console.error(error);
  }
}

function formData(form) {
  return Object.fromEntries(new FormData(form).entries());
}

// ─── Format uang ─────────────────────────────────────────────────────────────
function money(value) {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    maximumFractionDigits: 0,
  }).format(Number(value) || 0);
}

// ─── Bug #2: Perbaikan productImage — prefix ../ untuk path lokal ────────────
function localImageSrc(path) {
  if (!path) return "";
  return path.startsWith("http") ? path : `../${path}`;
}

function productImage(product) {
  if (product.image) {
    const src = localImageSrc(product.image);
    return `<img src="${src}" alt="${esc(product.name)}" loading="lazy">`;
  }
  return `<div class="product-image-placeholder"><span>${esc(product.category)}</span></div>`;
}

function productGallery(product) {
  const images = product.images?.length
    ? product.images
    : product.image
    ? [{ image_path: product.image, is_primary: 1 }]
    : [];

  const gallery = images
    .map((image) => {
      const src = localImageSrc(image.image_path);
      return `<img src="${src}" alt="${esc(product.name)}" loading="lazy">`;
    })
    .join("");

  return gallery
    ? `<div class="product-gallery">${gallery}</div>`
    : productImage(product);
}

// ─── Escape helper (mencegah XSS di innerHTML) ────────────────────────────────
function esc(str) {
  return String(str ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

// ─── Auth forms ───────────────────────────────────────────────────────────────
function authForm(action) {
  init();
  document.getElementById("authForm").addEventListener("submit", async (event) => {
    event.preventDefault();
    const message = document.getElementById("message");
    try {
      const data = await api("../api/auth.php?action=" + action, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ ...formData(event.target), csrf }),
      });
      location.href = data.role === "admin" ? "../admin/index.php" : "index.php";
    } catch (error) {
      message.textContent = error.message;
    }
  });
}

// ─── Notifikasi (Fitur #16) ───────────────────────────────────────────────────
async function loadNotifications() {
  const bell = document.getElementById("notifBell");
  const list = document.getElementById("notifList");
  if (!bell) return;

  try {
    const notifications = await api("../api/notifications.php");
    const unread = notifications.filter((n) => !parseInt(n.is_read)).length;

    bell.innerHTML = `🔔${unread > 0 ? `<sup class="notif-badge">${unread}</sup>` : ""}`;
    bell.setAttribute("aria-label", `Notifikasi${unread > 0 ? ` (${unread} belum dibaca)` : ""}`);

    if (list) {
      list.innerHTML = notifications.length
        ? notifications
            .map(
              (n) =>
                `<div class="notif-item${parseInt(n.is_read) ? "" : " unread"}" data-id="${n.id}">
                  <strong>${esc(n.title)}</strong>
                  <p>${esc(n.message)}</p>
                  <small>${n.created_at}</small>
                  ${!parseInt(n.is_read) ? `<button class="text-button" onclick="markNotifRead(${n.id})">Tandai dibaca</button>` : ""}
                </div>`
            )
            .join("")
        : '<p class="empty">Tidak ada notifikasi.</p>';
    }
  } catch (e) {
    // Notif bukan halaman kritis, cukup log
    console.error("Notifikasi:", e.message);
  }
}

async function markNotifRead(id) {
  try {
    await api("../api/notifications.php?id=" + id, {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ csrf }),
    });
    loadNotifications();
  } catch (e) {
    console.error(e);
  }
}

// ─── Katalog produk ───────────────────────────────────────────────────────────
async function loadCatalog() {
  await init();
  await loadNotifications();
  const search = document.getElementById("search");

  async function render() {
    try {
      const data = await api(
        "../api/products.php?search=" + encodeURIComponent(search.value)
      );
      document.getElementById("categories").innerHTML = data.categories
        .map(
          (c) =>
            `<button class="chip" onclick="document.getElementById('search').value='${esc(c.name)}';renderCatalog()">${esc(c.name)}</button>`
        )
        .join("");
      document.getElementById("products").innerHTML =
        data.products
          .map(
            (p) =>
              `<article class="product-card">
                ${productImage(p)}
                <div>
                  <small>${esc(p.category)}</small>
                  <h3>${esc(p.name)}</h3>
                  <strong>${money(p.price_per_day)} <small>/ hari</small></strong>
                  <a class="button outline" href="product.php?id=${esc(p.id)}">Lihat detail</a>
                </div>
              </article>`
          )
          .join("") || '<p class="empty">Produk tidak ditemukan.</p>';
    } catch (error) {
      document.getElementById("products").innerHTML = `<p class="error">${esc(error.message)}</p>`;
    }
  }

  window.renderCatalog = render;
  search.addEventListener("input", render);
  render();
}

// ─── Detail produk ────────────────────────────────────────────────────────────
async function loadProduct(id) {
  await init();
  await loadNotifications();
  try {
    const p = await api("../api/products.php?id=" + id);
    const minDate = new Date().toISOString().split("T")[0];
    document.getElementById("productDetail").innerHTML = `
      <div class="detail-grid">
        <div>${productGallery(p)}</div>
        <div>
          <p class="eyebrow dark">${esc(p.category)}</p>
          <h1>${esc(p.name)}</h1>
          <p class="price">${money(p.price_per_day)} <small>/ hari</small></p>
          <p>${esc(p.short_description || p.description || "Perlengkapan outdoor bersih dan terawat.")}</p>
          <div class="product-terms">
            <p><b>Deposit</b><br>${money(p.deposit || 0)}</p>
            <p><b>Durasi rental</b><br>${p.minimum_rental_days || 1} hari${p.maximum_rental_days ? ` - ${p.maximum_rental_days} hari` : " atau lebih"}</p>
            <p><b>Stok tersedia</b><br>${esc(p.stock)} ${esc(p.unit || "unit")}</p>
          </div>
          <details>
            <summary>Ketentuan rental</summary>
            <p>${esc(p.rental_terms || "Hubungi admin untuk ketentuan rental.")}</p>
            <p>${esc(p.usage_terms || "Gunakan perlengkapan sesuai fungsinya.")}</p>
            <p>${esc(p.return_terms || "Kembalikan sesuai jadwal dan kondisi semula.")}</p>
          </details>
          <form id="rentForm" class="stack">
            <label>Mulai sewa
              <input class="input" type="date" name="start_date" min="${minDate}" required>
            </label>
            <label>Kembali
              <input class="input" type="date" name="end_date" min="${minDate}" required>
            </label>
            <label>Jumlah
              <input class="input" type="number" name="quantity" value="1" min="1" max="${esc(p.stock)}" required>
            </label>
            ${p.minimum_rental_days > 1 ? `<p class="form-hint">Minimal sewa: <b>${p.minimum_rental_days} hari</b></p>` : ""}
            <button class="button">Tambah ke keranjang</button>
            <p id="message" class="form-message"></p>
          </form>
        </div>
      </div>`;

    document.getElementById("rentForm").addEventListener("submit", async (e) => {
      e.preventDefault();
      const msg = document.getElementById("message");
      try {
        await api("../api/cart.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ ...formData(e.target), product_id: id, csrf }),
        });
        location.href = "cart.php";
      } catch (error) {
        msg.textContent = error.message;
      }
    });
  } catch (error) {
    document.getElementById("productDetail").innerHTML = `<p class="error">${esc(error.message)}</p>`;
  }
}

// ─── Keranjang ────────────────────────────────────────────────────────────────
async function loadCart() {
  await init();
  await loadNotifications();
  try {
    const items = await api("../api/cart.php");
    if (!items.length) {
      document.getElementById("cart").innerHTML = '<p class="empty">Keranjang masih kosong.</p>';
      return;
    }
    const days = Math.max(
      1,
      Math.round((new Date(items[0].end_date) - new Date(items[0].start_date)) / 86400000)
    );
    const total = items.reduce((sum, item) => sum + item.price_per_day * item.quantity * days, 0);
    document.getElementById("cart").innerHTML =
      items
        .map(
          (i) =>
            `<div class="cart-row">
              <div class="cart-image">${productImage(i)}</div>
              <div>
                <h3>${esc(i.name)}</h3>
                <p>${money(i.price_per_day)} / hari · ${esc(i.quantity)} unit · ${esc(i.start_date)} sampai ${esc(i.end_date)}</p>
                <button class="text-button" onclick="removeCart(${i.id})">Hapus</button>
              </div>
              <strong>${money(i.price_per_day * i.quantity * days)}</strong>
            </div>`
        )
        .join("") +
      `<div class="summary">
        <span>Total estimasi</span>
        <strong>${money(total)}</strong>
        <a class="button" href="checkout.php">Lanjut checkout</a>
      </div>`;
  } catch (error) {
    document.getElementById("cart").innerHTML = `<p class="error">${esc(error.message)}</p>`;
  }
}

async function removeCart(id) {
  try {
    await api("../api/cart.php?id=" + id, {
      method: "DELETE",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ csrf }),
    });
    loadCart();
  } catch (error) {
    alert(error.message);
  }
}

// ─── Checkout (Bug #1: tambah pilihan metode pembayaran) ─────────────────────
async function loadCheckout() {
  await init();
  await loadNotifications();
  try {
    const items = await api("../api/cart.php");
    if (!items.length) throw new Error("Keranjang kosong.");
    const days = Math.max(
      1,
      Math.round((new Date(items[0].end_date) - new Date(items[0].start_date)) / 86400000)
    );
    const subtotal = items.reduce((sum, item) => sum + item.price_per_day * item.quantity * days, 0);

    document.getElementById("checkout").innerHTML = `
      <div class="checkout-grid">
        <div>
          <h3>Ringkasan pesanan</h3>
          ${items
            .map(
              (i) =>
                `<p>${esc(i.name)} × ${i.quantity}<br>
                 <small>${money(i.price_per_day)} × ${days} hari = ${money(i.price_per_day * i.quantity * days)}</small></p>`
            )
            .join("")}
          <p><small>Periode: ${esc(items[0].start_date)} sampai ${esc(items[0].end_date)} (${days} hari)</small></p>
        </div>
        <div class="summary">
          <span>Subtotal</span><strong>${money(subtotal)}</strong>
          <p class="form-hint">Deposit akan dihitung setelah pesanan dikonfirmasi.</p>
          <textarea id="notes" class="input" placeholder="Catatan pesanan (opsional)"></textarea>
          <button class="button" onclick="placeOrder()">Konfirmasi pesanan</button>
          <p id="message" class="form-message"></p>
        </div>
      </div>`;
  } catch (error) {
    document.getElementById("checkout").innerHTML = `<p class="error">${esc(error.message)}</p>`;
  }
}

async function placeOrder() {
  const btn = document.querySelector("#checkout .button");
  if (btn) btn.disabled = true;
  try {
    const data = await api("../api/orders.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        notes: document.getElementById("notes")?.value || "",
        csrf,
      }),
    });
    location.href = "orders.php?created=" + data.order_code;
  } catch (error) {
    document.getElementById("message").textContent = error.message;
    if (btn) btn.disabled = false;
  }
}

// ─── Pesanan customer (Bug #1 + Fitur #13 + Fitur #14) ───────────────────────
async function loadOrders() {
  await init();
  await loadNotifications();
  const container = document.getElementById("orders");

  // Tampilkan pesan sukses jika baru buat order
  const params = new URLSearchParams(location.search);
  if (params.get("created")) {
    const banner = document.createElement("div");
    banner.className = "alert alert-success";
    banner.textContent = `Pesanan ${params.get("created")} berhasil dibuat! Menunggu persetujuan admin, tombol untuk membayar akan muncul jika admin sudah menyetujui pesanan.`;
    container.before(banner);
  }

  try {
    const orders = await api("../api/orders.php");
    if (!orders.length) {
      container.innerHTML = '<p class="empty">Belum ada pesanan.</p>';
      return;
    }

    container.innerHTML = orders
      .map((o) => {
        const canCancel  = ["pending", "approved"].includes(o.status);
        const canPay     = ["approved", "waiting_payment"].includes(o.status) && o.payment_status !== "paid";
        const hasPayment = o.payment !== null;

        return `
          <article class="order-card" id="order-${o.id}">
            <div>
              <small>${esc(o.order_code)}</small>
              <h3>${esc(o.start_date)} sampai ${esc(o.end_date)}</h3>
              <p>${o.items.map((i) => `${esc(i.product_name)} × ${i.quantity}`).join(", ")}</p>
              ${o.notes ? `<p><small>Catatan: ${esc(o.notes)}</small></p>` : ""}
            </div>
            <div class="order-actions">
              <b>${money(o.total)}</b>
              <span class="status">${esc(o.status)} · ${esc(o.payment_status)}</span>
              ${o.deposit > 0 ? `<small>Deposit: ${money(o.deposit)}</small>` : ""}
              ${
                hasPayment
                  ? `<small>Pembayaran: ${esc(o.payment.payment_method)} — ${esc(o.payment.payment_status)}</small>`
                  : ""
              }
              <div class="order-buttons">
                ${
                  canPay && !hasPayment
                    ? `<button class="button small" onclick="showPaymentForm(${o.id})">Bayar sekarang</button>`
                    : ""
                }
                ${
                  canPay && hasPayment && o.payment.payment_status !== "paid" && !o.payment.proof_image
                    ? `<button class="button small outline" onclick="showUploadForm(${o.id}, ${o.payment.id})">Upload bukti</button>`
                    : ""
                }
                ${
                  canCancel
                    ? `<button class="text-button" onclick="cancelOrder(${o.id})">Batalkan</button>`
                    : ""
                }
              </div>
              <div id="payment-form-${o.id}" class="payment-form" style="display:none"></div>
            </div>
          </article>`;
      })
      .join("");
  } catch (error) {
    container.innerHTML = `<p class="error">${esc(error.message)}</p>`;
  }
}

// Bug #1: Form pilihan metode pembayaran
function showPaymentForm(orderId) {
  const container = document.getElementById(`payment-form-${orderId}`);
  container.style.display = "block";
  container.innerHTML = `
    <div class="stack">
      <p><b>Pilih metode pembayaran:</b></p>
      <label class="radio-option">
        <input type="radio" name="pay-method-${orderId}" value="bank_transfer" checked>
        Transfer Bank
      </label>
      <label class="radio-option">
        <input type="radio" name="pay-method-${orderId}" value="ewallet">
        E-Wallet (GoPay/OVO/DANA)
      </label>
      <label class="radio-option">
        <input type="radio" name="pay-method-${orderId}" value="cod">
        Bayar di Tempat (COD)
      </label>
      <button class="button small" onclick="submitPayment(${orderId})">Konfirmasi pembayaran</button>
      <button class="text-button" onclick="document.getElementById('payment-form-${orderId}').style.display='none'">Batal</button>
      <p id="pay-msg-${orderId}" class="form-message"></p>
    </div>`;
}

async function submitPayment(orderId) {
  const selected = document.querySelector(`input[name="pay-method-${orderId}"]:checked`);
  if (!selected) return;
  const msg = document.getElementById(`pay-msg-${orderId}`);
  try {
    await api("../api/payment.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ order_id: orderId, payment_method: selected.value, csrf }),
    });
    // Setelah pilih metode non-COD, tampilkan form upload bukti
    if (selected.value !== "cod") {
      loadOrders();
    } else {
      loadOrders();
    }
  } catch (error) {
    if (msg) msg.textContent = error.message;
  }
}

// Bug #1 / Fitur #14: Form upload bukti pembayaran
function showUploadForm(orderId, paymentId) {
  const container = document.getElementById(`payment-form-${orderId}`);
  container.style.display = "block";
  container.innerHTML = `
    <div class="stack">
      <p><b>Upload bukti transfer:</b></p>
      <input class="input" type="file" id="proof-file-${orderId}" accept="image/jpeg,image/png,image/webp" required>
      <button class="button small" onclick="uploadProof(${orderId})">Upload</button>
      <button class="text-button" onclick="document.getElementById('payment-form-${orderId}').style.display='none'">Batal</button>
      <p id="upload-msg-${orderId}" class="form-message"></p>
    </div>`;
}

async function uploadProof(orderId) {
  const input = document.getElementById(`proof-file-${orderId}`);
  const msg   = document.getElementById(`upload-msg-${orderId}`);
  if (!input?.files[0]) {
    if (msg) msg.textContent = "Pilih file gambar terlebih dahulu.";
    return;
  }

  const formPayload = new FormData();
  formPayload.append("order_id", orderId);
  formPayload.append("image", input.files[0]);
  formPayload.append("csrf", csrf);

  try {
    const response = await fetch("../api/payment_upload.php", {
      method: "POST",
      body: formPayload,
    });
    const result = await response.json();
    if (!result.success) throw new Error(result.message);
    loadOrders();
  } catch (error) {
    if (msg) msg.textContent = error.message;
  }
}

// Fitur #13: Batalkan pesanan
async function cancelOrder(orderId) {
  if (!confirm("Yakin ingin membatalkan pesanan ini?")) return;
  try {
    await api("../api/orders.php?id=" + orderId, {
      method: "DELETE",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ csrf }),
    });
    loadOrders();
  } catch (error) {
    alert(error.message);
  }
}

// ─── Profil (Fitur #15) ───────────────────────────────────────────────────────
async function loadProfile() {
  await init();
  await loadNotifications();
  const container = document.getElementById("profile");
  try {
    const data = await api("../api/session.php");
    const u = data.user;
    if (!u) { location.href = "login.php"; return; }

    container.innerHTML = `
      <div class="profile-info">
        <p><b>Nama</b><br>${esc(u.name)}</p>
        <p><b>Email</b><br>${esc(u.email)}</p>
        <p><b>Nomor HP</b><br>${esc(u.phone)}</p>
      </div>
      <hr>
      <h2>Edit profil</h2>
      <form id="profileForm" class="stack">
        <label>Nama
          <input class="input" type="text" name="name" value="${esc(u.name)}" required>
        </label>
        <label>Nomor HP
          <input class="input" type="tel" name="phone" value="${esc(u.phone)}" required>
        </label>
        <h3>Ganti password <small>(kosongkan jika tidak ingin mengubah)</small></h3>
        <label>Password saat ini
          <input class="input" type="password" name="current_password" autocomplete="current-password">
        </label>
        <label>Password baru
          <input class="input" type="password" name="new_password" minlength="6" autocomplete="new-password">
        </label>
        <button class="button" type="submit">Simpan perubahan</button>
        <p id="profileMessage" class="form-message"></p>
      </form>`;

    document.getElementById("profileForm").addEventListener("submit", async (e) => {
      e.preventDefault();
      const msg     = document.getElementById("profileMessage");
      const payload = { ...formData(e.target), csrf };
      // Hapus field password kosong agar tidak dikirim
      if (!payload.current_password) delete payload.current_password;
      if (!payload.new_password)     delete payload.new_password;
      try {
        await api("../api/session.php", {
          method: "PUT",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload),
        });
        msg.textContent  = "Profil berhasil diperbarui.";
        msg.style.color  = "var(--green, green)";
        // Refresh tampilan
        loadProfile();
      } catch (error) {
        msg.textContent = error.message;
        msg.style.color = "";
      }
    });
  } catch (error) {
    container.innerHTML = `<p class="error">${esc(error.message)}</p>`;
  }
}
