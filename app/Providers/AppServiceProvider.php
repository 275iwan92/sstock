<?php

namespace App\Providers;

use App\Support\Company;
use App\Support\GeoLookup;
use App\Support\Indonesian;
use App\Support\ItemLookups;
use Devlib\PrintDoc\Document;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        ItemLookups::register();
        GeoLookup::register();
        Indonesian::register();
        Document::companyUsing(fn () => Company::kop());   // satu sumber kop semua cetakan: layar Perusahaan

        // E-mail reset password berbahasa Indonesia; tautan ke layar password baru, berlaku sesuai config auth.passwords.users.expire.
        ResetPassword::toMailUsing(function (object $user, string $token) {
            $url = route('password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()]);
            $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

            return (new MailMessage)->subject('Atur ulang password Stoku')
                ->greeting('Halo '.$user->name.',')
                ->line('Kami menerima permintaan untuk mengatur ulang password akun Stoku Anda.')
                ->action('Atur password baru', $url)
                ->line("Tautan ini berlaku {$minutes} menit. Jika bukan Anda yang meminta, abaikan e-mail ini; password Anda tidak berubah.")
                ->salutation('Stoku');
        });
    }
}
