<?php

namespace App\Services;

/* REMARK
 * Tujuan       : SARAN Titik Pesan Ulang dan Jml Pesan Ulang per barang dari pemakaian harian rata-rata dan lead time (tidak pernah mengubah data).
 *                Titik Pesan Ulang = pemakaian/hari x (lead time + hari pengaman), dibulatkan ke atas ke bilangan bulat; Jml Pesan Ulang = pemakaian/hari x hari cakupan, dibulatkan ke atas (paling sedikit 1).
 *                Hari pengaman 7 dan hari cakupan 30 adalah angka bawaan (keputusan Jo 2026-10-22); form Barang boleh mencoba angka lain di layar tanpa menyimpannya.
 * Sumber hulu  : LeadTimes::dailyUsage (90 hari), LeadTime::effective() (engine DevLib).
 * Output       : advise() = ['rop', 'qty', 'lead', 'usage', 'safety', 'cover'] atau null bila belum ada lead time atau belum ada pemakaian.
 * Dipakai oleh : products/form.blade.php (kotak saran).
 * Status       : Aktif.
 */
final class ReorderAdvice
{
    public const SAFETY_DAYS = 7;

    public const COVER_DAYS = 30;

    /** @return array{rop: int, qty: int, lead: int, usage: float, safety: int, cover: int}|null */
    public static function advise(?int $leadDays, float $dailyUsage, int $safetyDays = self::SAFETY_DAYS, int $coverDays = self::COVER_DAYS): ?array
    {
        if ($leadDays === null || $dailyUsage <= 0) {
            return null;
        }
        // 1e-9: pecahan biner (0,6 x 5 = 3,0000000000000004) tidak boleh menaikkan hasil satu angka
        $up = fn (float $x): int => (int) ceil($x - 1e-9);

        return [
            'rop' => max(1, $up($dailyUsage * ($leadDays + $safetyDays))),
            'qty' => max(1, $up($dailyUsage * $coverDays)),
            'lead' => $leadDays, 'usage' => $dailyUsage, 'safety' => $safetyDays, 'cover' => $coverDays,
        ];
    }
}
