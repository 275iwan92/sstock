<?php

namespace App\Services;

use App\Enums\StockDocumentType;
use App\Models\Item;
use App\Models\ReorderOrder;
use App\Models\StockDocument;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

/* REMARK
 * Tujuan       : Aturan penutupan pesanan ulang (reorder_orders). Pesanan yang punya jumlah ditutup HANYA saat jumlah diterima memenuhi jumlah dipesan; penerimaan sebagian = "diterima sebagian", tetap terbuka.
 *                Pesanan lama tanpa jumlah: penerimaan pertama sejak tanggal pesan menutupnya (aturan lama). Pesanan yang tak akan dipenuhi penuh ditutup manual (close_reason 'manual'), tidak ikut dialokasikan lagi.
 *                Perkiraan tiba / terlambat memakai kedatangan PERTAMA: setelah ada penerimaan pertama pesanan tidak ditandai terlambat lagi.
 * Sumber hulu  : penerimaan terkonfirmasi yang tidak dibatalkan (stock_movements jenis Penerimaan, satuan stok, semua batch produk), reorder_orders.
 * Output       : reorder_orders.received_qty, first_received_at, closed_at, close_reason, receiving_id. reconcile() idempoten: hasilnya hanya bergantung pada data penerimaan, jadi pembatalan lalu reconcile membuka kembali pesanan yang seharusnya terbuka.
 * Dipakai oleh : StockDocuments (konfirmasi dan pembatalan Penerimaan), ReorderOrderController.
 * Status       : Aktif.
 */
final class ReorderOrders
{
    /** Pergerakan masuk dari Penerimaan yang terkonfirmasi dan tidak dibatalkan, semua batch produk, menurut urutan waktu. */
    public static function receipts(int $productId)
    {
        return StockMovement::query()
            ->join('items', 'items.id', '=', 'stock_movements.item_id')
            ->join('stock_documents', 'stock_documents.id', '=', 'stock_movements.stock_document_id')
            ->where('items.product_id', $productId)->where('stock_movements.type', StockDocumentType::Receiving->value)->whereNull('stock_movements.reversal_of')
            ->whereIn('stock_documents.status', ['confirmed', 'cancel_pending'])
            ->orderBy('stock_movements.moved_at')->orderBy('stock_movements.id')
            ->get(['stock_movements.id', 'stock_movements.stock_document_id', 'stock_movements.moved_at', 'stock_movements.qty']);
    }

    /** Hitung ulang semua pesanan produk dari data penerimaan; dipanggil setelah Penerimaan dikonfirmasi atau dibatalkan. */
    public function reconcileDocument(StockDocument $doc): void
    {
        if ($doc->type !== StockDocumentType::Receiving) {
            return;
        }
        $products = Item::query()->whereIn('id', $doc->lines()->pluck('item_id'))->whereNotNull('product_id')->distinct()->pluck('product_id');
        foreach ($products as $productId) {
            $this->reconcile((int) $productId);
        }
    }

    public function reconcile(int $productId): void
    {
        DB::transaction(function () use ($productId) {
            $receipts = self::receipts($productId)->map(fn ($m) => ['at' => $m->moved_at, 'doc' => $m->stock_document_id, 'left' => (float) $m->qty])->all();
            $orders = ReorderOrder::query()->where('product_id', $productId)->where(fn ($q) => $q->whereNull('close_reason')->orWhere('close_reason', '!=', 'manual'))
                ->orderBy('ordered_at')->orderBy('id')->lockForUpdate()->get();

            foreach ($orders as $order) {
                $from = $order->ordered_at->copy()->startOfDay();   // tanggal pesan tanpa jam: penerimaan pada hari itu ikut dihitung
                $got = 0.0;
                $first = $last = null;

                if ($order->qty === null) {
                    // Pesanan lama tanpa jumlah: penerimaan pertama sejak tanggal pesan menutupnya; tidak memakai jatah penerimaan.
                    $first = $last = collect($receipts)->first(fn ($r) => $r['at'] >= $from);
                } else {
                    foreach ($receipts as &$r) {
                        $take = min($r['left'], round($order->qty - $got, 3));
                        if ($r['at'] < $from || $take <= 0) {
                            continue;
                        }
                        $r['left'] = round($r['left'] - $take, 3);
                        $got = round($got + $take, 3);
                        $first ??= $r;
                        $last = $r;
                    }
                    unset($r);
                }

                $done = $order->qty === null ? $last !== null : $got >= $order->qty;
                $order->update([
                    'received_qty' => $got,
                    'first_received_at' => $first['at'] ?? null,
                    'closed_at' => $done ? $last['at'] : null,
                    'close_reason' => $done ? 'received' : null,
                    'receiving_id' => $done ? $last['doc'] : null,
                ]);
            }
        });
    }

    /** Tutup manual pesanan yang tidak akan dipenuhi penuh: tidak menghapus apa pun; alasan opsional ditambahkan ke catatan. */
    public function closeManually(ReorderOrder $order, ?string $reason): void
    {
        DB::transaction(function () use ($order, $reason) {
            $order->setRawAttributes(ReorderOrder::query()->lockForUpdate()->findOrFail($order->id)->getAttributes(), true);
            abort_if($order->closed_at !== null, 422, 'Pesanan ini sudah tertutup.');
            $reason = trim((string) $reason);
            $order->update([
                'closed_at' => now(), 'close_reason' => 'manual',
                'note' => $reason === '' ? $order->note : mb_substr(trim(($order->note ? $order->note.' ' : '')."Ditutup: {$reason}"), 0, 255),
            ]);
        });
    }
}
