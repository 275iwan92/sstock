{{-- REMARK
 Tujuan       : Halaman login tampilan HP (smartphone potret): satu kolom mengalir (320 sampai sekitar 440 px lebar), isian dan tombol besar, teks 16 px ke atas, tepi aman. Tombol kecil "Tampilan desktop" untuk kembali ke tampilan komputer.
 Sumber hulu  : AuthController::show (error, username); DevLib x-dl::login-box (isian, galat, tombol), Devlib\Device.
 Input        : error (pesan login gagal), username (isian terakhir).
 Output       : form login yang diposting ke route login.attempt; form kecil ke ui.set (ui=desktop).
 Dipakai oleh : route login bila perangkat = hp (ponsel atau pilihan pengguna).
 Status       : Aktif.
--}}
@extends('layout', ['title' => 'Sign in', 'bodyClass' => 'sstock-bare sstock-hp'])
@section('body')
    <main class="sstock-hplogin">
        @if (session('status'))<x-dl::alert tone="success" :message="session('status')" />@endif
        <x-dl::login-box show="brand, title, username, password, forgot, button" :forgot-url="route('password.request')" :action="route('login.attempt')" :error="$error ?? ($errors->first() ?: null)" :username="$username" :brand="config('devlib.brand.name')" :tagline="config('devlib.brand.tagline')" />
        <form class="sstock-hplogin__desk" method="POST" action="{{ route('ui.set') }}">
            @csrf
            <input type="hidden" name="ui" value="desktop">
            <button type="submit"><i class="fa-solid fa-desktop" aria-hidden="true"></i> {{ \Devlib\Lang::text('Desktop view') }}</button>
        </form>
    </main>
@endsection
