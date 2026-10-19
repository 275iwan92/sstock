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
    <x-dl::frame bg="sstock" bg-order="random" box="right" color="light" widgets="brand@top-left, clock@top-right, quote@bottom-left" quotes="motivasi.txt">
        <div class="dl-loginbox">
            <form class="dl-loginbox__form" method="POST" action="{{ route('password.update') }}" novalidate>
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ old('email', $email) }}">
                <x-dl::brand :name="config('devlib.brand.name')" :tagline="config('devlib.brand.tagline')" tone="auto" class="dl-loginbox__brand" />
                <h2 class="dl-loginbox__title">{{ \Devlib\Lang::text('Reset password') }}</h2>
                <p class="dl-loginbox__sub">{{ \Devlib\Lang::text('Choose a new password (at least 8 characters).') }}</p>
                @if ($errors->any())<x-dl::alert tone="danger" :message="$errors->first()" />@endif
                <x-dl::field.password name="password" label="New password" icon="fa-lock" size="lg" autocomplete="new-password" />
                <x-dl::field.password name="password_confirmation" label="Confirm new password" icon="fa-lock" size="lg" autocomplete="new-password" />
                <x-dl::button type="submit" size="lg" block label="Save new password" />
                <p class="dl-loginbox__reg"><x-dl::button variant="link" :href="route('login')" label="Back to sign in" /></p>
            </form>
        </div>
    </x-dl::frame>
@endsection
