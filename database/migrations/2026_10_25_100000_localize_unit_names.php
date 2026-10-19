<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/* REMARK
 * Tujuan       : Nama satuan baku diganti ke bahasa Indonesia pada database yang sudah ada (hanya baris yang masih memakai nama English bawaan, nama ubahan user tidak disentuh).
 * Sumber hulu  : units (dibuat oleh 2026_10_21_100000_create_units_table).
 * Output       : units.name Indonesia.
 * Dipakai oleh : form + grid Satuan, pilihan satuan di Barang dan dokumen.
 * Status       : Aktif.
 */
return new class extends Migration
{
    private const NAMES = [
        'pcs' => ['Pieces', 'Buah'], 'box' => ['Box', 'Dus'], 'pack' => ['Pack', 'Pak'], 'roll' => ['Roll', 'Gulung'], 'pair' => ['Pair', 'Pasang'],
        'sheet' => ['Sheet', 'Lembar'], 'can' => ['Can', 'Kaleng'], 'bottle' => ['Bottle', 'Botol'],
    ];

    public function up(): void
    {
        foreach (self::NAMES as $code => [$old, $new]) {
            DB::table('units')->where('code', $code)->where('name', $old)->update(['name' => $new]);
        }
    }

    public function down(): void
    {
        foreach (self::NAMES as $code => [$old, $new]) {
            DB::table('units')->where('code', $code)->where('name', $new)->update(['name' => $old]);
        }
    }
};
