{{-- REMARK
 Tujuan       : Peringatan item yang stoknya menyentuh / di bawah Reorder Point: kartu alert + tabel ringkas (nama, jumlah pesan ulang, stok saat ini) di dashboard, tanpa popup.
 Sumber hulu  : HomeController ($below = Item::belowReorderPoint).
 Input        : below.
 Output       : x-dl::alert, x-dl::table (termasuk status pesanan), x-dl::button menuju Receiving, modal catat pemesanan.
 Dipakai oleh : home.blade.php (slot dashboard).
 Status       : Aktif.
--}}
@if ($below->isNotEmpty())
    @php
        $text = $below->count().' barang berada di atau di bawah titik pesan ulang. Segera pesan ke supplier.';
        $cols = ['Item', 'Reorder Qty', 'Current Stock', 'Order Status'];
        $orderable = $below->filter(fn ($i) => ! $i->openOrder)->mapWithKeys(fn ($i) => [$i->id => $i->name])->all();
    @endphp
    <x-dl::alert tone="danger" :message="$text">
        <x-dl::button variant="secondary" size="sm" icon="fa-truck-ramp-box" label="Open Receiving" href="#" data-open-tab="RCV" />
        @if ($orderable)
            <x-dl::button size="sm" icon="fa-cart-shopping" label="Record Order" data-dl-open="reorder-modal" />
        @endif
    </x-dl::alert>
    @if ($orderable)
        <x-dl::modal id="reorder-modal" title="Record Order" icon="fa-cart-shopping" size="sm">
            <form id="reorder-form" method="POST" action="{{ route('reorder-orders.store') }}">
                @csrf
                <x-dl::field.select name="item_id" label="Item" :options="$orderable" required />
                <x-dl::field.date name="ordered_at" label="Order Date" :value="today()->format('Y-m-d')" required />
                <x-dl::field.text name="ref_no" label="Order Reference No. (PO)" maxlength="60" required />
            </form>
            <x-slot:footer><x-dl::button type="submit" form="reorder-form" icon="save" label="Save" /></x-slot:footer>
        </x-dl::modal>
    @endif
    {{-- Tabel ditulis langsung (bukan x-dl::table) karena kolom status memakai x-dl::badge berwarna. --}}
    <div class="dl-table-wrap sstock-reorder">
        <table class="dl-table is-striped">
            <thead><tr>@foreach ($cols as $c)<th>{{ \Devlib\Lang::text($c) }}</th>@endforeach</tr></thead>
            <tbody>
                @foreach ($below as $i)
                    <tr>
                        <td>{{ $i->name }}</td>
                        <td>{{ \Devlib\Locale::number($i->reorder_qty, 2) }} {{ $i->unit }}</td>
                        <td>{{ \Devlib\Locale::number($i->stock_qty, 2) }} {{ $i->unit }}</td>
                        <td>
                            @if ($i->openOrder)
                                <x-dl::badge tone="success" icon="fa-circle-check" :label="'Dipesan '.$i->openOrder->ordered_at->format('d/m/Y').' · '.$i->openOrder->ref_no" />
                            @else
                                <x-dl::badge tone="danger" icon="fa-triangle-exclamation" label="Belum dipesan" />
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
