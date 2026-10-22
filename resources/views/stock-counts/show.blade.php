{{-- REMARK
 Tujuan       : Layar satu Stok Opname: kepala (nomor, status, ringkasan, aksi) lalu isinya menurut status.
 Sumber hulu  : StockCountController@show.
 Input        : count, sup, summary.
 Output       : stock-counts.header.
 Dipakai oleh : route stock-counts.show.
 Status       : Aktif.
--}}
@extends('layout', ['title' => 'Stok Opname'])
@section('body')
    @include('partials.flash')
    @include('stock-counts.header')
@endsection
