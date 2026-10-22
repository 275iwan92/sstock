{{-- REMARK
 Tujuan       : Layar satu Stok Opname: kepala (nomor, status, ringkasan, aksi) lalu isinya menurut status.
 Sumber hulu  : StockCountController@show.
 Input        : count, sup, summary, review (Supervisor di luar status Sedang dihitung).
 Output       : stock-counts.header, lalu stock-counts.review (Supervisor) atau stock-counts.entry.
 Dipakai oleh : route stock-counts.show.
 Status       : Aktif.
--}}
@extends('layout', ['title' => 'Stok Opname'])
@section('body')
    @include('partials.flash')
    @include('stock-counts.header')
    @include($review ? 'stock-counts.review' : 'stock-counts.entry')
@endsection
