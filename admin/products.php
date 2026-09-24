<?php require __DIR__.'/_header.php'; ?>
<section class="section">
    <p class="eyebrow dark">INVENTARIS</p>
    <div class="page-title-row"><div><h1>Kelola produk.</h1><p class="muted">Atur harga, stok, kategori, foto, dan ketentuan rental dari satu tempat.</p></div><a class="button" href="products/create.php">+ Tambah produk</a></div>
    <div class="admin-filters"><input id="productSearch" class="input" placeholder="Cari nama atau SKU..."><select id="productStatusFilter" class="input"><option value="all">Semua status</option><option value="active">Aktif</option><option value="inactive">Nonaktif</option></select></div>
    <div id="adminProducts">Memuat...</div>
    <div class="category-panel">
        <div class="page-title-row"><div><h2>Kategori produk</h2><p class="muted">Kelola kategori tanpa menghapus relasi produk existing.</p></div></div>
        <form id="categoryForm" class="inline-form"><input class="input" name="name" placeholder="Nama kategori baru" required><button class="button small" type="submit">Tambah kategori</button></form>
        <div id="adminCategories" class="category-list">Memuat kategori...</div>
    </div>
</section>
<script src="../assets/js/admin.js"></script>
<script>loadAdminProducts(); loadAdminCategories();</script>
<?php require __DIR__.'/_footer.php'; ?>
