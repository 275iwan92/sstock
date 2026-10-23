{{-- REMARK
 Tujuan       : Beranda tampilan HP: ringkasan stok sebagai kartu besar (angka + nama) yang membuka modulnya, dan pengingat Stok Opname.
 Sumber hulu  : HpController@home (cards, countReminder).
 Input        : cards (label, value, icon, code, tone), countReminder (CountSchedule::state atau null).
 Output       : kartu tautan ke hp.open / hp.counts, pengingat opname.
 Dipakai oleh : HomeController bila perangkat = hp.
 Status       : Aktif.
--}}
@extends('hp.layout')
@section('content')
    @include('partials.flash')
    @if ($countReminder)
        @php $r = $countReminder; @endphp
        <a class="sstock-hp-card sstock-hp-link sstock-hp-tone-{{ $r['running'] ? 'info' : ($r['tone'] === 'neutral' ? 'info' : $r['tone']) }}" href="{{ route('hp.counts') }}">
            <i class="fa-solid fa-clipboard-check" aria-hidden="true"></i>
            <span>{{ $r['running'] ? 'Stok Opname '.$r['running']->doc_no.' sedang berjalan ('.$r['running']->statusLabel().', terisi '.$r['running_progress'].')' : $r['headline'].' '.$r['detail'] }}</span>
        </a>
    @endif
    <div class="sstock-hp-cards">
        @foreach ($cards as $c)
            <a class="sstock-hp-card sstock-hp-kpi sstock-hp-tone-{{ $c['tone'] ?? '' }}" href="{{ \App\Support\HpShell::url($c['code']) }}">
                <i class="{{ $c['icon'] }}" aria-hidden="true"></i>
                <strong>{{ \Devlib\Locale::number($c['value'], 0) }}</strong>
                <span>{{ $c['label'] }}</span>
            </a>
        @endforeach
    </div>
@endsection
