# Serah terima SStock (Simple Stock) — 2026-10-19

## Lokasi & akses
- Folder: `C:\Users\iwanp\Herd\sstock` (sejajar `tmerp` dan `devlib`)
- Situs: http://sstock.test (otomatis lewat Herd; folder `Herd` sudah di-park)
- GitHub: https://github.com/275iwan92/sstock (privat, branch `main`)

## Stack
- Laravel 13.37, PHP 8.4 (Herd), Vite + Tailwind 4
- `telasih/devlib` lewat Composer path repository `../devlib` (symlink), terdaftar otomatis
- Database: SQLite (`database/database.sqlite`), migrasi bawaan sudah jalan
- `.env`: `APP_NAME=SStock`, `APP_URL=http://sstock.test`

## Sudah dikerjakan
- Proyek Laravel dibuat, devlib terpasang (`@source` Tailwind ke `../../../devlib/*`)
- **Aplikasi stok lengkap (2026-10-19)**: login (`x-dl::frame` + `x-dl::login-box`), Layer Shell dengan menu per peran, dashboard KPI + popup Reorder Point
- Master: Item (kategori, satuan, rak, harga, Reorder Point/Qty, Lead Time), Supplier; kategori hanya lewat seeder
- Transaksi (`docs/{type}`): Opening Balance, Receiving, Issuance langsung diposting saat Save (tidak bisa diubah; koreksi lewat penyesuaian); Stock Adjustment: Draft > Confirm > Approve/Reject (Supervisor)
- Buku besar `stock_movements` + saldo `items.stock_qty/avg_cost` (moving average) hanya diubah `App\Services\StockLedger`
- Laporan `x-dl::report` (bisa dicetak): Stock Card, Summary Stock, Receiving, Issuance, Opname & Adjustment
- Peran: `data_entry` (Receiving, Issuance, lihat stok; tanpa harga/nilai) dan `supervisor` (semua + Opening, Adjustment, parameter item, nilai valuasi)
- Seeder: akun (lihat `database/seeders/UserSeeder.php`), kategori, supplier, 24 item contoh, riwayat transaksi contoh
- DDL MySQL 8: `database/sql/sstock_mysql_ddl.sql` (dihasilkan dari migrasi; migrasi tetap sumber kebenaran)
- Tes: `php artisan test` (alur stok, hak akses, laporan); diuji juga di browser (login, dashboard, form, laporan, approval)

## Belum dikerjakan / sengaja dilewati
- Cetak dokumen tunggal (nota Receiving/Issuance); yang ada baru cetak laporan
- Layar kelola user, kategori, dan ganti password (user baru lewat tinker/seeder)
- Terjemahan Indonesia untuk label layar (label English, bahasa tampilan `id` hanya untuk angka/tanggal dan teks baku DevLib)
- Persetujuan penyesuaian boleh oleh pembuat yang sama (belum ada pemisahan tugas)
- Tanggal/jam transaksi = waktu server saat Save (tidak bisa diisi mundur, kecuali tanggal Opening Balance)
- `CLAUDE.md` khusus sstock

## Keputusan terbuka
- DB memakai SQLite karena akun MySQL root butuh password dan kredensial tmerp tidak dipakai.
  Untuk MySQL seperti tmerp: tentukan user dan nama database, ubah `DB_*` di `.env`, lalu `php artisan migrate --seed`
  (atau impor `database/sql/sstock_mysql_ddl.sql`). Migrasi belum dijalankan di MySQL.

## Menjalankan di mesin lain
```bash
git clone https://github.com/275iwan92/sstock.git   # taruh sejajar dengan devlib
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate --seed
npm install && npm run build    # wajib setiap menambah class Tailwind
```
Lalu daftarkan folder ke Herd (park/link) → http://sstock.test

## Catatan kerja
- Aturan tmerp (`tmerp/CLAUDE.md`, skill `tmerp/.ai/skills/devlib-oo`): DevLib membuat, aplikasi mewarisi;
  layar = deklarasi `x-dl::*`, tanpa HTML mentah / warna literal; label English, nilai data dibungkus `raw()`.
- Perubahan pada devlib di-commit di repo devlib (`C:\Users\iwanp\Herd\devlib`), bukan di sstock.
