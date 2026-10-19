{{-- REMARK
 Tujuan       : Daftar dokumen stok satu jenis (Receiving / Issuance / Opening Balance / Stock Adjustment) sebagai Grid Transactional POLOS (cepat dipindai untuk data banyak): nomor dokumen tebal di kolom pertama, hanya kolom Status yang berwarna; baris dibuka menampilkan item-itemnya.
 Sumber hulu  : StockDocumentController@index. Kolom nilai hanya untuk Supervisor.
 Input        : t (StockDocumentType), rows, sup.
 Output       : x-dl::grid; klik 2x / Open membuka form dokumen, + New Record membuat dokumen baru.
 Dipakai oleh : route docs.index (menu Transactions).
 Status       : Aktif.
--}}
@extends('layout', ['title' => $t->label()])
@section('body')
    @php
        $refLabel = match ($t) { \App\Enums\StockDocumentType::Receiving => 'Reference No.', \App\Enums\StockDocumentType::Issuance => 'Department', default => 'Reason' };
        $isAdj = $t === \App\Enums\StockDocumentType::Adjustment;
    @endphp
    @include('partials.flash')
    <x-dl::grid :id="'docs-'.$t->slug()" :rows="$rows" actions="worklist" :new-url="route('docs.create', $t->slug())" :open-url="url('docs/'.$t->slug().'/{id}')" search="Search document, party, reference..."
        :columns="array_values(array_filter([
            ['key' => 'doc_no', 'label' => 'Document', 'type' => 'identity', 'code' => 'party', 'avatar' => false],
            ['key' => 'doc_at', 'label' => 'Date & Time', 'type' => 'datetime'],
            ['key' => 'ref', 'label' => $refLabel],
            $sup ? ['key' => 'value', 'label' => 'Value', 'type' => 'money', 'decimals' => 2, 'sum' => true] : null,
            ['key' => 'status', 'label' => 'Status', 'type' => 'badge', 'tones' => ['Confirmed' => 'success', 'Draft' => 'neutral', 'Waiting Approval' => 'warn', 'Approved' => 'success', 'Rejected' => 'danger']],
            ['key' => 'user', 'label' => 'Entered by'],
        ]))"
        :detail="['key' => 'lines', 'title' => 'Items', 'columns' => array_values(array_filter([
            ['key' => 'name', 'label' => 'Item', 'strong' => true],
            ['key' => 'code', 'label' => 'Code'],
            ['key' => 'qty', 'label' => $isAdj ? 'Difference' : 'Qty', 'type' => 'number'],
            ['key' => 'unit', 'label' => 'Unit'],
            $sup ? ['key' => 'cost', 'label' => 'Unit Cost', 'type' => 'money'] : null,
        ]))]"
        :stats="$isAdj ? [['label' => 'All'], ['label' => 'Waiting approval', 'where' => ['status' => 'Waiting Approval'], 'tone' => 'warn'], ['label' => 'Draft', 'where' => ['status' => 'Draft']]] : [['label' => 'All']]" />
@endsection
