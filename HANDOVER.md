# Serah terima Stoku (kode proyek: sstock, dulu "Simple Stock") — 2026-10-19

## Lokasi & akses
- Folder: `C:\Users\iwanp\Herd\sstock` (sejajar `tmerp` dan `devlib`)
- Situs: http://sstock.test (otomatis lewat Herd; folder `Herd` sudah di-park)
- GitHub: https://github.com/275iwan92/sstock (privat, branch `main`)

## Stack
- Laravel 13.37, PHP 8.4 (Herd), Vite + Tailwind 4
- `telasih/devlib` lewat Composer path repository `../devlib` (symlink), terdaftar otomatis
- Database: MySQL 8, database `sstock`, user `sstock_usr` (dibuat otomatis oleh `php artisan sstock:install`; nilai di `.env.example`)
- `.env`: `APP_NAME=SStock`, `APP_URL=http://sstock.test`

## Sudah dikerjakan
- Proyek Laravel dibuat, devlib terpasang (`@source` Tailwind ke `../../../devlib/*`)
- **Aplikasi stok lengkap (2026-10-19)**: login (`x-dl::frame` + `x-dl::login-box`), Layer Shell dengan menu per peran, dashboard KPI + popup Reorder Point
- Master: Item (kategori, satuan, rak, harga, Reorder Point/Qty, Lead Time), Supplier; kategori hanya lewat seeder
- Supplier: telepon kantor (PSTN, kolom `phone`), nama kontak + HP kontak, alamat jalan, lalu satu kotak cari `geo_area` (kecamatan > kota/kabupaten > provinsi > negara + kode pos, wajib; kode pos disimpan server dari wilayah terpilih); grid punya satu kolom "City, Country"
- Master Satuan (Master Data > Units, Supervisor): kode tetap, nama, aktif/nonaktif; Item memilih satuan dari master (foreign key `items.unit` > `units.code`). Konversi satu aturan untuk semua item (`unit_conversions`: 1 box = 12 pcs; arah sebaliknya dihitung, tanpa rantai) lewat tabel Conversions di form Unit; `App\Support\UnitConverter`
- Receiving boleh dalam satuan lain dari satuan stok item: baris punya kolom Unit, jumlah + harga dikonversi ke satuan stok saat Save (qty/unit_cost = satuan stok, entered_* = yang diketik); tanpa aturan konversi ditolak. Issuance, Opening, Adjustment tetap satuan stok
- Tampilan grid: transaksi POLOS (nomor dokumen tebal di kolom pertama, hanya Status yang berwarna, untuk data banyak); master memakai kotak warna per baris: Items per kategori (ATK hijau, Sparepart biru, Tools ungu, variasi per item), Units per jenis (berat oranye, panjang toska, volume biru muda, hitungan indigo), Suppliers warna dari nama (`App\Support\Palette`). Memakai prop baru `hue` pada kolom identity di DevLib (`grid.js`, `Grid.php`; perubahan ada di repo `../devlib` dan harus di-commit di sana)
- Wilayah: tabel `geo_*` salinan tmerp (`tm2mst`, id sama; 250 negara, ±80 rb kelurahan) lewat `php artisan sstock:import-geo` (otomatis di `sstock:install`, tidak fatal bila gagal); logika cari di `App\Support\GeoArea`, lookup di `App\Support\GeoLookup`. Pencarian ±1 detik (join 5 tabel, LIKE di tengah kata)
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

## Database dan deploy pertama
- `php artisan sstock:install` (dipanggil `composer setup`): bila database + user belum ada, membuatnya lewat akun admin MySQL
  (`--admin-user/--admin-password`, atau env `DB_ADMIN_USERNAME/DB_ADMIN_PASSWORD`, atau ditanya), lalu `migrate` dan `db:seed` (seed hanya bila belum ada user). Aman dijalankan ulang.
- Tes memakai SQLite in-memory (`phpunit.xml`), tidak menyentuh MySQL. DDL referensi: `database/sql/sstock_mysql_ddl.sql`.
- Login: wallpaper `public/media/bg/sstock` (dibuat sendiri, bukan gambar DevLib), kutipan `public/media/text/*.txt` (salinan DevLib).

## Menjalankan di mesin lain
```bash
git clone https://github.com/275iwan92/sstock.git   # taruh sejajar dengan devlib
composer setup    # install, .env, key, buat DB + user MySQL, migrate, seed, build
```
Lalu daftarkan folder ke Herd (park/link) → http://sstock.test

## Catatan kerja
- Aturan tmerp (`tmerp/CLAUDE.md`, skill `tmerp/.ai/skills/devlib-oo`): DevLib membuat, aplikasi mewarisi;
  layar = deklarasi `x-dl::*`, tanpa HTML mentah / warna literal; label English, nilai data dibungkus `raw()`.
- Perubahan pada devlib di-commit di repo devlib (`C:\Users\iwanp\Herd\devlib`), bukan di sstock.
