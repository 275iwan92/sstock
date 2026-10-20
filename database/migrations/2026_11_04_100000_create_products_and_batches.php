<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/* REMARK
 * Tujuan       : Model dua tingkat: Barang (produk / group, kode stabil tanpa tanggal, misalnya ATK-0008) dan Batch (baris items; barang berkedaluwarsa punya satu batch per tanggal, kode ATK-0008-260315).
 *                Nama, kategori, satuan, lokasi, harga, titik pesan ulang, jumlah pesan ulang, dan lead time milik produk (satu tempat) dan disalin ke batch-nya oleh Product::propagate.
 *                Pesan ulang kini per produk: reorder_orders.item_id diganti product_id. Barang lama jadi satu produk (barang kedaluwarsa dengan kode berakhiran -NNNN digabung ke kode induknya).
 * Output       : products, items.product_id, reorder_orders.product_id.
 * Dipakai oleh : ProductController, StockDocuments, StockLedger, laporan, beranda.
 * Status       : Aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
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
            $table->boolean('tracks_expiry')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('name');
        });
        Schema::table('items', fn (Blueprint $table) => $table->foreignId('product_id')->nullable()->after('id')->constrained('products')->restrictOnDelete());
        Schema::table('reorder_orders', fn (Blueprint $table) => $table->foreignId('product_id')->nullable()->after('id')->constrained('products')->restrictOnDelete());

        // Barang lama -> produk. Barang berkedaluwarsa berkode "...-NNNN" digabung ke kode induknya dan kodenya menjadi {induk}-YYMMDD.
        $products = [];
        foreach (DB::table('items')->orderBy('id')->get() as $i) {
            $dated = $i->expiry_date !== null;
            $base = $dated && preg_match('/^(.+)-\d{4}$/', $i->code, $m) ? $m[1] : $i->code;
            $products[$base] ??= DB::table('products')->insertGetId([
                'code' => $base, 'name' => $i->name, 'category_id' => $i->category_id, 'unit' => $i->unit, 'location' => $i->location, 'unit_price' => $i->unit_price,
                'reorder_point' => $i->reorder_point, 'reorder_qty' => $i->reorder_qty, 'lead_time_days' => $i->lead_time_days, 'tracks_expiry' => $dated, 'is_active' => $i->is_active,
                'created_at' => $i->created_at, 'updated_at' => $i->updated_at,
            ]);
            DB::table('items')->where('id', $i->id)->update(['product_id' => $products[$base]] + ($dated ? ['code' => $base.'-'.date('ymd', strtotime($i->expiry_date))] : []));
        }
        foreach (DB::table('reorder_orders')->get() as $o) {
            DB::table('reorder_orders')->where('id', $o->id)->update(['product_id' => DB::table('items')->where('id', $o->item_id)->value('product_id')]);
        }

        Schema::table('reorder_orders', fn (Blueprint $table) => $table->dropForeign(['item_id']));
        Schema::table('reorder_orders', fn (Blueprint $table) => $table->dropIndex(['item_id', 'closed_at']));
        Schema::table('reorder_orders', fn (Blueprint $table) => $table->dropColumn('item_id'));
        Schema::table('reorder_orders', fn (Blueprint $table) => $table->index(['product_id', 'closed_at']));
    }

    public function down(): void
    {
        // Pembalikan kasar (dipakai tes dan rollback pengembangan): data produk / batch tidak dipulihkan ke bentuk lama.
        Schema::table('reorder_orders', fn (Blueprint $table) => $table->dropForeign(['product_id']));
        Schema::table('reorder_orders', fn (Blueprint $table) => $table->dropIndex(['product_id', 'closed_at']));
        Schema::table('reorder_orders', fn (Blueprint $table) => $table->dropColumn('product_id'));
        Schema::table('reorder_orders', fn (Blueprint $table) => $table->unsignedBigInteger('item_id')->nullable()->after('id'));
        Schema::table('items', fn (Blueprint $table) => $table->dropForeign(['product_id']));
        Schema::table('items', fn (Blueprint $table) => $table->dropColumn('product_id'));
        Schema::dropIfExists('products');
    }
};
