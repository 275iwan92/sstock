<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/* REMARK
 * Tujuan       : Mengirim satu surel uji lewat pengaturan MAIL_* yang sedang aktif, untuk memastikan SMTP benar (mis. di server) tanpa lewat layar Lupa password.
 * Output       : "Terkirim" atau pesan galat transport; kode keluar 1 bila gagal. MAIL_MAILER=log hanya menulis ke log, bukan mengirim.
 * Dipakai oleh : php artisan sstock:mail-test alamat@contoh.id.
 * Status       : Aktif.
 */
class MailTestCommand extends Command
{
    protected $signature = 'sstock:mail-test {to : Alamat surel tujuan}';

    protected $description = 'Kirim satu surel uji lewat pengaturan MAIL_* yang aktif';

    public function handle(): int
    {
        $name = config('app.name');
        $this->line('Mailer: '.config('mail.default').' | dari: '.config('mail.from.address').' | host: '.config('mail.mailers.smtp.host').':'.config('mail.mailers.smtp.port'));

        try {
            Mail::raw("Ini surel uji dari {$name}. Bila Anda membacanya, pengiriman surel sudah berfungsi.", fn ($m) => $m->to($this->argument('to'))->subject("Uji surel {$name}"));
        } catch (Throwable $e) {
            $this->error('Gagal: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Terkirim ke '.$this->argument('to').' (bila mailer bukan log).');

        return self::SUCCESS;
    }
}
