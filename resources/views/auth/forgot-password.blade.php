{{-- REMARK
 Tujuan       : Layar lupa password: isi username atau e-mail, tautan reset dikirim ke e-mail akun.
 Sumber hulu  : DevLib (Frame, Brand, field, Button); PasswordResetController@request.
 Input        : session status (pesan sukses), errors.
 Output       : POST password.email.
 Dipakai oleh : route password.request (tautan "Lupa password?" di login).
 Status       : Aktif.
--}}
@extends('layout', ['title' => 'Forgot password', 'bodyClass' => 'sstock-bare'])
@section('body')
    @if (\Devlib\Device::view(request()) === \Devlib\Device::PHONE)
        <main class="dl-plogin">@include('auth._forgot-password-box')</main>
    @else
        <x-dl::frame bg="sstock" bg-order="random" box="right" color="light" widgets="brand@top-left, clock@top-right, quote@bottom-left" quotes="motivasi.txt">
            @include('auth._forgot-password-box')
        </x-dl::frame>
    @endif
@endsection
