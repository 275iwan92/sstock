# Surel (SMTP) produksi

Satu-satunya surel yang dikirim Stoku adalah **tautan lupa / atur ulang password** (`PasswordResetController`). Surelnya berbahasa Indonesia; subjek dan tanda tangannya memakai `APP_NAME`. Semua pengaturan lewat `.env` (dibaca dari `config/mail.php`, jadi tetap jalan saat `config:cache` dipakai di server).

Bila pengiriman gagal (kata sandi SMTP salah, port tertutup, ditolak penerima), pengguna melihat pesan "Surel belum bisa dikirim saat ini..." di layar Lupa password (bukan error 500), penyebab teknisnya tercatat di `storage/logs/laravel.log` (`Surel reset password gagal dikirim: ...`, tercatat juga dengan `LOG_LEVEL=warning`), dan pengguna boleh langsung meminta lagi.

## Pilihan mailer

| `MAIL_MAILER` | Arti |
|---|---|
| `log` | Surel tidak dikirim, hanya ditulis ke `storage/logs/laravel.log` (tautan reset bisa disalin dari sana). Bawaan pengembangan. |
| `smtp` | Dikirim lewat server SMTP di `MAIL_HOST:MAIL_PORT`. Untuk produksi. |

## Mengatur SMTP (contoh: Gmail sebagai relay)

Server tidak perlu memasang program surel (Postfix, msmtp): Laravel berbicara SMTP sendiri. Cukup akun SMTP luar.

1. Di akun Google yang dipakai, aktifkan Verifikasi 2 Langkah, lalu buat **Sandi aplikasi** (myaccount.google.com/apppasswords): 16 huruf. Ini bukan sandi akun Google.
2. Isi `.env` (laptop atau server):

   ```ini
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_SCHEME=null
   MAIL_USERNAME=alamat@gmail.com
   MAIL_PASSWORD=sandi-aplikasi-16-huruf-tanpa-spasi
   MAIL_FROM_ADDRESS=alamat@gmail.com
   MAIL_FROM_NAME="${APP_NAME}"
   MAIL_TIMEOUT=15
   ```

   - Port 587 + `MAIL_SCHEME=null` = STARTTLS. Alternatif: `MAIL_PORT=465` + `MAIL_SCHEME=smtps`.
   - `MAIL_FROM_ADDRESS` samakan dengan `MAIL_USERNAME`: Gmail menulis ulang pengirim lain menjadi akun itu.
   - Penyedia lain (domain sendiri, Brevo, Mailgun, dll.) sama: ganti host, port, username, password dari panel penyedia.
3. Setelah mengubah `.env` di server: `php artisan optimize:clear && php artisan optimize`, lalu `sudo systemctl reload php8.5-fpm`.
4. Uji satu surel: `php artisan sstock:mail-test alamat@contoh.id`. Hasilnya "Terkirim" atau pesan galat transport (kode keluar 1). Di server Biznet, jalankan sebagai pengguna web agar log bisa ditulis: `sudo -u www-data php artisan sstock:mail-test alamat@contoh.id`.
5. Uji lengkap dari layar: Lupa password, isi username akun yang e-mailnya milik Anda.

## Daftar periksa server

- [ ] Port keluar ke penyedia terbuka (di Biznet: 25, 465, 587 ke Google terbuka; 2525 tertutup, jangan dipakai).
- [ ] `.env` server berisi nilai di atas; `MAIL_PASSWORD` terisi (jangan dicetak ke chat atau di-commit; `.env` tidak ikut git).
- [ ] `php artisan optimize:clear && php artisan optimize` dan reload php-fpm sudah dijalankan.
- [ ] `sstock:mail-test` ke alamat sendiri berhasil dan surel diterima (cek juga folder Spam).
- [ ] `APP_URL` benar: tautan di dalam surel dibangun darinya (di Biznet `http://103.127.135.74/stoku`).
- [ ] Setiap akun yang perlu reset punya e-mail yang benar di layar Pengguna.

## Batas yang perlu diketahui

- **Server hanya punya alamat IP (tanpa domain)**: tidak ada SPF / DKIM / DMARC untuk IP itu. Surel yang dikirim LANGSUNG dari server ke penerima ditolak. Terbukti di Biznet (29 Okt 2026): Gmail menjawab `550 5.7.26 ... SPF [103.127.135.74] ... did not pass, DKIM did not pass`. Karena itu **jangan memakai pengiriman langsung**; lewati relay SMTP berotentikasi (Gmail / penyedia) seperti di atas. Relay memakai SPF/DKIM milik penyedia, jadi surel lolos.
- Dengan relay Gmail gratis, surel tampak berasal dari akun Gmail itu, bukan dari domain Stoku; kuota ±500 surel per hari. Cukup untuk lupa password. Untuk tampilan pengirim profesional, pakai domain sendiri + penyedia surel transaksional dan atur SPF / DKIM di DNS domain itu.
- Tanpa kredensial yang valid, `MAIL_MAILER=smtp` selalu gagal (pesan ramah + log; Gmail menjawab `535 5.7.8 Username and Password not accepted`). `MAIL_MAILER=log` di server tidak berguna: dengan `LOG_LEVEL=warning` surel yang "dikirim" ke log pun tidak tertulis, sehingga pengguna mengira surel terkirim. Karena itu `.env` Biznet memakai `smtp` dan hanya `MAIL_PASSWORD` yang menunggu diisi.
- Pesan "Surel belum bisa dikirim" hanya muncul untuk akun yang ada dan aktif (kegagalan terjadi saat mengirim), jadi secara teknis membuka sedikit informasi bahwa akun itu ada. Dianggap wajar untuk aplikasi internal UAT.
