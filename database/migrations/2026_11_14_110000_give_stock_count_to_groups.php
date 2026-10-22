<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/* REMARK
 * Tujuan       : Modul baru Stok Opname (SO) diberikan ke Grup yang sudah menulis (RW) Penyesuaian Stok (ADJ) atau Penerimaan Barang (RCV): Grup Supervisor (ADJ) mendapat SO untuk memulai / meninjau,
 *                Grup Data Entry (RCV) mendapat SO untuk mengisi hasil hitung. Akses diberikan RW; hak memulai / menyetujui tetap dikunci peran Supervisor di kode, bukan oleh Grup.
 *                Grup yang hanya RO atau tanpa kedua modul itu tidak diberi apa pun (Administrator dapat menambahkannya di layar Grup). Pengguna tanpa Grup memakai bawaan peran (SO sudah termasuk).
 * Output       : user_group_modules (baris SO).
 * Dipakai oleh : Modules, GroupController.
 * Status       : Aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        $groups = DB::table('user_group_modules')->whereIn('module', ['ADJ', 'RCV'])->where('access', 'rw')->pluck('group_id')->unique();
        foreach ($groups as $groupId) {
            DB::table('user_group_modules')->insertOrIgnore(['group_id' => $groupId, 'module' => 'SO', 'access' => 'rw']);
        }
    }

    public function down(): void
    {
        DB::table('user_group_modules')->where('module', 'SO')->delete();
    }
};
