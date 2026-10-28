<?php

namespace App\Http\Middleware;

use Closure;
use Devlib\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/* REMARK
 * Tujuan       : Melupakan SEKALI pilihan tampilan lama (cookie ui) di tiap browser, supaya peralihan otomatis x-dl::phone-offer auto bekerja untuk pengguna yang pilihannya tersimpan dari masa uji ("Tetap desktop" / "Tampilan Desktop").
 *                Sesudahnya cookie ui_ver menandai browser itu; pilihan baru pengguna menang seperti biasa. Naikkan VERSION bila pilihan lama perlu dilupakan lagi.
 * Sumber hulu  : Devlib\Device (nama cookie ui).
 * Output       : cookie ui dihapus (di permintaan ini juga) dan cookie ui_ver = VERSION; hanya pada permintaan GET (POST ui.set / login yang baru memilih tidak dihapus).
 * Dipakai oleh : bootstrap/app.php (grup web).
 * Status       : Aktif.
 */
class ResetViewChoice
{
    // Naik ke 3: pilihan phone yang tersimpan oleh peralihan otomatis versi lama (tanpa penanda ui_auto) dianggap pilihan pengguna dan tidak kembali ke desktop saat layar melebar.
    public const VERSION = '3';

    public function handle(Request $request, Closure $next)
    {
        if (! $request->isMethod('GET') || $request->cookie('ui_ver') === self::VERSION) {
            return $next($request);
        }
        $request->cookies->remove(Device::COOKIE);
        $response = $next($request);
        $response->headers->setCookie(Cookie::forget(Device::COOKIE));
        $response->headers->setCookie(cookie('ui_ver', self::VERSION, 60 * 24 * 365));

        return $response;
    }
}
