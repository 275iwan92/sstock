<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* REMARK
 * Tujuan       : Jejak audit input hasil hitung: entered_by / entered_at (akun yang menyimpan, selain teks counted_by = penghitung di kertas) dan rev (naik tiap simpan; penjaga bila dua orang mengisi baris yang sama). Hanya kolom baru.
 * Output       : stock_count_lines.entered_by, entered_at, rev.
 * Dipakai oleh : App\Services\StockCounts::saveLine, layar input / tinjau, Berita Acara.
 * Status       : Aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_count_lines', function (Blueprint $table) {
            $table->foreignId('entered_by')->nullable()->after('counted_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('entered_at')->nullable()->after('entered_by');
            $table->unsignedInteger('rev')->default(0)->after('entered_at');
        });
    }

    public function down(): void
    {
        Schema::table('stock_count_lines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('entered_by');
            $table->dropColumn(['entered_at', 'rev']);
        });
    }
};
