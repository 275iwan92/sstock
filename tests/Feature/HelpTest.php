<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga Technical Guide (Help): hanya Administrator yang bisa membukanya dan hanya Administrator yang melihat tautan Help di layar utama.
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class TechnicalGuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_administrators_open_the_guide(): void
    {
        $this->get(route('help.technical-guide'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('help.technical-guide'))->assertForbidden();
        $this->actingAs(User::factory()->supervisor()->create())->get(route('help.technical-guide'))->assertForbidden();
        $this->actingAs(User::factory()->administrator()->create())->get(route('help.technical-guide'))->assertOk()->assertSee('Stoku Technical Guide');
    }

    public function test_help_link_exists_only_on_the_administrator_home(): void
    {
        $url = route('help.technical-guide');

        $this->actingAs(User::factory()->administrator()->create())->get(route('home'))->assertSee('data-dl-help="'.e($url).'"', false);
        $this->actingAs(User::factory()->supervisor()->create())->get(route('home'))->assertDontSee($url, false)->assertDontSee('Help &amp; support', false);
    }
}
