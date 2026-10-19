{{-- REMARK
 Tujuan       : Daftar master Item (Grid Master): cari, saring kategori / status stok, baris merah bila stok di/bawah Reorder Point.
 Sumber hulu  : ItemController@index (items + categories). Kolom harga & nilai hanya untuk Supervisor.
 Input        : rows, sup, categories.
 Output       : x-dl::grid; klik 2x / Edit membuka form item; + New Record dan saklar aktif hanya Supervisor.
 Dipakai oleh : route items.index (menu Master Data > Items).
 Status       : Aktif.
--}}
@extends('layout', ['title' => 'Items'])
@section('body')
    @include('partials.flash')
    <x-dl::grid id="items" :rows="$rows" :actions="$sup ? 'master' : 'worklist'" :new-record="$sup ? '+ New Record' : null" :new-url="route('items.create')"
        :open-url="url('items/{id}')" :toggle-url="$sup ? url('items/{id}/toggle') : null" search="Search code, name, location..." row-tone="tone"
        :columns="array_values(array_filter([
            ['key' => 'name', 'label' => 'Item', 'type' => 'identity', 'code' => 'code', 'hue' => 'hue', 'box' => true],
            ['key' => 'category', 'label' => 'Category'],
            ['key' => 'unit', 'label' => 'Unit'],
            ['key' => 'location', 'label' => 'Location'],
            ['key' => 'stock', 'label' => 'Stock', 'type' => 'number', 'decimals' => 2],
            ['key' => 'rop', 'label' => 'Reorder Point', 'type' => 'number', 'decimals' => 2],
            ['key' => 'roq', 'label' => 'Reorder Qty', 'type' => 'number', 'decimals' => 2, 'visible' => false],
            ['key' => 'lead', 'label' => 'Lead Time (days)', 'type' => 'integer'],
            ['key' => 'level', 'label' => 'Level', 'type' => 'badge', 'tones' => ['OK' => 'success', 'REORDER' => 'danger']],
            $sup ? ['key' => 'price', 'label' => 'Unit Price', 'type' => 'money', 'decimals' => 2, 'visible' => false] : null,
            $sup ? ['key' => 'value', 'label' => 'Stock Value', 'type' => 'money', 'decimals' => 2, 'sum' => true] : null,
        ]))"
        :stats="[
            ['label' => 'All items'],
            ['label' => 'Reorder', 'where' => ['level' => 'REORDER'], 'tone' => 'danger'],
            ['label' => 'Inactive', 'where' => ['active' => false], 'tone' => 'warn'],
        ]"
        :filters="['category' => 'Category', 'unit' => 'Unit']" />
@endsection
