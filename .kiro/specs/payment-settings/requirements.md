# Requirements: Payment Settings (Pengaturan Metode Pembayaran)

## Overview
Fitur ini memungkinkan admin mengatur detail metode pembayaran (Transfer Bank & QRIS) melalui panel admin tanpa mengubah kode. Customer melihat info pembayaran terbaru secara dinamis saat memilih metode pembayaran.

## Requirements

### REQ-1: Tabel Database payment_settings
- Sistem harus memiliki tabel `payment_settings` dengan kolom: id, key (UNIQUE), value (TEXT), updated_at
- Data default: bank_name, bank_account_number, bank_account_name, bank_is_active, qris_image_path, qris_is_active

### REQ-2: API Endpoint payment_settings
- GET `/api/payment_settings.php` — publik (tidak perlu login), mengembalikan semua setting aktif
- PUT `/api/payment_settings.php` — admin only, update satu atau lebih key
- POST `/api/payment_settings.php?resource=qris-image` — admin only, upload gambar QRIS

### REQ-3: Halaman Admin — Payment Settings
- Halaman baru `/admin/payment_settings.php` dapat diakses dari menu navigasi admin
- Form Transfer Bank: input Nama Bank, Nomor Rekening, Nama Pemilik, toggle Aktif/Nonaktif
- Form QRIS: upload gambar, preview gambar aktif, toggle Aktif/Nonaktif
- Tombol Simpan untuk masing-masing section

### REQ-4: Halaman User — Detail Pembayaran Dinamis
- Saat customer memilih metode Transfer Bank, tampilkan: Nama Bank, Nomor Rekening (+ tombol salin), Nama Pemilik
- Saat customer memilih metode QRIS, tampilkan: gambar QRIS (bisa diperbesar/download), petunjuk scan
- Data diambil dari API payment_settings secara real-time
- Jika metode dinonaktifkan admin, pilihan tidak muncul di form pembayaran customer

### REQ-5: Validasi & Keamanan
- Upload QRIS hanya menerima JPEG/PNG, maksimal 2MB
- Hanya admin yang dapat mengubah settings
- CSRF token wajib untuk semua mutasi
