<?php

namespace App\Models;

use App\Enums\StockDocumentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['doc_no', 'type', 'doc_at', 'status', 'supplier_id', 'ref_no', 'recipient', 'recipient_id', 'department', 'department_id', 'reason', 'note', 'created_by', 'approved_by', 'approved_at'])]
class StockDocument extends Model
{
    protected function casts(): array
    {
        return [
            'type' => StockDocumentType::class,
            'doc_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockDocumentLine::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function recipientMaster(): BelongsTo
    {
        return $this->belongsTo(Recipient::class, 'recipient_id');
    }

    public function departmentMaster(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
