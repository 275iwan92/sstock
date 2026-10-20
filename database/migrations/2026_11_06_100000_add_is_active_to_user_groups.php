<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* REMARK
 * Tujuan       : Grup pengguna adalah data master: tidak dihapus, hanya Activate / Deactivate. Grup nonaktif tidak bisa dipilih untuk pengguna (anggota yang sudah ada tidak berubah).
 * Output       : user_groups.is_active (bawaan aktif).
 * Dipakai oleh : GroupController (toggle, activate / deactivate), UserController (daftar grup yang bisa dipilih).
 * Status       : Aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_groups', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('user_groups', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
