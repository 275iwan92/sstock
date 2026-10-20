<?php

/* REMARK
 * Tujuan       : Pengaturan khas Stoku. sample_refresh = layar Administrator "Data Contoh" boleh menghapus seluruh data lalu mengisi ulang data contoh.
 *                Bawaan: aktif selain di production; di production wajib dinyalakan sengaja lewat SSTOCK_SAMPLE_REFRESH=true.
 * Dipakai oleh : App\Services\SampleData, SampleDataController.
 * Status       : Aktif.
 */
return [
    // Authority bertingkat: peran paling rendah yang boleh menyetujui tiap tingkat dokumen, berurutan (entry < supervisor < administrator).
    // Bawaan satu tingkat (Supervisor). Dua tingkat: SSTOCK_APPROVAL_ADJUSTMENT=supervisor,administrator. Berlaku untuk persetujuan Penyesuaian Stok.
    'approval' => [
        'adjustment' => array_values(array_filter(array_map('trim', explode(',', (string) env('SSTOCK_APPROVAL_ADJUSTMENT', 'supervisor'))))),
    ],
    'sample_log' => storage_path('app/sample-refresh.json'),  // catatan penyegaran terakhir, di luar database
    // Batch yang kedaluwarsa dalam N hari ke depan tidak dihitung sebagai stok tersedia untuk titik pesan ulang. 0 = hanya yang sudah lewat (bawaan).
    'reorder_exclude_days' => (int) env('SSTOCK_REORDER_EXCLUDE_DAYS', 0),
    'sample_refresh' => (bool) env('SSTOCK_SAMPLE_REFRESH', env('APP_ENV', 'production') !== 'production'),
];
