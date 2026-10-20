<?php

namespace App\Services;

use Database\Seeders\ItemSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/* REMARK
 * Tujuan       : Menyegarkan data contoh secara keseluruhan: semua tabel dikosongkan (dibuang lalu dibuat ulang lewat migrasi) dan diisi ulang oleh seeder, supaya tanggal contoh
 *                (kedaluwarsa, riwayat bulan lalu) kembali segar dan mengikuti perubahan program. Data wilayah (geo_*) dipertahankan karena salinan dari tmerp butuh akun admin MySQL.
 * Sumber hulu  : config sstock.sample_refresh (penjaga), DatabaseSeeder, storage/app/sample-refresh.json (catatan penyegaran terakhir, di luar database).
 * Output       : database terisi ulang; catatan waktu + pelaku.
 * Dipakai oleh : SampleDataController. Setara CLI: composer sample.
 * Status       : Aktif.
 */
final class SampleData
{
    // Tabel yang dihitung di layar (nama tabel => label).
    public const COUNTED = ['products' => 'Barang', 'items' => 'Batch', 'stock_documents' => 'Dokumen stok', 'stock_movements' => 'Pergerakan stok', 'users' => 'Pengguna'];

    public static function enabled(): bool
    {
        return (bool) config('sstock.sample_refresh');
    }

    public static function counts(): array
    {
        return array_map(fn ($t) => Schema::hasTable($t) ? DB::table($t)->count() : 0, array_keys(self::COUNTED));
    }

    /** @return array{at: string, by: string}|null */
    public static function last(): ?array
    {
        $file = self::file();

        return is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
    }

    public static function refresh(string $by): void
    {
        abort_unless(self::enabled(), 403, 'Penyegaran data contoh dimatikan di lingkungan ini.');
        set_time_limit(0);
        ignore_user_abort(true);

        Schema::disableForeignKeyConstraints();
        foreach (array_column(Schema::getTables(), 'name') as $table) {
            if (! str_starts_with($table, 'geo_')) {
                Schema::drop($table);
            }
        }
        Schema::enableForeignKeyConstraints();

        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('db:seed', ['--force' => true]);

        file_put_contents(self::file(), json_encode(['at' => now()->format('Y-m-d H:i:s'), 'by' => $by]));
    }

    // Ringkasan isi data contoh untuk layar (dihitung dari seeder, bukan angka tetap).
    public static function summary(): array
    {
        return [
            'Akun uji: admin, supervisor, entry (kata sandi bawaan seeder)',
            'Kategori, lokasi penyimpanan, satuan, departemen, penerima, dan 3 supplier',
            (count(ItemSeeder::ITEMS) + count(array_unique(array_column(ItemSeeder::EXPIRING, 0)))).' barang, termasuk '.count(array_unique(array_column(ItemSeeder::EXPIRING, 0))).' barang berkedaluwarsa dengan '.count(ItemSeeder::EXPIRING).' batch (sudah lewat, hampir, dan masih lama; tanggalnya dihitung dari hari ini)',
            'Riwayat transaksi: saldo awal bulan lalu, penerimaan, pengeluaran, dan opname, bertanggal relatif terhadap hari ini',
        ];
    }

    private static function file(): string
    {
        return config('sstock.sample_log');
    }
}
