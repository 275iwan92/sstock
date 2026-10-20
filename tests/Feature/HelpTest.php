<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga HELP: User's Guide (Indonesia) terbuka untuk semua yang login dan hanya menampilkan modul yang boleh dibuka pengguna itu;
 *                Technical Guide (English) hanya Administrator (403 untuk URL langsung, tautan tab tidak dirender untuk peran lain); item Help ada di menu semua peran.
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class HelpTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('help.user-guide'))->assertRedirect(route('login'));
        $this->get(route('help.technical-guide'))->assertRedirect(route('login'));
    }

    public function test_user_guide_opens_for_every_role(): void
    {
        foreach ([User::factory(), User::factory()->supervisor(), User::factory()->administrator()] as $factory) {
            $this->actingAs($factory->create())->get(route('help.user-guide'))->assertOk()->assertSee('Panduan Pengguna Stoku')->assertSee('Cara memakai Grid');
        }
    }

    public function test_user_guide_lists_only_modules_the_role_may_open(): void
    {
        $entry = $this->actingAs(User::factory()->create())->get(route('help.user-guide'))->assertOk();
        $entry->assertSee('id="t-rcv"', false)->assertSee('id="t-iss"', false)->assertSee('id="laporan"', false);
        $entry->assertDontSee('id="t-opn"', false)->assertDontSee('id="t-adj"', false)->assertDontSee('id="keamanan"', false)->assertDontSee('id="m-uom"', false);

        $sup = $this->actingAs(User::factory()->supervisor()->create())->get(route('help.user-guide'))->assertOk();
        $sup->assertSee('id="t-opn"', false)->assertSee('id="t-adj"', false)->assertSee('id="m-uom"', false)->assertDontSee('id="keamanan"', false);

        $this->actingAs(User::factory()->administrator()->create())->get(route('help.user-guide'))->assertOk()->assertSee('id="keamanan"', false);
    }

    public function test_only_administrators_open_the_technical_guide(): void
    {
        $this->actingAs(User::factory()->create())->get(route('help.technical-guide'))->assertForbidden();
        $this->actingAs(User::factory()->supervisor()->create())->get(route('help.technical-guide'))->assertForbidden();
        $this->actingAs(User::factory()->administrator()->create())->get(route('help.technical-guide'))->assertOk()->assertSee('Stoku Technical Guide');
    }

    public function test_technical_guide_tab_is_rendered_only_for_administrators(): void
    {
        $tg = route('help.technical-guide');

        $this->actingAs(User::factory()->create())->get(route('help.user-guide'))->assertDontSee($tg, false)->assertDontSee('>Technical Guide</a>', false);
        $this->actingAs(User::factory()->supervisor()->create())->get(route('help.user-guide'))->assertDontSee($tg, false);
        $this->actingAs(User::factory()->administrator()->create())->get(route('help.user-guide'))->assertSee($tg, false);
        $this->actingAs(User::factory()->administrator()->create())->get($tg)->assertSee(route('help.user-guide'), false);
    }

    public function test_help_item_is_in_the_menu_of_every_role_and_opens_the_user_guide(): void
    {
        $url = route('help.user-guide');

        foreach ([User::factory(), User::factory()->supervisor(), User::factory()->administrator()] as $factory) {
            $this->actingAs($factory->create())->get(route('home'))->assertSee('data-dl-help="'.e($url).'"', false);
        }
    }
}
