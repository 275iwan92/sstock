<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/* REMARK
 * Tujuan       : Modul baru Riwayat Pemesanan (ORD) mengikuti hak Penerimaan Barang (RCV): Grup yang menulis RCV mendapat ORD RW, Grup yang hanya membaca RCV mendapat ORD RO.
 *                Grup lain tidak diberi apa pun (Administrator dapat menambahkannya di layar Grup). Pengguna tanpa Grup memakai bawaan peran (ORD sudah termasuk). Hanya data; tidak ada tabel atau kolom baru.
 * Output       : user_group_modules (baris ORD).
 * Dipakai oleh : Modules, GroupController.
 * Status       : Aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('user_group_modules')->where('module', 'RCV')->get(['group_id', 'access']) as $row) {
            DB::table('user_group_modules')->insertOrIgnore(['group_id' => $row->group_id, 'module' => 'ORD', 'access' => $row->access]);
        }
    }

    public function down(): void
    {
        DB::table('user_group_modules')->where('module', 'ORD')->delete();
    }
};
