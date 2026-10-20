{{-- REMARK
 Tujuan       : Layar utama SStock: Layer Shell + dashboard (KPI, peringatan Reorder Point).
 Sumber hulu  : HomeController (modules menurut peran, KPI, daftar item di/bawah ROP dan yang hampir kedaluwarsa), partial partials.reorder-alert dan partials.expiry-alert.
 Input        : modules, user, open, below, itemCount, stockValue, receivingToday, issuanceToday, valuation, pending.
 Output       : x-dl::layer-shell; tiap menu terbuka sebagai tab (iframe) layar masing-masing.
 Dipakai oleh : route home.
 Status       : Aktif.
--}}
@extends('layout', ['title' => 'Home', 'bodyClass' => 'sstock-bare'])
@section('body')
    {{-- Help & support membuka Technical Guide (HTML, English) dan hanya ada untuk Administrator. --}}
    <x-dl::layer-shell :classic="false" :without="$admin ? ['chat', 'ai', 'theme'] : ['chat', 'ai', 'theme', 'help']" :help-url="$admin ? route('help.technical-guide') : null" :modules="$modules" :user="$user" :logout-action="route('logout')" :open="$open" :fresh="$fresh" menu-source="App\Support\Menu">
        <x-slot:dashboard>
            @include('partials.flash')
            <x-dl::quote :quotes="\Devlib\Components\Frame::textLines('motivasi-harian.txt')" order="day" />
            <x-dl::kpi-row>
                <x-dl::kpi label="Active items" :value="$itemCount" icon="fa-boxes-stacked" data-open-tab="ITM" class="sstock-link" role="link" tabindex="0" />
                <x-dl::kpi label="At / below reorder point" :value="$below->count()" icon="fa-triangle-exclamation" :tone="$below->isNotEmpty() ? 'danger' : 'success'" good-when="down" data-open-tab="ITM" class="sstock-link" role="link" tabindex="0" />
                <x-dl::kpi label="Near expiry / expired" :value="$expiring->count()" icon="fa-hourglass-end" :tone="$expiring->isNotEmpty() ? ($expiring->contains(fn ($i) => $i->expiryState() === 'KEDALUWARSA') ? 'danger' : 'warn') : 'success'" good-when="down" data-open-tab="R-EXP" class="sstock-link" role="link" tabindex="0" />
                <x-dl::kpi label="Receiving today" :value="$receivingToday" icon="fa-truck-ramp-box" data-open-tab="RCV" class="sstock-link" role="link" tabindex="0" />
                <x-dl::kpi label="Issuance today" :value="$issuanceToday" icon="fa-dolly" data-open-tab="ISS" class="sstock-link" role="link" tabindex="0" />
                @if ($stockValue !== null)
                    <x-dl::kpi label="Stock value" :value="$stockValue" format="money" icon="fa-coins" data-open-tab="R-SUM" class="sstock-link" role="link" tabindex="0" />
                @endif
                @if ($pending !== null)
                    <x-dl::kpi label="Documents waiting approval" :value="$pending" icon="fa-scale-balanced" :tone="$pending ? 'warn' : 'success'" good-when="down" />
                @endif
            </x-dl::kpi-row>
            <div class="sstock-split">
                @if ($below->isNotEmpty())
                    <div>@include('partials.reorder-alert')</div>
                @endif
                @if ($expiring->isNotEmpty())
                    <div>@include('partials.expiry-alert')</div>
                @endif
                @if ($valuation)
                    <div class="sstock-box">
                        <div class="sstock-box-nav">
                            @if ($valuation['prev'])
                                <x-dl::button variant="secondary" size="sm" icon="fa-chevron-left" label="Bulan lalu" :href="route('home', ['month' => $valuation['prev']])" />
                            @else
                                <span></span>
                            @endif
                            <strong>{{ $valuation['title'] }}</strong>
                            @if ($valuation['next'])
                                <x-dl::button variant="secondary" size="sm" icon="fa-chevron-right" label="Bulan depan" :href="route('home', ['month' => $valuation['next']])" />
                            @else
                                <span></span>
                            @endif
                        </div>
                        <x-dl::chart type="hbar" format="money" height="320px" :legend="false" title="Nilai Stok" subtitle="Per minggu"
                            :labels="$valuation['labels']" :series="[['name' => 'Nilai stok', 'data' => $valuation['values'], 'tone' => 'violet']]" source="stock_movements" />
                    </div>
                @endif
            </div>
        </x-slot:dashboard>
    </x-dl::layer-shell>
    {{-- Kotak ringkasan dan tombol ber-data-open-tab membuka menu yang sama sebagai tab kerja Layer Shell (pesan open-tab ke Shell). --}}
    <script>
        const openTab = el => window.postMessage({dl: 'open-tab', code: el.dataset.openTab}, location.origin);
        document.addEventListener('click', e => { const el = e.target.closest('[data-open-tab]'); if (el) { e.preventDefault(); openTab(el); } });
        document.addEventListener('keydown', e => { const el = e.target.closest?.('[data-open-tab]'); if (el && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); openTab(el); } });
    </script>
@endsection
