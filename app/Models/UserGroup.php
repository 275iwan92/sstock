<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

// Grup pengguna: modul (kode menu, lihat App\Support\Modules) yang boleh dibuka anggotanya, tiap modul rw (baca + tulis) atau ro (hanya baca).
#[Fillable(['code', 'name'])]
class UserGroup extends Model
{
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'group_id');
    }

    /** @return array<string, string> kode modul => 'rw'|'ro' */
    public function moduleAccess(): array
    {
        return DB::table('user_group_modules')->where('group_id', $this->id)->pluck('access', 'module')->all();
    }

    /** @return array<int, string> kode modul grup ini */
    public function moduleCodes(): array
    {
        return array_keys($this->moduleAccess());
    }

    /** @param  array<string, string>  $access  kode modul => 'rw'|'ro' (modul yang tidak ada = tanpa akses) */
    public function syncAccess(array $access): void
    {
        DB::table('user_group_modules')->where('group_id', $this->id)->delete();
        DB::table('user_group_modules')->insert(array_map(fn ($m, $a) => ['group_id' => $this->id, 'module' => $m, 'access' => $a], array_keys($access), $access));
    }
}
