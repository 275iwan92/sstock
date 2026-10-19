<?php

namespace App\Http\Middleware;

use App\Support\Modules;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/* REMARK
 * Tujuan       : Menjaga akses langsung (URL) ke layar modul: pengguna hanya boleh membuka modul yang diizinkan Grup dan perannya, bukan hanya yang tampil di menu; modul berakses RO menolak semua permintaan tulis (bukan GET).
 * Sumber hulu  : Modules::forRoute (rute -> kode modul), User::canAccess.
 * Dipakai oleh : routes/web.php (grup auth).
 * Status       : Aktif.
 */
class EnsureModuleAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $code = Modules::forRoute($request->route());
        $user = $request->user();
        abort_if($code !== null && ! $user?->canAccess($code), 403, 'Anda tidak punya akses ke modul ini.');
        abort_if($code !== null && ! $request->isMethodSafe() && ! $user->canWrite($code), 403, 'Akses Anda ke modul ini hanya-baca (RO).');

        return $next($request);
    }
}
