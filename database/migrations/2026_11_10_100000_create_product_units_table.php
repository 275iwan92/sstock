<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/* REMARK
 * Tujuan       : Satuan masuk per barang: kemasan yang sah saat barang DITERIMA (mis. dus = 12, lusin = 12) beserta faktornya ke satuan stok barang itu (1 {unit} = {factor} {products.unit}).
 *                Satuan stok sendiri tidak punya baris (faktor 1 tersirat). Konversi global (unit_conversions) tetap sebagai cadangan; migrasi ini hanya menambah tabel, tidak mengubah atau menghapus data.
 *                created_by / updated_by + updated_at mencatat siapa membuat dan terakhir mengubah faktor.
 * Output       : product_units (unik product_id + unit; unit = foreign key ke units.code; faktor > 0 dijaga CHECK di MySQL / MariaDB).
 * Dipakai oleh : ProductUnits (layanan), UnitConverter, StockDocuments (Penerimaan), ProductController (bagian Satuan masuk).
 * Status       : Aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('unit', 20);
            $table->decimal('factor', 18, 6);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['product_id', 'unit']);
            $table->foreign('unit')->references('code')->on('units')->restrictOnDelete();
        });

        // shortcut: CHECK hanya di MySQL / MariaDB (seperti items_stock_qty_nonneg); SQLite (tes) tetap dijaga ProductUnits::save.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE product_units ADD CONSTRAINT product_units_factor_positive CHECK (factor > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_units');
    }
};
