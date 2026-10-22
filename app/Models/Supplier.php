<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

// is_system = supplier bawaan (Kontan): tak bisa dinonaktifkan, diganti nama / kodenya, atau dihapus; selalu pertama di dropdown. Hanya diisi migrasi / seeder (tidak mass-assignable).
// phone = telepon kantor (PSTN); contact_mobile = HP kontak; address = nama jalan; area_level + area_id = wilayah (lihat App\Support\GeoArea).
#[Fillable(['code', 'name', 'phone', 'contact_name', 'contact_mobile', 'address', 'area_level', 'area_id', 'postal_code', 'is_active'])]
class Supplier extends Model
{
    public const SYSTEM_MESSAGE = 'Kontan adalah supplier sistem: tidak bisa dinonaktifkan, diganti nama, atau dihapus.';

    protected static function booted(): void
    {
        static::deleting(fn (Supplier $s) => $s->is_system ? throw new LogicException(self::SYSTEM_MESSAGE) : null);
    }

    // Urutan semua dropdown supplier: supplier sistem (Kontan) dulu, lalu nama.
    public function scopeForSelect(Builder $query): Builder
    {
        return $query->orderByDesc('is_system')->orderBy('name');
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_system' => 'boolean', 'area_id' => 'integer'];
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
