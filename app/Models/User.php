<?php

namespace App\Models;

use App\Support\Modules;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'password', 'role', 'group_id', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    private ?array $access = null;

    public const ROLE_DATA_ENTRY = 'data_entry';

    public const ROLE_SUPERVISOR = 'supervisor';

    public const ROLE_ADMINISTRATOR = 'administrator';

    // Administrator = semua hak Supervisor + menu Keamanan (kelola pengguna).
    public const ROLES = [self::ROLE_DATA_ENTRY => 'Data Entry', self::ROLE_SUPERVISOR => 'Supervisor', self::ROLE_ADMINISTRATOR => 'Administrator'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isSupervisor(): bool
    {
        return in_array($this->role, [self::ROLE_SUPERVISOR, self::ROLE_ADMINISTRATOR], true);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class, 'group_id');
    }

    // Tingkat akses ke modul: 'rw' | 'ro' | null. Peran harus cukup DAN modul diberikan Grup; tanpa Grup = bawaan peran (rw). Administrator selalu rw.
    public function moduleAccess(string $module): ?string
    {
        $def = Modules::all()[$module] ?? null;
        $level = Modules::levelOf($this);
        if (! $def || ! Modules::levelAllows($def[5], $level)) {
            return null;
        }
        if ($this->isAdministrator()) {
            return 'rw';
        }
        $this->access ??= $this->group?->moduleAccess() ?? array_fill_keys(Modules::defaults($level), 'rw');

        return $this->access[$module] ?? null;
    }

    public function canAccess(string $module): bool
    {
        return $this->moduleAccess($module) !== null;
    }

    public function canWrite(string $module): bool
    {
        return $this->moduleAccess($module) === 'rw';
    }

    public function isAdministrator(): bool
    {
        return $this->role === self::ROLE_ADMINISTRATOR;
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? 'Data Entry';
    }
}
