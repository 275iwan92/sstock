<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ConnectsAsMysqlAdmin;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDOException;

/* REMARK
 * Tujuan       : Instalasi pertama saat deploy: membuat database + user MySQL (bila belum ada), migrate, menyalin data wilayah dari tmerp, lalu seed. Aman dijalankan ulang (tidak menimpa data).
 * Sumber hulu  : config database.connections.mysql (DB_DATABASE, DB_USERNAME, DB_PASSWORD). Akun admin MySQL hanya untuk membuat database / user dan menyalin wilayah:
 *                opsi --admin-user / --admin-password, atau env DB_ADMIN_USERNAME / DB_ADMIN_PASSWORD, atau ditanya (mode interaktif).
 * Output       : database sstock, user sstock_usr dengan hak penuh hanya ke database itu, tabel, data wilayah (sstock:import-geo, tidak fatal bila gagal), dan data awal (sekali, bila belum ada user).
 * Dipakai oleh : composer setup (deploy pertama); manual: php artisan sstock:install.
 * Status       : Aktif.
 */
#[Signature('sstock:install {--admin-user= : Akun admin MySQL (bawaan env DB_ADMIN_USERNAME atau root)} {--admin-password= : Password admin MySQL (bawaan env DB_ADMIN_PASSWORD, atau ditanya)} {--no-seed : Jangan isi data awal}')]
#[Description('Deploy pertama: buat database + user MySQL, migrate, salin data wilayah, dan isi data awal')]
class InstallCommand extends Command
{
    use ConnectsAsMysqlAdmin;

    public function handle(): int
    {
        $cfg = config('database.connections.'.config('database.default'));
        if (($cfg['driver'] ?? null) !== 'mysql') {
            $this->error('Set DB_CONNECTION=mysql di .env terlebih dahulu.');

            return self::FAILURE;
        }
        foreach (['database', 'username'] as $k) {
            if (! preg_match('/^[A-Za-z0-9_]{1,32}$/', (string) $cfg[$k])) {
                $this->error('DB_'.strtoupper($k === 'username' ? 'username' : 'database').' hanya boleh huruf, angka, dan garis bawah (maks. 32).');

                return self::FAILURE;
            }
        }

        if ($this->canConnect($cfg, $cfg['username'], (string) $cfg['password'], $cfg['database'])) {
            $this->info("Database {$cfg['database']} dan user {$cfg['username']} sudah ada.");
        } elseif (! $this->provision($cfg)) {
            return self::FAILURE;
        }

        $this->call('migrate', ['--force' => true]);
        $this->importGeo();
        if (! $this->option('no-seed') && User::query()->doesntExist()) {
            $this->call('db:seed', ['--force' => true]);
        }
        $this->info('Instalasi selesai.');

        return self::SUCCESS;
    }

    private function provision(array $cfg): bool
    {
        $pdo = $this->adminConnection($cfg);
        if (! $pdo) {
            return false;
        }

        $db = $cfg['database'];
        $user = $cfg['username'];
        $pass = $pdo->quote((string) $cfg['password']);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        // 'localhost' dan '127.0.0.1' dua-duanya, karena MySQL mencocokkan host koneksi TCP secara berbeda antar mesin.
        foreach (['localhost', '127.0.0.1'] as $host) {
            $pdo->exec("CREATE USER IF NOT EXISTS '{$user}'@'{$host}' IDENTIFIED BY {$pass}");
            $pdo->exec("ALTER USER '{$user}'@'{$host}' IDENTIFIED BY {$pass}");
            $pdo->exec("GRANT ALL PRIVILEGES ON `{$db}`.* TO '{$user}'@'{$host}'");
        }
        $pdo->exec('FLUSH PRIVILEGES');
        $this->info("Database {$db} dan user {$user} dibuat.");

        return true;
    }

    private function canConnect(array $cfg, string $user, string $pass, ?string $db): bool
    {
        try {
            $this->connect($cfg, $user, $pass, $db);

            return true;
        } catch (PDOException) {
            return false;
        }
    }

    // Data wilayah dari tmerp; gagal tidak menghentikan instalasi (jalankan sstock:import-geo nanti).
    private function importGeo(): void
    {
        if (DB::table('geo_country')->exists()) {
            return;
        }
        $args = $this->adminCredentials ? ['--admin-user' => $this->adminCredentials[0], '--admin-password' => $this->adminCredentials[1]] : [];
        if ($this->call('sstock:import-geo', $args) !== self::SUCCESS) {
            $this->warn('Data wilayah belum disalin. Jalankan nanti: php artisan sstock:import-geo');
        }
    }
}
