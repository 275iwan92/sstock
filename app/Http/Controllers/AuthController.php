<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/* REMARK
 * Tujuan       : Login / logout. Username atau e-mail + password; akun nonaktif ditolak; percobaan dibatasi per login + IP.
 * Output       : redirect sesuai peran: Supervisor ke dashboard, Data Entry ke layar Receiving.
 * Dipakai oleh : routes/web.php; tampilan: resources/views/login.blade.php.
 * Status       : Aktif.
 */
class AuthController extends Controller
{
    public function show()
    {
        return view('login', ['error' => session('error'), 'username' => old('login', '')]);
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate(['login' => ['required', 'string'], 'password' => ['required', 'string']], [
            'login.required' => 'Enter your username or e-mail.',
            'password.required' => 'Enter your password.',
        ]);

        $key = Str::lower($data['login']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return $this->fail($request, 'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.');
        }

        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        if (! Auth::attempt([$field => $data['login'], 'password' => $data['password'], 'is_active' => true], $request->boolean('remember'))) {
            RateLimiter::hit($key);

            return $this->fail($request, 'Wrong username/e-mail or password, or the account is inactive.');
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        // fresh: buang tab kerja sisa user sebelumnya di browser yang sama.
        return redirect()->intended($request->user()->isSupervisor() ? route('home') : route('home', ['open' => 'RCV']))->with('fresh', true);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function fail(Request $request, string $message): RedirectResponse
    {
        return back()->withInput($request->only('login'))->with('error', $message);
    }
}
