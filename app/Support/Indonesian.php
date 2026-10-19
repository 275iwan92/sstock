<?php

namespace App\Support;

use Devlib\Lang;

/* REMARK
 * Tujuan       : Kamus Indonesia aplikasi: layar ditulis English (kebiasaan DevLib), kamus ini menerjemahkan semua label komponen x-dl::* (label, judul, kolom, hint, tombol, status) ke bahasa Indonesia.
 *                Teks yang dirangkai di PHP (pesan, menu, jenis dokumen) ditulis langsung Indonesia di tempatnya.
 * Sumber hulu  : Devlib\Lang::translateUsing; config devlib.translate_fixed menyalakan terjemahan istilah tombol / status DevLib yang biasanya dipaksa English.
 * Output       : pemetaan English => Indonesia; teks yang tidak ada di kamus tampil apa adanya.
 * Dipakai oleh : AppServiceProvider::boot.
 * Status       : Aktif.
 */
final class Indonesian
{
    public static function register(): void
    {
        Lang::extend(['pr_printed_from' => ['Dicetak dari Stoku', 'Printed from Stoku']]);
        Lang::translateUsing(fn (string $en, string $lang) => $lang === 'id' ? (self::WORDS[$en] ?? null) : null);
    }

    private const WORDS = [
        // Tombol, status, istilah tetap DevLib
        'New' => 'Baru', 'Save' => 'Simpan', 'Undo' => 'Urungkan', 'Delete' => 'Hapus', 'Confirm' => 'Konfirmasi', 'Cancel' => 'Batalkan', 'Duplicate' => 'Duplikat',
        'Back to Draft' => 'Kembali ke Draf', 'Back' => 'Kembali', 'Deactivate' => 'Nonaktifkan', 'Activate' => 'Aktifkan', 'Approve' => 'Setujui', 'Reject' => 'Tolak',
        'Hold' => 'Tahan', 'Print' => 'Cetak', 'Email' => 'Email', 'Close' => 'Tutup',
        'Draft' => 'Draf', 'Confirmed' => 'Terkonfirmasi', 'Cancelled' => 'Dibatalkan', 'Closed' => 'Ditutup', 'Active' => 'Aktif', 'Inactive' => 'Nonaktif',
        'Approved' => 'Disetujui', 'Rejected' => 'Ditolak', 'Waiting Approval' => 'Menunggu Persetujuan', 'Waiting Approval 1' => 'Menunggu Persetujuan 1',
        'Approval 1' => 'Persetujuan 1', 'Hold L1' => 'Ditahan T1',
        '+ New Record' => '+ Data Baru', 'Filters' => 'Saring', 'Reset filter' => 'Reset saringan', 'Export' => 'Ekspor', 'Created by' => 'Dibuat oleh', 'Created at' => 'Dibuat pada',
        'Last updated by' => 'Terakhir diubah oleh', 'Last updated at' => 'Terakhir diubah pada', 'History' => 'Riwayat',

        // Judul halaman
        'Home' => 'Beranda', 'Sign in' => 'Masuk', 'Items' => 'Barang', 'Item' => 'Barang', 'Suppliers' => 'Supplier', 'Supplier' => 'Supplier', 'Units' => 'Satuan', 'Unit' => 'Satuan',
        'Stock Card' => 'Kartu Stok', 'Summary Stock' => 'Ringkasan Stok', 'Receiving Report' => 'Laporan Penerimaan', 'Issuance Report' => 'Laporan Pengeluaran',
        'Opname & Adjustment Report' => 'Laporan Opname & Penyesuaian', 'Stoku' => 'Stoku',
        'Item · ' => 'Barang · ', 'Item · New' => 'Barang · Baru', 'Supplier · ' => 'Supplier · ', 'Supplier · New' => 'Supplier · Baru', 'Unit · ' => 'Satuan · ', 'Unit · New' => 'Satuan · Baru',

        // Beranda
        'Active items' => 'Barang aktif', 'At / below reorder point' => 'Di / bawah titik pesan ulang', 'Receiving + issuance today' => 'Penerimaan + pengeluaran hari ini',
        'Stock value' => 'Nilai stok', 'Adjustments waiting approval' => 'Penyesuaian menunggu persetujuan', 'Open Receiving' => 'Buka Penerimaan',
        'Code' => 'Kode', 'Category' => 'Kategori', 'Location' => 'Lokasi', 'Stock' => 'Stok', 'Reorder Point' => 'Titik Pesan Ulang', 'Reorder Qty' => 'Jml Pesan Ulang', 'Lead Time' => 'Lead Time',

        // Grid master
        'All items' => 'Semua barang', 'Reorder' => 'Pesan ulang', 'Search code, name, location...' => 'Cari kode, nama, lokasi...', 'Level' => 'Status stok',
        'Lead Time (days)' => 'Lead Time (hari)', 'Unit Price' => 'Harga Satuan', 'Stock Value' => 'Nilai Stok',
        'All suppliers' => 'Semua supplier', 'Search code, name, contact, city...' => 'Cari kode, nama, kontak, kota...', 'Company Phone' => 'Telepon Kantor', 'Contact' => 'Kontak',
        'Contact Mobile' => 'HP Kontak', 'City, Country' => 'Kota, Negara', 'Street Address' => 'Alamat Jalan',
        'All units' => 'Semua satuan', 'Search code or name...' => 'Cari kode atau nama...', 'Type' => 'Jenis', 'Conversions' => 'Konversi', 'Items using it' => 'Barang pemakai',

        // Form Barang
        'Stock on hand' => 'Stok saat ini', 'Average cost' => 'Biaya rata-rata', 'Moving average of purchases' => 'Rata-rata bergerak dari pembelian', 'Stock x average cost' => 'Stok x biaya rata-rata',
        'Reorder and Price' => 'Pesan Ulang dan Harga', 'Item Name' => 'Nama Barang', 'Item Code' => 'Kode Barang', 'Rack / Storage Location' => 'Lokasi Rak / Penyimpanan',
        'Unique. Cannot be changed after saving.' => 'Unik. Tidak bisa diubah setelah disimpan.', 'Unit Price (IDR)' => 'Harga Satuan (Rp)',
        'Alert shows when stock is at or below this quantity. 0 = no alert.' => 'Peringatan muncul bila stok sama dengan atau di bawah jumlah ini. 0 = tanpa peringatan.',
        'Suggested quantity to order.' => 'Jumlah pesanan yang disarankan.', 'Days from placing the order until the supplier delivers.' => 'Hari sejak pesanan dibuat sampai supplier mengirim.',
        'Reference price. Stock valuation uses the moving average of purchase prices.' => 'Harga acuan. Valuasi stok memakai rata-rata bergerak harga pembelian.',

        // Form Supplier
        'Supplier Code' => 'Kode Supplier', 'Supplier Name' => 'Nama Supplier', 'Company Phone (PSTN)' => 'Telepon Kantor (PSTN)', 'Landline, with area code.' => 'Telepon kabel, dengan kode area.',
        'Contact Name' => 'Nama Kontak', 'Contact Mobile (smartphone)' => 'HP Kontak (smartphone)', 'Also used for WhatsApp.' => 'Juga dipakai untuk WhatsApp.',
        'District, City / Regency, Province, Country' => 'Kecamatan, Kota / Kabupaten, Provinsi, Negara', 'Search district / city / postcode...' => 'Cari kecamatan / kota / kode pos...',
        'One choice gives district, city / regency, province, country, and the postal code.' => 'Satu pilihan mengisi kecamatan, kota / kabupaten, provinsi, negara, dan kode pos.',
        'Region' => 'Wilayah', 'Postcode' => 'Kode Pos',

        // Form Satuan
        'Unit Code' => 'Kode Satuan', 'Unit Name' => 'Nama Satuan', 'Short, lowercase (pcs, box, kg). Cannot be changed after saving.' => 'Singkat, huruf kecil (pcs, box, kg). Tidak bisa diubah setelah disimpan.',
        'Sets the colour of the unit in lists (for example weight units share one colour).' => 'Menentukan warna satuan di daftar (misalnya satuan berat satu warna).',
        '+ Add Conversion' => '+ Tambah Konversi', 'Factor' => 'Faktor',

        // Dokumen stok
        'Document No.' => 'No. Dokumen', 'Date & Time' => 'Tanggal & Jam', 'Entered by' => 'Diinput oleh', 'Balance As Of (period start)' => 'Saldo per Tanggal (awal periode)',
        'Only for items without any stock movement yet.' => 'Hanya untuk barang yang belum punya pergerakan stok.', 'Reference No. (delivery note / PO / invoice)' => 'No. Referensi (surat jalan / PO / faktur)',
        'Recipient' => 'Penerima', 'Department' => 'Departemen', 'Purpose (maintenance / ATK ...)' => 'Keperluan (maintenance / ATK ...)',
        'Reason for adjustment / opname' => 'Alasan penyesuaian / opname', 'Notes' => 'Catatan', '+ Add Item' => '+ Tambah Barang',
        'Qty' => 'Jml', 'Purchase Price / Unit' => 'Harga Beli / Satuan', 'Subtotal' => 'Subtotal', '= In stock unit' => '= Dalam satuan stok', 'Line Note' => 'Catatan Baris',
        'Available' => 'Tersedia', 'Opening Qty' => 'Jml Awal', 'Unit Value' => 'Nilai Satuan', 'Valuation' => 'Valuasi', 'System Qty' => 'Jml Sistem',
        'Counted Qty (physical)' => 'Jml Hitung (fisik)', 'Difference' => 'Selisih',
        'Document' => 'Dokumen', 'Reference No.' => 'No. Referensi', 'Reason' => 'Alasan', 'Value' => 'Nilai', 'Status' => 'Status', 'All' => 'Semua', 'Waiting approval' => 'Menunggu persetujuan',
        'Search document, party, reference...' => 'Cari dokumen, pihak, referensi...',

        // Laporan
        'From date' => 'Dari tanggal', 'To date' => 'Sampai tanggal', 'Apply' => 'Terapkan', 'All categories' => 'Semua kategori', 'Filter' => 'Saring',
        'Only items at / below reorder point' => 'Hanya barang di / bawah titik pesan ulang', 'Include inactive items' => 'Termasuk barang nonaktif', 'All suppliers ' => 'Semua supplier',
        'Recipient / Department' => 'Penerima / Departemen', 'Choose an item' => 'Pilih barang', 'Select an item and a period, then press Apply to see its stock card.' => 'Pilih barang dan periode, lalu tekan Terapkan untuk melihat kartu stoknya.',
        'Stock movement history per item' => 'Riwayat pergerakan stok per barang', 'Stock position and movement per period' => 'Posisi dan pergerakan stok per periode',
        'Goods received from suppliers' => 'Barang diterima dari supplier', 'Goods issued for maintenance / ATK' => 'Barang keluar untuk maintenance / ATK', 'Physical count vs system stock' => 'Hitung fisik dibanding stok sistem',
        'Period' => 'Periode', 'Scope' => 'Cakupan', 'Type ' => 'Tipe',
        'Opening' => 'Awal', 'In' => 'Masuk', 'Out' => 'Keluar', 'Adjustment' => 'Penyesuaian', 'Balance' => 'Saldo', 'Closing' => 'Akhir', 'Unit Cost' => 'Biaya Satuan', 'Balance Value' => 'Nilai Saldo',
        'Reference / Note' => 'Referensi / Catatan', 'User' => 'User', 'Item Code ' => 'Kode Barang', 'Lead Time (d)' => 'Lead Time (hari)', 'Avg Cost' => 'Biaya Rata-rata',
        'Qty (stock unit)' => 'Jml (satuan stok)', 'Received as' => 'Diterima sebagai', 'Purchase Price' => 'Harga Beli', 'Amount' => 'Jumlah', 'Cost' => 'Biaya', 'Counted Qty' => 'Jml Hitung',
        'Value Effect' => 'Dampak Nilai', 'Counted by' => 'Dihitung oleh', 'Approved / Rejected by' => 'Disetujui / Ditolak oleh',
        'Per Month' => 'Per Bulan', 'Per Category' => 'Per Kategori', 'Per Status' => 'Per Status', 'Per Supplier' => 'Per Supplier', 'Per Department' => 'Per Departemen',
        'Per Recipient' => 'Per Penerima', 'Per Document' => 'Per Dokumen',
        'Opening Balance' => 'Saldo Awal', 'Total In' => 'Total Masuk', 'Total Out' => 'Total Keluar', 'Closing Balance' => 'Saldo Akhir', 'Total Stock Value' => 'Total Nilai Stok',
        'Lines received' => 'Baris diterima', 'Total Purchase Value' => 'Total Nilai Pembelian', 'Lines issued' => 'Baris dikeluarkan', 'Total Cost Issued' => 'Total Biaya Keluar',
        'Lines counted' => 'Baris dihitung', 'Net value effect' => 'Dampak nilai bersih',
        'Prepared by' => 'Dibuat oleh', 'Warehouse' => 'Gudang', 'Checked by' => 'Diperiksa oleh', 'Received by' => 'Diterima oleh', 'Approved by' => 'Disetujui oleh', 'Supervisor' => 'Supervisor',

        // Login / umum
        'Supervisor Gudang' => 'Supervisor Gudang',
    ];
}
