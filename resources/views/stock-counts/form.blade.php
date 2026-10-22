{{-- REMARK
 Tujuan       : Form MULAI Stok Opname (Supervisor): tanggal, cakupan (semua / kategori / lokasi), hitung buta, kunci transaksi, ambang selisih. Simpan = mulai: sistem mengambil snapshot stok per batch (cut-off).
 Sumber hulu  : StockCountController@create (categories, locations).
 Input        : categories (kode => label), locations (kode => label).
 Output       : POST stock-counts.store, lalu layar opname.
 Dipakai oleh : route stock-counts.create (tombol Start Count di daftar).
 Status       : Aktif.
--}}
@extends('layout', ['title' => 'Stok Opname'])
@section('body')
    @php
        $old = session()->hasOldInput();
        $v = fn (string $k, $default = null) => old($k, $default);
        $on = fn (string $k) => $old ? (bool) old($k) : true;   // saklar bawaan menyala; sesudah galat validasi mengikuti isian
        $e = fn (string $k) => $errors->first($k) ?: null;
    @endphp
    @include('partials.flash')
    <x-dl::form title="Stok Opname · Baru" subtitle="Simpan = mulai opname dan ambil snapshot stok sistem (cut-off) per batch" panel="top" kind="master" status="new" :back="route('stock-counts.index')" :action="route('stock-counts.store')">
        <x-dl::form.row :cols="3">
            <x-dl::field.date name="count_date" label="Count Date" :value="$v('count_date', today()->format('Y-m-d'))" required :error="$e('count_date')" />
            <x-dl::field.select name="scope_type" label="Scope" :options="['all' => 'Semua barang', 'category' => 'Per Kategori', 'location' => 'Per Lokasi']" :value="$v('scope_type', 'all')" required :error="$e('scope_type')" />
            <x-dl::field.decimal name="threshold_pct" label="Difference Threshold (%)" :decimals="2" :value="$v('threshold_pct', 5)" required :error="$e('threshold_pct')" hint="Selisih di atas persen ini wajib diberi alasan saat ditinjau." />
        </x-dl::form.row>
        <x-dl::form.row :cols="3">
            <x-dl::field.select name="category_code" label="Category (for scope per Category)" :options="$categories" :value="$v('category_code')" :error="$e('category_code')" />
            <x-dl::field.select name="location_code" label="Location (for scope per Location)" :options="$locations" :value="$v('location_code')" :error="$e('location_code')" />
        </x-dl::form.row>
        <x-dl::form.row :cols="3">
            <x-dl::field.toggle name="blind" label="Blind count" on-label="Yes" off-label="No" :value="$on('blind')" hint="Ya: stok sistem disembunyikan di lembar hitung dan layar input." />
            <x-dl::field.toggle name="lock_transactions" label="Lock transactions during count" on-label="Yes" off-label="No" :value="$on('lock_transactions')" hint="Ya: Confirm / Approve Penerimaan, Pengeluaran, dan Penyesuaian ditolak sampai opname selesai atau dibatalkan." />
        </x-dl::form.row>
        <x-dl::form.row>
            <x-dl::field.text name="note" label="Notes" :value="$v('note')" multiline maxlength="1000" :error="$e('note')" />
        </x-dl::form.row>
    </x-dl::form>
@endsection
