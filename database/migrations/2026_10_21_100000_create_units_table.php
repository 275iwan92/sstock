<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/* REMARK
 * Tujuan       : Master Satuan (unit of measure). items.unit tetap berisi kode satuan, kini dijaga foreign key ke units.code.
 * Sumber hulu  : daftar satuan baku di bawah + satuan yang sudah dipakai item (agar data lama tidak putus).
 * Output       : tabel units; foreign key items.unit > units.code.
 * Dipakai oleh : App\Models\Unit, UnitController, ItemRequest, form Item.
 * Status       : Aktif.
 */
return new class extends Migration
{
    private const DEFAULTS = [
        'pcs' => 'Pieces', 'box' => 'Box', 'set' => 'Set', 'pack' => 'Pack', 'roll' => 'Roll', 'pair' => 'Pair', 'unit' => 'Unit',
        'sheet' => 'Sheet', 'kg' => 'Kilogram', 'gram' => 'Gram', 'meter' => 'Meter', 'liter' => 'Liter', 'can' => 'Can', 'bottle' => 'Bottle',
    ];

    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $rows = self::DEFAULTS;
        foreach (DB::table('items')->distinct()->pluck('unit') as $used) {
            $rows[$used] ??= ucfirst($used);
        }
        DB::table('units')->insert(array_map(fn ($code, $name) => ['code' => $code, 'name' => $name, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()], array_keys($rows), $rows));

        Schema::table('items', function (Blueprint $table) {
            $table->foreign('unit')->references('code')->on('units')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['unit']);
        });
        Schema::dropIfExists('units');
    }
};
