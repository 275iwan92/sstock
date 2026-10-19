{{-- REMARK
 Tujuan       : Layar utama SStock: Layer Shell + dashboard (KPI, peringatan Reorder Point).
 Sumber hulu  : HomeController (modules menurut peran, KPI, daftar item di/bawah ROP), partial partials.reorder-alert.
 Input        : modules, user, open, below, popup, itemCount, stockValue, todayCount, pending.
 Output       : x-dl::layer-shell; tiap menu terbuka sebagai tab (iframe) layar masing-masing.
 Dipakai oleh : route home.
 Status       : Aktif.
--}}
@extends('layout', ['title' => 'Home'])
@section('body')
    <x-dl::layer-shell :classic="false" :modules="$modules" :user="$user" :logout-action="route('logout')" :open="$open" :fresh="$fresh" menu-source="App\Support\Menu">
        <x-slot:dashboard>
            <x-dl::kpi-row>
                <x-dl::kpi label="Active items" :value="$itemCount" icon="fa-boxes-stacked" />
                <x-dl::kpi label="At / below reorder point" :value="$below->count()" icon="fa-triangle-exclamation" :tone="$below->isNotEmpty() ? 'danger' : 'success'" good-when="down" />
                <x-dl::kpi label="Receiving + issuance today" :value="$todayCount" icon="fa-right-left" />
                @if ($stockValue !== null)
                    <x-dl::kpi label="Stock value" :value="$stockValue" format="money" icon="fa-coins" />
                @endif
                @if ($pending !== null)
                    <x-dl::kpi label="Adjustments waiting approval" :value="$pending" icon="fa-scale-balanced" :tone="$pending ? 'warn' : 'success'" good-when="down" />
                @endif
            </x-dl::kpi-row>
            @include('partials.reorder-alert')
        </x-slot:dashboard>
    </x-dl::layer-shell>
@endsection
