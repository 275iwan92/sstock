<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* REMARK
 * Tujuan       : Mencatat siapa yang membuat dan terakhir mengubah faktor konversi satuan (faktor berlaku global, jadi perubahannya harus bisa ditelusuri).
 * Output       : kolom unit_conversions.created_by / updated_by (boleh kosong untuk baris lama).
 * Dipakai oleh : StockDocumentController@conversion, UnitController (sinkron konversi).
 * Status       : Aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_conversions', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('unit_conversions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('updated_by');
        });
    }
};
