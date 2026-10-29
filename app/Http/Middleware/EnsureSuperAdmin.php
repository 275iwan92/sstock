<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/* REMARK
 * Tujuan       : Hanya super admin (SSTOCK_SUPER_ADMIN) yang lolos; semua yang lain mendapat 404 (bukan 403) supaya layarnya tidak kelihatan ada.
 * Dipakai oleh : routes/web.php (grup super.*), alias 'super' di bootstrap/app.php.
 * Status       : Aktif.
 */
class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isSuperAdmin(), 404);

        return $next($request);
    }
}
