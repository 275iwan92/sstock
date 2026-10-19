{{-- REMARK
 Tujuan       : Form master Supplier (Single Form kind=master).
 Sumber hulu  : SupplierController (supplier).
 Input        : supplier.
 Output       : POST suppliers.store (baru) / PUT suppliers.update (ubah, Activate, Deactivate).
 Dipakai oleh : routes suppliers.create, suppliers.show.
 Status       : Aktif.
--}}
@extends('layout', ['title' => 'Supplier'])
@section('body')
    @php
        $isNew = ! $supplier->exists;
        $v = fn (string $k) => old($k, $supplier->{$k});
        $e = fn (string $k) => $errors->first($k) ?: null;
    @endphp
    @include('partials.flash')
    <x-dl::form :title="$isNew ? 'Supplier · New' : 'Supplier · '.raw($supplier->code)" :subtitle="$isNew ? null : raw($supplier->name)" panel="top" kind="master"
        :status="$isNew ? 'new' : ($supplier->is_active ? 'active' : 'inactive')" :back="route('suppliers.index')" :new-url="route('suppliers.create')"
        :action="$isNew ? route('suppliers.store') : route('suppliers.update', $supplier)" :hidden="$isNew ? [] : ['_method' => 'PUT']"
        :created-at="$supplier->created_at?->format('Y-m-d H:i:s')" :updated-at="$supplier->updated_at?->format('Y-m-d H:i:s')">
        <x-dl::form.row :cols="3">
            <x-dl::field.text name="code" label="Supplier Code" :value="$v('code')" code :case-toggle="false" required :readonly="! $isNew" maxlength="20" :error="$e('code')" hint="Unique. Cannot be changed after saving." />
            <x-dl::field.text name="name" label="Supplier Name" :value="$v('name')" required maxlength="150" :error="$e('name')" />
            <x-dl::field.phone name="phone" label="Phone" :value="$v('phone')" :error="$e('phone')" />
        </x-dl::form.row>
        <x-dl::form.row>
            <x-dl::field.text name="address" label="Address" :value="$v('address')" maxlength="255" :error="$e('address')" />
        </x-dl::form.row>
    </x-dl::form>
@endsection
