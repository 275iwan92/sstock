<?php

namespace App\Services;

use App\Enums\StockDocumentType;
use App\Models\Item;
use App\Models\StockDocument;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/* REMARK
 * Tujuan       : Satu-satunya pintu yang mengubah saldo stok: memposting baris dokumen ke buku besar stock_movements dan memperbarui items.stock_qty / avg_cost (moving average).
 * Sumber hulu  : stock_documents + stock_document_lines (qty, unit_cost, selisih penyesuaian).
 * Output       : baris stock_movements (dengan saldo sesudah transaksi); daftar item yang menyentuh Reorder Point.
 * Dipakai oleh : StockDocuments (masuk, keluar, saldo awal, persetujuan penyesuaian).
 * Status       : Aktif.
 */
final class StockLedger
{
    private const EPS = 0.0005;

    /**
     * @return array<int, Item> item yang SESUDAH posting berada di/bawah Reorder Point
     */
    public function post(StockDocument $doc, User $user, ?\DateTimeInterface $movedAt = null): array
    {
        $movedAt ??= $doc->doc_at;
        $touched = [];

        DB::transaction(function () use ($doc, $user, $movedAt, &$touched) {
            foreach ($doc->lines()->orderBy('id')->get() as $line) {
                $item = Item::query()->lockForUpdate()->findOrFail($line->item_id);
                $signed = $doc->type->signed((float) $line->qty);
                if (abs($signed) < self::EPS) {
                    continue;  // hasil opname sama dengan sistem: tidak ada pergerakan
                }
                $balance = round($item->stock_qty + $signed, 3);

                if ($balance < -self::EPS) {
                    throw ValidationException::withMessages(['lines' => "Insufficient stock for {$item->code} {$item->name}: available ".rtrim(rtrim(number_format($item->stock_qty, 3, '.', ''), '0'), '.').', needed '.rtrim(rtrim(number_format(abs($signed), 3, '.', ''), '0'), '.').'.']);
                }
                if ($doc->type === StockDocumentType::Opening && $item->movements()->exists()) {
                    throw ValidationException::withMessages(['lines' => "{$item->code} already has stock movements; use a Stock Adjustment instead of an Opening Balance."]);
                }

                // Penambahan memakai biaya baris (masuk / saldo awal) atau biaya rata-rata (penyesuaian +); pengurangan memakai biaya rata-rata.
                $cost = match (true) {
                    $signed > 0 && in_array($doc->type, [StockDocumentType::Receiving, StockDocumentType::Opening], true) => (float) $line->unit_cost,
                    $signed > 0 => $item->avg_cost > 0 ? $item->avg_cost : $item->unit_price,
                    default => $item->avg_cost,
                };
                $avg = $signed > 0 && $balance > 0
                    ? round(($item->stock_qty * $item->avg_cost + $signed * $cost) / $balance, 4)
                    : $item->avg_cost;

                $line->update(['unit_cost' => $cost]);
                // Saldo sengaja tidak mass-assignable di Item: hanya buku besar ini yang boleh mengubahnya.
                $item->forceFill(['stock_qty' => $balance, 'avg_cost' => $avg])->save();
                StockMovement::create([
                    'item_id' => $item->id,
                    'stock_document_id' => $doc->id,
                    'type' => $doc->type->value,
                    'moved_at' => $movedAt,
                    'qty' => $signed,
                    'unit_cost' => $cost,
                    'balance_qty' => $balance,
                    'balance_avg_cost' => $avg,
                    'user_id' => $user->id,
                ]);

                if ($item->isBelowReorderPoint()) {
                    $touched[$item->id] = $item;
                }
            }
        });

        return array_values($touched);
    }
}
