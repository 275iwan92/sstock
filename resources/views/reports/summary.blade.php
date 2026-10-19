{{-- REMARK
 Tujuan       : Summary Stock: posisi stok terkini semua item (qty, Reorder Point, status) dan, untuk Supervisor, nilai valuasi + total aset persediaan.
 Sumber hulu  : ReportController@summary (items). Data Entry tidak melihat biaya / nilai.
 Input        : sup, categories, items, rows.
 Output       : filter (kategori, hanya reorder, termasuk nonaktif) + x-dl::report.
 Dipakai oleh : route reports.summary (menu Reports > Summary Stock).
 Status       : Aktif.
--}}
@extends('layout', ['title' => 'Summary Stock'])
@section('body')
    <x-dl::action-form method="get" :action="url()->current()">
        <x-dl::form.row :cols="4">
            <x-dl::field.select name="category_id" label="Category" :options="['' => 'All categories'] + $categories" :value="request('category_id')" />
            <x-dl::field.checkbox name="only_reorder" label="Filter" text="Only items at / below reorder point" :value="request()->boolean('only_reorder')" />
            <x-dl::field.checkbox name="inactive" label="Inactive" text="Include inactive items" :value="request()->boolean('inactive')" />
        </x-dl::form.row>
        <x-dl::action-bar><x-dl::button type="submit" icon="fa-filter" label="Apply" size="sm" /></x-dl::action-bar>
    </x-dl::action-form>
    <x-dl::report title="Summary Stock" subtitle="Current stock position" :company="config('devlib.company')" :printed-by="raw(auth()->user()->name)" paper="a4l"
        :criteria="array_filter([
            'As of' => raw(Devlib\Locale::dateTime(now())),
            'Category' => raw($categories[request('category_id')] ?? 'All categories'),
            'Scope' => raw(request()->boolean('only_reorder') ? 'Reorder items only' : 'All items'),
            'Valuation' => $sup ? 'Moving average' : null,
        ])"
        group-by="cat" :groups="['cat' => 'Per Category', 'status' => 'Per Status']"
        :summary="array_values(array_filter([
            ['label' => 'Items', 'value' => count($rows)],
            ['label' => 'At / below reorder point', 'value' => collect($rows)->where('status', 'REORDER')->count()],
            $sup ? ['label' => 'Total Stock Value', 'sum' => 'value'] : null,
        ]))"
        :columns="array_values(array_filter([
            ['key' => 'code', 'label' => 'Item Code'],
            ['key' => 'name', 'label' => 'Item Name', 'sub' => 'loc'],
            ['key' => 'unit', 'label' => 'Unit'],
            ['key' => 'stock', 'label' => 'Stock', 'type' => 'number', 'bold' => true],
            ['key' => 'rop', 'label' => 'Reorder Point', 'type' => 'number'],
            ['key' => 'roq', 'label' => 'Reorder Qty', 'type' => 'number'],
            ['key' => 'lead', 'label' => 'Lead Time (d)', 'type' => 'number'],
            $sup ? ['key' => 'cost', 'label' => 'Avg Cost', 'type' => 'money'] : null,
            $sup ? ['key' => 'value', 'label' => 'Stock Value', 'type' => 'money', 'sum' => true, 'bold' => true] : null,
            ['key' => 'status', 'label' => 'Status', 'type' => 'badge', 'tones' => ['OK' => 'success', 'REORDER' => 'danger'], 'rowTones' => ['REORDER' => 'danger']],
        ]))"
        :rows="$rows" :signatures="[['Prepared by', 'Warehouse'], ['Checked by', 'Supervisor']]" source="items" />
@endsection
