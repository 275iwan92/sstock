<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* REMARK
 * Tujuan       : Modul Stok Opname (penghitungan fisik rutin): kepala opname + satu baris per batch (snapshot stok sistem saat cut-off, hasil hitung, hitung ulang, alasan).
 *                Hanya tabel baru; tidak mengubah atau menghapus data yang ada. Stok baru berubah saat opname disetujui, lewat satu dokumen Penyesuaian (adjustment_document_id).
 * Output       : stock_counts, stock_count_lines.
 * Dipakai oleh : App\Models\StockCount, App\Models\StockCountLine, App\Services\StockCounts.
 * Status       : Aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        // status: counting | review | done | cancelled. scope_type: all | category | location (scope_value = kode kategori / kode lokasi).
        Schema::create('stock_counts', function (Blueprint $table) {
            $table->id();
            $table->string('doc_no', 30)->unique();
            $table->date('count_date');
            $table->string('scope_type', 10)->default('all');
            $table->string('scope_value', 50)->nullable();
            $table->string('status', 12)->default('counting');
            $table->boolean('blind')->default(true);
            $table->boolean('lock_transactions')->default(true);
            $table->decimal('threshold_pct', 5, 2)->default(5);
            $table->dateTime('cutoff_at');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('adjustment_document_id')->nullable()->constrained('stock_documents')->restrictOnDelete();
            $table->foreignId('unlocked_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('unlocked_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('cancelled_at')->nullable();
            $table->string('review_note')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index('status');
            $table->index('count_date');
        });

        // counted_qty kosong = belum dihitung (bukan nol); skipped = "Tidak dihitung" (catatan wajib); hasil akhir = recount_qty bila ada.
        Schema::create('stock_count_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_count_id')->constrained('stock_counts')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->decimal('system_qty', 15, 3);
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->decimal('counted_qty', 15, 3)->nullable();
            $table->decimal('recount_qty', 15, 3)->nullable();
            $table->string('counted_by', 100)->nullable();
            $table->boolean('skipped')->default(false);
            $table->string('reason')->nullable();
            $table->string('note')->nullable();
            $table->boolean('added_manually')->default(false);
            $table->timestamps();
            $table->unique(['stock_count_id', 'item_id']);
            $table->index('item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_count_lines');
        Schema::dropIfExists('stock_counts');
    }
};
