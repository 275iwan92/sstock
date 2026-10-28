{{-- REMARK
 Tujuan       : Kotak isian atur password baru (dipakai view reset-password di kerangka desktop dan di kerangka HP).
 Dipakai oleh : auth/reset-password.blade.php.
 Status       : Aktif.
--}}
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
