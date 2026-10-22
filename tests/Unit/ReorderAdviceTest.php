<?php

namespace Tests\Unit;

use App\Services\ReorderAdvice;
use PHPUnit\Framework\TestCase;

/* REMARK
 * Tujuan       : Menjaga rumus saran Titik Pesan Ulang dan Jml Pesan Ulang (pemakaian x (lead time + hari pengaman), pemakaian x hari cakupan, dibulatkan ke atas).
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class ReorderAdviceTest extends TestCase
{
    public function test_it_rounds_up_from_usage_lead_time_safety_and_cover_days(): void
    {
        $a = ReorderAdvice::advise(3, 0.2143);   // 0,2143 x (3 + 7) = 2,143 -> 3 ; 0,2143 x 30 = 6,43 -> 7

        $this->assertSame(3, $a['rop']);
        $this->assertSame(7, $a['qty']);
        $this->assertSame(['rop' => 8, 'qty' => 18], array_intersect_key(ReorderAdvice::advise(5, 0.6), ['rop' => 1, 'qty' => 1]));
    }

    public function test_an_exact_result_is_not_raised_and_the_options_apply(): void
    {
        $this->assertSame(3, ReorderAdvice::advise(5, 0.5, 1, 30)['rop']);   // 0,5 x 6 = 3 tepat
        $this->assertSame(1, ReorderAdvice::advise(1, 0.01)['qty']);          // paling sedikit 1
        $this->assertSame(2, ReorderAdvice::advise(5, 0.2, 0, 10)['qty']);
    }

    public function test_without_lead_time_or_usage_there_is_no_advice(): void
    {
        $this->assertNull(ReorderAdvice::advise(null, 1.0));
        $this->assertNull(ReorderAdvice::advise(3, 0.0));
    }
}
