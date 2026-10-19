<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* REMARK
 * Tujuan       : Skema inti Simple Stock: master (kategori, supplier, item), dokumen stok (saldo awal, masuk, keluar, penyesuaian) dan buku besar pergerakan stok.
 * Sumber hulu  : users (pembuat / penyetuju dokumen).
 * Output       : categories, suppliers, items, stock_documents, stock_document_lines, stock_movements.
 * Dipakai oleh : App\Models\*, App\Services\StockLedger.
 * Status       : Aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 150);
            $table->string('phone', 40)->nullable();
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // stock_qty / avg_cost = saldo berjalan (cache dari buku besar stock_movements), diubah hanya oleh StockLedger.
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 150);
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('unit', 20);
            $table->string('location', 50)->nullable();
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('reorder_point', 15, 3)->default(0);
            $table->decimal('reorder_qty', 15, 3)->default(0);
            $table->unsignedSmallInteger('lead_time_days')->default(0);
            $table->decimal('stock_qty', 15, 3)->default(0);
            $table->decimal('avg_cost', 15, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('name');
            $table->index('category_id');
        });

        // type: OPENING | IN | OUT | ADJ. status: confirmed (OPENING/IN/OUT) | draft, approval, approved, rejected (ADJ).
        Schema::create('stock_documents', function (Blueprint $table) {
            $table->id();
            $table->string('doc_no', 30)->unique();
            $table->string('type', 10);
            $table->dateTime('doc_at');
            $table->string('status', 12);
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->restrictOnDelete();
            $table->string('ref_no', 60)->nullable();
            $table->string('recipient', 100)->nullable();
            $table->string('department', 100)->nullable();
            $table->string('reason')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();
            $table->index(['type', 'doc_at']);
            $table->index('status');
        });

        // qty: jumlah (OPENING/IN/OUT) atau selisih bertanda (ADJ = counted_qty - system_qty).
        Schema::create('stock_document_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_document_id')->constrained('stock_documents')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->decimal('qty', 15, 3);
            $table->decimal('unit_cost', 15, 4)->nullable();
            $table->decimal('system_qty', 15, 3)->nullable();
            $table->decimal('counted_qty', 15, 3)->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
            $table->index('item_id');
        });

        // Buku besar: baris tidak pernah diubah / dihapus; koreksi lewat penyesuaian.
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('stock_document_id')->constrained('stock_documents')->restrictOnDelete();
            $table->string('type', 10);
            $table->dateTime('moved_at');
            $table->decimal('qty', 15, 3);
            $table->decimal('unit_cost', 15, 4);
            $table->decimal('balance_qty', 15, 3);
            $table->decimal('balance_avg_cost', 15, 4);
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->index(['item_id', 'moved_at', 'id']);
            $table->index(['type', 'moved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_document_lines');
        Schema::dropIfExists('stock_documents');
        Schema::dropIfExists('items');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('categories');
    }
};
