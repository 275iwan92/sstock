{{-- REMARK
 Tujuan       : Form master Item (Single Form kind=master) satu layar: kotak stok baca-saja (stok, rata-rata biaya, nilai), nama item besar, data umum, parameter reorder + harga.
 Sumber hulu  : ItemController (item, categories, units). Rata-rata biaya, nilai stok, dan harga hanya untuk Supervisor; Data Entry melihat saja (form terkunci).
 Input        : item, categories, units.
 Output       : POST items.store (baru) / PUT items.update (ubah, Activate, Deactivate).
 Dipakai oleh : routes items.create, items.show.
 Status       : Aktif.
--}}
@extends('layout', ['title' => 'Item'])
@section('body')
    @php
        use Devlib\Locale;
        $sup = auth()->user()->isSupervisor();
        $isNew = ! $item->exists;
        $below = ! $isNew && $item->isBelowReorderPoint();
        $v = fn (string $k) => old($k, $item->{$k});
        $e = fn (string $k) => $errors->first($k) ?: null;
    @endphp
    @include('partials.flash')
    <x-dl::form :title="$isNew ? 'Item · New' : 'Item · '.raw($item->code)" :subtitle="$isNew ? null : raw($item->name)" panel="top" kind="master"
        :status="$isNew ? 'new' : ($item->is_active ? 'active' : 'inactive')" :can-input="$sup" :hide="$sup ? [] : ['activate', 'deactivate']" :back="route('items.index')" :new-url="route('items.create')"
        :action="$isNew ? route('items.store') : route('items.update', $item)" :hidden="$isNew ? [] : ['_method' => 'PUT']"
        :created-at="$item->created_at?->format('Y-m-d H:i:s')" :updated-at="$item->updated_at?->format('Y-m-d H:i:s')">
        @unless ($isNew)
            <x-dl::kpi-row>
                <x-dl::stat-box label="Stock on hand" :value="Locale::number($item->stock_qty, 2).' '.$item->unit" :tone="$below ? 'danger' : 'accent'" :sub="'Reorder point '.Locale::number($item->reorder_point, 2).' · '.($below ? 'REORDER' : 'OK')" />
                @if ($sup)
                    <x-dl::stat-box label="Average cost" :value="Locale::money($item->avg_cost, 'IDR', 0)" tone="neutral" sub="Moving average of purchases" />
                    <x-dl::stat-box label="Stock value" :value="Locale::money($item->stockValue(), 'IDR', 0)" tone="success" sub="Stock x average cost" />
                @endif
            </x-dl::kpi-row>
        @endunless
        <x-dl::divider label="Item" />
        <x-dl::form.row :cols="1">
            <x-dl::field.text class="sstock-name" name="name" label="Item Name" :value="$v('name')" required maxlength="150" :error="$e('name')" />
        </x-dl::form.row>
        <x-dl::form.row :cols="4">
            <x-dl::field.text name="code" label="Item Code" :value="$v('code')" code :case-toggle="false" required :readonly="! $isNew" maxlength="40" :error="$e('code')" tooltip="Unique. Cannot be changed after saving." />
            <x-dl::field.select name="category_id" label="Category" :options="$categories" :value="$v('category_id')" required :error="$e('category_id')" />
            <x-dl::field.select name="unit" label="Unit" :options="$units" :value="$v('unit')" required :error="$e('unit')" />
            <x-dl::field.text name="location" label="Rack / Storage Location" :value="$v('location')" maxlength="50" :error="$e('location')" />
        </x-dl::form.row>
        <x-dl::divider :label="$sup ? 'Reorder and Price' : 'Reorder'" />
        <x-dl::form.row :cols="4">
            <x-dl::field.decimal name="reorder_point" label="Reorder Point" :decimals="2" :value="$v('reorder_point') ?? 0" required :error="$e('reorder_point')" tooltip="Alert shows when stock is at or below this quantity. 0 = no alert." />
            <x-dl::field.decimal name="reorder_qty" label="Reorder Qty" :decimals="2" :value="$v('reorder_qty') ?? 0" required :error="$e('reorder_qty')" tooltip="Suggested quantity to order." />
            <x-dl::field.number name="lead_time_days" label="Lead Time (days)" :min="0" :max="365" :value="$v('lead_time_days') ?? 0" required :error="$e('lead_time_days')" tooltip="Days from placing the order until the supplier delivers." />
            @if ($sup)
                <x-dl::field.decimal name="unit_price" label="Unit Price (IDR)" :decimals="2" :value="$v('unit_price') ?? 0" required :error="$e('unit_price')" tooltip="Reference price. Stock valuation uses the moving average of purchase prices." />
            @endif
        </x-dl::form.row>
        <p class="dl-note">Alert shows when stock is at or below the reorder point (0 = no alert). The unit price is a reference only; valuation uses the moving average of purchase prices.</p>
    </x-dl::form>
@endsection
