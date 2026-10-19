{{-- REMARK
 Tujuan       : Daftar grup pengguna (Grid): kode + nama, jumlah anggota, jumlah modul. Hanya Administrator.
 Sumber hulu  : GroupController@index.
 Input        : rows.
 Output       : x-dl::grid; klik 2x / Buka membuka form grup (tempat mencentang modul).
 Dipakai oleh : route groups.index (menu Keamanan > Grup).
 Status       : Aktif.
--}}
@extends('layout', ['title' => 'Groups'])
@section('body')
    @include('partials.flash')
    <x-dl::grid id="groups" :rows="$rows" actions="worklist" :new-url="route('groups.create')" :open-url="url('groups/{id}')"
        search="Search code, name..."
        :columns="[
            ['key' => 'name', 'label' => 'Group', 'type' => 'identity', 'code' => 'code', 'avatar' => false],
            ['key' => 'users', 'label' => 'Members', 'type' => 'number'],
            ['key' => 'modules', 'label' => 'Modules', 'type' => 'number'],
        ]"
        :stats="[['label' => 'All groups']]" />
@endsection
