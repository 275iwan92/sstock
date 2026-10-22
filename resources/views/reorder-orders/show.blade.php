{{-- REMARK
 Tujuan       : Detail satu pesanan di Riwayat Pemesanan: data pesanan (tanpa nilai uang), status berlencana, penerimaan yang dialokasikan ke pesanan itu (nomor dokumen Penerimaan, tanggal, jumlah), dan tombol Tutup Pesanan
                (hanya pesanan terbuka dan hanya yang boleh menulis modul ORD) yang memakai rute reorder-orders.close yang sama dengan Beranda.
 Sumber hulu  : ReorderOrderController@show (order, row, unit, allocations, canClose), ReorderOrders::allocations.
 Input        : order, row, unit, allocations, canClose.
 Output       : x-dl::headline, kotak ringkasan, tabel penerimaan, modal Tutup Pesanan (POST reorder-orders.close, return=history).
 Dipakai oleh : route reorder-orders.show.
 Status       : Aktif.
--}}
@extends('layout', ['title' => 'Order History'])
@section('body')
    @php
        $tone = ['Dipesan' => 'success', 'Diterima sebagian' => 'warn', 'Selesai' => 'info', 'Ditutup manual' => 'neutral'][$row['status']] ?? 'neutral';
        $manual = $order->close_reason === 'manual';
        $hasQty = $order->qty !== null;
    @endphp
    @include('partials.flash')
    <x-dl::headline>Pemesanan · {{ $row['product'] }} <x-dl::badge :tone="$tone" :label="raw($row['status'])" /></x-dl::headline>
    <x-dl::kpi-row>
        <x-dl::stat-box label="Order Date" :value="Devlib\Locale::d($order->ordered_at)" tone="neutral" :sub="$order->ref_no ? 'Ref '.$order->ref_no : null" />
        <x-dl::stat-box label="Supplier" :value="$row['supplier']" tone="neutral" :sub="'Dicatat oleh '.$row['creator']" />
        <x-dl::stat-box label="Ordered Qty" :value="$row['qty']" tone="neutral" />
        <x-dl::stat-box label="Received" :value="$row['received'].($hasQty ? ' '.$unit : '')" :tone="$hasQty && (float) $order->received_qty >= $order->qty ? 'success' : 'accent'" />
        <x-dl::stat-box label="Remaining" :value="$row['remaining'].($hasQty ? ' '.$unit : '')" tone="neutral" />
    </x-dl::kpi-row>
    <x-dl::form.row :cols="3">
        <x-dl::field.display label="First Received" :value="raw($order->first_received_at ? Devlib\Locale::d($order->first_received_at) : '-')" />
        <x-dl::field.display label="Lead Time (days)" :value="raw($row['lead'] === null ? '-' : $row['lead'].' hari (dari tanggal pesan sampai penerimaan pertama)')" />
        <x-dl::field.display label="Closed" :value="raw($row['closed'])" />
    </x-dl::form.row>
    <x-dl::form.row>
        <x-dl::field.display label="Catatan" :value="raw($row['note'])" />
    </x-dl::form.row>

    <x-dl::divider label="Penerimaan yang dialokasikan" />
    @if ($manual)
        <p class="dl-note">Pesanan ditutup manual: penerimaan tidak dirinci. Jumlah Diterima di atas adalah yang tercatat sampai pesanan ditutup.</p>
    @elseif (! $hasQty)
        <p class="dl-note">Pesanan lama tanpa jumlah: ditutup oleh penerimaan pertama sejak tanggal pesan.</p>
    @endif
    <x-dl::table :columns="['doc_no' => 'Document No.', 'at' => 'Date & Time', 'qty' => 'Qty']" :rows="array_map(fn ($a) => ['doc_no' => $a['doc_no'], 'at' => Devlib\Locale::stamp($a['at']), 'qty' => Devlib\Locale::decimal($a['qty'], 3).' '.$unit], $allocations)" empty="Belum ada penerimaan yang dialokasikan ke pesanan ini." />

    <x-dl::action-bar align="start">
        <x-dl::button variant="secondary" icon="back" label="Back" :href="route('reorder-orders.index')" />
        @if ($canClose)
            <x-dl::button variant="secondary" icon="fa-ban" label="Close Order" data-dl-open="close-order-modal" />
        @endif
    </x-dl::action-bar>
    @if ($canClose)
        <x-dl::modal id="close-order-modal" title="Close Order" icon="fa-ban" size="sm">
            <form id="close-order-form" method="POST" action="{{ route('reorder-orders.close', $order) }}">
                @csrf
                <input type="hidden" name="return" value="history">
                <p class="dl-note">Tutup pesanan {{ $row['product'] }} yang belum diterima penuh? Pesanan yang sudah ditutup tidak bisa dibuka lagi.</p>
                <x-dl::field.text name="note" label="Alasan (opsional)" maxlength="150" />
            </form>
            <x-slot:footer><x-dl::button type="submit" form="close-order-form" icon="save" label="Close Order" /></x-slot:footer>
        </x-dl::modal>
    @endif
@endsection
