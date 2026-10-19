<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/* REMARK
 * Tujuan       : Master Departemen dan Penerima barang (supaya pengeluaran bisa dianalisis per departemen / penerima), terhubung ke stock_documents lewat department_id + recipient_id.
 *                Kolom teks recipient / department di stock_documents tetap sebagai salinan nama saat transaksi; teks lama dipindah ke master.
 * Output       : departments, recipients, stock_documents.department_id + recipient_id.
 * Dipakai oleh : DepartmentController, RecipientController, StockDocuments.
 * Status       : Aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('recipients', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->foreignId('department_id')->nullable()->constrained('departments')->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::table('stock_documents', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('department')->constrained('departments')->restrictOnDelete();
            $table->foreignId('recipient_id')->nullable()->after('recipient')->constrained('recipients')->restrictOnDelete();
        });

        // Teks lama -> master (satu baris per nama unik), lalu dokumen lama ditautkan.
        $codes = [];
        foreach (DB::table('stock_documents')->whereNotNull('department')->where('department', '!=', '')->distinct()->pluck('department') as $name) {
            $base = strtoupper(Str::limit(Str::slug($name, ''), 12, '')) ?: 'DEP';
            $code = $base;
            for ($n = 2; isset($codes[$code]); $n++) {
                $code = $base.$n;
            }
            $codes[$code] = true;
            $id = DB::table('departments')->insertGetId(['code' => $code, 'name' => $name, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('stock_documents')->where('department', $name)->update(['department_id' => $id]);
        }
        foreach (DB::table('stock_documents')->whereNotNull('recipient')->where('recipient', '!=', '')->distinct()->pluck('recipient') as $name) {
            $id = DB::table('recipients')->insertGetId(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('stock_documents')->where('recipient', $name)->update(['recipient_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('stock_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recipient_id');
            $table->dropConstrainedForeignId('department_id');
        });
        Schema::dropIfExists('recipients');
        Schema::dropIfExists('departments');
    }
};
