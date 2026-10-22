<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/* REMARK
 * Tujuan       : Modul baru Perusahaan (CMP, kop cetak) diberikan RW ke Grup yang sudah menulis modul khusus Supervisor (Penyesuaian Stok, Saldo Awal, Satuan, Kategori, Lokasi Penyimpanan), yaitu Grup tingkat Supervisor.
 *                Grup lain tidak diberi apa pun (Administrator dapat menambahkannya di layar Grup); modul ini tetap tertutup bagi Data Entry karena peran minimalnya Supervisor. Pengguna tanpa Grup memakai bawaan peran (CMP sudah termasuk).
 * Output       : user_group_modules (baris CMP). Tidak mengubah data lain.
 * Dipakai oleh : Modules, GroupController.
 * Status       : Aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        $groups = DB::table('user_group_modules')->whereIn('module', ['ADJ', 'OPN', 'UOM', 'CAT', 'LOC'])->where('access', 'rw')->pluck('group_id')->unique();
        foreach ($groups as $groupId) {
            DB::table('user_group_modules')->insertOrIgnore(['group_id' => $groupId, 'module' => 'CMP', 'access' => 'rw']);
        }
    }

    public function down(): void
    {
        DB::table('user_group_modules')->where('module', 'CMP')->delete();
    }
};
