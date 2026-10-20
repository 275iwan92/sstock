<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* REMARK
 * Tujuan       : Saat dokumen yang sudah diproses dibatalkan, dokumen ADJUSTMENT pembalik dibuat OTOMATIS dan menunjuk dokumen asalnya (record tersimpan, bisa dicetak; pembalikan stok tetap baris reversal_of di buku besar).
 * Output       : stock_documents.reversal_of_document_id.
 * Dipakai oleh : StockDocuments::finishCancel, StockDocumentController (riwayat, tombol Cancel disembunyikan untuk dokumen pembalik).
 * Status       : Aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_documents', function (Blueprint $table) {
            $table->foreignId('reversal_of_document_id')->nullable()->after('cancel_requested_at')->constrained('stock_documents')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reversal_of_document_id');
        });
    }
};
