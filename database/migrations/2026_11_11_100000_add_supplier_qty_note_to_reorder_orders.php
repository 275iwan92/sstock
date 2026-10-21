<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* REMARK
 * Tujuan       : Catatan pemesanan ulang sederhana (bukan PO): ke supplier mana, berapa (dalam satuan stok barang), dan catatan, selain tanggal + referensi yang sudah ada.
 *                Hanya menambah kolom yang boleh kosong; baris lama tidak berubah (tanpa supplier / jumlah). Aturan penutupan pesanan tidak terpengaruh.
 * Output       : reorder_orders.supplier_id (FK suppliers, null bila supplier dihapus), qty decimal(15,3), note.
 * Dipakai oleh : ReorderOrderController, ReorderOrder::note().
 * Status       : Aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reorder_orders', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->decimal('qty', 15, 3)->nullable();
            $table->string('note', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('reorder_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_id');
            $table->dropColumn(['qty', 'note']);
        });
    }
};
