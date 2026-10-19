<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* REMARK
 * Tujuan       : Pembatalan dokumen yang sudah diproses diajukan pengguna (dengan alasan) dan menunggu persetujuan Supervisor: status cancel_pending, pengaju dan waktunya dicatat.
 * Output       : stock_documents.cancel_requested_by / cancel_requested_at.
 * Dipakai oleh : StockDocuments (requestCancel, approveCancel, rejectCancel).
 * Status       : Aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_documents', function (Blueprint $table) {
            $table->foreignId('cancel_requested_by')->nullable()->after('cancel_reason')->constrained('users')->restrictOnDelete();
            $table->dateTime('cancel_requested_at')->nullable()->after('cancel_requested_by');
        });
    }

    public function down(): void
    {
        Schema::table('stock_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancel_requested_by');
            $table->dropColumn('cancel_requested_at');
        });
    }
};
