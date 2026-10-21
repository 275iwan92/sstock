<?php

namespace App\Models;

use App\Enums\StockDocumentType;
use Devlib\Locale;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'ordered_at', 'ref_no', 'created_by', 'closed_at', 'receiving_id'])]
class ReorderOrder extends Model
{
    protected function casts(): array
    {
        return ['ordered_at' => 'date', 'closed_at' => 'datetime'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Penerimaan sejak tanggal pesan, dalam satuan stok, semua batch produk: pergerakan masuk dari dokumen Penerimaan yang terkonfirmasi dan tidak dibatalkan.
     * Hanya informasi: tidak menutup atau mengubah pesanan (penutupan tetap oleh StockDocuments::confirm). Jumlah yang DIPESAN tidak tercatat.
     */
    public function receivedSince(): float
    {
        return round((float) StockMovement::query()
            ->join('items', 'items.id', '=', 'stock_movements.item_id')
            ->join('stock_documents', 'stock_documents.id', '=', 'stock_movements.stock_document_id')
            ->where('items.product_id', $this->product_id)->where('stock_movements.type', StockDocumentType::Receiving->value)->whereNull('stock_movements.reversal_of')
            ->whereIn('stock_documents.status', ['confirmed', 'cancel_pending'])->where('stock_movements.moved_at', '>=', $this->ordered_at->copy()->startOfDay())
            ->sum('stock_movements.qty'), 3);
    }

    // Keterangan untuk peringatan stok menipis (beranda, laporan Ringkasan Stok, form barang).
    public function note(): string
    {
        $this->loadMissing('product', 'creator');

        return 'Sudah dicatat dipesan pada '.$this->ordered_at->format('d/m/Y').($this->ref_no ? ", ref {$this->ref_no}" : '').($this->creator ? " oleh {$this->creator->name}" : '')
            .'. Diterima sejak dipesan: '.Locale::decimal($this->receivedSince(), 3).' '.$this->product->unit.'.';
    }
}
