<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* REMARK
 * Tujuan       : Tabel pengaturan aplikasi kecil (key / value) yang dipakai bersama: jadwal Stok Opname (opname.*) sekarang, modul Perusahaan (kop cetak) nanti. Hanya tabel baru; dilewati bila sudah ada.
 * Output       : constants (key unik, value text null, group).
 * Dipakai oleh : App\Models\Constant, App\Support\Constants, App\Services\CountSchedule.
 * Status       : Aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('constants')) {
            return;
        }
        Schema::create('constants', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('group', 50)->default('general');
            $table->timestamps();
            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('constants');
    }
};
