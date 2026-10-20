<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/* REMARK
 * Tujuan       : Kode barang otomatis dari awalan kategori (3 karakter) + tanggal kedaluwarsa barang, dan master Lokasi Penyimpanan.
 *                items.location tetap menyimpan KODE lokasi (pola sama dengan items.unit); teks lokasi lama dipindah ke master.
 * Output       : categories.is_active, items.expiry_date, locations.
 * Dipakai oleh : CategoryController, LocationController, ItemController.
 * Status       : Aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', fn (Blueprint $t) => $t->boolean('is_active')->default(true)->after('name'));
        Schema::table('items', fn (Blueprint $t) => $t->date('expiry_date')->nullable()->after('location'));
        Schema::create('locations', function (Blueprint $t) {
            $t->id();
            $t->string('code', 50)->unique();
            $t->string('name', 100);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        foreach (DB::table('items')->whereNotNull('location')->where('location', '!=', '')->distinct()->pluck('location') as $code) {
            DB::table('locations')->insert(['code' => $code, 'name' => $code, 'created_at' => now(), 'updated_at' => now()]);
        }

        // Modul baru (Kategori, Lokasi Penyimpanan) diberikan ke Grup yang sudah memegang modul Satuan, dengan akses yang sama.
        foreach (DB::table('user_group_modules')->where('module', 'UOM')->get() as $g) {
            foreach (['CAT', 'LOC'] as $module) {
                DB::table('user_group_modules')->insertOrIgnore(['group_id' => $g->group_id, 'module' => $module, 'access' => $g->access]);
            }
        }
    }

    public function down(): void
    {
        DB::table('user_group_modules')->whereIn('module', ['CAT', 'LOC'])->delete();
        Schema::dropIfExists('locations');
        Schema::table('items', fn (Blueprint $t) => $t->dropColumn('expiry_date'));
        Schema::table('categories', fn (Blueprint $t) => $t->dropColumn('is_active'));
    }
};
