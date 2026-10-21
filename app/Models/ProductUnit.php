<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// 1 {unit} = {factor} satuan stok barang ini (products.unit). Faktor disimpan sebagai string desimal (6 digit) supaya perhitungan tetap presisi.
#[Fillable(['product_id', 'unit', 'factor', 'created_by', 'updated_by'])]
class ProductUnit extends Model
{
    protected function casts(): array
    {
        return ['factor' => 'decimal:6'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unitMaster(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit', 'code');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
