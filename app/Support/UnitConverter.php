<?php

namespace App\Support;

use App\Models\UnitConversion;

/* REMARK
 * Tujuan       : Mengubah jumlah antar satuan memakai master konversi: langsung (1 box = 12 pcs), kebalikannya (1 / faktor), atau satuan yang sama (faktor 1). Tanpa rantai (box > pcs > gram tidak dihitung otomatis).
 * Sumber hulu  : unit_conversions.
 * Output       : factor(from, to) atau null bila tidak ada aturannya; convert(qty, from, to).
 * Dipakai oleh : StockDocuments (Penerimaan: satuan baris dikonversi ke satuan stok barang).
 * Status       : Aktif.
 */
final class UnitConverter
{
    // Berapa {to} dalam 1 {from}; null bila tidak ada aturan.
    public static function factor(string $from, string $to): ?float
    {
        if ($from === $to) {
            return 1.0;
        }
        if ($direct = UnitConversion::query()->where('from_unit', $from)->where('to_unit', $to)->value('factor')) {
            return (float) $direct;
        }
        $inverse = UnitConversion::query()->where('from_unit', $to)->where('to_unit', $from)->value('factor');

        return $inverse ? 1 / (float) $inverse : null;
    }

    public static function convert(float $qty, string $from, string $to): ?float
    {
        $factor = self::factor($from, $to);

        return $factor === null ? null : round($qty * $factor, 6);
    }
}
