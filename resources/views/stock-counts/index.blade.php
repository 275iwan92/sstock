{{-- REMARK
 Tujuan       : Daftar Stok Opname (penghitungan fisik rutin) sebagai Grid Transactional POLOS: nomor SO tebal di kolom pertama, hanya kolom Status yang berwarna. Mulai opname hanya untuk Supervisor.
 Sumber hulu  : StockCountController@index.
 Input        : rows, sup.
 Output       : x-dl::grid; klik 2x / Open membuka layar opname, Start Count (Supervisor) membuka form mulai.
 Dipakai oleh : route stock-counts.index (menu Transaksi > Stok Opname).
 Status       : Aktif.
--}}
@extends('layout', ['title' => 'Stok Opname'])
@section('body')
    @include('partials.flash')
    <x-dl::grid id="stock-counts" actions="worklist" :rows="$rows" :new-record="$sup ? 'Start Count' : null" :new-url="$sup ? route('stock-counts.create') : null" :open-url="url('stock-counts/{id}')" search="Search count, scope..."
        :columns="[
            ['key' => 'doc_no', 'label' => 'Document', 'type' => 'identity', 'code' => 'scope', 'avatar' => false],
            ['key' => 'count_date', 'label' => 'Count Date', 'type' => 'date'],
            ['key' => 'progress', 'label' => 'Filled'],
            ['key' => 'adjustment', 'label' => 'Adjustment'],
            ['key' => 'status', 'label' => 'Status', 'type' => 'badge', 'tones' => ['Sedang dihitung' => 'warn', 'Tinjau' => 'info', 'Selesai' => 'success', 'Dibatalkan' => 'danger']],
            ['key' => 'user', 'label' => 'Started by'],
        ]"
        :stats="[['label' => 'All'], ['label' => 'Counting', 'where' => ['status' => 'Sedang dihitung'], 'tone' => 'warn'], ['label' => 'Review', 'where' => ['status' => 'Tinjau']], ['label' => 'Done', 'where' => ['status' => 'Selesai'], 'tone' => 'success']]" />
@endsection
