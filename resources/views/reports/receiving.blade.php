{{-- REMARK
 Tujuan       : Laporan Penerimaan Barang Masuk per periode (tanggal, jam, supplier, no. referensi, item, qty, harga beli, user).
 Sumber hulu  : ReportController@receiving (stock_document_lines tipe IN). Harga & jumlah hanya untuk Supervisor.
 Input        : sup, from, to, rows, suppliers.
 Output       : filter + x-dl::report.
 Dipakai oleh : route reports.receiving (menu Reports > Receiving Report).
 Status       : Aktif.
--}}
@extends('layout', ['title' => 'Receiving Report'])
@section('body')
    <x-report-filter :from="$from" :to="$to">
        <x-dl::field.select name="supplier_id" label="Supplier" :options="['' => 'All suppliers'] + $suppliers" :value="request('supplier_id')" />
    </x-report-filter>
    <x-dl::report title="Receiving Report" subtitle="Goods received from suppliers" :company="config('devlib.company')" :printed-by="raw(auth()->user()->name)" paper="a4l"
        :criteria="['Period' => raw(Devlib\Locale::d($from).' — '.Devlib\Locale::d($to)), 'Supplier' => raw($suppliers[request('supplier_id')] ?? 'All suppliers')]"
        group-by="party" :groups="['party' => 'Per Supplier', 'month' => 'Per Month']"
        :summary="array_values(array_filter([['label' => 'Lines received', 'value' => count($rows)], $sup ? ['label' => 'Total Purchase Value', 'sum' => 'amount'] : null]))"
        :columns="array_values(array_filter([
            ['key' => 'date', 'label' => 'Date & Time'],
            ['key' => 'doc', 'label' => 'Document No.', 'sub' => 'ref'],
            ['key' => 'party', 'label' => 'Supplier'],
            ['key' => 'item', 'label' => 'Item', 'sub' => 'code'],
            ['key' => 'qty', 'label' => 'Qty', 'type' => 'number'],
            ['key' => 'unit', 'label' => 'Unit'],
            $sup ? ['key' => 'cost', 'label' => 'Purchase Price', 'type' => 'money'] : null,
            $sup ? ['key' => 'amount', 'label' => 'Amount', 'type' => 'money', 'sum' => true, 'bold' => true] : null,
            ['key' => 'user', 'label' => 'Entered by'],
        ]))"
        :rows="$rows" :signatures="[['Prepared by', 'Warehouse'], ['Checked by', 'Supervisor']]" source="stock_document_lines (IN)" />
@endsection
