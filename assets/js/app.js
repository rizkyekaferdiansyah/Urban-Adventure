// ─── Core API helper ──────────────────────────────────────────────────────────
let csrf = "";

async function api(url, options = {}) {
  const response = await fetch(url, options);
  const result   = await response.json();
  if (result.data?.csrf) csrf = result.data.csrf;
  if (!result.success) throw new Error(result.message || "Terjadi kesalahan");
  return result.data;
}

async function init() {
  try { await api("../api/session.php"); } catch (e) { console.error(e); }
}

function formData(form) {
  return Object.fromEntries(new FormData(form).entries());
}

function money(value) {
  return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 })
    .format(Number(value) || 0);
}

function esc(str) {
  return String(str ?? "")
    .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;").replace(/'/g, "&#39;");
}

function formatDate(str) {
  if (!str) return "";
  const d = new Date(str);
  if (isNaN(d)) return str;
  return d.toLocaleDateString("id-ID", { day: "numeric", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" });
}

function localImageSrc(path) {
  if (!path) return "";
  return path.startsWith("http") ? path : `../${path}`;
}

function productImage(product) {
  if (product.image) return `<img src="${localImageSrc(product.image)}" alt="${esc(product.name)}" loading="lazy">`;
  return `<div class="product-image-placeholder"><span>${esc(product.category || "")}</span></div>`;
}

function productGallery(product) {
  const images = product.images?.length ? product.images : product.image ? [{ image_path: product.image }] : [];
  const html = images.map((img) => `<img src="${localImageSrc(img.image_path)}" alt="${esc(product.name)}" loading="lazy">`).join("");
  return html ? `<div class="product-gallery">${html}</div>` : productImage(product);
}

const STATUS_LABEL = {
  pending: "Menunggu", approved: "Disetujui", waiting_payment: "Menunggu Pembayaran",
  paid: "Lunas", ongoing: "Sedang Berjalan", completed: "Selesai", cancelled: "Dibatalkan",
};
const PAY_STATUS_LABEL = {
  unpaid: "Belum Bayar", waiting_verification: "Menunggu Verifikasi", paid: "Lunas", rejected: "Bukti Ditolak",
};
const STATUS_CLASS = {
  pending: "status-yellow", approved: "status-blue", waiting_payment: "status-orange",
  paid: "status-teal", ongoing: "status-green", completed: "status-gray", cancelled: "status-red",
};
const PAY_STATUS_CLASS = {
  unpaid: "status-gray", waiting_verification: "status-orange", paid: "status-green", rejected: "status-red",
};

// ─── Auth ─────────────────────────────────────────────────────────────────────
function authForm(action) {
  init();
  document.getElementById("authForm").addEventListener("submit", async (event) => {
    event.preventDefault();
    const message = document.getElementById("message");
    const btn     = event.target.querySelector("button[type=submit]");
    message.textContent = "";
    if (btn) { btn.disabled = true; btn.textContent = action === "login" ? "Masuk…" : "Mendaftar…"; }
    try {
      const data = await api("../api/auth.php?action=" + action, {
        method: "POST", headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ ...formData(event.target), csrf }),
      });
      window.location.replace(data.role === "admin" ? "../admin/index.php" : "index.php");
    } catch (error) {
      message.textContent = error.message;
      if (btn) { btn.disabled = false; btn.textContent = action === "login" ? "Masuk" : "Daftar"; }
    }
  });
}

// ─── Notifikasi ───────────────────────────────────────────────────────────────
async function loadNotifications() {
  const bell = document.getElementById("notifBell");
  const list = document.getElementById("notifList");
  if (!bell) return;

  try {
    const notifications = await api("../api/notifications.php");
    const unread = notifications.filter((n) => !parseInt(n.is_read)).length;

    // Update badge
    bell.innerHTML = `🔔${unread > 0 ? `<sup class="notif-badge">${unread}</sup>` : ""}`;
    bell.setAttribute("aria-label", `Notifikasi${unread > 0 ? ` (${unread} belum dibaca)` : ""}`);

    if (!list) return;

    if (!notifications.length) {
      list.innerHTML = '<p class="notif-empty">Tidak ada notifikasi.</p>';
      return;
    }

    // Toolbar: tandai semua dibaca + hapus semua
    const toolbar = `
      <div class="notif-toolbar">
        ${unread > 0
          ? `<button class="text-button" onclick="markAllNotifRead()">✓ Tandai semua dibaca</button>`
          : `<span class="notif-toolbar-spacer"></span>`}
        <button class="text-button danger" onclick="clearAllNotifications()">🗑 Hapus semua</button>
      </div>`;

    list.innerHTML = toolbar + notifications.map((n) => {
      const isRead = parseInt(n.is_read);
      return `
        <div class="notif-item${isRead ? "" : " unread"}" data-id="${n.id}">
          <div class="notif-item-body">
            <strong class="notif-title">${esc(n.title)}</strong>
            <p class="notif-msg">${esc(n.message)}</p>
            <small class="notif-time">${formatDate(n.created_at)}</small>
          </div>
          <div class="notif-item-actions">
            ${!isRead
              ? `<button class="notif-action-btn" title="Tandai dibaca" onclick="markNotifRead(${n.id})">✓</button>`
              : ""}
            <button class="notif-action-btn notif-delete-btn" title="Hapus notifikasi" onclick="deleteNotif(${n.id})">✕</button>
          </div>
        </div>`;
    }).join("");

  } catch (e) {
    console.error("Notifikasi:", e.message);
  }
}

async function markNotifRead(id) {
  try {
    await api(`../api/notifications.php?id=${id}`, {
      method: "PUT", headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ csrf }),
    });
    loadNotifications();
  } catch (e) { console.error(e); }
}

async function markAllNotifRead() {
  try {
    await api("../api/notifications.php", {
      method: "PUT", headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ csrf }),
    });
    loadNotifications();
  } catch (e) { console.error(e); }
}

async function deleteNotif(id) {
  try {
    await api(`../api/notifications.php?id=${id}`, {
      method: "DELETE", headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ csrf }),
    });
    // Animasi hilang dulu sebelum reload
    const el = document.querySelector(`.notif-item[data-id="${id}"]`);
    if (el) {
      el.style.transition = "opacity 0.15s, transform 0.15s";
      el.style.opacity    = "0";
      el.style.transform  = "translateX(8px)";
      setTimeout(() => loadNotifications(), 150);
    } else {
      loadNotifications();
    }
  } catch (e) { console.error(e); }
}

async function clearAllNotifications() {
  if (!confirm("Hapus semua notifikasi?")) return;
  try {
    await api("../api/notifications.php", {
      method: "DELETE", headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ csrf }),
    });
    loadNotifications();
  } catch (e) { console.error(e); }
}

// ─── Katalog ──────────────────────────────────────────────────────────────────
async function loadCatalog() {
  await init();
  // Notifikasi dihandle polling, tidak perlu dipanggil di sini
  const search = document.getElementById("search");
  async function render() {
    try {
      const data = await api("../api/products.php?search=" + encodeURIComponent(search.value));
      document.getElementById("categories").innerHTML = data.categories
        .map((c) => `<button class="chip" onclick="document.getElementById('search').value='${esc(c.name)}';renderCatalog()">${esc(c.name)}</button>`)
        .join("");
      document.getElementById("products").innerHTML =
        data.products.map((p) => `
          <article class="product-card">
            ${productImage(p)}
            <div>
              <small>${esc(p.category)}</small>
              <h3>${esc(p.name)}</h3>
              <strong>${money(p.price_per_day)} <small>/ hari</small></strong>
              <a class="button outline" href="product.php?id=${p.id}">Lihat detail</a>
            </div>
          </article>`).join("") || '<p class="empty">Produk tidak ditemukan.</p>';
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
            <label>Mulai sewa<input class="input" type="date" name="start_date" min="${minDate}" required></label>
            <label>Kembali<input class="input" type="date" name="end_date" min="${minDate}" required></label>
            <label>Jumlah<input class="input" type="number" name="quantity" value="1" min="1" max="${esc(p.stock)}" required></label>
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
          method: "POST", headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ ...formData(e.target), product_id: id, csrf }),
        });
        location.href = "cart.php";
      } catch (error) { msg.textContent = error.message; }
    });
  } catch (error) {
    document.getElementById("productDetail").innerHTML = `<p class="error">${esc(error.message)}</p>`;
  }
}

// ─── Keranjang ────────────────────────────────────────────────────────────────
async function loadCart() {
  await init();
  try {
    const items = await api("../api/cart.php");
    if (!items.length) {
      document.getElementById("cart").innerHTML = '<p class="empty">Keranjang masih kosong.</p>';
      return;
    }
    const days  = Math.max(1, Math.round((new Date(items[0].end_date) - new Date(items[0].start_date)) / 86400000));
    const total = items.reduce((sum, i) => sum + i.price_per_day * i.quantity * days, 0);
    document.getElementById("cart").innerHTML =
      items.map((i) => `
        <div class="cart-row">
          <div class="cart-image">${productImage(i)}</div>
          <div>
            <h3>${esc(i.name)}</h3>
            <p>${money(i.price_per_day)} / hari · ${esc(i.quantity)} unit · ${esc(i.start_date)} sampai ${esc(i.end_date)}</p>
            <button class="text-button" onclick="removeCart(${i.id})">Hapus</button>
          </div>
          <strong>${money(i.price_per_day * i.quantity * days)}</strong>
        </div>`).join("") +
      `<div class="summary"><span>Total estimasi</span><strong>${money(total)}</strong><a class="button" href="checkout.php">Lanjut checkout</a></div>`;
  } catch (error) {
    document.getElementById("cart").innerHTML = `<p class="error">${esc(error.message)}</p>`;
  }
}

async function removeCart(id) {
  try {
    await api("../api/cart.php?id=" + id, {
      method: "DELETE", headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ csrf }),
    });
    loadCart();
    if (window.LivePoll) LivePoll.ping();
  } catch (error) { alert(error.message); }
}

// ─── Checkout ─────────────────────────────────────────────────────────────────
async function loadCheckout() {
  await init();
  try {
    const items = await api("../api/cart.php");
    if (!items.length) throw new Error("Keranjang kosong.");
    const days     = Math.max(1, Math.round((new Date(items[0].end_date) - new Date(items[0].start_date)) / 86400000));
    const subtotal = items.reduce((sum, i) => sum + i.price_per_day * i.quantity * days, 0);
    const deposit  = items.reduce((sum, i) => sum + (parseFloat(i.deposit) || 0) * i.quantity, 0);
    const total    = subtotal + deposit;

    document.getElementById("checkout").innerHTML = `
      <div class="checkout-grid">
        <div>
          <h3>Ringkasan pesanan</h3>
          ${items.map((i) => `
            <div class="checkout-item">
              <div class="checkout-item-info">
                ${productImage(i)}
                <div><strong>${esc(i.name)}</strong><p>${esc(i.quantity)} unit × ${days} hari</p></div>
              </div>
              <strong>${money(i.price_per_day * i.quantity * days)}</strong>
            </div>`).join("")}
          <p class="checkout-period">📅 Periode: <strong>${esc(items[0].start_date)}</strong> sampai <strong>${esc(items[0].end_date)}</strong> (${days} hari)</p>
        </div>
        <div class="summary">
          <div class="summary-rows">
            <div class="summary-row"><span>Subtotal sewa</span><span>${money(subtotal)}</span></div>
            ${deposit > 0 ? `<div class="summary-row"><span>Deposit</span><span>${money(deposit)}</span></div>` : ""}
            <div class="summary-row summary-total"><span>Total</span><strong>${money(total)}</strong></div>
          </div>
          <textarea id="notes" class="input" placeholder="Catatan pesanan (opsional)" rows="2"></textarea>
          <button class="button" id="placeOrderBtn" onclick="placeOrder()">Konfirmasi &amp; Lanjut Bayar</button>
          <p class="form-hint">Setelah konfirmasi, kamu langsung bisa memilih metode pembayaran.</p>
          <p id="message" class="form-message"></p>
        </div>
      </div>`;
  } catch (error) {
    document.getElementById("checkout").innerHTML = `<p class="error">${esc(error.message)}</p>`;
  }
}

async function placeOrder() {
  const btn = document.getElementById("placeOrderBtn");
  const msg = document.getElementById("message");
  if (btn) { btn.disabled = true; btn.textContent = "Memproses…"; }
  if (msg)  msg.textContent = "";
  try {
    const data = await api("../api/orders.php", {
      method: "POST", headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ notes: document.getElementById("notes")?.value || "", csrf }),
    });
    if (window.LivePoll) LivePoll.ping();
    location.href = "orders.php?new=" + data.order_code;
  } catch (error) {
    if (msg) msg.textContent = error.message;
    if (btn) { btn.disabled = false; btn.textContent = "Konfirmasi & Lanjut Bayar"; }
  }
}

// ─── Riwayat pesanan ──────────────────────────────────────────────────────────
// PENTING: tidak memanggil loadNotifications() di sini.
// Notifikasi diupdate terpisah oleh polling.
async function loadOrders() {
  await init();
  const container = document.getElementById("orders");
  if (!container) return;

  // Selalu bersihkan banner lama setiap loadOrders dipanggil (termasuk dari polling)
  document.querySelector(".alert-success")?.remove();

  // Banner pesanan baru — hanya tampil sekali saat redirect dari checkout
  const params  = new URLSearchParams(location.search);
  const newCode = params.get("new");
  if (newCode) {
    const banner = document.createElement("div");
    banner.className = "alert alert-success";
    banner.innerHTML = `<strong>Pesanan ${esc(newCode)} berhasil dibuat!</strong> Pilih metode pembayaran di bawah.`;
    container.before(banner);
    // Hapus ?new= dari URL agar tidak muncul lagi saat refresh manual
    history.replaceState(null, "", location.pathname);
  }

  try {
    const orders = await api("../api/orders.php");
    if (!orders.length) {
      container.innerHTML = '<p class="empty">Belum ada pesanan.</p>';
      return;
    }
    container.innerHTML = orders.map((o) => renderOrderCard(o)).join("");
  } catch (error) {
    container.innerHTML = `<p class="error">${esc(error.message)}</p>`;
  }
}

function renderOrderCard(o) {
  const hasActivePayment = o.payment && o.payment.payment_status !== "rejected";
  const isDone           = ["completed", "cancelled", "paid", "ongoing"].includes(o.status);
  const canPay           = !isDone && !hasActivePayment;
  const canUpload        = o.payment && !o.payment.proof_image
                           && o.payment.payment_status === "waiting_verification"
                           && o.payment.payment_method !== "cod";
  const canCancel        = ["pending", "approved", "waiting_payment"].includes(o.status);
  const itemList         = o.items.map((i) => `${esc(i.product_name)} ×${i.quantity}`).join(", ");
  const methodLabel      = { bank_transfer: "Transfer Bank", qris: "QRIS", cod: "Bayar di Tempat" };

  return `
    <article class="order-card" id="order-${o.id}">
      <div class="order-card-main">
        <div class="order-card-header">
          <span class="order-code">${esc(o.order_code)}</span>
          <span class="order-date">${formatDate(o.created_at)}</span>
        </div>
        <p class="order-items">${itemList}</p>
        <p class="order-period">📅 ${esc(o.start_date)} – ${esc(o.end_date)} (${o.total_days} hari)</p>
        ${o.notes ? `<p class="order-notes">💬 ${esc(o.notes)}</p>` : ""}
      </div>
      <div class="order-card-side">
        <div class="order-amounts">
          <strong class="order-total">${money(o.total)}</strong>
          ${parseFloat(o.deposit) > 0 ? `<small>termasuk deposit ${money(o.deposit)}</small>` : ""}
        </div>
        <div class="order-badges">
          <span class="status-badge ${STATUS_CLASS[o.status] || "status-gray"}">${STATUS_LABEL[o.status] || esc(o.status)}</span>
          <span class="status-badge ${PAY_STATUS_CLASS[o.payment_status] || "status-gray"}">${PAY_STATUS_LABEL[o.payment_status] || esc(o.payment_status)}</span>
        </div>
        ${o.payment ? `
          <div class="order-payment-info">
            <small>${esc(methodLabel[o.payment.payment_method] || o.payment.payment_method)}
            ${o.payment.proof_image ? ` · <a href="../${esc(o.payment.proof_image)}" target="_blank">Lihat bukti</a>` : ""}</small>
          </div>` : ""}
        <div class="order-actions-row">
          ${canPay    ? `<button class="button small" onclick="showPaymentForm(${o.id})">💳 Bayar Sekarang</button>` : ""}
          ${canUpload ? `<button class="button small outline" onclick="showUploadForm(${o.id},${o.payment.id})">📎 Upload Bukti</button>` : ""}
          ${canCancel ? `<button class="text-button danger" onclick="cancelOrder(${o.id})">Batalkan</button>` : ""}
        </div>
        <div id="payment-form-${o.id}" class="payment-inline" style="display:none"></div>
      </div>
    </article>`;
}

function showPaymentForm(orderId) {
  const container = document.getElementById(`payment-form-${orderId}`);
  container.style.display = "block";
  container.innerHTML = `<p class="muted" style="padding:8px 0">Memuat metode pembayaran…</p>`;

  // Fetch payment settings, lalu render form
  fetch("../api/payment_settings.php", { cache: "no-store" })
    .then((r) => r.json())
    .then((res) => _renderPaymentForm(orderId, container, res.success ? (res.data || {}) : {}))
    .catch(() => _renderPaymentForm(orderId, container, {}));
}

function _renderPaymentForm(orderId, container, s) {
  // Default: semua aktif jika setting belum ada
  const bankActive = s.bank_is_active !== "0";  // default aktif
  const qrisActive = s.qris_is_active !== "0";  // default aktif

  // Tentukan default yang tercentang: bank > qris > cod
  const defaultBank = bankActive;
  const defaultQris = !bankActive && qrisActive;

  const bankOption = bankActive ? `
    <label class="radio-option">
      <input type="radio" name="pm-${orderId}" value="bank_transfer" ${defaultBank ? "checked" : ""}>
      <span>🏦 Transfer Bank</span>
    </label>` : "";

  const qrisOption = qrisActive ? `
    <label class="radio-option">
      <input type="radio" name="pm-${orderId}" value="qris" ${defaultQris ? "checked" : ""}>
      <span>📲 QRIS</span>
    </label>` : "";

  const codOption = `
    <label class="radio-option">
      <input type="radio" name="pm-${orderId}" value="cod">
      <span>🤝 Bayar di Tempat (COD)</span>
    </label>`;

  container.innerHTML = `
    <div class="payment-method-form">
      <p><strong>Pilih metode pembayaran:</strong></p>
      ${bankOption}
      ${qrisOption}
      ${codOption}
      <div id="pay-detail-${orderId}" class="pay-detail-box" style="display:none"></div>
      <div class="payment-method-actions">
        <button class="button small" onclick="submitPayment(${orderId})">Konfirmasi</button>
        <button class="text-button" onclick="document.getElementById('payment-form-${orderId}').style.display='none'">Batal</button>
      </div>
      <p id="pay-msg-${orderId}" class="form-message"></p>
    </div>`;

  // Tampilkan detail untuk pilihan yang sudah tercentang
  const checkedRadio = container.querySelector(`input[name="pm-${orderId}"]:checked`);
  if (checkedRadio) _renderPayDetail(orderId, checkedRadio.value, s);

  // Update detail saat user ganti pilihan
  container.querySelectorAll(`input[name="pm-${orderId}"]`).forEach((radio) => {
    radio.addEventListener("change", () => _renderPayDetail(orderId, radio.value, s));
  });
}

function _renderPayDetail(orderId, method, s) {
  const box = document.getElementById(`pay-detail-${orderId}`);
  if (!box) return;

  if (method === "bank_transfer") {
    box.style.display = "block";
    box.innerHTML = `
      <div class="pay-info-card">
        <p class="pay-info-label">Detail Rekening Tujuan</p>
        <div class="pay-info-row">
          <span class="pay-info-key">Bank</span>
          <span class="pay-info-val">${esc(s.bank_name || "-")}</span>
        </div>
        <div class="pay-info-row">
          <span class="pay-info-key">No. Rekening</span>
          <span class="pay-info-val pay-account-number">
            <strong id="accNum-${orderId}">${esc(s.bank_account_number || "-")}</strong>
            <button class="pay-copy-btn" onclick="_copyAccount('${esc(s.bank_account_number || "")}','copy-label-${orderId}')" title="Salin nomor rekening">
              📋 <span id="copy-label-${orderId}">Salin</span>
            </button>
          </span>
        </div>
        <div class="pay-info-row">
          <span class="pay-info-key">Atas Nama</span>
          <span class="pay-info-val">${esc(s.bank_account_name || "-")}</span>
        </div>
        <p class="pay-info-hint">Setelah transfer, klik Konfirmasi lalu upload bukti pembayaran.</p>
      </div>`;

  } else if (method === "qris") {
    if (s.qris_image_path) {
      box.style.display = "block";
      box.innerHTML = `
        <div class="pay-info-card pay-qris-card">
          <p class="pay-info-label">Scan QRIS untuk membayar</p>
          <a href="../${esc(s.qris_image_path)}" target="_blank" title="Perbesar / download QRIS">
            <img src="../${esc(s.qris_image_path)}" alt="Kode QRIS" class="qris-checkout-img">
          </a>
          <p class="pay-info-hint">
            Buka aplikasi GoPay, OVO, DANA, atau mobile banking → Scan QRIS →
            Masukkan nominal sesuai total pesanan → Bayar.<br>
            Setelah berhasil, klik Konfirmasi dan upload screenshot bukti pembayaran.
          </p>
          <a class="button small outline" href="../${esc(s.qris_image_path)}" download="qris-urban-adventure.png" style="margin-top:8px">
            ⬇ Download QRIS
          </a>
        </div>`;
    } else {
      box.style.display = "block";
      box.innerHTML = `<p class="muted" style="font-size:.85rem;margin:8px 0">Gambar QRIS belum tersedia. Hubungi admin.</p>`;
    }
  } else {
    box.style.display = "none";
    box.innerHTML = "";
  }
}

function _copyAccount(text, labelId) {
  if (!text) return;
  navigator.clipboard.writeText(text).then(() => {
    const label = document.getElementById(labelId);
    if (label) {
      label.textContent = "Tersalin!";
      setTimeout(() => { label.textContent = "Salin"; }, 2000);
    }
  }).catch(() => {
    // Fallback untuk browser tanpa clipboard API
    const el = document.createElement("textarea");
    el.value = text;
    el.style.position = "fixed";
    el.style.opacity  = "0";
    document.body.appendChild(el);
    el.select();
    document.execCommand("copy");
    document.body.removeChild(el);
    const label = document.getElementById(labelId);
    if (label) {
      label.textContent = "Tersalin!";
      setTimeout(() => { label.textContent = "Salin"; }, 2000);
    }
  });
}

async function submitPayment(orderId) {
  const selected = document.querySelector(`input[name="pm-${orderId}"]:checked`);
  if (!selected) return;
  const msg = document.getElementById(`pay-msg-${orderId}`);
  const btn = document.querySelector(`#payment-form-${orderId} .button.small`);
  if (btn) { btn.disabled = true; btn.textContent = "Memproses…"; }
  try {
    const result = await api("../api/payment.php", {
      method: "POST", headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ order_id: orderId, payment_method: selected.value, csrf }),
    });
    if (result.need_upload) {
      document.getElementById(`payment-form-${orderId}`).innerHTML = `
        <div class="payment-method-form">
          <p>✅ Metode pembayaran disimpan. Silakan upload bukti transfer:</p>
          <input class="input" type="file" id="proof-file-${orderId}" accept="image/jpeg,image/png,image/webp">
          <div class="payment-method-actions">
            <button class="button small" onclick="uploadProof(${orderId})">📎 Upload Bukti</button>
            <button class="text-button" onclick="loadOrders()">Nanti saja</button>
          </div>
          <p id="upload-msg-${orderId}" class="form-message"></p>
        </div>`;
    } else {
      if (window.LivePoll) LivePoll.ping();
      await loadOrders();
    }
  } catch (error) {
    if (msg) msg.textContent = error.message;
    if (btn) { btn.disabled = false; btn.textContent = "Konfirmasi"; }
  }
}

function showUploadForm(orderId) {
  const container = document.getElementById(`payment-form-${orderId}`);
  container.style.display = "block";
  container.innerHTML = `
    <div class="payment-method-form">
      <p><strong>Upload bukti transfer:</strong></p>
      <input class="input" type="file" id="proof-file-${orderId}" accept="image/jpeg,image/png,image/webp">
      <p class="form-hint">JPG, PNG, atau WEBP. Maks. 3 MB.</p>
      <div class="payment-method-actions">
        <button class="button small" onclick="uploadProof(${orderId})">📎 Upload</button>
        <button class="text-button" onclick="document.getElementById('payment-form-${orderId}').style.display='none'">Batal</button>
      </div>
      <p id="upload-msg-${orderId}" class="form-message"></p>
    </div>`;
}

async function uploadProof(orderId) {
  const input = document.getElementById(`proof-file-${orderId}`);
  const msg   = document.getElementById(`upload-msg-${orderId}`);
  const btn   = input?.closest(".payment-method-form")?.querySelector(".button.small");
  if (!input?.files[0]) { if (msg) msg.textContent = "Pilih file terlebih dahulu."; return; }
  if (btn) { btn.disabled = true; btn.textContent = "Mengunggah…"; }
  const fd = new FormData();
  fd.append("order_id", orderId);
  fd.append("image", input.files[0]);
  fd.append("csrf", csrf);
  try {
    const res = await fetch("../api/payment_upload.php", { method: "POST", body: fd });
    const result = await res.json();
    if (!result.success) throw new Error(result.message);
    if (window.LivePoll) LivePoll.ping();
    await loadOrders();
  } catch (error) {
    if (msg) msg.textContent = error.message;
    if (btn) { btn.disabled = false; btn.textContent = "📎 Upload"; }
  }
}

async function cancelOrder(orderId) {
  if (!confirm("Yakin ingin membatalkan pesanan ini? Stok akan dikembalikan.")) return;
  try {
    await api("../api/orders.php?id=" + orderId, {
      method: "DELETE", headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ csrf }),
    });
    if (window.LivePoll) LivePoll.ping();
    await loadOrders();
  } catch (error) { alert(error.message); }
}

// ─── Profil ───────────────────────────────────────────────────────────────────
async function loadProfile() {
  await init();
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
        <label>Nama<input class="input" type="text" name="name" value="${esc(u.name)}" required></label>
        <label>Nomor HP<input class="input" type="tel" name="phone" value="${esc(u.phone)}" required></label>
        <h3>Ganti password <small>(kosongkan jika tidak ingin mengubah)</small></h3>
        <label>Password saat ini<input class="input" type="password" name="current_password" autocomplete="current-password"></label>
        <label>Password baru (min. 6 karakter)<input class="input" type="password" name="new_password" minlength="6" autocomplete="new-password"></label>
        <button class="button" type="submit">Simpan perubahan</button>
        <p id="profileMessage" class="form-message"></p>
      </form>`;
    document.getElementById("profileForm").addEventListener("submit", async (e) => {
      e.preventDefault();
      const msg     = document.getElementById("profileMessage");
      const payload = { ...formData(e.target), csrf };
      if (!payload.current_password) delete payload.current_password;
      if (!payload.new_password)     delete payload.new_password;
      try {
        await api("../api/session.php", {
          method: "PUT", headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload),
        });
        msg.textContent = "✓ Profil berhasil diperbarui.";
        msg.className   = "form-message form-message--success";
        loadProfile();
      } catch (error) {
        msg.textContent = error.message;
        msg.className   = "form-message form-message--error";
      }
    });
  } catch (error) {
    container.innerHTML = `<p class="error">${esc(error.message)}</p>`;
  }
}
