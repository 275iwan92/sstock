<?php

namespace App\Models;

use App\Enums\StockDocumentType;
use App\Services\LeadTimes;
use Devlib\Engine\LeadTime;
use Devlib\Locale;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'ordered_at', 'ref_no', 'created_by', 'closed_at', 'receiving_id', 'supplier_id', 'qty', 'note'])]
class ReorderOrder extends Model
{
    protected function casts(): array
    {
        return ['ordered_at' => 'date', 'closed_at' => 'datetime', 'qty' => 'float'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Penerimaan sejak tanggal pesan, dalam satuan stok, semua batch produk: pergerakan masuk dari dokumen Penerimaan yang terkonfirmasi dan tidak dibatalkan.
     * Hanya informasi: tidak menutup atau mengubah pesanan (penutupan tetap oleh StockDocuments::confirm). Jumlah yang dipesan (qty, boleh kosong pada baris lama) hanya ditampilkan; tidak dipakai untuk menutup pesanan.
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

    // Keterangan untuk peringatan stok menipis (beranda, laporan Ringkasan Stok, form barang). Pesanan yang masih terbuka ditambah perkiraan tiba (engine LeadTime) bila ada angkanya; $leadTime dari LeadTimes::forMany menghindari query per baris.
    public function note(?LeadTime $leadTime = null): string
    {
        $this->loadMissing('product', 'creator', 'supplier');
        $unit = $this->product->unit;
        $qty = $this->attributes['qty'] ?? null;   // model yang baru dibuat belum membawa kolom yang kosong
        $note = $this->attributes['note'] ?? null;

        // Baris lama tidak punya jumlah / supplier: bagian itu dilewati.
        return ($qty !== null || $this->supplier ? 'Sudah dipesan' : 'Sudah dicatat dipesan').($qty !== null ? ' '.Locale::decimal($qty, 3)." {$unit}" : '').($this->supplier ? " ke {$this->supplier->name}" : '')
            .' pada '.$this->ordered_at->format('d/m/Y').($this->ref_no ? ", ref {$this->ref_no}" : '').($this->creator ? " oleh {$this->creator->name}" : '')
            .'. Diterima sejak dipesan: '.Locale::decimal($this->receivedSince(), 3)." {$unit}.".($note ? " Catatan: {$note}." : '')
            .($this->closed_at === null ? LeadTimes::openOrderSentence($leadTime ?? LeadTimes::for($this->product), $this) : '');
    }
}
