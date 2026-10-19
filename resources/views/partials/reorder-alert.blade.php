{{-- REMARK
 Tujuan       : Peringatan item yang stoknya menyentuh / di bawah Reorder Point: kartu alert tetap di dashboard + popup modal (sekali per login atau saat daftar berubah).
 Sumber hulu  : HomeController ($below = Item::belowReorderPoint, $popup).
 Input        : below, popup.
 Output       : x-dl::alert, x-dl::modal, x-dl::table, x-dl::button menuju Receiving.
 Dipakai oleh : home.blade.php (slot dashboard).
 Status       : Aktif.
--}}
@if ($below->isNotEmpty())
    @php
        $text = $below->count().' item(s) are at or below their reorder point. Order them from the supplier now.';
        $rows = $below->map(fn ($i) => [
            'code' => $i->code, 'name' => $i->name, 'category' => $i->category->name, 'location' => $i->location ?? '—',
            'stock' => \Devlib\Locale::number($i->stock_qty, 2).' '.$i->unit, 'rop' => \Devlib\Locale::number($i->reorder_point, 2),
            'roq' => \Devlib\Locale::number($i->reorder_qty, 2), 'lead' => $i->lead_time_days.' d',
        ])->all();
        $cols = ['code' => 'Code', 'name' => 'Item', 'category' => 'Category', 'location' => 'Location', 'stock' => 'Stock', 'rop' => 'Reorder Point', 'roq' => 'Reorder Qty', 'lead' => 'Lead Time'];
    @endphp
    <x-dl::alert tone="danger" :message="$text">
        <x-dl::button variant="secondary" size="sm" icon="fa-truck-ramp-box" label="Open Receiving" :href="route('docs.index', 'receiving')" />
    </x-dl::alert>
    <x-dl::table :columns="$cols" :rows="$rows" />
    <x-dl::modal id="reorder-alert" title="Reorder Point Reached" icon="fa-triangle-exclamation" tone="danger" size="lg" :open="$popup">
        <x-dl::box as="p"><x-dl::box as="b">{{ $text }}</x-dl::box></x-dl::box>
        <x-dl::table :columns="$cols" :rows="$rows" />
        <x-slot:footer>
            <x-dl::button preset="close" data-dl-close-modal />
        </x-slot:footer>
    </x-dl::modal>
@endif
