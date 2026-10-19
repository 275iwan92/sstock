{{-- REMARK
 Tujuan       : Form master Supplier (Single Form kind=master): kode, nama, telepon kantor (PSTN), nama kontak + HP kontak, wilayah (kelurahan s.d. negara lewat lookup geo_area, wajib minimal sampai kota), kode pos, alamat jalan.
 Sumber hulu  : SupplierController (supplier, geo = wilayah terpilih), Lookup geo_area (App\Support\GeoLookup).
 Input        : supplier, geo.
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
        $area = old('area', $supplier->areaKey());
        $geo = old('area') ? \App\Support\GeoArea::find(old('area')) : $geo;
    @endphp
    @include('partials.flash')
    <x-dl::form :title="$isNew ? 'Supplier · New' : 'Supplier · '.raw($supplier->code)" :subtitle="$isNew ? null : raw($supplier->name)" panel="top" kind="master"
        :status="$isNew ? 'new' : ($supplier->is_active ? 'active' : 'inactive')" :back="route('suppliers.index')" :new-url="route('suppliers.create')"
        :action="$isNew ? route('suppliers.store') : route('suppliers.update', $supplier)" :hidden="$isNew ? [] : ['_method' => 'PUT']"
        :created-at="$supplier->created_at?->format('Y-m-d H:i:s')" :updated-at="$supplier->updated_at?->format('Y-m-d H:i:s')">
        <x-dl::form.row :cols="3">
            <x-dl::field.text name="code" label="Supplier Code" :value="$v('code')" code :case-toggle="false" required :readonly="! $isNew" maxlength="20" :error="$e('code')" hint="Unique. Cannot be changed after saving." />
            <x-dl::field.text name="name" label="Supplier Name" :value="$v('name')" required maxlength="150" :error="$e('name')" />
        </x-dl::form.row>
        <x-dl::form.row :cols="3">
            <x-dl::field.phone name="phone" label="Company Phone (PSTN)" :value="$v('phone')" :error="$e('phone')" hint="Landline, with area code." />
            <x-dl::field.text name="contact_name" label="Contact Name" :value="$v('contact_name')" maxlength="100" :error="$e('contact_name')" />
            <x-dl::field.phone name="contact_mobile" label="Contact Mobile (smartphone)" :value="$v('contact_mobile')" :error="$e('contact_mobile')" hint="Also used for WhatsApp." />
        </x-dl::form.row>
        <x-dl::form.row :cols="2">
            <x-dl::field.lookup name="area" lookup="geo_area" label="Region" placeholder="Search village / district / city / postcode..." :value="$area" required :error="$e('area')" hint="Choose at least the city." />
            <x-dl::field.text name="postal_code" label="Postal Code" :value="old('postal_code', $supplier->postal_code)" :case-toggle="false" maxlength="20" :error="$e('postal_code')" hint="Filled from the region, can be changed." />
        </x-dl::form.row>
        <x-dl::form.row :cols="4">
            <x-dl::field.text name="district_name" label="District (Kecamatan)" :value="$geo['district'] ?? null" readonly :case-toggle="false" />
            <x-dl::field.text name="city_name" label="City / Regency" :value="$geo['city'] ?? null" readonly :case-toggle="false" />
            <x-dl::field.text name="region_name" label="Province" :value="$geo['region'] ?? null" readonly :case-toggle="false" />
            <x-dl::field.text name="country_name" label="Country" :value="$geo['country'] ?? null" readonly :case-toggle="false" />
        </x-dl::form.row>
        <x-dl::form.row>
            <x-dl::field.text name="address" label="Street Address" :value="$v('address')" maxlength="255" :error="$e('address')" />
        </x-dl::form.row>
    </x-dl::form>
@endsection
