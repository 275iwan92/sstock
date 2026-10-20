<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* REMARK
 * Tujuan       : Jejak authority bertingkat: satu baris per keputusan (level, pemutus, Approve / Reject, waktu) pada dokumen yang menunggu persetujuan (Penyesuaian Stok). Dokumen disetujui penuh setelah semua tingkat di config sstock.approval menyetujui.
 * Output       : tabel stock_document_approvals.
 * Dipakai oleh : StockDocuments (approve, reject), StockDocumentController (log form).
 * Status       : Aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_document_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_document_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('level');
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('decision', 10);   // approved | rejected
            $table->dateTime('decided_at');
            $table->timestamps();
            $table->index(['stock_document_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_document_approvals');
    }
};
