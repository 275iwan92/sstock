<?php

namespace App\Services;

use App\Enums\StockDocumentType;
use App\Models\Department;
use App\Models\Item;
use App\Models\Recipient;
use App\Models\ReorderOrder;
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
     * Dokumen baru SELALU berstatus draf dan belum menyentuh stok; stok baru diproses saat Confirm (penyesuaian: saat Approve).
     *
     * @param  array<string, mixed>  $header  supplier_id, ref_no, recipient, department, reason, note, doc_at (hanya saldo awal)
     * @param  array<int, array<string, mixed>>  $lines  item_id, qty, unit_cost, counted_qty, note
     */
    public function create(StockDocumentType $type, array $header, array $lines, User $user): StockDocument
    {
        $header = $this->withNames($type, $header);

        return DB::transaction(function () use ($type, $header, $lines, $user) {
            $doc = StockDocument::create([
                ...$header,
                'doc_no' => $this->nextNumber($type),
                'type' => $type,
                'doc_at' => $type === StockDocumentType::Opening ? $header['doc_at'] : now(),
                'status' => 'draft',
                'created_by' => $user->id,
            ]);
            $this->writeLines($doc, $lines);

            return $doc;
        });
    }

    /**
     * Confirm = diproses ke stok (saldo awal, masuk, keluar). Tanggal / jam dokumen = saat Confirm (saldo awal: tanggal yang dipilih).
     *
     * @return array<int, Item> item yang kini di/bawah Reorder Point
     */
    public function confirm(StockDocument $doc, User $user): array
    {
        abort_if($doc->type === StockDocumentType::Adjustment, 422, 'Penyesuaian diajukan lalu disetujui Supervisor.');
        $this->assertStatus($doc, ['draft']);
        abort_if($doc->lines()->doesntExist(), 422, 'Tambahkan minimal satu baris sebelum Confirm.');

        return DB::transaction(function () use ($doc, $user) {
            if ($doc->type !== StockDocumentType::Opening) {
                $doc->update(['doc_at' => now()]);
            }
            $below = $this->ledger->post($doc->refresh(), $user);
            $doc->update(['status' => 'confirmed', 'confirmed_by' => $user->id, 'confirmed_at' => now()]);
            if ($doc->type === StockDocumentType::Receiving) {
                // Barang datang menutup pemesanan ulang yang terbuka (juga bila baru sebagian; pesan lagi bila masih kurang).
                ReorderOrder::query()->whereNull('closed_at')->whereIn('item_id', $doc->lines()->pluck('item_id'))->update(['closed_at' => now(), 'receiving_id' => $doc->id]);
            }

            return $below;
        });
    }

    /**
     * Cancel: pengguna MENGAJUKAN pembatalan (alasan wajib) yang menunggu persetujuan Supervisor; Supervisor yang menekan Cancel membatalkan langsung.
     * Ditolak lebih dulu bila stok tidak cukup untuk menarik dokumen kembali (koreksi lewat Stock Adjustment). Draf dihapus, bukan dibatalkan.
     * Penyesuaian yang baru diajukan (belum disetujui) hanya ditarik; pengajuan pembatalan yang menunggu bisa ditarik kembali.
     *
     * @return string requested | cancelled | withdrawn
     */
    public function cancel(StockDocument $doc, User $user, string $reason): string
    {
        $reason = mb_substr(trim($reason), 0, 255);
        $this->assertStatus($doc, ['confirmed', 'approved', 'approval', 'cancel_pending']);

        if ($doc->status === 'cancel_pending') {
            $doc->update($this->backToProcessed($doc) + ['cancel_reason' => null, 'cancel_requested_by' => null, 'cancel_requested_at' => null]);

            return 'withdrawn';
        }
        abort_if(mb_strlen($reason) < 3, 422, 'Isi alasan pembatalan (minimal 3 huruf).');

        if ($doc->status === 'approval') {
            $this->finishCancel($doc, $user, $reason);

            return 'cancelled';
        }
        $this->ledger->assertReversible($doc);
        if ($user->isSupervisor()) {
            $this->finishCancel($doc, $user, $reason);

            return 'cancelled';
        }
        $doc->update(['status' => 'cancel_pending', 'cancel_reason' => $reason, 'cancel_requested_by' => $user->id, 'cancel_requested_at' => now()]);

        return 'requested';
    }

    /** Hasil hitungan "apakah stok masih cukup untuk menarik dokumen ini kembali": pesan masalahnya, atau null bila bisa dibatalkan. */
    public function cancelProblem(StockDocument $doc): ?string
    {
        if (! in_array($doc->status, ['confirmed', 'approved'], true)) {
            return null;
        }
        try {
            $this->ledger->assertReversible($doc);
        } catch (ValidationException $e) {
            return $e->errors()['lines'][0] ?? 'Pembatalan tidak bisa dilakukan.';
        }

        return null;
    }

    // Supervisor menyetujui pengajuan pembatalan: stok dibalik (diperiksa ulang), dokumen dibatalkan.
    public function approveCancel(StockDocument $doc, User $approver): void
    {
        $this->assertStatus($doc, ['cancel_pending']);
        $this->finishCancel($doc, $approver, (string) $doc->cancel_reason);
    }

    // Supervisor menolak pengajuan: dokumen kembali berstatus sebelumnya, stok tidak berubah.
    public function rejectCancel(StockDocument $doc): void
    {
        $this->assertStatus($doc, ['cancel_pending']);
        $doc->update($this->backToProcessed($doc) + ['cancel_reason' => null, 'cancel_requested_by' => null, 'cancel_requested_at' => null]);
    }

    private function backToProcessed(StockDocument $doc): array
    {
        return ['status' => $doc->type === StockDocumentType::Adjustment ? 'approved' : 'confirmed'];
    }

    private function finishCancel(StockDocument $doc, User $by, string $reason): void
    {
        DB::transaction(function () use ($doc, $by, $reason) {
            if (in_array($doc->status, ['confirmed', 'approved', 'cancel_pending'], true)) {
                $this->ledger->reverse($doc, $by);
            }
            $doc->update(['status' => 'cancelled', 'cancelled_by' => $by->id, 'cancelled_at' => now(), 'cancel_reason' => $reason]);
            if ($doc->type === StockDocumentType::Receiving) {
                // Pemesanan yang ditutup oleh penerimaan ini terbuka lagi.
                ReorderOrder::query()->where('receiving_id', $doc->id)->update(['closed_at' => null, 'receiving_id' => null]);
            }
        });
    }

    // Semua jenis dokumen: departemen kosong -> departemen bawaan penerima. Nama master disalin ke kolom teks dokumen (tetap terbaca bila master diubah).
    private function withNames(StockDocumentType $type, array $header): array
    {
        $recipient = isset($header['recipient_id']) ? Recipient::find($header['recipient_id']) : null;
        $header['department_id'] = $header['department_id'] ?? $recipient?->department_id;
        $header['recipient'] = $recipient?->name ?? $header['recipient'] ?? null;
        $header['department'] = isset($header['department_id']) ? Department::find($header['department_id'])?->name : ($header['department'] ?? null);

        return $header;
    }

    // Hanya draf yang boleh diubah (semua jenis).
    public function update(StockDocument $doc, array $header, array $lines): void
    {
        $this->assertStatus($doc, ['draft']);
        $header = $this->withNames($doc->type, $header);
        DB::transaction(function () use ($doc, $header, $lines) {
            $doc->update($header);
            $doc->lines()->delete();
            $this->writeLines($doc, $lines);
        });
    }

    public function submit(StockDocument $doc): void
    {
        $this->assertStatus($doc, ['draft'], StockDocumentType::Adjustment);
        abort_if($doc->lines()->doesntExist(), 422, 'Tambahkan minimal satu baris sebelum konfirmasi.');
        $doc->update(['status' => 'approval']);
    }

    public function approve(StockDocument $doc, User $approver): void
    {
        $this->assertStatus($doc, ['approval'], StockDocumentType::Adjustment);
        DB::transaction(function () use ($doc, $approver) {
            foreach ($doc->lines()->with('item')->get() as $line) {
                if (abs($line->item->stock_qty - (float) $line->system_qty) > 0.0005) {
                    throw ValidationException::withMessages(['lines' => "Stok {$line->item->code} berubah sejak penghitungan (sistem {$line->system_qty}, sekarang {$line->item->stock_qty}). Tolak dokumen ini lalu hitung ulang."]);
                }
            }
            $this->ledger->post($doc, $approver, now());
            $doc->update(['status' => 'approved', 'approved_by' => $approver->id, 'approved_at' => now()]);
        });
    }

    public function reject(StockDocument $doc, User $approver): void
    {
        $this->assertStatus($doc, ['approval'], StockDocumentType::Adjustment);
        $doc->update(['status' => 'rejected', 'approved_by' => $approver->id, 'approved_at' => now()]);
    }

    public function delete(StockDocument $doc): void
    {
        $this->assertStatus($doc, ['draft']);
        $doc->delete();
    }

    private function assertStatus(StockDocument $doc, array $allowed, ?StockDocumentType $only = null): void
    {
        abort_unless(($only === null || $doc->type === $only) && in_array($doc->status, $allowed, true), 422, 'Aksi ini tidak diizinkan pada status sekarang.');
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
            throw ValidationException::withMessages(['lines' => "Belum ada konversi dari {$row['unit']} ke {$item->unit} untuk {$item->code}. Tambahkan di Data Master > Satuan."]);
        }
        $qty = round((float) $row['qty'] * $factor, 3);
        if ($qty <= 0) {
            throw ValidationException::withMessages(['lines' => "Jumlah {$item->code} terlalu kecil untuk dikonversi ke {$item->unit}."]);
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
