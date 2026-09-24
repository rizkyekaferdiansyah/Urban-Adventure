<?php $title='Beranda | Urban Adventure'; require __DIR__ . '/_header.php'; ?>
<section class="hero compact home-hero">
    <div class="home-hero-copy">
        <p class="eyebrow">RENTAL OUTDOOR JOGJA</p>
        <h1>Siap berangkat.<br><em>Tanpa harus membeli.</em></h1>
        <p>Peralatan camping bersih, terawat, dan siap menemani petualanganmu dari Jogja.</p>
        <div class="home-actions"><a class="button" href="#katalog">Lihat katalog</a><a class="home-link" href="https://wa.me/6282225708380?text=Halo%20Urban%20Adventure%2C%20saya%20ingin%20menanyakan%20ketersediaan%20alat%20camping." target="_blank" rel="noopener">Cek ketersediaan &rarr;</a></div>
        <div class="home-trust"><span><strong>5.0</strong> &middot; 521 ulasan</span><span>Peralatan terawat</span><span>Pelayanan ramah</span></div>
    </div>
</section>
<section class="home-intro">
    <div class="home-intro-grid">
        <div>
            <p class="eyebrow dark">URBAN ADVENTURE</p>
            <h2>Perlengkapan yang siap diajak bertualang.</h2>
        </div>
        <div>
            <p class="home-lead">Mau mendaki, camping, atau sekadar menikmati alam? Urban Adventure membantu kamu mendapatkan perlengkapan outdoor tanpa harus membeli semuanya sendiri.</p>
            <p class="muted">Berlokasi di Wirobrajan, Yogyakarta. Cocok untuk pendaki pemula maupun yang sudah berpengalaman.</p>
        </div>
    </div>
</section>
<section class="section" id="katalog">
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
<section class="home-why" id="kenapa">
    <div class="home-why-grid">
        <div class="why-intro">
            <p class="eyebrow">KENAPA URBAN ADVENTURE?</p>
            <h2>Camping lebih simpel.<br><em>Petualangan lebih dekat.</em></h2>
            <p>Semua yang kamu butuhkan untuk pergi lebih tenang, dari alat yang siap pakai sampai bantuan sebelum berangkat.</p>
            <span class="why-stamp">DIPERCAYA<br>PENDUKI JOGJA</span>
        </div>
        <div class="feature-list">
            <div><b>01</b><span><strong>Bersih &amp; terawat</strong><small>Peralatan dirawat agar tetap nyaman digunakan.</small></span><i>&rarr;</i></div>
            <div><b>02</b><span><strong>Pelayanan ramah</strong><small>Komunikasi jelas dan siap membantu kebutuhan sewa.</small></span><i>&rarr;</i></div>
            <div><b>03</b><span><strong>Cocok untuk pemula</strong><small>Bisa konsultasi perlengkapan sebelum berangkat.</small></span><i>&rarr;</i></div>
            <div><b>04</b><span><strong>Lokasi di Jogja</strong><small>Ambil perlengkapan di Wirobrajan, Yogyakarta.</small></span><i>&rarr;</i></div>
        </div>
    </div>
</section>
<section class="home-reviews" id="ulasan">
    <div class="home-reviews-head">
        <div>
            <p class="eyebrow dark">KATA MEREKA</p>
            <h2>Pengalaman yang ikut berangkat.</h2>
            <p class="reviews-subtitle">Cerita kecil dari orang-orang yang sudah mempercayakan perlengkapannya kepada kami.</p>
        </div>
        <div class="home-rating"><strong>5.0</strong><span>★★★★★</span><small>521 ulasan Google</small></div>
    </div>
    <div class="review-grid">
        <blockquote><span class="quote-mark">&ldquo;</span><p>Barangnya terawat, bersih. Pelayanannya ramah dan sangat membantu buat yang baru pertama kali mau mendaki.</p><footer><b>Rissa Nadia Putri</b><small>Pendaki pemula</small></footer></blockquote>
        <blockquote><span class="quote-mark">&ldquo;</span><p>Perlengkapannya lengkap banget. Kondisi alat outdoor bersih, terawat, dan harganya juga masih terjangkau.</p><footer><b>Parves Angga</b><small>Pecinta camping</small></footer></blockquote>
        <blockquote><span class="quote-mark">&ldquo;</span><p>Pelayanannya ramah, alat lengkap dan bersih. Proses sewa cepat dan jelas.</p><footer><b>Listiana Pamugi</b><small>Traveler Jogja</small></footer></blockquote>
    </div>
</section>
<script src="../assets/js/app.js"></script><script>loadCatalog();</script>
<?php require __DIR__ . '/_footer.php'; ?>
