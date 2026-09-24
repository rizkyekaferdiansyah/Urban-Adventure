# Urban Adventure Full-Stack

Aplikasi rental alat camping berbasis PHP 8+, MySQL, session authentication, dan REST API internal.

## Struktur utama

- `public/` halaman customer: katalog, detail, login, register, cart, checkout, order, profil.
- `admin/` dashboard, pesanan, produk, pelanggan, laporan, pengaturan.
- `api/` endpoint JSON untuk auth, produk, cart, order, pembayaran, notifikasi, dan admin.
- `admin/products/create.php` dan `admin/products/edit.php` workflow katalog lengkap.
- `config/database.php` koneksi PDO dari environment variable.
- `database.sql` schema lengkap.
- `migrate_catalog.php` migrasi aman untuk field produk, gallery, dan audit log.
- `seed_products.sql` kategori awal.
- `seed_products.php` import seluruh 77 produk langsung dari `data.js`.
- `uploads/` bukti pembayaran.

## Instalasi lokal

1. Pastikan PHP 8+ dengan PDO MySQL dan MySQL 8+ tersedia.
2. Buat database dan tabel:

	```sh
	mysql -u root -p < database.sql
	mysql -u root -p urban_adventure < seed_products.sql
	```

3. Atur koneksi jika tidak memakai default `127.0.0.1`, database `urban_adventure`, user `root`, password kosong:

	```sh
	export UA_DB_HOST=127.0.0.1
	export UA_DB_NAME=urban_adventure
	export UA_DB_USER=root
	export UA_DB_PASS=secret
	```

4. Import semua katalog:

	```sh
	php migrate_catalog.php
	php seed_products.php
	php create_admin.php "Urban Admin" admin@example.com ganti-password-ini
	```

	Migrasi tidak menghapus produk atau order existing. Produk yang sudah pernah dipakai transaksi akan dinonaktifkan secara soft delete.

5. Jalankan server dari root project agar halaman customer dan admin sama-sama tersedia:

	```sh
	php -S 127.0.0.1:8000
	```

	Buka `http://127.0.0.1:8000`. Dashboard admin berada di `http://127.0.0.1:8000/admin/index.php`.

## Catatan deployment

Arahkan document root web server ke folder project agar `/public` dan `/admin` sama-sama dapat diakses. Jangan commit password database. Folder `uploads` harus writable dan dibatasi agar hanya menyimpan JPG, PNG, dan WEBP maksimal 3 MB.

Stok rental dihitung berdasarkan order yang periodenya overlap, bukan hanya stok total. Produk yang pernah masuk order dinonaktifkan secara soft delete melalui status `inactive`.

## Manajemen katalog admin

Admin dapat membuka `admin/products.php` untuk melihat produk, mengubah stok/status, dan masuk ke form tambah/edit. Form mendukung SKU, slug otomatis, kategori, harga harian/weekend, deposit, satuan, durasi rental, denda, ketentuan penggunaan/pengembalian, dan gallery gambar.

Upload produk dibatasi server-side ke JPG, PNG, dan WEBP maksimal 5 MB per file. File disimpan dengan nama acak di `uploads/products/original/`, dan `.htaccess` mencegah eksekusi PHP pada folder upload. Produk yang sudah memiliki order tidak dihapus permanen; sistem menyimpannya sebagai inactive agar historical order aman.

API admin katalog utama:

- `GET/POST/PUT/DELETE /api/admin.php?resource=products`
- `GET/POST/PUT /api/admin.php?resource=categories`
- `POST/DELETE /api/admin.php?resource=product-images`

## Validasi lingkungan

JavaScript sudah divalidasi dengan `node --check`. PHP dan MySQL belum dapat dijalankan pada lingkungan pengembangan ini karena executable `php` dan client `mysql` tidak tersedia; jalankan langkah instalasi di atas untuk menguji flow register → login → katalog → cart → checkout → order serta flow admin.
