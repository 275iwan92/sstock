{{-- REMARK
 Tujuan       : Kepala layar satu Stok Opname: nomor + status, ringkasan (tanggal, cakupan, cut-off, ambang, hitung buta, kunci), pesan penting, dan tombol aksi (Supervisor: Buka kunci, Batalkan).
 Sumber hulu  : StockCountController@show (count, sup, summary).
 Input        : count, sup, summary.
 Output       : x-dl::headline, x-dl::kpi-row, x-dl::alert, x-dl::action-bar (cetak lembar hitung / Berita Acara; POST stock-counts.unlock / stock-counts.cancel).
 Dipakai oleh : stock-counts/show.
 Status       : Aktif.
--}}
@php
    use Devlib\Locale;
    $tone = ['counting' => 'warn', 'review' => 'info', 'done' => 'success', 'cancelled' => 'danger'][$count->status] ?? 'neutral';
    $running = in_array($count->status, ['counting', 'review'], true);
    $lockText = match (true) {
        ! $running => 'Dilepas',
        $count->lock_transactions => 'Aktif',
        $count->unlocked_at !== null => 'Dibuka',   // kunci sempat dibuka dan tidak dikunci lagi
        default => 'Tanpa kunci',
    };
@endphp
<x-dl::headline>Stok Opname · {{ $count->doc_no }} <x-dl::badge :tone="$tone" :label="raw($count->statusLabel())" /></x-dl::headline>
<x-dl::kpi-row>
    <x-dl::stat-box label="Count Date" :value="Locale::d($count->count_date)" tone="neutral" />
    <x-dl::stat-box label="Scope" :value="$count->scopeLabel()" tone="neutral" :sub="'Cut-off '.Locale::stamp($count->cutoff_at)" />
    {{-- Sedang dihitung: jumlah terisi tampil hidup di kotak Terisi / Belum diisi pada lembar, bukan angka beku di sini. --}}
    @if ($count->status !== 'counting')
        <x-dl::stat-box label="Filled" :value="$summary['filled'].' / '.$summary['lines']" :tone="$summary['filled'] === $summary['lines'] ? 'success' : 'accent'" :sub="'Oleh '.$count->creator->name" />
    @endif
    <x-dl::stat-box label="Difference Threshold" :value="Locale::number($count->threshold_pct, 2).' %'" tone="neutral" :sub="$count->blind ? 'Hitung buta: ya' : 'Hitung buta: tidak'" />
    <x-dl::stat-box label="Transaction Lock" :value="$lockText" :tone="$running && $count->lock_transactions ? 'danger' : 'neutral'" :sub="$count->lock_transactions && $count->locked_at ? 'Dikunci '.$count->locker?->name.' · '.Locale::stamp($count->locked_at) : ($count->unlocked_at ? 'Dibuka '.$count->unlocker?->name.' · '.Locale::stamp($count->unlocked_at) : null)" />
</x-dl::kpi-row>
@if ($running && $count->lock_transactions)
    <x-dl::alert tone="warn" message="Transaksi dikunci: Confirm / Approve Penerimaan, Pengeluaran, dan Penyesuaian ditolak sampai opname selesai atau dibatalkan." />
@endif
@if ($count->status === 'counting' && $count->review_note)
    <x-dl::alert tone="info" :message="raw('Dikembalikan Supervisor untuk dihitung ulang: '.$count->review_note)" />
@endif
@if ($count->status === 'done')
    <x-dl::alert tone="success" :message="raw($count->adjustmentDocument ? 'Disetujui '.Locale::stamp($count->approved_at).'. Penyesuaian otomatis: '.$count->adjustmentDocument->doc_no.'; stok sudah mengikuti hasil hitung fisik.' : 'Disetujui '.Locale::stamp($count->approved_at).'. Tidak ada selisih, jadi tidak ada Penyesuaian.')" />
@endif
@if ($count->status === 'cancelled')
    <x-dl::alert tone="danger" :message="raw('Dibatalkan '.Locale::stamp($count->cancelled_at).'. Stok tidak berubah.')" />
@endif
<x-dl::action-bar align="start">
    <x-dl::button variant="secondary" icon="back" label="Back" :href="route('stock-counts.index')" />
    <x-dl::button variant="secondary" icon="print" label="Print Count Sheet" :href="route('stock-counts.sheet', $count)" target="_blank" />
    @if ($sup && in_array($count->status, ['review', 'done'], true))
        <x-dl::button variant="secondary" icon="print" :label="$count->status === 'done' ? 'Print Minutes' : 'Preview Minutes'" :href="route('stock-counts.report', $count)" target="_blank" />
    @endif
    @if ($running && $sup)
        @unless ($count->lock_transactions)
            <x-dl::action-form :action="route('stock-counts.lock', $count)">
                <x-dl::button type="submit" variant="secondary" icon="fa-lock" label="Lock" data-dl-confirm="Kunci transaksi lagi? Confirm / Approve Penerimaan, Pengeluaran, dan Penyesuaian ditolak sampai opname selesai atau dibatalkan." data-dl-confirm-tone="warn" />
            </x-dl::action-form>
        @endunless
        @if ($count->lock_transactions)
            <x-dl::action-form :action="route('stock-counts.unlock', $count)">
                <x-dl::button type="submit" variant="secondary" icon="fa-lock-open" label="Unlock" data-dl-confirm="Buka kunci transaksi? Transaksi stok boleh diproses lagi selama opname berjalan; yang menggeser stok akan ditolak saat persetujuan." data-dl-confirm-tone="warn" />
            </x-dl::action-form>
        @endif
        <x-dl::action-form :action="route('stock-counts.cancel', $count)">
            <x-dl::button type="submit" variant="danger" icon="fa-ban" label="Cancel Count" data-dl-confirm="Batalkan Stok Opname ini? Kunci dilepas dan stok tidak berubah." data-dl-confirm-tone="warn" />
        </x-dl::action-form>
    @endif
</x-dl::action-bar>
