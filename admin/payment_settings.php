<?php
require __DIR__ . '/_header.php';

$pdo = db();
$msg = '';
$msgType = '';

// ── Helper: baca semua setting ────────────────────────────────────────────────
function getSetting(PDO $pdo, string $key, string $default = ''): string {
    $s = $pdo->prepare('SELECT value FROM payment_settings WHERE `key`=?');
    $s->execute([$key]);
    $v = $s->fetchColumn();
    return $v !== false ? $v : $default;
}

function saveSetting(PDO $pdo, string $key, string $value): void {
    $pdo->prepare(
        'INSERT INTO payment_settings (`key`, value) VALUES (?,?)
         ON DUPLICATE KEY UPDATE value=VALUES(value)'
    )->execute([$key, $value]);
}

// ── Handle form submit ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifikasi CSRF dari session
    $csrf = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $csrf)) {
        $msg = 'Token tidak valid. Refresh halaman dan coba lagi.';
        $msgType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'save_bank') {
            saveSetting($pdo, 'bank_name',           trim($_POST['bank_name'] ?? ''));
            saveSetting($pdo, 'bank_account_number', trim($_POST['bank_account_number'] ?? ''));
            saveSetting($pdo, 'bank_account_name',   trim($_POST['bank_account_name'] ?? ''));
            saveSetting($pdo, 'bank_is_active',      isset($_POST['bank_is_active']) ? '1' : '0');
            $msg = '✓ Pengaturan Transfer Bank disimpan.';
            $msgType = 'success';
        }

        if ($action === 'save_qris') {
            saveSetting($pdo, 'qris_is_active', isset($_POST['qris_is_active']) ? '1' : '0');

            // Upload gambar QRIS baru jika ada
            if (isset($_FILES['qris_image']) && $_FILES['qris_image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['qris_image'];

                if ($file['size'] > 2 * 1024 * 1024) {
                    $msg = 'Ukuran file maksimal 2 MB.';
                    $msgType = 'error';
                } else {
                    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
                    $exts = ['image/jpeg' => 'jpg', 'image/png' => 'png'];

                    if (!isset($exts[$mime])) {
                        $msg = 'Format harus JPG atau PNG.';
                        $msgType = 'error';
                    } else {
                        // Hapus file lama
                        $old = getSetting($pdo, 'qris_image_path');
                        if ($old && is_file(__DIR__ . '/../' . $old)) {
                            unlink(__DIR__ . '/../' . $old);
                        }

                        $dir = __DIR__ . '/../uploads/qris';
                        if (!is_dir($dir)) mkdir($dir, 0750, true);

                        $filename = 'qris-' . bin2hex(random_bytes(8)) . '.' . $exts[$mime];
                        if (move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
                            saveSetting($pdo, 'qris_image_path', 'uploads/qris/' . $filename);
                            $msg = '✓ Gambar QRIS berhasil diperbarui.';
                            $msgType = 'success';
                        } else {
                            $msg = 'Gagal menyimpan gambar. Periksa permission folder uploads/qris.';
                            $msgType = 'error';
                        }
                    }
                }
            } else {
                // Tidak ada file baru, hanya update status aktif
                if ($msgType !== 'error') {
                    $msg = '✓ Pengaturan QRIS disimpan.';
                    $msgType = 'success';
                }
            }
        }
    }
}

// ── Baca nilai terkini ────────────────────────────────────────────────────────
$bankName    = getSetting($pdo, 'bank_name',           'Bank BCA');
$bankNumber  = getSetting($pdo, 'bank_account_number', '');
$bankOwner   = getSetting($pdo, 'bank_account_name',   '');
$bankActive  = getSetting($pdo, 'bank_is_active',      '1') === '1';
$qrisPath    = getSetting($pdo, 'qris_image_path',     '');
$qrisActive  = getSetting($pdo, 'qris_is_active',      '1') === '1';

$csrfToken   = $_SESSION['csrf'] ??= bin2hex(random_bytes(32));

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>

<section class="section admin-form-page" style="max-width:780px">
    <p class="eyebrow dark">KONFIGURASI</p>
    <h1>Pengaturan pembayaran.</h1>
    <p class="muted" style="margin-bottom:32px">
        Atur detail rekening bank dan kode QRIS yang ditampilkan ke pelanggan saat memilih metode pembayaran.
    </p>

    <?php if ($msg): ?>
    <div class="alert <?= $msgType === 'success' ? 'alert-success' : 'alert-error' ?>" style="margin-bottom:24px">
        <?= e($msg) ?>
    </div>
    <?php endif; ?>

    <!-- ── Transfer Bank ──────────────────────────────────────────────────── -->
    <div class="form-section" style="margin-bottom:24px">
        <h2>🏦 Transfer Bank</h2>
        <form method="POST" action="">
            <input type="hidden" name="csrf"   value="<?= e($csrfToken) ?>">
            <input type="hidden" name="action" value="save_bank">

            <label class="toggle-label" style="margin-bottom:16px;display:flex;align-items:center;gap:10px">
                <span style="font-weight:600">Status</span>
                <label class="toggle-switch">
                    <input type="checkbox" name="bank_is_active" <?= $bankActive ? 'checked' : '' ?>>
                    <span class="toggle-slider"></span>
                </label>
                <span class="toggle-status"><?= $bankActive ? 'Aktif' : 'Nonaktif' ?></span>
            </label>

            <div class="form-grid">
                <label class="form-label">
                    Nama Bank
                    <input class="input" type="text" name="bank_name"
                           value="<?= e($bankName) ?>" placeholder="Contoh: Bank BCA">
                </label>
                <label class="form-label">
                    Nomor Rekening
                    <input class="input" type="text" name="bank_account_number"
                           value="<?= e($bankNumber) ?>" placeholder="Contoh: 1234567890">
                </label>
                <label class="form-label">
                    Nama Pemilik Rekening (A.N.)
                    <input class="input" type="text" name="bank_account_name"
                           value="<?= e($bankOwner) ?>" placeholder="Contoh: Urban Adventure">
                </label>
            </div>

            <div class="form-actions" style="margin-top:8px">
                <button class="button" type="submit">Simpan pengaturan bank</button>
            </div>
        </form>
    </div>

    <!-- ── QRIS ───────────────────────────────────────────────────────────── -->
    <div class="form-section">
        <h2>📲 QRIS</h2>
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="csrf"   value="<?= e($csrfToken) ?>">
            <input type="hidden" name="action" value="save_qris">

            <label class="toggle-label" style="margin-bottom:16px;display:flex;align-items:center;gap:10px">
                <span style="font-weight:600">Status</span>
                <label class="toggle-switch">
                    <input type="checkbox" name="qris_is_active" <?= $qrisActive ? 'checked' : '' ?>>
                    <span class="toggle-slider"></span>
                </label>
                <span class="toggle-status"><?= $qrisActive ? 'Aktif' : 'Nonaktif' ?></span>
            </label>

            <!-- Preview gambar QRIS aktif -->
            <?php if ($qrisPath): ?>
            <div style="margin-bottom:16px">
                <p class="muted" style="font-size:.82rem;margin:0 0 8px">Gambar QRIS aktif saat ini:</p>
                <img src="../<?= e($qrisPath) ?>" alt="QRIS" class="qris-preview-img"
                     style="max-width:220px;border-radius:10px;border:1px solid var(--line)">
                <p class="muted" style="font-size:.78rem;margin:6px 0 0">
                    Upload gambar baru di bawah untuk mengganti.
                </p>
            </div>
            <?php else: ?>
            <div class="alert" style="background:var(--green-100);border:1px solid var(--green-500);border-radius:10px;padding:12px 14px;margin-bottom:16px;font-size:.85rem;color:var(--green-800)">
                Belum ada gambar QRIS. Upload gambar di bawah.
            </div>
            <?php endif; ?>

            <label class="form-label">
                <?= $qrisPath ? 'Ganti gambar QRIS' : 'Upload gambar QRIS' ?>
                <input class="input" type="file" name="qris_image"
                       accept="image/jpeg,image/png" id="qrisFileInput">
                <small class="muted">JPG atau PNG, maksimal 2 MB.</small>
            </label>

            <!-- Preview gambar yang dipilih sebelum submit -->
            <div id="qrisNewPreview" style="margin:10px 0"></div>

            <div class="form-actions" style="margin-top:8px">
                <button class="button" type="submit">
                    <?= $qrisPath ? 'Simpan / Ganti gambar QRIS' : 'Upload gambar QRIS' ?>
                </button>
            </div>
        </form>
    </div>
</section>

<script>
// Preview gambar QRIS sebelum upload
document.getElementById("qrisFileInput").addEventListener("change", function() {
    const file = this.files[0];
    const prev = document.getElementById("qrisNewPreview");
    if (!file) { prev.innerHTML = ""; return; }
    const url = URL.createObjectURL(file);
    prev.innerHTML = `
        <p class="muted" style="font-size:.82rem;margin:0 0 6px">Preview:</p>
        <img src="${url}" alt="Preview QRIS"
             style="max-width:220px;border-radius:10px;border:1px solid var(--line)">`;
});

// Toggle status label secara live
document.querySelectorAll(".toggle-switch input[type=checkbox]").forEach(function(cb) {
    cb.addEventListener("change", function() {
        const label = this.closest("label.toggle-label")?.querySelector(".toggle-status");
        if (label) label.textContent = this.checked ? "Aktif" : "Nonaktif";
    });
});
</script>

<?php require __DIR__ . '/_footer.php'; ?>
