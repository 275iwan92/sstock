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
    <x-dl::frame bg="sstock" bg-order="random" box="right" color="light" widgets="brand@top-left, clock@top-right, quote@bottom-left" quotes="motivasi.txt">
        <div class="dl-loginbox">
            <form class="dl-loginbox__form" method="POST" action="{{ route('password.email') }}" novalidate>
                @csrf
                <x-dl::brand :name="config('devlib.brand.name')" :tagline="config('devlib.brand.tagline')" tone="auto" class="dl-loginbox__brand" />
                <h2 class="dl-loginbox__title">{{ \Devlib\Lang::text('Forgot password') }}</h2>
                <p class="dl-loginbox__sub">{{ \Devlib\Lang::text('Enter your username or e-mail. We will send a link to set a new password.') }}</p>
                @if (session('status'))<x-dl::alert tone="success" :message="session('status')" />@endif
                @if ($errors->any())<x-dl::alert tone="danger" :message="$errors->first()" />@endif
                <x-dl::field.text name="login" label="Username or e-mail" icon="fa-user" size="lg" :value="old('login')" :case-toggle="false" autocomplete="username" />
                <x-dl::button type="submit" size="lg" block label="Send reset link" />
                <p class="dl-loginbox__reg"><x-dl::button variant="link" :href="route('login')" label="Back to sign in" /></p>
            </form>
        </div>
    </x-dl::frame>
@endsection
