<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/* REMARK
 * Tujuan       : Akun awal untuk dua peran: Supervisor dan Data Entry. Ganti password sebelum dipakai di luar mesin pengembangan.
 * Output       : tabel users (username, peran).
 * Dipakai oleh : DatabaseSeeder.
 * Status       : Aktif (data uji lokal).
 */
class UserSeeder extends Seeder
{
    private const PASSWORD = 'sstock-dev-2026';

    public function run(): void
    {
        $users = [
            ['username' => 'supervisor', 'name' => 'Supervisor Gudang', 'email' => 'supervisor@sstock.test', 'role' => User::ROLE_SUPERVISOR],
            ['username' => 'entry', 'name' => 'Staff Data Entry', 'email' => 'entry@sstock.test', 'role' => User::ROLE_DATA_ENTRY],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(['username' => $u['username']], $u + ['password' => self::PASSWORD, 'is_active' => true]);
        }
    }
}
