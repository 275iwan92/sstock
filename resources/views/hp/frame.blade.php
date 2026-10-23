{{-- REMARK
 Tujuan       : Modul yang belum punya tampilan HP: halaman desktop modul itu dibuka di dalam kerangka HP (bilah atas + tab bawah tetap ada).
 Sumber hulu  : HpController@open.
 Input        : title (nama modul), frame (alamat halaman desktop), active (tab menyala).
 Output       : hp.layout dengan iframe sebagai isi.
 Dipakai oleh : route hp.open.
 Status       : Aktif.
--}}
{{-- Layar tingkat atas (tab Stok / Transaksi / Opname) = hamburger; modul dari Menu = layar anak dengan tombol kembali. --}}
@extends('hp.layout', ['back' => ($active ?? '') === 'menu' ? route('home') : null])
