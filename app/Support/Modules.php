<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Routing\Route;

/* REMARK
 * Tujuan       : Daftar modul (layar menu) aplikasi, satu-satunya sumber untuk: menu Layer Shell, pengaturan akses Grup terhadap Modul, daftar modul, dan penjagaan rute.
 *                Akses = peran cukup (min) DAN modul dicentang di Grup pengguna; pengguna tanpa Grup memakai bawaan peran. Administrator selalu penuh.
 * Output       : all() (kode => definisi), defaults($level), forRoute(Route), parents().
 * Dipakai oleh : Menu, User::canAccess, EnsureModuleAccess, GroupController, ModuleController.
 * Status       : Aktif.
 */
final class Modules
{
    // min = peran paling rendah yang boleh memakai modul: entry < supervisor < administrator.
    public const LEVELS = ['entry' => 1, 'supervisor' => 2, 'administrator' => 3];

    public const PARENTS = [
        'MST' => ['Data Master', 'fa-solid fa-boxes-stacked'],
        'TRX' => ['Transaksi', 'fa-solid fa-right-left'],
        'RPT' => ['Laporan', 'fa-solid fa-chart-column'],
        'SEC' => ['Keamanan', 'fa-solid fa-shield-halved'],
    ];

    // kode => [induk, label, ikon, tipe tab, nama rute daftar, peran minimal, awalan rute yang dijaga]
    public static function all(): array
    {
        return [
            'ITM' => ['MST', 'Barang', 'fa-solid fa-box', 'grid', 'items.index', 'entry', ['items.']],
            'SUP' => ['MST', 'Suppliers', 'fa-solid fa-truck', 'grid', 'suppliers.index', 'entry', ['suppliers.']],
            'DEP' => ['MST', 'Departemen', 'fa-solid fa-building', 'grid', 'departments.index', 'entry', ['departments.']],
            'RCP' => ['MST', 'Penerima', 'fa-solid fa-user-tag', 'grid', 'recipients.index', 'entry', ['recipients.']],
            'UOM' => ['MST', 'Satuan', 'fa-solid fa-ruler', 'grid', 'units.index', 'supervisor', ['units.']],
            'OPN' => ['TRX', 'Saldo Awal', 'fa-solid fa-flag-checkered', 'grid', ['docs.index', 'opening'], 'supervisor', []],
            'RCV' => ['TRX', 'Penerimaan Barang', 'fa-solid fa-truck-ramp-box', 'grid', ['docs.index', 'receiving'], 'entry', []],
            'ISS' => ['TRX', 'Pengeluaran Barang', 'fa-solid fa-dolly', 'grid', ['docs.index', 'issuance'], 'entry', []],
            'ADJ' => ['TRX', 'Penyesuaian Stok', 'fa-solid fa-scale-balanced', 'grid', ['docs.index', 'adjustment'], 'supervisor', []],
            'R-CARD' => ['RPT', 'Kartu Stok', 'fa-solid fa-clipboard-list', 'report', 'reports.stock-card', 'entry', ['reports.stock-card']],
            'R-SUM' => ['RPT', 'Ringkasan Stok', 'fa-solid fa-table-list', 'report', 'reports.summary', 'entry', ['reports.summary']],
            'R-IN' => ['RPT', 'Laporan Penerimaan', 'fa-solid fa-file-arrow-down', 'report', 'reports.receiving', 'entry', ['reports.receiving']],
            'R-OUT' => ['RPT', 'Laporan Pengeluaran', 'fa-solid fa-file-arrow-up', 'report', 'reports.issuance', 'entry', ['reports.issuance']],
            'R-ADJ' => ['RPT', 'Laporan Opname & Penyesuaian', 'fa-solid fa-file-circle-check', 'report', 'reports.adjustment', 'supervisor', ['reports.adjustment']],
            'USR' => ['SEC', 'Pengguna', 'fa-solid fa-users-gear', 'grid', 'users.index', 'administrator', ['users.']],
            'GRP' => ['SEC', 'Grup', 'fa-solid fa-people-group', 'grid', 'groups.index', 'administrator', ['groups.']],
            'MOD' => ['SEC', 'Daftar Modul', 'fa-solid fa-table-list', 'grid', 'modules.index', 'administrator', ['modules.']],
        ];
    }

    public static function levelOf(User $user): string
    {
        return $user->isAdministrator() ? 'administrator' : ($user->isSupervisor() ? 'supervisor' : 'entry');
    }

    public static function levelAllows(string $min, string $level): bool
    {
        return self::LEVELS[$level] >= self::LEVELS[$min];
    }

    // Modul bawaan satu peran (dipakai Grup bawaan dan pengguna tanpa Grup).
    public static function defaults(string $level): array
    {
        return array_keys(array_filter(self::all(), fn ($m) => $m[0] !== 'SEC' && self::levelAllows($m[5], $level)));
    }

    // Kode modul yang menjaga rute ini (null = rute bebas, mis. beranda, logout, lookup).
    public static function forRoute(Route $route): ?string
    {
        $name = (string) $route->getName();
        if (str_starts_with($name, 'docs.')) {
            return ['opening' => 'OPN', 'receiving' => 'RCV', 'issuance' => 'ISS', 'adjustment' => 'ADJ'][$route->parameter('type')] ?? null;
        }
        foreach (self::all() as $code => $m) {
            foreach ($m[6] as $prefix) {
                if (str_starts_with($name, $prefix)) {
                    return $code;
                }
            }
        }

        return null;
    }
}
