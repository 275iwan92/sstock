<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga alur lupa password: tautan lewat username / e-mail, akun tak dikenal / nonaktif tidak dikirimi (pesan tetap sama), reset dengan token, token tak valid ditolak.
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_links_to_forgot_password(): void
    {
        $this->get(route('login'))->assertOk()->assertSee(route('password.request'), false)->assertSee('Lupa password?', false);
        $this->get(route('password.request'))->assertOk()->assertSee('Kirim tautan reset', false);
    }

    public function test_reset_link_is_sent_by_username_or_email_with_the_same_answer_for_everyone(): void
    {
        Notification::fake();
        $user = User::factory()->create(['username' => 'budi', 'email' => 'budi@x.test']);
        $off = User::factory()->create(['username' => 'off', 'is_active' => false]);

        $msg = 'Jika akun terdaftar';
        $this->from(route('password.request'))->post(route('password.email'), ['login' => 'Budi'])->assertSessionHas('status', fn ($s) => str_contains($s, $msg));
        Notification::assertSentTo($user, ResetPassword::class);

        Notification::fake();
        $other = User::factory()->create(['username' => 'sari', 'email' => 'sari@x.test']);   // pengguna lain: permintaan kedua untuk akun yang sama ditahan broker 60 detik
        $this->post(route('password.email'), ['login' => 'sari@x.test'])->assertSessionHas('status');
        Notification::assertSentTo($other, ResetPassword::class);

        Notification::fake();
        $this->post(route('password.email'), ['login' => 'tidak-ada'])->assertSessionHas('status', fn ($s) => str_contains($s, $msg));
        $this->post(route('password.email'), ['login' => 'off'])->assertSessionHas('status', fn ($s) => str_contains($s, $msg));
        Notification::assertNothingSent();
        $this->post(route('password.email'), ['login' => ''])->assertSessionHasErrors('login');
    }

    public function test_password_can_be_reset_with_a_valid_token_only(): void
    {
        $user = User::factory()->create(['username' => 'budi', 'email' => 'budi@x.test']);
        $token = Password::createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))->assertOk()->assertSee('Simpan password baru', false);

        $this->post(route('password.update'), ['token' => 'salah', 'email' => $user->email, 'password' => 'baru12345', 'password_confirmation' => 'baru12345'])->assertSessionHasErrors('email');
        $this->post(route('password.update'), ['token' => $token, 'email' => $user->email, 'password' => 'pendek', 'password_confirmation' => 'pendek'])->assertSessionHasErrors('password');
        $this->post(route('password.update'), ['token' => $token, 'email' => $user->email, 'password' => 'baru12345', 'password_confirmation' => 'beda'])->assertSessionHasErrors('password');

        $this->post(route('password.update'), ['token' => $token, 'email' => $user->email, 'password' => 'baru12345', 'password_confirmation' => 'baru12345'])->assertRedirect(route('login'))->assertSessionHas('status');
        $this->assertTrue(password_verify('baru12345', $user->fresh()->password));

        // token hanya sekali pakai
        $this->post(route('password.update'), ['token' => $token, 'email' => $user->email, 'password' => 'lain123456', 'password_confirmation' => 'lain123456'])->assertSessionHasErrors('email');

        $this->post(route('login.attempt'), ['login' => 'budi', 'password' => 'baru12345'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }
}
