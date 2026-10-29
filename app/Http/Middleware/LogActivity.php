<?php

namespace App\Http\Middleware;

use App\Support\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/* REMARK
 * Tujuan       : Mencatat aktivitas pengguna yang sudah masuk ke ActivityLog: Buka (halaman HTML dibuka), Unduh (berkas), Input / Ubah / Hapus / Batalkan (permintaan tulis), Login, Login gagal, Logout, Reset data contoh.
 *                Dicatat SESUDAH respons terkirim (terminate), jadi tidak memperlambat layar. Ping sesi, permintaan JSON baca (pencarian lookup) dan berkas statis tidak dicatat.
 *                Yang dicatat hanya nama rute + alamat tanpa query + kode status bila galat; isi formulir dan kata sandi tidak pernah.
 * Sumber hulu  : rute web (nama rute), pengguna dari sesi.
 * Output       : satu baris log per permintaan yang dicatat.
 * Dipakai oleh : bootstrap/app.php (grup web).
 * Status       : Aktif.
 */
class LogActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        // Pengguna diambil SEBELUM rute jalan: sesudah logout sesi sudah kosong.
        $request->attributes->set('activity_user', $request->user()?->username);

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $route = $request->route()?->getName();
        $method = $request->method();
        $path = '/'.ltrim($request->path(), '/');

        if ($route === 'login.attempt') {
            $user = $request->user()?->username;
            $user !== null
                ? ActivityLog::write($user, (string) $request->ip(), 'Login', $path)
                : ActivityLog::write((string) $request->input('login'), (string) $request->ip(), 'Login gagal', $path);

            return;
        }

        $user = $request->attributes->get('activity_user');
        if ($user === null || $route === 'session.ping') {
            return;
        }

        $action = $this->action($request, $response, (string) $route, $method);
        if ($action === null) {
            return;
        }

        $status = $response->getStatusCode();
        ActivityLog::write($user, (string) $request->ip(), $action, trim(($route ?? '-').' '.$path.($method !== 'GET' && $status >= 400 ? " [{$status}]" : '')));
    }

    private function action(Request $request, Response $response, string $route, string $method): ?string
    {
        if ($route === 'logout') {
            return 'Logout';
        }
        if ($route === 'sample.refresh') {
            return 'Reset data contoh';
        }
        if (in_array($method, ['GET', 'HEAD'], true)) {
            if ($response->getStatusCode() !== 200 || $request->expectsJson() || $request->ajax()) {
                return null;
            }
            if (str_contains((string) $response->headers->get('Content-Disposition'), 'attachment')) {
                return 'Unduh';
            }

            return str_contains((string) $response->headers->get('Content-Type'), 'text/html') ? 'Buka' : null;
        }

        return match (true) {
            $method === 'DELETE' || preg_match('/destroy|delete|discard|remove/', $route) === 1 => 'Hapus',
            str_contains($route, 'cancel') => 'Batalkan',
            preg_match('/store|create|add/', $route) === 1 => 'Input',
            $method === 'POST' && $route === '' => 'Input',
            default => 'Ubah',
        };
    }
}
