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

    public function test_guides_describe_incoming_units_per_item_and_the_reorder_note(): void
    {
        $this->actingAs(User::factory()->supervisor()->create())->get(route('help.user-guide'))->assertOk()
            ->assertSee('Satuan masuk (kemasan saat barang diterima)', false)->assertSee('Faktor satuan belum ada', false)
            ->assertSee('Diterima sejak dipesan', false)->assertSee('Jumlah Dipesan', false)->assertSee('bukan dokumen PO', false)->assertSee('Perkiraan tiba', false)->assertSee('Saran Titik Pesan Ulang', false)->assertSee('Lead Time Otomatis (d)', false)->assertSee('selalu dalam satuan stok barang', false)
            ->assertSee('Faktor ini sudah dipakai di dokumen; hubungi Supervisor untuk mengubahnya.', false);

        $this->actingAs(User::factory()->administrator()->create())->get(route('help.technical-guide'))->assertOk()
            ->assertSee('product_units', false)->assertSee('ProductUnits::save', false)->assertSee('ReorderOrder::note()', false)->assertSee('add_supplier_qty_note_to_reorder_orders', false)->assertSee('App\Services\LeadTimes', false)->assertSee('Devlib\Engine\LeadTime', false);
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

    public function test_flows_are_inline_svg_diagrams_kept_outside_public(): void
    {
        $ug = $this->actingAs(User::factory()->administrator()->create())->get(route('help.user-guide'))->assertOk();
        $tg = $this->get(route('help.technical-guide'))->assertOk();

        foreach (['ug-alur-kerja', 'ug-siklus-dokumen', 'ug-pembatalan'] as $name) {
            $ug->assertSee('id="'.$name.'-t"', false);
        }
        foreach (['tg-system-flow', 'tg-doc-lifecycle', 'tg-adjustment-lifecycle', 'tg-stock-ledger', 'tg-erd', 'tg-access-flow'] as $name) {
            $tg->assertSee('id="'.$name.'-t"', false);
        }
        $this->assertDirectoryDoesNotExist(public_path('help'));
        $this->assertFileDoesNotExist(public_path('help/diagrams/tg-erd.svg'));
    }

    public function test_help_item_is_in_the_menu_of_every_role_and_opens_the_user_guide(): void
    {
        $url = route('help.user-guide');

        foreach ([User::factory(), User::factory()->supervisor(), User::factory()->administrator()] as $factory) {
            $this->actingAs($factory->create())->get(route('home'))->assertSee('data-dl-help="'.e($url).'"', false);
        }
    }

    public function test_help_covers_session_idle_logout_and_conversion_rules(): void
    {
        $ug = $this->actingAs(User::factory()->administrator()->create())->get(route('help.user-guide'))->assertOk();
        $ug->assertSee('langsung keluar')->assertSee('Tidak ada pilihan &quot;ingat saya&quot;', false)->assertSee('Tetap masuk')->assertSee('30 menit')
            ->assertSee('hanya bisa diubah Supervisor')->assertSee('Dihitung presisi');

        $this->get(route('help.technical-guide'))->assertOk()->assertSee('x-dl::idle-logout')->assertSee('session.ping')->assertSee('SESSION_LIFETIME=30')
            ->assertSee('EnsureActive')->assertSee('bcmath')->assertSee('global');
    }

    public function test_help_covers_quick_add_and_the_kontan_supplier(): void
    {
        $ug = $this->actingAs(User::factory()->administrator()->create())->get(route('help.user-guide'))->assertOk();
        $ug->assertSee('Tombol plus (Tambah baru)')->assertSee('Kontan')->assertSee('supplier sistem')->assertDontSee('Penerima baru</b>', false);

        $this->get(route('help.technical-guide'))->assertOk()->assertSee('QuickAdd')->assertSee('is_system')->assertSee('add_system_supplier_kontan');
    }
}
