<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* REMARK
 * Tujuan       : Penerimaan barang boleh dicatat dalam satuan lain dari satuan stok item (mis. 5 box untuk item berstok pcs). Baris menyimpan apa yang diketik (entered_*);
 *                kolom qty / unit_cost tetap dalam satuan stok dan itulah yang masuk ke buku besar.
 * Sumber hulu  : units, unit_conversions (UnitConverter).
 * Output       : kolom entered_qty, entered_unit, entered_unit_cost di stock_document_lines (kosong untuk baris yang tidak memakai konversi).
 * Dipakai oleh : StockDocuments::writeLines, form Receiving, laporan Receiving.
 * Status       : Aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_document_lines', function (Blueprint $table) {
            $table->decimal('entered_qty', 15, 3)->nullable()->after('note');
            $table->string('entered_unit', 20)->nullable()->after('entered_qty');
            $table->decimal('entered_unit_cost', 15, 4)->nullable()->after('entered_unit');
            $table->foreign('entered_unit')->references('code')->on('units')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_document_lines', function (Blueprint $table) {
            $table->dropForeign(['entered_unit']);
            $table->dropColumn(['entered_qty', 'entered_unit', 'entered_unit_cost']);
        });
    }
};
