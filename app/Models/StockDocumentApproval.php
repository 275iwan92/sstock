<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Satu keputusan persetujuan (level ke-n, Approve / Reject) pada dokumen stok.
#[Fillable(['stock_document_id', 'level', 'user_id', 'decision', 'decided_at'])]
class StockDocumentApproval extends Model
{
    protected function casts(): array
    {
        return ['decided_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
