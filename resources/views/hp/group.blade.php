{{-- REMARK
 Tujuan       : Daftar modul satu kelompok (mis. Transaksi) sebagai kartu besar yang mudah disentuh; tiap kartu membuka modulnya.
 Sumber hulu  : HpController@group (title, items hasil HpShell::modules).
 Input        : title, items (code, label, url, icon, badge), active.
 Output       : kartu tautan, satu per modul yang boleh diakses.
 Dipakai oleh : route hp.group (tab Transaksi).
 Status       : Aktif.
--}}
@extends('hp.layout', ['back' => route('home')])
@section('content')
    @forelse ($items as $it)
        <a class="sstock-hp-card sstock-hp-link" href="{{ $it['url'] }}">
            <i class="{{ $it['icon'] }}" aria-hidden="true"></i>
            <span>{{ $it['label'] }}</span>
            @if (! empty($it['badge']))<em>{{ $it['badge'] }}</em>@endif
        </a>
    @empty
        <x-dl::alert tone="info" message="Tidak ada modul yang bisa Anda buka di kelompok ini." />
    @endforelse
@endsection
