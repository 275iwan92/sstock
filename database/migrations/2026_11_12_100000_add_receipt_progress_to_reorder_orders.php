<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/* REMARK
 * Tujuan       : Pesanan yang punya jumlah ditutup hanya saat jumlah yang diterima memenuhi jumlah dipesan (penerimaan sebagian = "diterima sebagian", tetap terbuka); pesanan yang tak akan dipenuhi penuh bisa ditutup manual.
 * Output       : reorder_orders.received_qty (jumlah teralokasi dari Penerimaan, satuan stok), first_received_at (penerimaan pertama; dasar lead time), close_reason ('received' | 'manual').
 *                Hanya menambah kolom; baris yang sudah tertutup diisi: first_received_at = closed_at, close_reason = 'received', received_qty = qty (0 bila tanpa jumlah). Tidak ada data yang diubah atau dihapus.
 * Dipakai oleh : ReorderOrders::reconcile, LeadTimes, ReorderOrder::note().
 * Status       : Aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reorder_orders', function (Blueprint $table) {
            $table->decimal('received_qty', 15, 3)->default(0);
            $table->timestamp('first_received_at')->nullable();
            $table->string('close_reason', 20)->nullable();
        });

        DB::table('reorder_orders')->whereNotNull('closed_at')->update([
            'first_received_at' => DB::raw('closed_at'), 'close_reason' => 'received', 'received_qty' => DB::raw('coalesce(qty, 0)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('reorder_orders', function (Blueprint $table) {
            $table->dropColumn(['received_qty', 'first_received_at', 'close_reason']);
        });
    }
};
