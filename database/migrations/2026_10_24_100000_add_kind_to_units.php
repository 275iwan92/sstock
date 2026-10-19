<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/* REMARK
 * Tujuan       : Jenis satuan (count, weight, length, volume, area, other) supaya satuan mudah dikenali di grid lewat warna kotaknya (mis. satuan berat punya warna sendiri).
 * Sumber hulu  : units.code (satuan baku diberi jenis awal; satuan buatan user bawaannya count).
 * Output       : kolom units.kind.
 * Dipakai oleh : App\Models\Unit, form + grid Units.
 * Status       : Aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->string('kind', 12)->default('count')->after('name');
        });

        foreach (['weight' => ['kg', 'gram'], 'length' => ['meter'], 'volume' => ['liter']] as $kind => $codes) {
            DB::table('units')->whereIn('code', $codes)->update(['kind' => $kind]);
        }
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn('kind');
        });
    }
};
