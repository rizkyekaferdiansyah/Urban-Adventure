<?php $title='Katalog | Urban Adventure'; require __DIR__ . '/_header.php'; ?>
<section class="hero compact">
    <p class="eyebrow">RENTAL OUTDOOR JOGJA</p>
    <h1>Sewa alat outdoor,<br><em>tinggal berangkat.</em></h1>
    <p>Peralatan camping bersih dan terawat untuk perjalananmu dari Jogja.</p>
</section>
<section class="section">
    <div class="section-head">
        <div>
            <p class="eyebrow dark">KATALOG RESMI</p>
            <h2>Pilih perlengkapanmu</h2>
        </div>
        <input id="search" class="input search" placeholder="Cari produk...">
    </div>
    <div id="categories" class="chips"></div>
    <div id="products" class="product-grid">
        <p class="muted">Memuat katalog...</p>
    </div>
</section>
<script src="../assets/js/app.js"></script>
<script>loadCatalog();</script>
<?php require __DIR__ . '/_footer.php'; ?>
