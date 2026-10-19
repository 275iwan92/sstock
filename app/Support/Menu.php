<?php

namespace App\Support;

use App\Enums\StockDocumentType;
use App\Models\User;

/* REMARK
 * Tujuan       : Menu Layer Shell menurut peran. Data Entry: master (lihat), Receiving, Issuance, laporan stok. Supervisor: semuanya + Opening Balance, Stock Adjustment, laporan penyesuaian.
 * Output       : array $modules untuk x-dl::layer-shell (code item dipakai juga oleh ?open=).
 * Dipakai oleh : HomeController.
 * Status       : Aktif.
 */
final class Menu
{
    public static function modules(User $user): array
    {
        $item = fn (string $code, string $label, string $url, string $type, string $icon) => compact('code', 'label', 'url', 'type', 'icon');
        $doc = fn (string $code, StockDocumentType $t, string $icon) => $item($code, $t->label(), route('docs.index', $t->slug()), 'grid', $icon);

        $trx = [$doc('RCV', StockDocumentType::Receiving, 'fa-solid fa-truck-ramp-box'), $doc('ISS', StockDocumentType::Issuance, 'fa-solid fa-dolly')];
        if ($user->isSupervisor()) {
            $trx = [$doc('OPN', StockDocumentType::Opening, 'fa-solid fa-flag-checkered'), ...$trx, $doc('ADJ', StockDocumentType::Adjustment, 'fa-solid fa-scale-balanced')];
        }

        $reports = [
            $item('R-CARD', 'Kartu Stok', route('reports.stock-card'), 'report', 'fa-solid fa-clipboard-list'),
            $item('R-SUM', 'Ringkasan Stok', route('reports.summary'), 'report', 'fa-solid fa-table-list'),
            $item('R-IN', 'Laporan Penerimaan', route('reports.receiving'), 'report', 'fa-solid fa-file-arrow-down'),
            $item('R-OUT', 'Laporan Pengeluaran', route('reports.issuance'), 'report', 'fa-solid fa-file-arrow-up'),
        ];
        if ($user->isSupervisor()) {
            $reports[] = $item('R-ADJ', 'Laporan Opname & Penyesuaian', route('reports.adjustment'), 'report', 'fa-solid fa-file-circle-check');
        }

        $master = [
            $item('ITM', 'Barang', route('items.index'), 'grid', 'fa-solid fa-box'),
            $item('SUP', 'Suppliers', route('suppliers.index'), 'grid', 'fa-solid fa-truck'),
        ];
        if ($user->isSupervisor()) {
            $master[] = $item('UOM', 'Satuan', route('units.index'), 'grid', 'fa-solid fa-ruler');
        }

        return [
            ['code' => 'MST', 'short' => 'MST', 'label' => 'Data Master', 'icon' => 'fa-solid fa-boxes-stacked', 'children' => $master],
            ['code' => 'TRX', 'short' => 'TRX', 'label' => 'Transaksi', 'icon' => 'fa-solid fa-right-left', 'children' => $trx],
            ['code' => 'RPT', 'short' => 'RPT', 'label' => 'Laporan', 'icon' => 'fa-solid fa-chart-column', 'children' => $reports],
        ];
    }
}
