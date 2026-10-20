<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\StockDocument;
use App\Models\User;
use App\Services\SampleData;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga layar Administrator "Data Contoh": hanya Administrator, wajib mengetik SEGARKAN, seluruh data diganti data contoh, data wilayah (geo_*) dipertahankan, dimatikan lewat config.
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class SampleDataTest extends TestCase
{
    use DatabaseMigrations;  // penyegaran membuang tabel, jadi tidak bisa memakai transaksi RefreshDatabase

    protected function setUp(): void
    {
        parent::setUp();
        config(['sstock.sample_log' => sys_get_temp_dir().'/sstock-sample-test.json']);
        @unlink(config('sstock.sample_log'));
    }

    public function test_only_the_administrator_can_open_the_screen(): void
    {
        $this->actingAs(User::factory()->supervisor()->create())->get(route('sample.index'))->assertForbidden();
        $this->actingAs(User::factory()->administrator()->create())->get(route('sample.index'))->assertOk()->assertSee('Belum pernah');
    }

    public function test_refresh_needs_the_typed_confirmation(): void
    {
        $this->actingAs(User::factory()->administrator()->create());
        Item::query()->count();

        $this->post(route('sample.refresh'), ['confirm' => 'segarkan'])->assertSessionHasErrors('confirm');
        $this->post(route('sample.refresh'))->assertSessionHasErrors('confirm');
        $this->assertSame(1, User::count());
    }

    public function test_refresh_replaces_everything_with_sample_data_but_keeps_the_geo_tables(): void
    {
        $admin = User::factory()->administrator()->create(['username' => 'siapa-saja']);
        DB::table('geo_country')->insert(['id_country' => 1, 'country_name' => 'Indonesia']);

        $this->actingAs($admin)->post(route('sample.refresh'), ['confirm' => 'SEGARKAN'])->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertSame(0, User::where('username', 'siapa-saja')->count());
        $this->assertSame(1, User::where('username', 'admin')->count());
        $this->assertGreaterThan(20, Item::count());
        $this->assertTrue(StockDocument::exists());
        $this->assertSame(1, DB::table('geo_country')->count());
        $this->assertNotNull(SampleData::last());
    }

    public function test_refresh_is_blocked_when_switched_off(): void
    {
        config(['sstock.sample_refresh' => false]);
        $this->actingAs(User::factory()->administrator()->create());

        $this->get(route('sample.index'))->assertOk()->assertSee('Dimatikan');
        $this->post(route('sample.refresh'), ['confirm' => 'SEGARKAN'])->assertForbidden();
        $this->assertSame(1, User::count());
    }
}
