<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga perintah sstock:mail-test: surel uji terkirim lewat mailer aktif; transport yang gagal memberi kode keluar 1 dan pesan galat.
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class MailTestCommandTest extends TestCase
{
    public function test_sends_one_test_mail_to_the_given_address(): void
    {
        $this->artisan('sstock:mail-test', ['to' => 'pemilik@x.test'])->assertSuccessful();

        $sent = Mail::mailer()->getSymfonyTransport()->messages();
        $this->assertCount(1, $sent);
        $this->assertSame('pemilik@x.test', $sent[0]->getOriginalMessage()->getTo()[0]->getAddress());
    }

    public function test_reports_failure_when_the_transport_cannot_send(): void
    {
        config(['mail.default' => 'failing', 'mail.mailers.failing' => ['transport' => 'smtp', 'host' => '127.0.0.1', 'port' => 1, 'timeout' => 2]]);

        $this->artisan('sstock:mail-test', ['to' => 'pemilik@x.test'])->assertFailed();
    }
}
