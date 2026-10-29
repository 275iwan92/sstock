<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ConnectsAsMysqlAdmin;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDOException;

/* REMARK
 * Tujuan       : Menyalin master wilayah dari tmerp (database tm2mst: geo_country, geo_region, geo_city, geo_district, geo_village) ke database sstock dengan id yang sama.
 *                Sekali jalan, dilewati bila tabel sudah terisi (kecuali --fresh).
 * Sumber hulu  : database sumber (opsi --source, bawaan env GEO_SOURCE_DB atau tm2mst) di server MySQL yang sama; butuh akun admin yang boleh membaca sumber dan menulis ke sstock.
 * Output       : lima tabel geo_* terisi.
 * Dipakai oleh : InstallCommand (otomatis, tidak fatal bila gagal); manual: php artisan sstock:import-geo.
 * Status       : Aktif.
 */
#[Signature('sstock:import-geo {--source= : Database sumber (bawaan env GEO_SOURCE_DB atau tm2mst)} {--fresh : Kosongkan lalu isi ulang} {--admin-user= : Akun admin MySQL} {--admin-password= : Password admin MySQL}')]
#[Description('Salin master wilayah (negara sampai kelurahan) dari tmerp ke database sstock')]
class ImportGeoCommand extends Command
{
    use ConnectsAsMysqlAdmin;

    // Urutan induk > anak; kolom sama di sumber dan tujuan.
    private const TABLES = [
        'geo_country' => ['id_country', 'country_code', 'iso_code', 'country_name', 'call_country_no', 'activate'],
        'geo_region' => ['id_region', 'country_id', 'region_code', 'region_name', 'is_active'],
        'geo_city' => ['id_city', 'region_id', 'city_code', 'city_type', 'city_name', 'post_code', 'activate'],
        'geo_district' => ['id_district', 'city_id', 'district_code', 'district_name', 'post_code', 'activate'],
        'geo_village' => ['id_village', 'district_id', 'village_code', 'village_name', 'post_code', 'activate'],
    ];

    public function handle(): int
    {
        $cfg = config('database.connections.'.config('database.default'));
        if (($cfg['driver'] ?? null) !== 'mysql') {
            $this->error('Impor wilayah hanya untuk MySQL (DB_CONNECTION=mysql).');

            return self::FAILURE;
        }
        if (! $this->option('fresh') && DB::table('geo_country')->exists()) {
            $this->info('Data wilayah sudah ada; dilewati (pakai --fresh untuk isi ulang).');

            return self::SUCCESS;
        }

        $source = $this->option('source') ?: config('sstock.geo_source_db');
        $target = $cfg['database'];
        foreach ([$source, $target] as $name) {
            if (! preg_match('/^[A-Za-z0-9_]{1,64}$/', $name)) {
                $this->error("Nama database tidak valid: {$name}");

                return self::FAILURE;
            }
        }

        $pdo = $this->adminConnection($cfg);
        if (! $pdo) {
            return self::FAILURE;
        }

        try {
            $found = $pdo->prepare('SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name = ?');
            $found->execute([$source]);
            if (! $found->fetchColumn()) {
                $this->error("Database sumber '{$source}' tidak ditemukan di server ini.");

                return self::FAILURE;
            }

            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
            foreach (self::TABLES as $table => $cols) {
                $list = '`'.implode('`,`', $cols).'`';
                if ($this->option('fresh')) {
                    $pdo->exec("TRUNCATE TABLE `{$target}`.`{$table}`");
                }
                $n = $pdo->exec("INSERT INTO `{$target}`.`{$table}` ({$list}) SELECT {$list} FROM `{$source}`.`{$table}`");
                $this->line(sprintf('  %-13s %s baris', $table, number_format((int) $n, 0, ',', '.')));
            }
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        } catch (PDOException $e) {
            $this->error('Impor gagal: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Data wilayah disalin dari '.$source.'.');

        return self::SUCCESS;
    }
}
