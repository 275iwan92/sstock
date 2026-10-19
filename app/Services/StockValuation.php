<?php

namespace App\Services;

use App\Models\StockMovement;
use Devlib\Locale;
use Illuminate\Support\Carbon;

/* REMARK
 * Tujuan       : Deret nilai persediaan (saldo x biaya rata-rata semua barang) untuk grafik di opening screen: per minggu (12 minggu terakhir) selama riwayat stok masih singkat,
 *                otomatis per bulan (12 bulan terakhir) setelah riwayat lebih dari 180 hari.
 * Sumber hulu  : stock_movements (balance_qty, balance_avg_cost sesudah tiap pergerakan).
 * Output       : ['labels' => [...], 'values' => [...], 'mode' => 'weekly'|'monthly'].
 * Dipakai oleh : HomeController (hanya Supervisor).
 * Status       : Aktif.
 */
final class StockValuation
{
    private const WEEKLY_POINTS = 12;

    private const MONTHLY_POINTS = 12;

    // shortcut: memuat seluruh pergerakan ke memori lalu menjumlah per akhir periode, cukup untuk puluhan ribu baris; upgrade ke tabel ringkasan harian bila lebih besar.
    public static function series(?Carbon $today = null): array
    {
        $today = ($today ?? now())->copy()->endOfDay();
        $moves = StockMovement::query()->where('moved_at', '<=', $today)->orderBy('moved_at')->orderBy('id')->get(['item_id', 'moved_at', 'balance_qty', 'balance_avg_cost']);
        $monthly = $moves->isNotEmpty() && $moves->first()->moved_at->diffInDays($today) > 180;

        $cutoffs = [];
        if ($monthly) {
            for ($i = self::MONTHLY_POINTS - 1; $i >= 0; $i--) {
                $cutoffs[] = $i === 0 ? $today->copy() : $today->copy()->subMonthsNoOverflow($i)->endOfMonth();
            }
        } else {
            for ($i = self::WEEKLY_POINTS - 1; $i >= 0; $i--) {
                $cutoffs[] = $today->copy()->subDays(7 * $i)->endOfDay();
            }
        }

        $value = [];
        $i = 0;
        $labels = [];
        $values = [];
        foreach ($cutoffs as $cutoff) {
            while ($i < $moves->count() && $moves[$i]->moved_at <= $cutoff) {
                $value[$moves[$i]->item_id] = $moves[$i]->balance_qty * $moves[$i]->balance_avg_cost;
                $i++;
            }
            $labels[] = Locale::at($cutoff)->translatedFormat($monthly ? 'M Y' : 'd M');
            $values[] = round(array_sum($value));
        }

        return ['labels' => $labels, 'values' => $values, 'mode' => $monthly ? 'monthly' : 'weekly'];
    }
}
