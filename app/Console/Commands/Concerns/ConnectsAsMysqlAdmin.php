<?php

namespace App\Console\Commands\Concerns;

use PDO;
use PDOException;

/* REMARK
 * Tujuan       : Koneksi PDO ke MySQL dengan akun admin untuk perintah instalasi (buat database / user, salin data wilayah). Akun admin tidak dipakai aplikasi.
 * Sumber hulu  : opsi --admin-user / --admin-password, env DB_ADMIN_USERNAME / DB_ADMIN_PASSWORD, atau ditanya (mode interaktif).
 * Dipakai oleh : InstallCommand, ImportGeoCommand (keduanya wajib punya opsi admin-user dan admin-password).
 * Status       : Aktif.
 */
trait ConnectsAsMysqlAdmin
{
    // [user, password] akun admin yang berhasil dipakai; diteruskan ke perintah lain agar tidak ditanya dua kali.
    private ?array $adminCredentials = null;

    private function adminConnection(array $cfg): ?PDO
    {
        $user = $this->option('admin-user') ?: config('sstock.db_admin.username');
        $pass = $this->option('admin-password') ?? (config('sstock.db_admin.password') ?: null);
        if ($pass === null) {
            $pass = $this->input->isInteractive() ? (string) $this->secret("Password MySQL admin '{$user}' (kosongkan bila tanpa password)") : '';
        }

        try {
            $pdo = $this->connect($cfg, $user, (string) $pass);
            $this->adminCredentials = [$user, (string) $pass];

            return $pdo;
        } catch (PDOException $e) {
            $this->error("Tidak bisa masuk ke MySQL sebagai '{$user}': ".$e->getMessage());
            $this->line('Beri akun admin lewat --admin-user / --admin-password atau env DB_ADMIN_USERNAME / DB_ADMIN_PASSWORD.');

            return null;
        }
    }

    private function connect(array $cfg, string $user, string $pass, ?string $db = null): PDO
    {
        $dsn = "mysql:host={$cfg['host']};port={$cfg['port']}".($db ? ";dbname={$db}" : '').';charset=utf8mb4';

        return new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]);
    }
}
