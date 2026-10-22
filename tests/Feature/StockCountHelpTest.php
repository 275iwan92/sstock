<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga bantuan Stok Opname: Panduan Pengguna (Indonesia) hanya untuk yang punya modul SO, Panduan Teknis (English, Administrator) memuat tabel, alur, dan penjaga kunci; diagram ikut tampil.
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class StockCountHelpTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_guide_describes_stok_opname_for_users_with_the_module_only(): void
    {
        foreach ([User::factory(), User::factory()->supervisor()] as $factory) {
            $this->actingAs($factory->create())->get(route('help.user-guide'))->assertOk()
                ->assertSee('id="t-so"', false)->assertSee('Mulai Opname')->assertSee('Cetak Lembar Hitung')->assertSee('Hitung buta')->assertSee('Kunci transaksi')->assertSee('Berita Acara Stok Opname')->assertSee('Alur Stok Opname');
        }

        $group = UserGroup::create(['code' => 'GD', 'name' => 'Gudang']);
        $group->syncAccess(['ISS' => 'rw']);
        $this->actingAs(User::factory()->create(['group_id' => $group->id]))->get(route('help.user-guide'))->assertOk()->assertDontSee('id="t-so"', false);
    }

    public function test_technical_guide_documents_the_tables_flow_and_the_single_lock_guard(): void
    {
        $this->actingAs(User::factory()->administrator()->create())->get(route('help.technical-guide'))->assertOk()
            ->assertSee('stock_counts', false)->assertSee('stock_count_lines', false)->assertSee('StockCounts', false)->assertSee('recordCountAdjustment', false)
            ->assertSee('StockLedger::assertNotLocked()', false)->assertSee('StockCount::lockingNow()', false)->assertSee('give_stock_count_to_groups', false)->assertSee('State diagram of a Stok Opname', false);
    }
}
