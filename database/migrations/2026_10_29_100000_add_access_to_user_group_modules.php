<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* REMARK
 * Tujuan       : Hak Grup terhadap Modul punya dua tingkat: rw (baca + tulis) atau ro (hanya baca). Baris yang sudah ada tetap rw.
 * Output       : user_group_modules.access.
 * Dipakai oleh : UserGroup, User::canAccess / canWrite, EnsureModuleAccess.
 * Status       : Aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_group_modules', function (Blueprint $table) {
            $table->string('access', 2)->default('rw')->after('module');
        });
    }

    public function down(): void
    {
        Schema::table('user_group_modules', function (Blueprint $table) {
            $table->dropColumn('access');
        });
    }
};
