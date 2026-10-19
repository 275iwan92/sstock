<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/* REMARK
 * Tujuan       : Membatasi rute menu Keamanan (kelola pengguna) hanya untuk Administrator.
 * Dipakai oleh : routes/web.php (alias 'administrator').
 * Status       : Aktif.
 */
class EnsureAdministrator
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isAdministrator(), 403, 'Hanya untuk Administrator.');

        return $next($request);
    }
}
