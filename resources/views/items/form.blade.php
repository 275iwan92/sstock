{{-- REMARK
 Tujuan       : Form master Item (Single Form kind=master): data umum, parameter reorder + harga, saldo stok saat ini.
 Sumber hulu  : ItemController (item, categories). Harga & nilai stok hanya tampil untuk Supervisor; Data Entry melihat saja (form terkunci).
 Input        : item, categories.
 Output       : POST items.store (baru) / PUT items.update (ubah, Activate, Deactivate).
 Dipakai oleh : routes items.create, items.show.
 Status       : Aktif.
--}}
@extends('layout', ['title' => 'Item'])
@section('body')
    @php
        $sup = auth()->user()->isSupervisor();
        $isNew = ! $item->exists;
        $v = fn (string $k) => old($k, $item->{$k});
        $e = fn (string $k) => $errors->first($k) ?: null;
        $units = array_combine(\App\Http\Requests\ItemRequest::UNITS, \App\Http\Requests\ItemRequest::UNITS);
    @endphp
    @include('partials.flash')
    <x-dl::form :title="$isNew ? 'Item · New' : 'Item · '.raw($item->code)" :subtitle="$isNew ? null : raw($item->name)" panel="top" kind="master"
        :status="$isNew ? 'new' : ($item->is_active ? 'active' : 'inactive')" :can-input="$sup" :hide="$sup ? [] : ['activate', 'deactivate']" :back="route('items.index')" :new-url="route('items.create')"
        :action="$isNew ? route('items.store') : route('items.update', $item)" :hidden="$isNew ? [] : ['_method' => 'PUT']"
        :tabs="['general' => 'General', 'reorder' => $sup ? 'Reorder & Price' : 'Reorder', 'stock' => 'Current Stock']"
        :created-at="$item->created_at?->format('Y-m-d H:i:s')" :updated-at="$item->updated_at?->format('Y-m-d H:i:s')">
        <x-slot:general>
            <x-dl::form.row :cols="3">
                <x-dl::field.text name="code" label="Item Code" :value="$v('code')" code :case-toggle="false" required :readonly="! $isNew" maxlength="40" :error="$e('code')" hint="Unique. Cannot be changed after saving." />
                <x-dl::field.text name="name" label="Item Name" :value="$v('name')" required maxlength="150" :error="$e('name')" />
                <x-dl::field.select name="category_id" label="Category" :options="$categories" :value="$v('category_id')" required :error="$e('category_id')" />
            </x-dl::form.row>
            <x-dl::form.row :cols="3">
                <x-dl::field.select name="unit" label="Unit" :options="$units" :value="$v('unit')" required :error="$e('unit')" />
                <x-dl::field.text name="location" label="Rack / Storage Location" :value="$v('location')" maxlength="50" :error="$e('location')" />
            </x-dl::form.row>
        </x-slot:general>
        <x-slot:reorder>
            <x-dl::form.row :cols="3">
                <x-dl::field.decimal name="reorder_point" label="Reorder Point" :decimals="2" :value="$v('reorder_point') ?? 0" required :error="$e('reorder_point')" hint="Alert shows when stock is at or below this quantity. 0 = no alert." />
                <x-dl::field.decimal name="reorder_qty" label="Reorder Qty" :decimals="2" :value="$v('reorder_qty') ?? 0" required :error="$e('reorder_qty')" hint="Suggested quantity to order." />
                <x-dl::field.number name="lead_time_days" label="Lead Time (days)" :min="0" :max="365" :value="$v('lead_time_days') ?? 0" required :error="$e('lead_time_days')" hint="Days from placing the order until the supplier delivers." />
            </x-dl::form.row>
            @if ($sup)
                <x-dl::form.row :cols="3">
                    <x-dl::field.decimal name="unit_price" label="Unit Price (IDR)" :decimals="2" :value="$v('unit_price') ?? 0" required :error="$e('unit_price')" hint="Reference price. Stock valuation uses the moving average of purchase prices." />
                </x-dl::form.row>
            @endif
        </x-slot:reorder>
        <x-slot:stock>
            <x-dl::form.row :cols="3">
                <x-dl::field.display label="Stock on hand" format="number" :value="\Devlib\Locale::number($item->stock_qty ?? 0, 2).' '.($item->unit ?? '')" />
                @if ($sup)
                    <x-dl::field.display label="Average cost" format="money" :value="\Devlib\Locale::money($item->avg_cost ?? 0, 'IDR', 2)" />
                    <x-dl::field.display label="Stock value" format="money" :value="\Devlib\Locale::money($item->exists ? $item->stockValue() : 0, 'IDR', 2)" />
                @endif
            </x-dl::form.row>
        </x-slot:stock>
    </x-dl::form>
@endsection
