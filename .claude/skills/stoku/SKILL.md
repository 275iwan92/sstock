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
5. **Form dokumen:** wajib Confirm, Delete (selama draf), dan Cancel; aturan lengkap di bagian "Siklus dokumen". Form tidak boleh terkirim dua kali (penjaga di `layout.blade.php`).
6. **Stok** hanya diubah lewat `App\Services\StockLedger` (`post`, `reverse`, `assertReversible`); siklus dokumen di `App\Services\StockDocuments`. Pembalikan = baris baru `reversal_of`, baris asal tidak diubah.
7. **Verifikasi** tampilan di http://sstock.test. Setelah mengubah `resources/css/app.css` jalankan `npm run build` dan muat ulang keras. Kembalikan viewport emulasi ke `desktop`. Jangan tinggalkan data uji di database lokal.
8. Tanya pemilik hanya bila benar-benar buntu; selebihnya putuskan sendiri dan sebutkan asumsinya.

## Siklus dokumen: Confirm, Delete, Cancel
Berlaku untuk SEMUA form dokumen stok (Saldo Awal, Penerimaan, Pengeluaran, Penyesuaian) dan setiap jenis dokumen baru. Logika hanya di `App\Services\StockDocuments`; controller `StockDocumentController@update` membaca `_action` (save, confirm, delete, cancel, approve, reject). Form = `x-dl::form kind="document" :pipeline="true"` ([docs/form.blade.php](resources/views/docs/form.blade.php)); tombol Confirm/Delete/Cancel/Approve/Reject berasal dari status DevLib, jangan dibuat manual.

**Status:** `draft` > `confirmed` > `cancel_pending` (pengajuan batal) > `cancelled`. Penyesuaian: `draft` > `approval` (setelah Confirm) > `approved` | `rejected`, lalu bisa `cancel_pending` > `cancelled`.

**Draf**
- Dokumen baru selalu `draft`: stok TIDAK berubah, nomor dokumen otomatis (PREFIX-YYMM-NNNN).
- Hanya draf yang boleh diubah (Simpan) dan di-Delete (hapus permanen; draf dihapus, bukan dibatalkan).
- Tombol **Confirm** wajib ada. Syarat: minimal satu baris (jika kosong ditolak dengan pesan).

**Confirm** (Saldo Awal, Penerimaan, Pengeluaran)
- Diproses ke stok lewat `StockLedger::post` dalam satu transaksi; status `confirmed`, `confirmed_by/at` terisi.
- Tanggal/jam dokumen = saat Confirm (Saldo Awal: tanggal yang dipilih pengguna).
- Penerimaan Confirm menutup pemesanan ulang (`reorder_orders`) yang terbuka untuk item di dokumen itu.
- Dokumen yang sudah Confirm terkunci (tidak bisa diedit); koreksi lewat Stock Adjustment.

**Confirm Penyesuaian** = diajukan (`approval`), stok belum berubah. **Approve** (hanya Supervisor) baru memposting ke stok, dan ditolak bila stok item berubah sejak dihitung (suruh Reject lalu hitung ulang). **Reject** (hanya Supervisor) menutup dokumen tanpa stok berubah.

**Cancel dokumen yang sudah Confirm/Approve**
- Alasan WAJIB (minimal 3 huruf, maks 255).
- Pengguna biasa MENGAJUKAN: status `cancel_pending`, stok belum berubah, form menampilkan peringatan + tahap persetujuan DevLib. Supervisor/Administrator menekan Cancel = langsung dibatalkan, tanpa persetujuan lain.
- Supervisor menyetujui (`approveCancel`: stok dibalik, diperiksa ulang) atau menolak (`rejectCancel`: kembali ke `confirmed`/`approved`, alasan dikosongkan).
- Pengaju (atau Supervisor) boleh menarik pengajuan yang masih menunggu (`withdrawn`); orang lain tidak (403).
- Sebelum menerima pengajuan, `StockLedger::assertReversible` MENGHITUNG apakah stok cukup untuk menarik dokumen itu kembali. Tidak cukup: DITOLAK, pengguna membuat Stock Adjustment. Hasil hitungan (`cancelProblem`) tampil sebagai peringatan di form sebelum tombol ditekan dan dihitung ulang saat persetujuan.
- Pembalikan stok = baris baru di `stock_movements` dengan `reversal_of`; baris asal tidak diubah. Penerimaan yang dibatalkan membuka lagi pemesanan ulang yang tadinya ditutupnya.
- Penyesuaian yang baru diajukan (`approval`, belum disetujui) hanya ditarik tanpa pembalikan stok.

**Jejak:** setiap tahap (Dibuat, Dikonfirmasi, Mengajukan pembatalan, Disetujui, Dibatalkan + alasan) masuk `log` form.

**Hak:** Approve/Reject Supervisor saja (403 untuk lainnya). Belum ada pemisahan tugas: pembuat penyesuaian masih boleh menyetujui sendiri.

**Tes wajib** di `tests/Feature/DocumentLifecycleTest.php` (dan `StockFlowTest.php`) untuk setiap jenis dokumen (Confirm tidak mengubah stok sebelum waktunya, Cancel ditolak saat stok kurang, pengajuan lalu persetujuan).

## Gaya grid
- **Grid transaksi** (dokumen stok, dan grid transaksi baru lainnya): POLOS. Kolom terdepan = nomor dokumen, hanya tebal, TANPA kotak/avatar warna (`'type' => 'identity', 'avatar' => false`, tanpa `box`/`hue`). WAJIB ada kolom Status bertipe `badge` dengan `tones` (hijau Terkonfirmasi/Disetujui, abu Draf, kuning menunggu, merah Dibatalkan/Ditolak); itu satu-satunya warna di baris. Contoh: `resources/views/docs/index.blade.php`.
- **Grid master** (barang, kategori, lokasi, supplier, satuan, departemen, penerima, pengguna, dst.): kolom terdepan memakai KOTAK warna per baris berisi inisial, dengan nama tebal dan kode kecil di bawahnya: `['key' => 'name', 'label' => '<Nama>', 'type' => 'identity', 'code' => '<kolom kode>', 'box' => true]`. Tambahkan `'hue' => 'hue'` bila warnanya bermakna dan controller mengirim kolom `hue` dari `App\Support\Palette` (barang menurut kategori, satuan menurut jenis); tanpa `hue` warna dipilih otomatis. Contoh: `resources/views/items/index.blade.php`, `suppliers/index.blade.php`. Grid master BARU wajib mengikuti pola ini, bukan `avatar => false`.
- Pengecualian yang ada: `groups/index` dan `modules/index` (menu Keamanan) memakai `avatar => false`, belum berkotak.

## Tombol Kembali
Setiap form (dokumen maupun master) wajib punya tombol Kembali yang menuju GRID modulnya, bukan layar sebelumnya. Caranya: pada komponen toolbar form isi prop `:back="route('<modul>.index')"` (lihat `resources/views/items/form.blade.php`). `config/devlib.php` `back_history => false` (`DL_BACK_HISTORY=false`) membuat tombol memakai alamat itu, bukan `history.back()`. Jangan memakai `history.back()` atau `url()->previous()` di form baru.

## Perintah
`php artisan test` (SQLite in-memory), `vendor\bin\pint --dirty`, `npm run build`, `php artisan sstock:wallpapers`, `php artisan sstock:import-geo`.

## Jebakan alat
- Heredoc Python di bash gagal bila berisi `\` atau tanda kutip: pakai Write/Edit atau berkas skrip.
- Tinker satu baris berisi `$` gagal: pakai berkas skrip (`php artisan tinker <berkas.php>`).
- Widget DevLib: select = `[data-dl-select]` + input tersembunyi `[data-dl-value]`; modal dibuka dengan class `is-on` pada `[data-dl-modal-id]`; validasi form menghitung `.dl-field__req` yang kosong.
- `database/sql/sstock_mysql_ddl.sql` ketinggalan; migrasi adalah sumber kebenaran.
