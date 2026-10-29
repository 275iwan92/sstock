<?php

namespace App\Http\Middleware;

use App\Support\HpShell;
use Closure;
use Devlib\Device;
use Illuminate\Http\Request;

/* REMARK
 * Tujuan       : Ponsel asli selalu Tampilan HP sampai keluar atau ganti perangkat: cookie ui=desktop (sisa pilihan lama) di ponsel diabaikan di setiap permintaan.
 *                Tombol Tampilan Desktop tidak digambar di ponsel (HpShell::deviceAction); middleware ini menutup jalan lain (cookie lama, POST /ui langsung).
 * Sumber hulu  : Devlib\Device (nama cookie ui), HpShell::isRealPhone (UA ponsel).
 * Output       : cookie ui dibuang dari permintaan ini bila bernilai desktop di ponsel; komputer tidak tersentuh.
 * Dipakai oleh : bootstrap/app.php (grup web).
 * Status       : Aktif.
 */
class PhoneStaysPhone
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->cookie(Device::COOKIE) === Device::DESKTOP && HpShell::isRealPhone()) {
            $request->cookies->remove(Device::COOKIE);
        }

        return $next($request);
    }
}
