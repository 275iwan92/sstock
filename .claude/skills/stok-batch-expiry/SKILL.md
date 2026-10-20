---
name: stok-batch-expiry
description: Aturan domain stok untuk barang berkedaluwarsa dan batch (kode barang vs batch, FEFO, peringatan batch, titik pesan ulang per produk). Muat sebelum mengubah model produk/batch/kedaluwarsa, urutan pengeluaran, atau perhitungan Reorder Point di Stoku.
---

# Stok: batch, kedaluwarsa, FEFO, pesan ulang

Hasil riset (keyakinan tinggi) yang dipegang pemilik sebagai dasar desain. Jangan menyimpang tanpa alasan kuat dan izin pemilik. Aturan kerja umum ada di skill `stoku`.

1. **Tanggal kedaluwarsa TIDAK dimasukkan ke kode barang.** Kode barang tetap satu per produk; batch dan kedaluwarsa adalah data terpisah (pola GS1, SAP, Odoo).
   - GS1 memisahkan nomor batch (AI 10) dan kedaluwarsa (AI 17, format YYMMDD) dari kode barang (GTIN).
   - SAP menyimpan masa simpan di master material dan menghitung tanggal kedaluwarsa per batch saat barang diterima.
   - Penamaan SKU hanya memasukkan atribut yang stabil, seperti kategori.
2. **Urutan pengambilan barang berkedaluwarsa = FEFO** (kedaluwarsa duluan keluar duluan), bukan FIFO.
   - Ukuran utamanya tanggal kedaluwarsa; tanggal masuk hanya pemecah seri bila tanggalnya sama.
   - FIFO cukup hanya bila urutan datang sama dengan urutan kedaluwarsa, atau barangnya tidak punya kedaluwarsa.
3. **Mengambil batch yang kedaluwarsanya lebih lama dari batch lain = PERINGATAN saja.** Jangan memblokir (memblokir dengan kode alasan ditolak 0-3 itu bukan standar).
4. **Titik pesan ulang dihitung PER PRODUK, bukan per batch**, dari stok yang tersedia: stok fisik dikurangi yang akan keluar, ditambah yang sudah dipesan. Pemicu: jumlah itu di bawah minimum (Reorder Point); pesanan mengisi sampai maksimum.

## Terjemahan ke Stoku (turunan dari riset, bukan kutipannya)
- Master produk (kode tetap, kategori, satuan, `tracks_expiry`, Reorder Point/Qty, Lead Time) terpisah dari batch (nomor batch, tanggal kedaluwarsa, jumlah). Barang tanpa kedaluwarsa tidak wajib batch.
- Kedaluwarsa per batch dihitung/diisi saat Penerimaan Confirm (masa simpan di master bila ada); stok per batch ikut buku besar (`StockLedger`) dan pembalikan Cancel harus mengembalikan batch yang sama.
- Pengeluaran menyarankan batch menurut FEFO; memilih batch lain boleh dengan peringatan yang terlihat, tanpa blokir.
- Reorder Point di beranda dan laporan menghitung per produk (jumlah semua batch), memakai pesanan terbuka (`reorder_orders`) sebagai "sudah dipesan".
- Batch yang sudah/akan kedaluwarsa: peringatan terpisah (kartu beranda dan Laporan Kedaluwarsa sudah ada).
- Status implementasi: sesi lain sedang membangun produk + batch (migrasi `create_products_and_batches`); baca migrasi dan model `Product`/`Item` untuk keadaan terkini sebelum mengubah, dan perbarui baris ini bila berubah.
