<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['code', 'name', 'category_id', 'unit', 'location', 'unit_price', 'reorder_point', 'reorder_qty', 'lead_time_days', 'is_active'])]
class Item extends Model
{
    protected function casts(): array
    {
        return [
            'unit_price' => 'float',
            'reorder_point' => 'float',
            'reorder_qty' => 'float',
            'stock_qty' => 'float',
            'avg_cost' => 'float',
            'lead_time_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockDocumentLine::class);
    }

    // Pemesanan ulang yang masih menunggu barang datang (ditutup saat Receiving).
    public function openOrder(): HasOne
    {
        return $this->hasOne(ReorderOrder::class)->whereNull('closed_at')->latestOfMany();
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    // Item aktif yang stoknya menyentuh atau di bawah Reorder Point (ROP 0 = parameter belum diisi, tidak dihitung).
    public function scopeBelowReorderPoint(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('reorder_point', '>', 0)->whereColumn('stock_qty', '<=', 'reorder_point');
    }

    public function isBelowReorderPoint(): bool
    {
        return $this->is_active && $this->reorder_point > 0 && $this->stock_qty <= $this->reorder_point;
    }

    public function stockValue(): float
    {
        return round($this->stock_qty * $this->avg_cost, 2);
    }
}
