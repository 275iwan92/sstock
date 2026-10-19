<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Database\Seeder;

/* REMARK
 * Tujuan       : Akun awal untuk dua peran: Supervisor dan Data Entry, plus akun admin. Ganti password sebelum dipakai di luar mesin pengembangan.
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
            // Password akun uji (permintaan pemilik untuk pengujian); ganti sebelum produksi.
            ['username' => 'supervisor', 'name' => 'Supervisor Gudang', 'email' => 'supervisor@sstock.test', 'role' => User::ROLE_SUPERVISOR, 'password' => '1qa2ws3ed'],
            ['username' => 'entry', 'name' => 'Staf Data Entry', 'email' => 'entry@sstock.test', 'role' => User::ROLE_DATA_ENTRY, 'password' => '12345678'],
            // Akun admin untuk pengembangan dan UAT nanti (permintaan pemilik): peran Administrator (hak Supervisor + menu Keamanan), password lemah sengaja. Hapus / ganti sebelum produksi.
            ['username' => 'admin', 'name' => 'Admin UAT', 'email' => 'admin@sstock.test', 'role' => User::ROLE_ADMINISTRATOR, 'password' => 'admin'],
        ];

        // Grup bawaan dibuat migrasi; akun contoh masuk grup sesuai perannya.
        $groups = UserGroup::query()->pluck('id', 'code');
        $groups = ['data_entry' => $groups['DATAENTRY'] ?? null, 'supervisor' => $groups['SUPERVISOR'] ?? null, 'administrator' => $groups['ADMIN'] ?? null];

        foreach ($users as $u) {
            User::updateOrCreate(['username' => $u['username']], $u + ['password' => self::PASSWORD, 'is_active' => true, 'group_id' => $groups[$u['role']] ?? null]);
        }
    }
}
