<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/* REMARK
 * Tujuan       : Modul baru Laporan Kedaluwarsa (R-EXP) diberikan ke Grup yang sudah memegang Ringkasan Stok (R-SUM), dengan akses yang sama.
 * Output       : user_group_modules (baris R-EXP).
 * Dipakai oleh : Modules, GroupController.
 * Status       : Aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('user_group_modules')->where('module', 'R-SUM')->get() as $g) {
            DB::table('user_group_modules')->insertOrIgnore(['group_id' => $g->group_id, 'module' => 'R-EXP', 'access' => $g->access]);
        }
    }

    public function down(): void
    {
        DB::table('user_group_modules')->where('module', 'R-EXP')->delete();
    }
};
