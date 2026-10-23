{{-- REMARK
 Tujuan       : Modul yang belum punya tampilan HP: halaman desktop modul itu dibuka di dalam kerangka HP (bilah atas dengan tombol menu tetap ada).
 Sumber hulu  : HpController@open.
 Input        : title (nama modul), frame (alamat halaman desktop), active (menu utama yang ditandai).
 Output       : hp.layout dengan iframe sebagai isi.
 Dipakai oleh : route hp.open.
 Status       : Aktif.
--}}
@extends('hp.layout', ['back' => route('home')])
