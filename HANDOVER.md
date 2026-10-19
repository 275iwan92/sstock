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
- Opening screen: 6 KPI yang bisa diklik (membuka tab menu terkait), tabel Reorder ringkas (nama, Reorder Qty, stok) di kiri, dan (hanya Supervisor) grafik garis nilai stok di kanan: per minggu (12 minggu terakhir), otomatis per bulan setelah riwayat >180 hari (`App\Services\StockValuation`)
- Pemesanan ulang: tabel `reorder_orders` (tanggal pesan + no. PO per barang); tombol "Catat Pemesanan" di opening screen; ditutup otomatis oleh Receiving; status tampil sebagai badge di tabel reorder
- Master Departemen dan Penerima (Data Master, semua peran): Issuance memilih dari dropdown (`recipient_id`, `department_id`; nama disalin ke kolom teks `recipient`/`department`; departemen kosong = departemen bawaan penerima). Baris "Penerima baru" / "Departemen baru" di atas dropdown membuka dialog tambah cepat (`docs/quick-add.blade.php`, JSON ke `recipients.store` / `departments.store`)
- Keamanan (hanya peran Administrator = hak Supervisor + menu Keamanan): Pengguna (tambah/ubah, peran, grup, password; tidak bisa menonaktifkan/mengubah peran akun sendiri), Grup (`user_groups`; tabel hak akses per modul di form grup: Tidak ada / RO hanya baca / RW baca-tulis, disimpan di `user_group_modules.access`; RO menolak semua permintaan non-GET ke modul itu), Daftar Modul (baca saja). Daftar modul tunggal di `App\Support\Modules` (menu, pengaturan grup, penjagaan URL lewat middleware `EnsureModuleAccess`). Akses = peran cukup DAN modul dicentang di grup; pengguna tanpa grup memakai bawaan peran. Menu profil: Chat, Asisten AI, Tema dihapus (`:without` di DevLib layer-shell), Setup diganti halaman Bahasa
- Wallpaper otomatis: gambar apa pun (PNG, WebP, BMP, GIF, AVIF, atau JPG terlalu besar) yang ditaruh di `public/media/bg/sstock` diubah ke JPG siap pakai saat halaman login dibuka (`App\Support\Wallpapers`, atau manual `php artisan sstock:wallpapers`); lebar maks 2560 px, rasio ganjil dipotong tengah ke 16:9, file asli dipindah ke `storage/app/wallpaper-source`. Butuh ekstensi PHP GD dan folder wallpaper yang bisa ditulis server
- Tombol Kembali selalu ke grid (config `devlib.back_history=false`, opsi baru di DevLib; bawaan DevLib tetap "layar sebelumnya"). Layout menolak kirim form ganda selama 8 detik setelah kiriman pertama (cegah dokumen/stok ganda karena klik Simpan dua kali)
- Lupa password: tautan "Lupa password?" di login → isi username/e-mail → tautan reset dikirim ke e-mail akun (broker password Laravel, berlaku 60 menit, sekali pakai; akun nonaktif/tidak dikenal tidak dikirimi, pesan tetap sama). `PasswordResetController`, layar `resources/views/auth/*`. Saat ini `MAIL_MAILER=log`: e-mail hanya tertulis di `storage/logs/laravel.log`; untuk produksi isi SMTP (`MAIL_*` di `.env`) supaya e-mail benar-benar terkirim
- Master: Item (kategori, satuan, rak, harga, Reorder Point/Qty, Lead Time), Supplier; kategori hanya lewat seeder
- Supplier: telepon kantor (PSTN, kolom `phone`), nama kontak + HP kontak, alamat jalan, lalu satu kotak cari `geo_area` (kecamatan > kota/kabupaten > provinsi > negara + kode pos, wajib; kode pos disimpan server dari wilayah terpilih); grid punya satu kolom "City, Country"
- Master Satuan (Master Data > Units, Supervisor): kode tetap, nama, aktif/nonaktif; Item memilih satuan dari master (foreign key `items.unit` > `units.code`). Konversi satu aturan untuk semua item (`unit_conversions`: 1 box = 12 pcs; arah sebaliknya dihitung, tanpa rantai) lewat tabel Conversions di form Unit; `App\Support\UnitConverter`
- Receiving boleh dalam satuan lain dari satuan stok item: baris punya kolom Unit, jumlah + harga dikonversi ke satuan stok saat Save (qty/unit_cost = satuan stok, entered_* = yang diketik); tanpa aturan konversi ditolak. Issuance, Opening, Adjustment tetap satuan stok
- Tampilan grid: transaksi POLOS (nomor dokumen tebal di kolom pertama, hanya Status yang berwarna, untuk data banyak); master memakai kotak warna per baris: Items per kategori (ATK hijau, Sparepart biru, Tools ungu, variasi per item), Units per jenis (berat oranye, panjang toska, volume biru muda, hitungan indigo), Suppliers warna dari nama (`App\Support\Palette`). Memakai prop baru `hue` pada kolom identity di DevLib (`grid.js`, `Grid.php`; perubahan ada di repo `../devlib` dan harus di-commit di sana)
- **Bahasa: seluruh tampilan Indonesia.** Kode layar tetap menulis label English (kebiasaan DevLib); kamus `App\Support\Indonesian` menerjemahkannya (`Lang::translateUsing`). Istilah tombol/status DevLib yang biasanya dipaksa English ikut kamus lewat `config('devlib.translate_fixed')` (perubahan opsional di `../devlib`: `Button`, `Badge`, `Form::steps`; belum di-commit di repo itu). Teks yang dirangkai di PHP (pesan, menu, jenis dokumen, nilai status) ditulis langsung Indonesia; pesan validasi di `lang/id/validation.php`. Label baru: tambahkan ke kamus, lalu cek layar. Mata uang tetap kode `IDR` (aturan DevLib)
- Wilayah: tabel `geo_*` salinan tmerp (`tm2mst`, id sama; 250 negara, ±80 rb kelurahan) lewat `php artisan sstock:import-geo` (otomatis di `sstock:install`, tidak fatal bila gagal); logika cari di `App\Support\GeoArea`, lookup di `App\Support\GeoLookup`. Pencarian ±1 detik (join 5 tabel, LIKE di tengah kata)
- Transaksi (`docs/{type}`) semua jenis: **Draf** (disimpan, BELUM menyentuh stok, boleh Hapus) > **Confirm** (diproses ke stok; tanggal/jam = saat Confirm; saldo awal memakai tanggalnya) > **Cancel** (hanya Supervisor/Administrator, alasan wajib; stok dikembalikan lewat baris pembalik `stock_movements.reversal_of`, baris asal tidak diubah; ditolak bila stok tidak cukup untuk menarik penerimaan; pemesanan ulang yang tertutup oleh penerimaan itu terbuka lagi). Dokumen dibatalkan/draf tidak dihitung di laporan Penerimaan/Pengeluaran dan KPI hari ini. Stock Adjustment: Draf > Confirm (diajukan) > Approve (Supervisor, baru diproses ke stok) / Reject; Cancel menarik pengajuan atau membalik yang sudah disetujui. Kartu Stok menandai baris pembalik "(BATAL)"
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
- Login: wallpaper `public/media/bg/sstock` (enam foto JPG 1920x1080: ATK/ruang kerja, gear bengkel, gudang; foto buatan pemilik, bukan gambar DevLib; file asli PNG ada di `storage/app/wallpaper-source`, tidak ikut git; login memilih acak dari semua `.jpg` di folder itu), kutipan `public/media/text/*.txt` (salinan DevLib).

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
