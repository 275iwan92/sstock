{{-- REMARK
 Tujuan       : Peringatan item yang stoknya menyentuh / di bawah Reorder Point: kartu alert + tabel ringkas (nama, jumlah pesan ulang, stok saat ini) di dashboard, tanpa popup.
 Sumber hulu  : HomeController ($below = Item::belowReorderPoint).
 Input        : below.
 Output       : x-dl::alert, x-dl::table, x-dl::button menuju Receiving.
 Dipakai oleh : home.blade.php (slot dashboard).
 Status       : Aktif.
--}}
@if ($below->isNotEmpty())
    @php
        $text = $below->count().' barang berada di atau di bawah titik pesan ulang. Segera pesan ke supplier.';
        $rows = $below->map(fn ($i) => [
            'name' => $i->name, 'stock' => \Devlib\Locale::number($i->stock_qty, 2).' '.$i->unit, 'roq' => \Devlib\Locale::number($i->reorder_qty, 2).' '.$i->unit,
        ])->all();
        $cols = ['name' => 'Item', 'roq' => 'Reorder Qty', 'stock' => 'Current Stock'];
    @endphp
    <x-dl::alert tone="danger" :message="$text">
        <x-dl::button variant="secondary" size="sm" icon="fa-truck-ramp-box" label="Open Receiving" href="#" data-open-tab="RCV" />
    </x-dl::alert>
    <x-dl::table class="sstock-reorder" :columns="$cols" :rows="$rows" />
@endif
