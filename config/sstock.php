<?php

/* REMARK
 * Tujuan       : Pengaturan khas Stoku. sample_refresh = layar Administrator "Data Contoh" boleh menghapus seluruh data lalu mengisi ulang data contoh.
 *                Bawaan: aktif selain di production; di production wajib dinyalakan sengaja lewat SSTOCK_SAMPLE_REFRESH=true.
 * Dipakai oleh : App\Services\SampleData, SampleDataController.
 * Status       : Aktif.
 */
return [
    'sample_log' => storage_path('app/sample-refresh.json'),  // catatan penyegaran terakhir, di luar database
    'sample_refresh' => (bool) env('SSTOCK_SAMPLE_REFRESH', env('APP_ENV', 'production') !== 'production'),
];
