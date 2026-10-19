<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* REMARK
 * Tujuan       : Catatan pemesanan ulang ke supplier per barang: tanggal pesan + nomor referensi (PO), terbuka sampai barangnya diterima (Receiving).
 * Output       : reorder_orders (closed_at kosong = pesanan masih terbuka).
 * Dipakai oleh : ReorderOrderController, StockDocuments (menutup saat Receiving), opening screen.
 * Status       : Aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reorder_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->date('ordered_at');
            $table->string('ref_no', 60);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('receiving_id')->nullable()->constrained('stock_documents')->nullOnDelete();
            $table->timestamps();
            $table->index(['item_id', 'closed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reorder_orders');
    }
};
