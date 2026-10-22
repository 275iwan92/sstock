<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* REMARK
 * Tujuan       : Supervisor boleh mengunci kembali transaksi sesudah membuka kunci: tercatat siapa dan kapan (locked_by / locked_at), melengkapi unlocked_by / unlocked_at. Hanya kolom baru.
 * Output       : stock_counts.locked_by, stock_counts.locked_at.
 * Dipakai oleh : App\Services\StockCounts::lock, stock-counts/header.
 * Status       : Aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_counts', function (Blueprint $table) {
            $table->foreignId('locked_by')->nullable()->after('unlocked_at')->constrained('users')->restrictOnDelete();
            $table->dateTime('locked_at')->nullable()->after('locked_by');
        });
    }

    public function down(): void
    {
        Schema::table('stock_counts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('locked_by');
            $table->dropColumn('locked_at');
        });
    }
};
