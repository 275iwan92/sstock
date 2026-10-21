<?php

namespace Tests\Feature;

use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga .env.example tetap aman bawaan untuk produksi (debug mati, Segarkan data contoh mati, sesi 30 menit tanpa aktivitas).
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class EnvExampleTest extends TestCase
{
    public function test_env_example_is_safe_by_default(): void
    {
        $values = [];
        foreach (file(base_path('.env.example'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (! str_starts_with(trim($line), '#') && str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $values[trim($key)] = trim($value);
            }
        }

        $this->assertSame('production', $values['APP_ENV']);
        $this->assertSame('false', $values['APP_DEBUG']);
        $this->assertSame('false', $values['SSTOCK_SAMPLE_REFRESH']);
        $this->assertSame('30', $values['SESSION_LIFETIME']);
    }
}
