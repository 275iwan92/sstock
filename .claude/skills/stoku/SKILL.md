---
name: stoku
description: Aturan kerja proyek Stoku (sstock, Laravel + DevLib). Muat sebelum mengubah apa pun di repo ini: layar/form, dokumen stok, terjemahan, DevLib, commit.
---

# Stoku (sstock) — aturan kerja

Bahasa kerja dengan pemilik: Indonesia, ringkas. Daftar fitur, akun uji, dan hal yang belum dikerjakan ada di `HANDOVER.md`.

## Wajib
1. **Selalu commit dan push** setiap selesai perubahan, walau belum "ok" atau tes gagal (backup pemilik). Pesan commit bahasa Indonesia + baris atribusi dari sistem. Jangan force-push, jangan commit `.env`. Repo `../devlib` tidak punya remote: cukup commit. Push gagal "Connection reset": ulangi.
2. **Semua tampilan Bahasa Indonesia.** Label layar boleh English (kebiasaan DevLib), lalu tambahkan ke kamus `App\Support\Indonesian` dan cek di layar. Teks yang dirangkai di PHP ditulis langsung Indonesia. Pesan validasi: `lang/id/validation.php` (termasuk nama atribut). Mata uang tetap `IDR`.
3. **DevLib** (`C:\Users\iwanp\Herd\devlib`, `telasih/devlib`, `x-dl::*`): layar = deklarasi `x-dl::*`, tanpa HTML mentah atau warna literal; nilai data dibungkus `raw()`. Perubahan DevLib harus OPT-IN (tmerp memakai DevLib dengan perilaku lama), commit di repo devlib. Perbaiki akar masalah di DevLib, bukan akal-akalan di aplikasi.
4. **Gaya grid:** lihat bagian "Gaya grid" di bawah.
5. **Form dokumen:** wajib ada Confirm (diproses ke stok), Delete selama belum Confirm, dan Cancel. Sebelum Confirm stok TIDAK berubah. Form tidak boleh terkirim dua kali (penjaga di `layout.blade.php`).
6. **Cancel dokumen yang sudah Confirm:** pengguna mengajukan dengan alasan, Supervisor menyetujui/menolak; Supervisor/Administrator langsung berlaku. Sistem menghitung dulu apakah stok cukup untuk menarik dokumen; bila tidak, DITOLAK dan pengguna membuat Stock Adjustment. Hitungan juga jadi peringatan di form dan diulang saat persetujuan.
7. **Stok** hanya diubah lewat `App\Services\StockLedger` (`post`, `reverse`, `assertReversible`); siklus dokumen di `App\Services\StockDocuments`. Pembalikan = baris baru `reversal_of`, baris asal tidak diubah.
8. **Verifikasi** tampilan di http://sstock.test. Setelah mengubah `resources/css/app.css` jalankan `npm run build` dan muat ulang keras. Kembalikan viewport emulasi ke `desktop`. Jangan tinggalkan data uji di database lokal.
9. Tanya pemilik hanya bila benar-benar buntu; selebihnya putuskan sendiri dan sebutkan asumsinya.

## Gaya grid
- **Grid transaksi** (dokumen stok, dan grid transaksi baru lainnya): POLOS. Kolom terdepan = nomor dokumen, hanya tebal, TANPA kotak/avatar warna (`'type' => 'identity', 'avatar' => false`, tanpa `box`/`hue`). WAJIB ada kolom Status bertipe `badge` dengan `tones` (hijau Terkonfirmasi/Disetujui, abu Draf, kuning menunggu, merah Dibatalkan/Ditolak); itu satu-satunya warna di baris. Contoh: `resources/views/docs/index.blade.php`.
- **Grid master** (barang, supplier, satuan, dst.): kotak warna per baris (`box`, `hue` dari `App\Support\Palette`).

## Tombol Kembali
Setiap form (dokumen maupun master) wajib punya tombol Kembali yang menuju GRID modulnya, bukan layar sebelumnya. Caranya: pada komponen toolbar form isi prop `:back="route('<modul>.index')"` (lihat `resources/views/items/form.blade.php`). `config/devlib.php` `back_history => false` (`DL_BACK_HISTORY=false`) membuat tombol memakai alamat itu, bukan `history.back()`. Jangan memakai `history.back()` atau `url()->previous()` di form baru.

## Perintah
`php artisan test` (SQLite in-memory), `vendor\bin\pint --dirty`, `npm run build`, `php artisan sstock:wallpapers`, `php artisan sstock:import-geo`.

## Jebakan alat
- Heredoc Python di bash gagal bila berisi `\` atau tanda kutip: pakai Write/Edit atau berkas skrip.
- Tinker satu baris berisi `$` gagal: pakai berkas skrip (`php artisan tinker <berkas.php>`).
- Widget DevLib: select = `[data-dl-select]` + input tersembunyi `[data-dl-value]`; modal dibuka dengan class `is-on` pada `[data-dl-modal-id]`; validasi form menghitung `.dl-field__req` yang kosong.
- `database/sql/sstock_mysql_ddl.sql` ketinggalan; migrasi adalah sumber kebenaran.
