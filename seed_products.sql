USE urban_adventure;
INSERT INTO categories (name, slug) VALUES
('Tenda Kapasitas 2-6 Orang','tenda-kapasitas-2-6-orang'),
('Carrier','carrier'),
('Perlengkapan Masak','perlengkapan-masak'),
('Alat Penerangan','alat-penerangan'),
('Perlengkapan Pribadi','perlengkapan-pribadi'),
('Tambahan Peneduh dan Alat Santai','tambahan-peneduh-dan-alat-santai'),
('Lain-Lain','lain-lain')
ON DUPLICATE KEY UPDATE name=VALUES(name);
-- Produk lengkap diimpor tanpa input manual melalui: php seed_products.php
