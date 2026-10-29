# Super admin (khusus UAT / demo)

Catatan pemilik. **Sengaja tidak ada di Bantuan dalam aplikasi** supaya Administrator lain tidak melihat bahwa akun ini ada. Stoku ini program demo / UAT, bukan data nyata.

## Akun

- Satu akun (username di `.env` `SSTOCK_SUPER_ADMIN`, kata sandinya sebagai **hash bcrypt** di `SSTOCK_SUPER_ADMIN_HASH`; tidak ada kata sandi di kode atau repo). Kosong = fitur mati.
- Berperan Administrator, tetapi **tidak tampil** di layar Pengguna, hitungan anggota Grup, dan hitungan di layar Data Contoh; tidak bisa dibuka, diubah, atau dinonaktifkan lewat layar Pengguna (404); tidak bisa memakai Lupa password.
- Dibuat ulang oleh `UserSeeder` setiap data contoh disegarkan (hash dari `.env`).
- Mengganti kata sandinya: buat hash baru (`php artisan tinker --execute="echo bcrypt('kata-sandi');"`), tulis di `.env` dengan tanda kutip tunggal (`'...'`, karena ada tanda `$`), lalu `php artisan config:clear`. Akun yang sudah ada ikut berubah setelah penyegaran data contoh berikutnya atau lewat layar Akun UAT.

## Hanya dia yang boleh menyegarkan data contoh

Layar Keamanan > Data Contoh (ketik `SEGARKAN`) menghapus seluruh data lalu mengisi ulang data contoh: semua akun kembali ke akun seeder dan kata sandi bawaannya. Bila super admin terdaftar, **hanya dia** yang boleh, di lingkungan mana pun (termasuk production); Administrator lain ditolak (403). Belum terdaftar = aturan lama (sakelar `SSTOCK_SAMPLE_REFRESH`, semua Administrator).

## Layar super admin (menu Keamanan, hanya tampil baginya; selain dia 404)

- **Log Aktivitas** (`/super/log`): Waktu, User, IP, Aksi (Buka, Input, Ubah, Hapus, Batalkan, Unduh, Login, Login gagal, Logout, Reset data contoh), Rincian (nama rute + alamat). Per bulan, 1.500 baris terbaru, cari / urutkan, unduh CSV.
- **Akun UAT** (`/super/akun`): semua akun dengan **kata sandi bawaan seeder** dan kolom untuk mengubah kata sandi akun mana pun (disimpan sebagai hash, sesi lama akun itu keluar).

Kata sandi di database adalah hash dan **tidak bisa dibaca**; yang ditampilkan hanya nilai bawaan seeder, berlaku selama belum diubah. Sengaja tidak ada penyimpanan kata sandi yang bisa dibaca kembali: penguji bisa saja memakai kata sandi yang sama dengan akun lain milik mereka.

## Log aktivitas

- Berkas CSV per bulan: `storage/app/activity/YYYY-MM.csv` (`config sstock.activity_dir`). Dipilih CSV karena menulis = menambah satu baris di akhir berkas (tercepat), tanpa migrasi, mudah diunduh, dan **tidak ikut terhapus saat data contoh disegarkan** (database dibuang, berkas ini tidak).
- Dicatat oleh middleware `LogActivity` sesudah respons terkirim (tidak memperlambat layar). Yang dicatat hanya nama rute, alamat tanpa query, dan kode status bila galat. **Isi formulir dan kata sandi tidak pernah dicatat.** Ping sesi, permintaan JSON baca, dan berkas statis tidak dicatat.
- Di server, folder dan berkas harus bisa ditulis PHP-FPM (grup `www-data`): `storage/app/activity` bergrup `www-data`, mode `2775`.
