<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use App\Support\GeoArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga Supplier: kontak + HP, wilayah wajib (minimal kota), pencarian wilayah 5 tingkat, dan satu kolom Kota, Negara di grid.
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class SupplierTest extends TestCase
{
    use RefreshDatabase;

    private function seedGeo(): void
    {
        DB::table('geo_country')->insert(['id_country' => 1, 'country_name' => 'Indonesia']);
        DB::table('geo_region')->insert(['id_region' => 1, 'country_id' => 1, 'region_name' => 'Jawa Timur']);
        DB::table('geo_city')->insert(['id_city' => 1, 'region_id' => 1, 'city_type' => 'Kab.', 'city_name' => 'Sidoarjo', 'post_code' => '61200']);
        DB::table('geo_district')->insert(['id_district' => 1, 'city_id' => 1, 'district_name' => 'Waru', 'post_code' => '61256']);
        DB::table('geo_village')->insert(['id_village' => 1, 'district_id' => 1, 'village_name' => 'Pepelegi', 'post_code' => null]);
    }

    private function payload(array $extra = []): array
    {
        return array_merge(['code' => 's9', 'name' => 'Supplier Nine', 'phone' => '+62315550101', 'contact_name' => 'Bapak Hendra', 'contact_mobile' => '+6281234567890',
            'address' => 'Jl. Contoh 1', 'area' => 'VILLAGE:1', 'postal_code' => '99999'], $extra);
    }

    public function test_geo_search_finds_each_level_and_resolves_the_full_label(): void
    {
        $this->seedGeo();
        $this->assertSame(['DISTRICT:1'], array_column(GeoArea::search('waru'), 'area'));  // kecamatan saja; kelurahan tidak dicari
        $this->assertSame(['DISTRICT:1', 'CITY:1'], array_column(GeoArea::search('sidoarjo'), 'area'));
        $this->assertSame('Waru, Kab. Sidoarjo, Jawa Timur, Indonesia (61256)', GeoArea::search('waru')[0]['display']);

        $village = GeoArea::find('VILLAGE:1');
        $this->assertSame('Pepelegi, Waru, Kab. Sidoarjo, Jawa Timur, Indonesia', $village['full_label']);
        $this->assertSame('61256', $village['postal_code']);  // kode pos diwarisi dari kecamatan
        $this->assertSame('Kab. Sidoarjo', GeoArea::find('CITY:1')['city']);
        $this->assertNull(GeoArea::find('VILLAGE:999'));
        $this->assertNull(GeoArea::find('bukan-kunci'));
    }

    public function test_supplier_is_saved_with_contact_and_region(): void
    {
        $this->seedGeo();
        $this->actingAs(User::factory()->create())->post(route('suppliers.store'), $this->payload())->assertRedirect();

        $s = Supplier::where('code', 'S9')->firstOrFail();
        $this->assertSame('Bapak Hendra', $s->contact_name);
        $this->assertSame('+6281234567890', $s->contact_mobile);
        $this->assertSame('+62315550101', $s->phone);
        $this->assertSame('VILLAGE:1', $s->areaKey());
        $this->assertSame('61256', $s->postal_code);
    }

    public function test_validation_messages_are_in_indonesian(): void
    {
        $this->actingAs(User::factory()->create())->post(route('suppliers.store'), ['code' => '', 'name' => ''])->assertSessionHasErrors(['name', 'area']);
        $messages = session('errors')->getBag('default');

        $this->assertSame('nama wajib diisi.', mb_strtolower($messages->first('name')));
        $this->assertStringContainsString('Wilayah wajib diisi', $messages->first('area'));
    }

    public function test_region_is_required(): void
    {
        $this->seedGeo();
        $this->actingAs(User::factory()->create())->post(route('suppliers.store'), $this->payload(['area' => '']))->assertSessionHasErrors('area');
        $this->assertSame(0, Supplier::count());
    }

    public function test_region_must_exist_in_the_geo_tables(): void
    {
        $this->seedGeo();
        $this->actingAs(User::factory()->create())->post(route('suppliers.store'), $this->payload(['area' => 'CITY:999']))->assertSessionHasErrors('area');
        $this->assertSame(0, Supplier::count());
    }

    public function test_grid_shows_city_and_country_in_one_column(): void
    {
        $this->seedGeo();
        Supplier::create(['code' => 'S1', 'name' => 'One', 'area_level' => 'DISTRICT', 'area_id' => 1]);

        $this->actingAs(User::factory()->create())->get(route('suppliers.index'))->assertOk()->assertSee('Kab. Sidoarjo, Indonesia', false);
        $this->get(route('suppliers.show', Supplier::first()))->assertOk()->assertSee('Jawa Timur', false);
    }
}
