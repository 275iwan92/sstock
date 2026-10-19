<?php

namespace App\Support;

use App\Models\Item;
use Devlib\Lookup;

/* REMARK
 * Tujuan       : Pilihan Item di baris dokumen (x-dl::lines) dicari ke server; memilih item mengisi kolom satuan, stok tersedia, selisih sistem, dan harga acuan di baris itu.
 * Sumber hulu  : items (hanya yang aktif). Harga acuan hanya untuk Supervisor.
 * Output       : Lookup item_in (Receiving), item_out (Issuance), item_opn (Opening Balance), item_adj (Stock Adjustment).
 * Dipakai oleh : AppServiceProvider::boot; rute /_devlib-lookup/{name} milik DevLib.
 * Status       : Aktif.
 */
final class ItemLookups
{
    public static function register(): void
    {
        $fills = [
            'item_in' => ['unit' => 'unit', 'unit_cost' => 'price'],
            'item_out' => ['unit' => 'unit', 'available' => 'stock'],
            'item_opn' => ['unit' => 'unit', 'unit_cost' => 'price'],
            'item_adj' => ['unit' => 'unit', 'system_qty' => 'stock'],
        ];

        foreach ($fills as $name => $fill) {
            Lookup::define($name, [
                'key' => 'id', 'label' => 'label', 'source' => 'items (is_active = 1)', 'fill' => $fill,
                'columns' => ['name' => 'Item', 'code' => 'Code', 'unit' => 'Unit', 'stock' => 'Stock'],
                'search' => fn (string $q) => Item::query()->where('is_active', true)
                    ->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%"))
                    ->orderBy('name')->limit(50)->get()->map(self::row(...)),
                'find' => fn ($id) => ($i = Item::query()->find($id)) ? self::row($i) : null,
            ]);
        }
    }

    private static function row(Item $i): array
    {
        return [
            'id' => $i->id, 'label' => "{$i->code} · {$i->name}", 'name' => $i->name, 'code' => $i->code, 'unit' => $i->unit,
            'stock' => $i->stock_qty, 'price' => auth()->user()?->isSupervisor() ? $i->unit_price : null,
        ];
    }
}
