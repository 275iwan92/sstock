<?php

namespace App\Models;

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
}
