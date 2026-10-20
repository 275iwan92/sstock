---
name: stok-batch-expiry
description: Penerapan aturan inventori (batch, kedaluwarsa, FEFO, pesan ulang per produk) di Stoku: produk + batch, StockDocuments, Reorder Point. Muat sebelum mengubah model produk/batch/kedaluwarsa, urutan pengeluaran, atau perhitungan Reorder Point di sstock.
---
# Stok: penerapan di Stoku

Aturan riset (kode vs batch, FEFO, peringatan, pesan ulang per produk) ada di skill lintas proyek `inventory` (~/.claude/skills/inventory), SATU sumber untuk semua proyek inventori. Baca itu dulu; file ini hanya penerapan khusus sstock. Aturan kerja umum ada di skill `stoku`.

## Terjemahan ke Stoku (turunan dari riset, bukan kutipannya)
- Master produk (kode tetap, kategori, satuan, `tracks_expiry`, Reorder Point/Qty, Lead Time) terpisah dari batch (nomor batch, tanggal kedaluwarsa, jumlah). Barang tanpa kedaluwarsa tidak wajib batch.
- Kedaluwarsa per batch dihitung/diisi saat Penerimaan Confirm (masa simpan di master bila ada); stok per batch ikut buku besar (`StockLedger`) dan pembalikan Cancel harus mengembalikan batch yang sama.
- Pengeluaran menyarankan batch menurut FEFO; memilih batch lain boleh dengan peringatan yang terlihat, tanpa blokir.
- Reorder Point di beranda dan laporan menghitung per produk (jumlah semua batch), memakai pesanan terbuka (`reorder_orders`) sebagai "sudah dipesan".
- Batch yang sudah/akan kedaluwarsa: peringatan terpisah (kartu beranda dan Laporan Kedaluwarsa sudah ada).
- Status implementasi: SELESAI (migrasi `create_products_and_batches`, model `Product`/`Item`, `StockDocuments::resolveItem/allocate/fefoWarnings`, `ProductController`, tes `ItemCodeTest`). Kode produk = `Product::nextCode`; kode batch = `{kode produk}-{YYMMDD}` (`Product::batchFor`), jadi tanggal ada di batch, tidak di kode produk. Pesan ulang per produk: `Product::availableSql()` / `scopeBelowReorderPoint` (batch kedaluwarsa tidak dihitung). Pembatalan memakai `StockLedger::reverse` per `item_id` baris, jadi batch yang sama otomatis kembali.
