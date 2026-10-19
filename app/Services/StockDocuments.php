<?php

namespace App\Services;

use App\Enums\StockDocumentType;
use App\Models\Item;
use App\Models\StockDocument;
use App\Models\User;
use App\Support\UnitConverter;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/* REMARK
 * Tujuan       : Siklus hidup dokumen stok. Saldo awal / masuk / keluar langsung diposting saat disimpan (status confirmed, tidak bisa diubah; koreksi lewat penyesuaian).
 *                Penyesuaian: draft -> approval -> approved (diposting) | rejected, hanya supervisor.
 * Sumber hulu  : data form yang sudah divalidasi (header + lines), StockLedger.
 * Output       : StockDocument beserta baris; untuk keluar: item yang kini di/bawah Reorder Point.
 * Dipakai oleh : StockDocumentController.
 * Status       : Aktif.
 */
final class StockDocuments
{
    public function __construct(private readonly StockLedger $ledger) {}

    /**
     * @param  array<string, mixed>  $header  supplier_id, ref_no, recipient, department, reason, note, doc_at (hanya saldo awal)
     * @param  array<int, array<string, mixed>>  $lines  item_id, qty, unit_cost, counted_qty, note
     * @return array{0: StockDocument, 1: array<int, Item>}
     */
    public function create(StockDocumentType $type, array $header, array $lines, User $user): array
    {
        return DB::transaction(function () use ($type, $header, $lines, $user) {
            $doc = StockDocument::create([
                ...$header,
                'doc_no' => $this->nextNumber($type),
                'type' => $type,
                'doc_at' => $type === StockDocumentType::Opening ? $header['doc_at'] : now(),
                'status' => $type === StockDocumentType::Adjustment ? 'draft' : 'confirmed',
                'created_by' => $user->id,
            ]);
            $this->writeLines($doc, $lines);

            $below = $type === StockDocumentType::Adjustment ? [] : $this->ledger->post($doc, $user);

            return [$doc, $below];
        });
    }

    // Hanya draft penyesuaian yang boleh diubah.
    public function update(StockDocument $doc, array $header, array $lines): void
    {
        $this->assertStatus($doc, ['draft']);
        DB::transaction(function () use ($doc, $header, $lines) {
            $doc->update($header);
            $doc->lines()->delete();
            $this->writeLines($doc, $lines);
        });
    }

    public function submit(StockDocument $doc): void
    {
        $this->assertStatus($doc, ['draft']);
        abort_if($doc->lines()->doesntExist(), 422, 'Add at least one line before confirming.');
        $doc->update(['status' => 'approval']);
    }

    public function approve(StockDocument $doc, User $approver): void
    {
        $this->assertStatus($doc, ['approval']);
        DB::transaction(function () use ($doc, $approver) {
            foreach ($doc->lines()->with('item')->get() as $line) {
                if (abs($line->item->stock_qty - (float) $line->system_qty) > 0.0005) {
                    throw ValidationException::withMessages(['lines' => "Stock of {$line->item->code} changed since the count (system {$line->system_qty}, now {$line->item->stock_qty}). Reject this document and count again."]);
                }
            }
            $this->ledger->post($doc, $approver, now());
            $doc->update(['status' => 'approved', 'approved_by' => $approver->id, 'approved_at' => now()]);
        });
    }

    public function reject(StockDocument $doc, User $approver): void
    {
        $this->assertStatus($doc, ['approval']);
        $doc->update(['status' => 'rejected', 'approved_by' => $approver->id, 'approved_at' => now()]);
    }

    public function delete(StockDocument $doc): void
    {
        $this->assertStatus($doc, ['draft']);
        $doc->delete();
    }

    private function assertStatus(StockDocument $doc, array $allowed): void
    {
        abort_unless($doc->type === StockDocumentType::Adjustment && in_array($doc->status, $allowed, true), 422, 'This action is not allowed in the current status.');
    }

    private function writeLines(StockDocument $doc, array $lines): void
    {
        $items = Item::query()->whereIn('id', array_column($lines, 'item_id'))->get()->keyBy('id');

        foreach ($lines as $row) {
            $item = $items[$row['item_id']];
            $isAdjustment = $doc->type === StockDocumentType::Adjustment;
            $qty = $isAdjustment ? round((float) $row['counted_qty'] - $item->stock_qty, 3) : $row['qty'];
            $cost = $row['unit_cost'] ?? null;
            $entered = [];

            // Penerimaan dalam satuan lain: simpan yang diketik, masukkan ke stok dalam satuan stok item.
            if ($doc->type === StockDocumentType::Receiving && ($row['unit'] ?? $item->unit) !== $item->unit) {
                [$qty, $cost, $entered] = $this->toStockUnit($item, $row);
            }

            $doc->lines()->create([
                'item_id' => $item->id,
                'qty' => $qty,
                'unit_cost' => $cost,
                'system_qty' => $isAdjustment ? $item->stock_qty : null,
                'counted_qty' => $isAdjustment ? $row['counted_qty'] : null,
                'note' => $row['note'] ?? null,
            ] + $entered);
        }
    }

    /** @return array{0: float, 1: float, 2: array<string, mixed>} qty dan biaya per satuan stok + data yang diketik */
    private function toStockUnit(Item $item, array $row): array
    {
        $factor = UnitConverter::factor($row['unit'], $item->unit);
        if ($factor === null) {
            throw ValidationException::withMessages(['lines' => "No conversion from {$row['unit']} to {$item->unit} for {$item->code}. Add it in Master Data > Units."]);
        }
        $qty = round((float) $row['qty'] * $factor, 3);
        if ($qty <= 0) {
            throw ValidationException::withMessages(['lines' => "The quantity of {$item->code} is too small to convert into {$item->unit}."]);
        }

        return [$qty, round((float) $row['unit_cost'] / $factor, 4), ['entered_qty' => $row['qty'], 'entered_unit' => $row['unit'], 'entered_unit_cost' => $row['unit_cost']]];
    }

    // PREFIX-YYMM-NNNN, urut per jenis per bulan; kolom doc_no unik menjaga dari nomor ganda.
    private function nextNumber(StockDocumentType $type): string
    {
        $stem = $type->prefix().'-'.now()->format('ym').'-';
        $last = StockDocument::query()->where('doc_no', 'like', $stem.'%')->orderByDesc('doc_no')->value('doc_no');

        return $stem.str_pad((string) ($last ? (int) substr($last, -4) + 1 : 1), 4, '0', STR_PAD_LEFT);
    }
}
