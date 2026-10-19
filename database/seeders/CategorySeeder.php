<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/* REMARK
 * Tujuan       : Kategori barang gudang: ATK, Sparepart Maintenance, Tools/Peralatan.
 * Output       : tabel categories.
 * Dipakai oleh : DatabaseSeeder.
 * Status       : Aktif.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['ATK' => 'ATK (Office Stationery)', 'SPR' => 'Sparepart Maintenance', 'TLS' => 'Tools / Peralatan'] as $code => $name) {
            Category::updateOrCreate(['code' => $code], ['name' => $name]);
        }
    }
}
