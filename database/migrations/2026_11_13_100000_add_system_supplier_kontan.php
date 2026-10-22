<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/* REMARK
 * Tujuan       : Supplier sistem "Kontan" (pembelian tunai / tanpa supplier tetap): kolom suppliers.is_system dan satu baris KONTAN yang aktif.
 *                Idempoten: bila kode KONTAN sudah ada hanya ditandai sistem; data lain tidak disentuh.
 * Output       : kolom suppliers.is_system; baris suppliers code KONTAN.
 * Dipakai oleh : App\Models\Supplier (is_system: tak bisa dinonaktifkan / diganti nama / dihapus, selalu pertama di dropdown).
 * Status       : Aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('suppliers', 'is_system')) {
            Schema::table('suppliers', fn (Blueprint $table) => $table->boolean('is_system')->default(false)->after('is_active'));
        }

        $kontan = DB::table('suppliers')->where('code', 'KONTAN');
        if ($kontan->exists()) {
            $kontan->update(['is_system' => true]);
        } else {
            DB::table('suppliers')->insert(['code' => 'KONTAN', 'name' => 'Kontan', 'is_active' => true, 'is_system' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::table('suppliers', fn (Blueprint $table) => $table->dropColumn('is_system'));
    }
};
