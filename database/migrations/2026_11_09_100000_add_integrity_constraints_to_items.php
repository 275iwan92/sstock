<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/* REMARK
 * Tujuan       : Aturan batch yang selama ini hanya dijaga aplikasi kini juga dijaga database: stok batch tidak boleh negatif (CHECK), satu produk satu batch per tanggal kedaluwarsa (unik; NULL boleh ganda),
 *                dan indeks expiry_date untuk peringatan / laporan kedaluwarsa.
 *                Aman pada data yang sudah ada: bila ada baris yang melanggar, migrasi berhenti dengan galat yang menyebut barisnya; tidak ada data yang dihapus atau diubah.
 * Output       : items_stock_qty_nonneg (CHECK, hanya MySQL / MariaDB), items_product_id_expiry_date_unique, items_expiry_date_index.
 * Dipakai oleh : Product::batchFor (satu batch per tanggal), StockLedger (stok tidak pernah negatif).
 * Status       : Aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->assertNoViolations();

        Schema::table('items', function (Blueprint $table) {
            $table->unique(['product_id', 'expiry_date']);
            $table->index('expiry_date');
        });

        // shortcut: CHECK hanya di MySQL 8.0.16+ / MariaDB 10.2.1+ (versi lebih lama menerima sintaks tapi mengabaikannya); SQLite (tes) tidak mendapat CHECK, aplikasi tetap menjaganya di StockLedger. Upgrade bila SQLite dipakai produksi.
        if ($this->supportsCheck()) {
            DB::statement('ALTER TABLE items ADD CONSTRAINT items_stock_qty_nonneg CHECK (stock_qty >= 0)');
        }
    }

    public function down(): void
    {
        if ($this->supportsCheck()) {
            DB::statement('ALTER TABLE items DROP CHECK items_stock_qty_nonneg');
        }
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['expiry_date']);
            $table->dropUnique(['product_id', 'expiry_date']);
        });
    }

    private function supportsCheck(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    private function assertNoViolations(): void
    {
        $negative = DB::table('items')->where('stock_qty', '<', 0)->orderBy('id')->get(['id', 'code', 'stock_qty']);
        $duplicate = DB::table('items')->whereNotNull('product_id')->whereNotNull('expiry_date')
            ->groupBy('product_id', 'expiry_date')->havingRaw('count(*) > 1')->orderBy('product_id')->get(['product_id', 'expiry_date', DB::raw('count(*) as total'), DB::raw('min(code) as sample_code')]);

        $problems = [];
        foreach ($negative as $row) {
            $problems[] = "stok negatif: item #{$row->id} {$row->code} (stock_qty {$row->stock_qty})";
        }
        foreach ($duplicate as $row) {
            $problems[] = "batch ganda: produk #{$row->product_id} tanggal kedaluwarsa {$row->expiry_date} dipakai {$row->total} batch (mis. {$row->sample_code})";
        }
        if ($problems) {
            throw new RuntimeException("Migrasi dihentikan, data tidak diubah. Perbaiki baris berikut lalu jalankan migrate lagi:\n- ".implode("\n- ", $problems));
        }
    }
};
