<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

/* REMARK
 * Tujuan       : Supplier contoh untuk transaksi Receiving.
 * Output       : tabel suppliers.
 * Dipakai oleh : DatabaseSeeder.
 * Status       : Aktif (data contoh).
 */
class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['SUP-001', 'CV Sinar Alat Tulis', '031-5550101', 'Jl. Kembang Jepun 12, Surabaya'],
            ['SUP-002', 'PT Teknik Baut Mandiri', '031-5550202', 'Jl. Rungkut Industri 8, Surabaya'],
            ['SUP-003', 'UD Perkakas Jaya', '031-5550303', 'Jl. Pasar Turi 45, Surabaya'],
        ];

        foreach ($rows as [$code, $name, $phone, $address]) {
            Supplier::updateOrCreate(['code' => $code], compact('name', 'phone', 'address'));
        }
    }
}
