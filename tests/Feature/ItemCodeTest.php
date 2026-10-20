<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga kode barang otomatis ({AWALAN}-{URUT 4 DIGIT}[-{YYMM kedaluwarsa}]), master Kategori (awalan 3 karakter), dan master Lokasi Penyimpanan.
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class ItemCodeTest extends TestCase
{
    use RefreshDatabase;

    private function payload(Category $cat, array $extra = []): array
    {
        return array_merge(['name' => 'Barang uji', 'category_id' => $cat->id, 'unit' => 'pcs', 'unit_price' => 100, 'reorder_point' => 1, 'reorder_qty' => 5, 'lead_time_days' => 1], $extra);
    }

    public function test_code_follows_category_prefix_running_number_and_expiry(): void
    {
        $atk = Category::create(['code' => 'ATK', 'name' => 'ATK']);
        $tls = Category::create(['code' => 'TLS', 'name' => 'Tools']);
        $this->actingAs(User::factory()->supervisor()->create());

        $this->post(route('items.store'), $this->payload($atk))->assertRedirect();
        $this->post(route('items.store'), $this->payload($atk, ['expiry_date' => '2027-03-15']))->assertRedirect();
        $this->post(route('items.store'), $this->payload($tls))->assertRedirect();

        $this->assertEqualsCanonicalizing(['ATK-0001', 'ATK-0002-2703', 'TLS-0001'], Item::pluck('code')->all());
    }

    public function test_code_category_and_expiry_are_fixed_after_saving(): void
    {
        $atk = Category::create(['code' => 'ATK', 'name' => 'ATK']);
        $tls = Category::create(['code' => 'TLS', 'name' => 'Tools']);
        $this->actingAs(User::factory()->supervisor()->create());
        $this->post(route('items.store'), $this->payload($atk, ['expiry_date' => '2027-03-15']));
        $item = Item::firstOrFail();

        $this->put(route('items.update', $item), ['_action' => 'save'] + $this->payload($tls, ['name' => 'Baru', 'code' => 'HACK', 'expiry_date' => '2030-01-01']))->assertRedirect();

        $item->refresh();
        $this->assertSame(['ATK-0001-2703', $atk->id, '2027-03-15', 'Baru'], [$item->code, $item->category_id, $item->expiry_date->format('Y-m-d'), $item->name]);
    }

    public function test_category_prefix_must_be_three_characters_and_unique(): void
    {
        $this->actingAs(User::factory()->supervisor()->create());

        $this->post(route('categories.store'), ['code' => 'ab', 'name' => 'Pendek'])->assertSessionHasErrors('code');
        $this->post(route('categories.store'), ['code' => 'abcd', 'name' => 'Panjang'])->assertSessionHasErrors('code');
        $this->post(route('categories.store'), ['code' => 'abc', 'name' => 'Boleh'])->assertRedirect();
        $this->assertDatabaseHas('categories', ['code' => 'ABC']);
        $this->post(route('categories.store'), ['code' => 'ABC', 'name' => 'Kembar'])->assertSessionHasErrors('code');
    }

    public function test_items_can_use_only_active_locations_and_categories(): void
    {
        $cat = Category::create(['code' => 'ATK', 'name' => 'ATK']);
        Location::create(['code' => 'A1', 'name' => 'Rak A1']);
        Location::create(['code' => 'OLD', 'name' => 'Rak lama', 'is_active' => false]);
        $off = Category::create(['code' => 'OFF', 'name' => 'Nonaktif', 'is_active' => false]);
        $this->actingAs(User::factory()->supervisor()->create());

        $this->post(route('items.store'), $this->payload($cat, ['location' => 'A1']))->assertRedirect();
        $this->post(route('items.store'), $this->payload($cat, ['location' => 'GHOST']))->assertSessionHasErrors('location');
        $this->post(route('items.store'), $this->payload($cat, ['location' => 'OLD']))->assertSessionHasErrors('location');
        $this->post(route('items.store'), $this->payload($off))->assertSessionHasErrors('category_id');
        $this->assertSame(1, Item::count());
    }

    public function test_location_master_is_supervisor_only_and_code_stays(): void
    {
        $this->actingAs(User::factory()->create())->get(route('locations.index'))->assertForbidden();

        $this->actingAs(User::factory()->supervisor()->create());
        $this->post(route('locations.store'), ['code' => 'b2-01', 'name' => 'Rak B2'])->assertRedirect();
        $loc = Location::where('code', 'B2-01')->firstOrFail();

        $this->put(route('locations.update', $loc), ['_action' => 'save', 'code' => 'ZZ', 'name' => 'Rak B2 baru'])->assertRedirect();
        $this->assertSame(['B2-01', 'Rak B2 baru'], [$loc->fresh()->code, $loc->fresh()->name]);
        $this->get(route('locations.index'))->assertOk();
        $this->get(route('categories.index'))->assertOk();
    }

    public function test_expiry_state_and_alerts_only_count_active_items_that_still_have_stock(): void
    {
        $cat = Category::create(['code' => 'ATK', 'name' => 'ATK']);
        $mk = fn (string $code, string $exp, float $stock, bool $active = true) => tap(Item::create(['code' => $code, 'name' => $code, 'category_id' => $cat->id, 'unit' => 'pcs', 'unit_price' => 1, 'expiry_date' => $exp, 'is_active' => $active])->forceFill(['stock_qty' => $stock]), fn ($i) => $i->save());
        $expired = $mk('A-1', today()->subDays(3)->toDateString(), 5);
        $soon = $mk('A-2', today()->addDays(10)->toDateString(), 5);
        $mk('A-3', today()->addDays(90)->toDateString(), 5);       // masih lama
        $mk('A-4', today()->subDays(3)->toDateString(), 0);        // stok habis: tidak ada yang dibuang
        $mk('A-5', today()->subDays(3)->toDateString(), 5, false); // nonaktif

        $this->assertSame(['KEDALUWARSA', 'HAMPIR'], [$expired->expiryState(), $soon->expiryState()]);
        $this->assertSame(['A-1', 'A-2'], Item::nearExpiry()->orderBy('expiry_date')->pluck('code')->all());

        $this->actingAs(User::factory()->supervisor()->create());
        $this->get(route('home'))->assertOk()->assertSee('Pisahkan dari rak');
        $report = $this->get(route('reports.expiry'))->assertOk()->assertSee('KEDALUWARSA')->assertSee('HAMPIR')->getContent();
        $this->assertStringNotContainsString('A-3', $report);
        $this->assertStringContainsString('A-3', $this->get(route('reports.expiry', ['days' => 120]))->getContent());
    }

    public function test_the_sample_data_builds_and_shows_the_expiry_alerts(): void
    {
        $this->seed();

        $this->assertSame(0, Item::whereNull('location')->count());
        $this->assertSame(Location::count(), Location::whereIn('code', Item::pluck('location'))->count());
        $this->assertSame(2, Item::nearExpiry()->count());  // satu sudah lewat, satu hampir; yang ke-3 masih lama
        $this->assertTrue(Item::where('code', 'like', 'ATK-%-%')->exists());
        $this->actingAs(User::where('username', 'supervisor')->firstOrFail())->get(route('home'))->assertOk()->assertSee('Lem Kayu Fox 100 ml');
    }
}
