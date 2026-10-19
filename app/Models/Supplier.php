<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// phone = telepon kantor (PSTN); contact_mobile = HP kontak; address = nama jalan; area_level + area_id = wilayah (lihat App\Support\GeoArea).
#[Fillable(['code', 'name', 'phone', 'contact_name', 'contact_mobile', 'address', 'area_level', 'area_id', 'postal_code', 'is_active'])]
class Supplier extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'area_id' => 'integer'];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(StockDocument::class);
    }

    // Kunci wilayah "LEVEL:id" untuk GeoArea / kotak cari.
    public function areaKey(): ?string
    {
        return $this->area_level && $this->area_id ? $this->area_level.':'.$this->area_id : null;
    }
}
