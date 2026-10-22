<?php

namespace App\Support;

use App\Models\User;
use App\Services\CountSchedule;

/* REMARK
 * Tujuan       : Menu Layer Shell dari daftar modul (App\Support\Modules), disaring menurut akses pengguna (peran + Grup). Modul tanpa akses tidak tampil.
 * Output       : array $modules untuk x-dl::layer-shell (code item dipakai juga oleh ?open= dan data-open-tab).
 * Dipakai oleh : HomeController.
 * Status       : Aktif.
 */
final class Menu
{
    public static function modules(User $user): array
    {
        $parents = [];
        foreach (Modules::all() as $code => [$parent, $label, $icon, $type, $route]) {
            if (! $user->canAccess($code)) {
                continue;
            }
            $url = is_array($route) ? route($route[0], $route[1]) : route($route);
            $badge = $code === 'SO' ? CountSchedule::badge() : null;   // lencana: Stok Opname sedang berjalan atau terlambat
            $parents[$parent][] = ['code' => $code, 'label' => $label, 'url' => $url, 'type' => $type, 'icon' => $icon]
                + ($badge ? ['badge' => $badge['badge'], 'badgeTone' => $badge['tone'], 'badgeTitle' => $badge['title']] : []);
        }

        $out = [];
        foreach (Modules::PARENTS as $code => [$label, $icon]) {
            if (isset($parents[$code])) {
                $out[] = ['code' => $code, 'short' => $code, 'label' => $label, 'icon' => $icon, 'pin' => $code === 'SEC', 'children' => $parents[$code]];
            }
        }

        return $out;
    }
}
