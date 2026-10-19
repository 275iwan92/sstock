{{-- REMARK
 Tujuan       : Halaman awal SStock; bukti DevLib terpasang (objek x-dl::*).
 Dipakai oleh : route '/'.
 Status       : Placeholder, ganti dengan dashboard stok.
--}}
@extends('layout')
@section('body')
    <x-dl::kpi label="Items" :value="0" icon="fa-boxes-stacked" />
@endsection
