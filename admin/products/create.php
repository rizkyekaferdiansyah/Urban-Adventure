<?php require dirname(__DIR__) . '/_header.php'; ?>
<section class="section admin-form-page">
    <p class="eyebrow dark">INVENTARIS</p>
    <div class="page-title-row">
        <div>
            <h1>Tambah produk baru.</h1>
            <p class="muted">Produk akan langsung tampil di katalog jika statusnya Aktif.</p>
        </div>
        <a class="button outline" href="../products.php">← Kembali</a>
    </div>

    <form id="productForm" class="product-form" enctype="multipart/form-data">

        <!-- ── Identitas produk ──────────────────────────────────────────── -->
        <div class="form-section">
            <h2>Identitas produk</h2>
            <div class="form-grid">
                <label class="form-label">
                    Nama produk <b class="required">*</b>
                    <input class="input" name="name" required maxlength="180" placeholder="Contoh: Tenda Dome 4 Orang">
                </label>
                <label class="form-label">
                    SKU / kode produk
                    <input class="input" name="sku" placeholder="Otomatis jika kosong">
                </label>
                <label class="form-label">
                    Kategori <b class="required">*</b>
                    <select class="input" name="category_id" id="category_id" required>
                        <option value="">— Memuat kategori… —</option>
                    </select>
                </label>
                <label class="form-label">
                    Satuan
                    <input class="input" name="unit" value="unit" placeholder="unit / set / pasang">
                </label>
            </div>
            <label class="form-label">
                Deskripsi singkat
                <textarea class="input" name="short_description" rows="2" maxlength="500"
                    placeholder="Tampil di kartu katalog — maks. 500 karakter"></textarea>
            </label>
            <label class="form-label">
                Deskripsi lengkap
                <textarea class="input" name="description" rows="5"
                    placeholder="Tampil di halaman detail produk"></textarea>
            </label>
        </div>

        <!-- ── Harga dan stok ───────────────────────────────────────────── -->
        <div class="form-section">
            <h2>Harga dan stok</h2>
            <div class="form-grid">
                <label class="form-label">
                    Harga per hari <b class="required">*</b>
                    <input class="input" type="number" name="price_per_day" min="0" required placeholder="50000">
                </label>
                <label class="form-label">
                    Harga weekend
                    <input class="input" type="number" name="weekend_price" min="0" placeholder="Sama dengan weekday jika kosong">
                </label>
                <label class="form-label">
                    Deposit
                    <input class="input" type="number" name="deposit" min="0" value="0">
                </label>
                <label class="form-label">
                    Total stok <b class="required">*</b>
                    <input class="input" type="number" name="stock" min="0" value="1" required>
                </label>
                <label class="form-label">
                    Minimal rental (hari)
                    <input class="input" type="number" name="minimum_rental_days" min="1" value="1">
                </label>
                <label class="form-label">
                    Maksimal rental (hari)
                    <input class="input" type="number" name="maximum_rental_days" min="1" placeholder="Kosong = tidak terbatas">
                </label>
            </div>
        </div>

        <!-- ── Biaya tambahan ───────────────────────────────────────────── -->
        <div class="form-section">
            <h2>Biaya tambahan</h2>
            <div class="form-grid">
                <label class="form-label">
                    Denda keterlambatan / hari
                    <input class="input" type="number" name="late_fee" min="0" value="0">
                </label>
                <label class="form-label">
                    Biaya kerusakan
                    <input class="input" type="number" name="damage_fee" min="0" value="0">
                </label>
                <label class="form-label">
                    Biaya kehilangan
                    <input class="input" type="number" name="lost_fee" min="0" value="0">
                </label>
            </div>
        </div>

        <!-- ── Ketentuan rental ─────────────────────────────────────────── -->
        <div class="form-section">
            <h2>Ketentuan rental</h2>
            <label class="form-label">
                Ketentuan penggunaan
                <textarea class="input" name="usage_terms" rows="3"
                    placeholder="Syarat dan cara penggunaan yang benar"></textarea>
            </label>
            <label class="form-label">
                Ketentuan pengembalian
                <textarea class="input" name="return_terms" rows="3"
                    placeholder="Kondisi pengembalian, waktu, lokasi"></textarea>
            </label>
            <label class="form-label">
                Ketentuan rental lainnya
                <textarea class="input" name="rental_terms" rows="3"
                    placeholder="Info tambahan khusus produk ini"></textarea>
            </label>
        </div>

        <!-- ── Foto produk ──────────────────────────────────────────────── -->
        <div class="form-section">
            <h2>Foto produk</h2>
            <p class="muted" style="font-size:.85rem;margin-top:0">JPG, PNG, atau WEBP. Maks. 5 MB per file. Foto pertama akan jadi foto utama.</p>
            <label class="form-label">
                Upload foto
                <input class="input" type="file" name="gallery" accept="image/jpeg,image/png,image/webp" multiple>
            </label>
            <div id="imagePreview" class="image-preview-grid"></div>
        </div>

        <!-- ── Status ───────────────────────────────────────────────────── -->
        <div class="form-section">
            <h2>Status produk</h2>
            <div class="form-grid form-grid--narrow">
                <label class="form-label">
                    Status
                    <select class="input" name="status">
                        <option value="active">Aktif — tampil di katalog</option>
                        <option value="inactive">Nonaktif — tersembunyi</option>
                    </select>
                </label>
            </div>
        </div>

        <div class="form-actions">
            <p id="productMessage" class="form-message"></p>
            <button class="button" type="submit">Simpan produk</button>
        </div>
    </form>
</section>
<script src="../../assets/js/admin.js"></script>
<script>loadProductForm();</script>
<?php require dirname(__DIR__) . '/_footer.php'; ?>
