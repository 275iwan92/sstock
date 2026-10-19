<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/* REMARK
 * Tujuan       : Membatasi rute khusus Supervisor (penyesuaian stok, saldo awal, ubah parameter master, laporan penyesuaian).
 * Dipakai oleh : routes/web.php (alias 'supervisor').
 * Status       : Aktif.
 */
class EnsureSupervisor
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isSupervisor(), 403, 'Hanya untuk Supervisor.');

        return $next($request);
    }
}
