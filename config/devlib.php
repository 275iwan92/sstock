<?php

/* REMARK
 * Tujuan       : Konfigurasi DevLib untuk Stoku (stok sederhana) (identitas, bahasa, zona waktu, media login, hak akses komponen).
 * Dipakai oleh : Devlib\* (Brand, Frame, Locale, Access, Report/PrintOut).
 * Status       : Aktif.
 */
return [
    'brand' => ['name' => 'STOKU', 'tagline' => 'STOK SEDERHANA', 'logo' => '/media/brand/stoku.svg'],

    // Kop laporan cetak.
    'company' => ['name' => 'Stoku', 'initials' => 'ST', 'tagline' => 'Maintenance & ATK Warehouse', 'address' => '', 'contact' => ''],

    'lang' => env('DEVLIB_LANG', 'id'),
    'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta'),
    'timezone_label' => 'WIB',

    'media_path' => public_path('media'),
    'media_url' => '/media',

    // Lookup remote (/_devlib-lookup/{name}) hanya untuk user yang login.
    'lookup_middleware' => ['web', 'auth'],

    // Hak akses atribut can="..." diatur lewat Access::resolveUsing di AppServiceProvider.
    'abilities' => ['*'],
];
