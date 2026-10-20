<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\Item;
use App\Models\Recipient;
use App\Models\StockDocument;
use App\Models\Supplier;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga aturan data transaksional dan master dari pemilik: cetakan membawa MODE (DRAFT / CONFIRMED / CANCELLED), Print tersedia di semua mode,
 *                authority bisa bertingkat (config sstock.approval), dokumen baru berisi nilai bawaan, dan master (grup) hanya Activate / Deactivate, tidak dihapus.
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class TransactionalRulesTest extends TestCase
{
    use RefreshDatabase;

    private User $entry;

    private User $sup;

    private int $itemId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entry = User::factory()->create();
        $this->sup = User::factory()->supervisor()->create();
        $cat = Category::create(['code' => 'ATK', 'name' => 'ATK']);
        $this->itemId = $this->makeItem(['code' => 'T1', 'name' => 'Item T1', 'category_id' => $cat->id, 'unit' => 'pcs', 'unit_price' => 10, 'reorder_point' => 1, 'reorder_qty' => 5])->id;
    }

    private function receive(): StockDocument
    {
        $payload = ['supplier_id' => Supplier::create(['code' => 'S1', 'name' => 'S'])->id, 'lines' => [['item_id' => $this->itemId, 'qty' => 10, 'unit_cost' => 10]]];
        $this->actingAs($this->entry)->post(route('docs.store', 'receiving'), $payload)->assertSessionHasNoErrors();

        return StockDocument::latest('id')->firstOrFail();
    }

    private function act(StockDocument $doc, string $action, User $by, array $payload = [])
    {
        return $this->actingAs($by)->put(route('docs.update', [$doc->type->slug(), $doc]), $payload + ['_action' => $action, '_reason' => 'koreksi data']);
    }

    private function adjustment(): StockDocument
    {
        $payload = ['reason' => 'Opname', 'lines' => [['item_id' => $this->itemId, 'counted_qty' => 4]]];
        $this->actingAs($this->sup)->post(route('docs.store', 'adjustment'), $payload)->assertSessionHasNoErrors();
        $doc = StockDocument::latest('id')->firstOrFail();
        $this->act($doc, 'confirm', $this->sup, $payload);

        return $doc;
    }

    public function test_the_printout_carries_the_mode_in_every_status(): void
    {
        $doc = $this->receive();
        $payload = ['supplier_id' => $doc->supplier_id, 'lines' => [['item_id' => $this->itemId, 'qty' => 10, 'unit_cost' => 10]]];

        $this->get(route('docs.show', ['receiving', $doc]))->assertOk()->assertSee(route('docs.print', ['receiving', $doc]), false);
        $this->get(route('docs.print', ['receiving', $doc]))->assertOk()->assertSee('DRAFT')->assertSee($doc->doc_no);

        $this->act($doc, 'confirm', $this->entry, $payload);
        $this->get(route('docs.print', ['receiving', $doc]))->assertOk()->assertSee('CONFIRMED')->assertDontSee('DRAFT');

        $this->act($doc, 'cancel', $this->sup);
        $this->get(route('docs.print', ['receiving', $doc]))->assertOk()->assertSee('CANCELLED');
    }

    public function test_every_stock_document_type_can_be_printed(): void
    {
        $this->actingAs($this->sup);
        $adj = $this->adjustment();
        $this->get(route('docs.print', ['adjustment', $adj]))->assertOk()->assertSee('WAITING APPROVAL');

        $out = ['recipient_id' => Recipient::create(['name' => 'R', 'department_id' => Department::create(['code' => 'D', 'name' => 'D'])->id])->id, 'lines' => [['item_id' => $this->itemId, 'qty' => 1]]];
        $this->post(route('docs.store', 'issuance'), $out)->assertSessionHasNoErrors();
        $this->get(route('docs.print', ['issuance', StockDocument::latest('id')->firstOrFail()]))->assertOk()->assertSee('DRAFT');
        $this->get(route('docs.print', ['receiving', $adj]))->assertNotFound();
    }

    public function test_adjustment_approval_is_a_single_level_by_default(): void
    {
        $doc = $this->adjustment();
        $this->act($doc, 'approve', $this->sup)->assertRedirect();

        $this->assertSame('approved', $doc->fresh()->status);
        $this->assertSame(1, $doc->approvals()->count());
    }

    public function test_authority_can_be_multi_level_and_stock_moves_only_after_the_last_level(): void
    {
        config(['sstock.approval.adjustment' => ['supervisor', 'administrator']]);
        $admin = User::factory()->administrator()->create();
        $doc = $this->adjustment();

        $this->act($doc, 'approve', $this->sup)->assertRedirect();                       // tingkat 1
        $this->assertSame('approval', $doc->fresh()->status);
        $this->assertSame(0.0, $this->item()->stock_qty);

        $this->act($doc, 'approve', $this->sup)->assertForbidden();                        // tingkat 2 butuh Administrator
        $this->get(route('docs.show', ['adjustment', $doc]))->assertOk();

        $this->act($doc, 'approve', $admin)->assertRedirect();                             // tingkat 2
        $this->assertSame(['approved', 4.0], [$doc->fresh()->status, $this->item()->stock_qty]);
        $this->assertSame([1, 2], $doc->approvals()->pluck('level')->all());
        $this->actingAs($admin)->get(route('docs.show', ['adjustment', $doc]))->assertOk()->assertSee('tingkat 2');
    }

    public function test_a_reject_at_any_level_stops_the_document(): void
    {
        config(['sstock.approval.adjustment' => ['supervisor', 'administrator']]);
        $doc = $this->adjustment();

        $this->act($doc, 'approve', $this->sup);
        $this->act($doc, 'reject', User::factory()->administrator()->create());

        $this->assertSame('rejected', $doc->fresh()->status);
        $this->assertSame(0.0, $this->item()->stock_qty);
        $this->assertSame(['approved', 'rejected'], $doc->approvals()->pluck('decision')->all());
    }

    public function test_new_documents_open_with_default_values(): void
    {
        $this->actingAs($this->entry)->get(route('docs.create', 'receiving'))->assertOk()->assertSee('qty&quot;:1', false);
        $this->actingAs($this->sup)->get(route('docs.create', 'adjustment'))->assertOk()->assertSee('Opname stok', false);
    }

    public function test_a_group_is_deactivated_not_deleted_and_leaves_the_user_choices(): void
    {
        $admin = User::factory()->administrator()->create();
        $group = UserGroup::create(['code' => 'QA', 'name' => 'QA Team']);
        $this->actingAs($admin);

        $this->get(route('users.create'))->assertSee('QA Team');
        $this->postJson(route('groups.toggle', $group))->assertOk()->assertJson(['active' => false]);
        $this->assertFalse($group->fresh()->is_active);
        $this->get(route('users.create'))->assertDontSee('QA Team');
        $this->get(route('groups.show', $group))->assertOk()->assertSee('Activate');

        $this->put(route('groups.update', $group), ['_action' => 'activate'])->assertRedirect();
        $this->assertTrue($group->fresh()->is_active);
    }

    private function item()
    {
        return Item::findOrFail($this->itemId);
    }
}
