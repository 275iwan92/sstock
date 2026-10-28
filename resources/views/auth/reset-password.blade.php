{{-- REMARK
 Tujuan       : Layar atur password baru dari tautan e-mail (token + e-mail di tautan).
 Sumber hulu  : DevLib (Frame, Brand, field, Button); PasswordResetController@reset.
 Input        : token, email, errors.
 Output       : POST password.update.
 Dipakai oleh : route password.reset (tautan di e-mail reset).
 Status       : Aktif.
--}}
@extends('layout', ['title' => 'Reset password', 'bodyClass' => 'sstock-bare'])
@section('body')
    @if (\Devlib\Device::view(request()) === \Devlib\Device::PHONE)
        <main class="dl-plogin">@include('auth._reset-password-box')</main>
    @else
        <x-dl::frame bg="sstock" bg-order="random" box="right" color="light" widgets="brand@top-left, clock@top-right, quote@bottom-left" quotes="motivasi.txt">
            @include('auth._reset-password-box')
        </x-dl::frame>
    @endif
@endsection
