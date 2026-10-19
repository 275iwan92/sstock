<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* REMARK
 * Tujuan       : Konversi antar satuan, berlaku sama untuk semua item: 1 {from_unit} = {factor} {to_unit} (mis. 1 box = 12 pcs). Arah sebaliknya dihitung (1 / factor), tidak disimpan.
 * Sumber hulu  : units.code.
 * Output       : tabel unit_conversions (pasangan from/to unik).
 * Dipakai oleh : App\Models\UnitConversion, UnitController, App\Support\UnitConverter.
 * Status       : Aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_conversions', function (Blueprint $table) {
            $table->id();
            $table->string('from_unit', 20);
            $table->string('to_unit', 20);
            $table->decimal('factor', 18, 6);
            $table->timestamps();
            $table->unique(['from_unit', 'to_unit']);
            $table->foreign('from_unit')->references('code')->on('units')->cascadeOnDelete();
            $table->foreign('to_unit')->references('code')->on('units')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_conversions');
    }
};
