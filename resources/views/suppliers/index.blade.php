{{-- REMARK
 Tujuan       : Daftar master Supplier (Grid Master).
 Sumber hulu  : SupplierController@index (suppliers).
 Input        : rows.
 Output       : x-dl::grid; klik 2x / Edit membuka form supplier.
 Dipakai oleh : route suppliers.index (menu Master Data > Suppliers).
 Status       : Aktif.
--}}
@extends('layout', ['title' => 'Suppliers'])
@section('body')
    @include('partials.flash')
    <x-dl::grid id="suppliers" :rows="$rows" actions="master" :new-url="route('suppliers.create')" :open-url="url('suppliers/{id}')" :toggle-url="url('suppliers/{id}/toggle')"
        search="Search code, name, phone..."
        :columns="[
            ['key' => 'name', 'label' => 'Supplier', 'type' => 'identity', 'code' => 'code', 'avatar' => false],
            ['key' => 'phone', 'label' => 'Phone'],
            ['key' => 'address', 'label' => 'Address'],
        ]"
        :stats="[['label' => 'All suppliers'], ['label' => 'Inactive', 'where' => ['active' => false], 'tone' => 'warn']]" />
@endsection
