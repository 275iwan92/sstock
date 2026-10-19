{{-- REMARK
 Tujuan       : Peringatan item yang stoknya menyentuh / di bawah Reorder Point: kartu alert + tabel tetap di dashboard (tanpa popup).
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
            'code' => $i->code, 'name' => $i->name, 'category' => $i->category->name, 'location' => $i->location ?? '—',
            'stock' => \Devlib\Locale::number($i->stock_qty, 2).' '.$i->unit, 'rop' => \Devlib\Locale::number($i->reorder_point, 2),
            'roq' => \Devlib\Locale::number($i->reorder_qty, 2), 'lead' => $i->lead_time_days.' hari',
        ])->all();
        $cols = ['code' => 'Code', 'name' => 'Item', 'category' => 'Category', 'location' => 'Location', 'stock' => 'Stock', 'rop' => 'Reorder Point', 'roq' => 'Reorder Qty', 'lead' => 'Lead Time'];
    @endphp
    <x-dl::alert tone="danger" :message="$text">
        <x-dl::button variant="secondary" size="sm" icon="fa-truck-ramp-box" label="Open Receiving" :href="route('docs.index', 'receiving')" />
    </x-dl::alert>
    <x-dl::table :columns="$cols" :rows="$rows" />
@endif
