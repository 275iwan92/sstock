<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/* REMARK
 * Tujuan       : Modul baru Data Contoh (SMP, menu Keamanan) diberikan ke Grup yang sudah memegang Daftar Modul (MOD), dengan akses yang sama.
 * Output       : user_group_modules (baris SMP).
 * Dipakai oleh : Modules, GroupController.
 * Status       : Aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('user_group_modules')->where('module', 'MOD')->get() as $g) {
            DB::table('user_group_modules')->insertOrIgnore(['group_id' => $g->group_id, 'module' => 'SMP', 'access' => $g->access]);
        }
    }

    public function down(): void
    {
        DB::table('user_group_modules')->where('module', 'SMP')->delete();
    }
};
