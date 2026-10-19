<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* REMARK
 * Tujuan       : Dokumen stok punya siklus Draf > Confirm (diproses ke stok) > Cancel (dibatalkan dengan alasan, stok dikembalikan lewat baris pembalik di buku besar).
 *                Dokumen lama berstatus confirmed tetap sah dan kini bisa dibatalkan.
 * Output       : stock_documents.confirmed_by/at + cancelled_by/at + cancel_reason; stock_movements.reversal_of (baris pembalik menunjuk baris asal).
 * Dipakai oleh : StockDocuments (confirm, cancel), StockLedger (reverse), laporan Kartu Stok.
 * Status       : Aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_documents', function (Blueprint $table) {
            $table->foreignId('confirmed_by')->nullable()->after('approved_at')->constrained('users')->restrictOnDelete();
            $table->dateTime('confirmed_at')->nullable()->after('confirmed_by');
            $table->foreignId('cancelled_by')->nullable()->after('confirmed_at')->constrained('users')->restrictOnDelete();
            $table->dateTime('cancelled_at')->nullable()->after('cancelled_by');
            $table->string('cancel_reason')->nullable()->after('cancelled_at');
        });
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('reversal_of')->nullable()->after('user_id')->constrained('stock_movements')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reversal_of');
        });
        Schema::table('stock_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropConstrainedForeignId('confirmed_by');
            $table->dropColumn(['confirmed_at', 'cancelled_at', 'cancel_reason']);
        });
    }
};
