<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/* REMARK
 * Tujuan       : Pengguna yang dinonaktifkan saat sesinya masih hidup langsung dikeluarkan pada permintaan berikutnya (is_active tidak hanya dicek saat login).
 * Output       : redirect ke login dengan pesan "Akun Anda dinonaktifkan."; permintaan JSON: 403 dengan pesan yang sama.
 * Dipakai oleh : routes/web.php (grup auth, sebelum module).
 * Status       : Aktif.
 */
class EnsureActive
{
    public const MESSAGE = 'Akun Anda dinonaktifkan.';

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_active === false) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson() ? response()->json(['message' => self::MESSAGE], 403) : redirect()->route('login')->with('error', self::MESSAGE);
        }

        return $next($request);
    }
}
