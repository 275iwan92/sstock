{{-- REMARK
 Tujuan       : Halaman login tampilan HP (smartphone potret): memakai x-dl::phone-login dari DevLib (satu kolom mengalir 320-440 px, isian besar, tautan Tampilan Desktop untuk kembali).
 Sumber hulu  : AuthController::show (error, username); DevLib x-dl::phone-login, Devlib\Device.
 Input        : error (pesan login gagal), username (isian terakhir).
 Output       : form login yang diposting ke route login.attempt; tautan kecil ke ui.set (ui=desktop).
 Dipakai oleh : route login bila perangkat = hp (ponsel atau pilihan pengguna).
 Status       : Aktif.
--}}
@extends('layout', ['title' => 'Sign in', 'bodyClass' => 'sstock-bare sstock-hp'])
@section('body')
    <x-dl::phone-login :action="route('login.attempt')" :error="$error ?? ($errors->first() ?: null)" :username="$username" :forgot-url="route('password.request')" :brand="config('devlib.brand.name')" :tagline="config('devlib.brand.tagline')" :device-action="route('ui.set')">
        @if (session('status'))<x-dl::alert tone="success" :message="session('status')" />@endif
    </x-dl::phone-login>
@endsection
