/**
 * poll.js — Live update engine untuk Urban Adventure
 *
 * Polling ringan ke /api/poll.php setiap POLL_INTERVAL ms.
 * Membandingkan fingerprint — jika berubah, fire callback terdaftar.
 *
 * Event yang tersedia:
 *   "notification"  → jumlah notif belum dibaca berubah       cb(unreadCount)
 *   "order"         → ada pesanan baru / status pesanan berubah  cb(data)
 *   "cart"          → jumlah item keranjang berubah            cb(cartCount)
 *   "any"           → ada perubahan apapun                     cb(data)
 */

(function () {
  "use strict";

  const POLL_INTERVAL = 10000;   // 10 detik interval normal
  const BACKOFF_MAX   = 60000;   // max 60 detik saat error jaringan

  // Hitung path API berdasarkan kedalaman halaman
  // admin/          → ../api
  // admin/products/ → ../../api
  // public/         → ../api
  const _pathDepth = (window.location.pathname.match(/\/admin(\/[^/]+)+\//)?.[0]?.match(/\//g) || []).length - 1;
  const _apiBase   = _pathDepth > 0 ? "../../api" : "../api";
  const POLL_URL   = `${_apiBase}/poll.php`;

  // State internal
  let _lastFingerprint    = null;
  let _lastUnread         = null;
  let _lastCartCount      = null;
  let _lastOrderUpdate    = null;   // track order timestamp secara terpisah
  let _lastRole           = null;
  let _timer              = null;
  let _interval           = POLL_INTERVAL;
  let _running            = false;
  let _callbacks          = {};

  window.LivePoll = {

    on(event, callback) {
      (_callbacks[event] = _callbacks[event] || []).push(callback);
      return this;
    },

    off(event, callback) {
      if (!callback) { _callbacks[event] = []; }
      else { _callbacks[event] = (_callbacks[event] || []).filter((cb) => cb !== callback); }
      return this;
    },

    start() {
      if (_running) return this;
      _running = true;
      _tick();
      document.addEventListener("visibilitychange", _onVisibility);
      return this;
    },

    stop() {
      _running = false;
      clearTimeout(_timer);
      document.removeEventListener("visibilitychange", _onVisibility);
      return this;
    },

    /** Paksa poll segera — panggil setelah user melakukan aksi penting */
    async ping() {
      clearTimeout(_timer);
      await _tick();
    },
  };

  function _onVisibility() {
    if (document.visibilityState === "visible") {
      clearTimeout(_timer);
      _tick();
    } else {
      clearTimeout(_timer);
    }
  }

  async function _tick() {
    if (!_running) return;

    try {
      const res = await fetch(POLL_URL, { cache: "no-store" });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const body = await res.json();
      if (!body.success) throw new Error(body.message);

      const data = body.data;
      _interval  = POLL_INTERVAL; // reset backoff setelah berhasil

      // Inisialisasi — simpan state awal, jangan fire event dulu
      if (_lastFingerprint === null) {
        _lastFingerprint = data.fingerprint;
        _lastUnread      = data.unread_notifications;
        _lastCartCount   = data.cart_count;
        _lastOrderUpdate = data.last_order_update ?? null;
        _lastRole        = data.role;
        _schedule();
        return;
      }

      const changed = data.fingerprint !== _lastFingerprint;

      if (changed) {
        // ── Admin: hanya "order" yang relevan ──────────────────────────────
        if (data.role === 'admin') {
          _emit("order", data);
          _emit("any",   data);
        }
        // ── Customer: cek setiap dimensi secara terpisah ──────────────────
        else {
          if (data.unread_notifications !== _lastUnread) {
            _emit("notification", data.unread_notifications);
          }
          if (data.cart_count !== _lastCartCount) {
            _emit("cart", data.cart_count);
          }
          // Event "order" hanya fire jika timestamp order benar-benar berubah
          // Bukan karena notif dibaca atau item cart berubah
          if (data.last_order_update !== _lastOrderUpdate) {
            _emit("order", data);
          }
          _emit("any", data);
        }

        _lastFingerprint = data.fingerprint;
        _lastUnread      = data.unread_notifications;
        _lastCartCount   = data.cart_count;
        _lastOrderUpdate = data.last_order_update ?? null;
      }

    } catch (err) {
      _interval = Math.min(_interval * 2, BACKOFF_MAX);
      console.debug("[poll] retry in", _interval / 1000, "s —", err.message);
    }

    _schedule();
  }

  function _schedule() {
    if (_running && document.visibilityState !== "hidden") {
      _timer = setTimeout(_tick, _interval);
    }
  }

  function _emit(event, payload) {
    (_callbacks[event] || []).forEach((cb) => {
      try { cb(payload); } catch (e) { console.error("[poll] callback error:", e); }
    });
  }

})();
