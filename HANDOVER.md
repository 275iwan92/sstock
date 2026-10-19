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
- Proyek Laravel dibuat, devlib terpasang
- `resources/css/app.css`: `@source` ke `../../../devlib/src` dan `../../../devlib/resources`
- `resources/views/layout.blade.php`: memuat `devlib.css`, `devlib.js`, Vite
- `resources/views/welcome.blade.php`: placeholder satu `x-dl::kpi` (bukti devlib jalan)
- Diverifikasi: sstock.test HTTP 200, aset devlib terlayani, `npm run build` OK, 2 tes bawaan lulus

## Belum dikerjakan
- Tabel dan layar stok (barang, mutasi masuk/keluar, saldo)
- Tema devlib: `x-dl::theme-boot` belum dipasang (di tmerp kemungkinan membaca tabel `sys_theme`, belum dicek)
- Login/auth, hak akses, bahasa (`Devlib\Lang`)
- `CLAUDE.md` khusus sstock

## Keputusan terbuka
- DB memakai SQLite karena akun MySQL root butuh password dan kredensial tmerp tidak dipakai.
  Untuk MySQL seperti tmerp: tentukan user dan nama database, lalu ubah `DB_*` di `.env`.

## Menjalankan di mesin lain
```bash
git clone https://github.com/275iwan92/sstock.git   # taruh sejajar dengan devlib
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate
npm install && npm run build    # wajib setiap menambah class Tailwind
```
Lalu daftarkan folder ke Herd (park/link) → http://sstock.test

## Catatan kerja
- Aturan tmerp (`tmerp/CLAUDE.md`, skill `tmerp/.ai/skills/devlib-oo`): DevLib membuat, aplikasi mewarisi;
  layar = deklarasi `x-dl::*`, tanpa HTML mentah / warna literal; label English, nilai data dibungkus `raw()`.
- Perubahan pada devlib di-commit di repo devlib (`C:\Users\iwanp\Herd\devlib`), bukan di sstock.
