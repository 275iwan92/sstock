<?php

namespace Tests\Unit;

use App\Models\StockCountLine;
use PHPUnit\Framework\TestCase;

/* REMARK
 * Tujuan       : Menjaga hitungan baris Stok Opname: kosong vs 0 vs Tidak dihitung, hasil akhir (hitung ulang menang), selisih jumlah / persen / nilai (bcmath), dan ambang alasan wajib.
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class StockCountLineTest extends TestCase
{
    private function line(array $a): StockCountLine
    {
        return new StockCountLine($a + ['system_qty' => 10, 'unit_cost' => 1500.5, 'skipped' => false]);
    }

    public function test_empty_is_not_counted_but_zero_is(): void
    {
        $empty = $this->line([]);
        $zero = $this->line(['counted_qty' => 0]);

        $this->assertFalse($empty->isFilled());
        $this->assertNull($empty->finalQty());
        $this->assertSame(0.0, $empty->diff());   // belum dihitung tidak menyumbang selisih
        $this->assertTrue($zero->isFilled());
        $this->assertSame(0.0, $zero->finalQty());
        $this->assertSame(-10.0, $zero->diff());
    }

    public function test_skipped_needs_a_note_and_has_no_difference(): void
    {
        $this->assertFalse($this->line(['skipped' => true])->isComplete());
        $skipped = $this->line(['skipped' => true, 'note' => 'rak terkunci']);
        $this->assertTrue($skipped->isComplete());
        $this->assertNull($skipped->finalQty());
        $this->assertSame(0.0, $skipped->diff());
    }

    public function test_recount_wins_over_the_first_count(): void
    {
        $line = $this->line(['counted_qty' => 7, 'recount_qty' => 9.5]);

        $this->assertSame(9.5, $line->finalQty());
        $this->assertSame(-0.5, $line->diff());
    }

    public function test_difference_percent_and_value_are_exact(): void
    {
        $line = $this->line(['system_qty' => 3, 'counted_qty' => 2.001, 'unit_cost' => 0.3333]);

        $this->assertSame(-0.999, $line->diff());
        $this->assertSame(-33.3, $line->pct());
        $this->assertSame(-0.33, $line->value());   // 0,999 x 0,3333 = 0,3329667 -> 0,33
    }

    public function test_threshold_compares_without_rounding_and_zero_system_always_exceeds(): void
    {
        $this->assertFalse($this->line(['counted_qty' => 9.5])->overThreshold(5));   // tepat 5% tidak melewati
        $this->assertTrue($this->line(['counted_qty' => 9.499])->overThreshold(5));
        $this->assertFalse($this->line(['counted_qty' => 10])->overThreshold(5));
        $zero = $this->line(['system_qty' => 0, 'counted_qty' => 1]);
        $this->assertNull($zero->pct());
        $this->assertTrue($zero->overThreshold(5));
    }
}
