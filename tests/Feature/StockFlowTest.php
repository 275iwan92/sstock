<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\StockDocument;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga aturan inti stok: login per peran, hak akses, saldo + moving average, stok tidak boleh minus, saldo awal, siklus penyesuaian, peringatan Reorder Point, dan nilai tersembunyi dari Data Entry.
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class StockFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $supervisor;

    private User $entry;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->supervisor = User::factory()->supervisor()->create(['username' => 'sup']);
        $this->entry = User::factory()->create(['username' => 'ent']);
        $this->supplier = Supplier::create(['code' => 'S1', 'name' => 'Supplier One']);
    }

    private function item(string $code = 'I1', float $rop = 0): Item
    {
        $category = Category::firstOrCreate(['code' => 'ATK'], ['name' => 'ATK']);

        return Item::create(['code' => $code, 'name' => "Item {$code}", 'category_id' => $category->id, 'unit' => 'pcs', 'unit_price' => 100, 'reorder_point' => $rop, 'reorder_qty' => 50, 'lead_time_days' => 3]);
    }

    private function receive(Item $item, float $qty, float $cost, ?User $by = null)
    {
        return $this->actingAs($by ?? $this->entry)->post(route('docs.store', 'receiving'), [
            'supplier_id' => $this->supplier->id, 'ref_no' => 'SJ-1',
            'lines' => [['item_id' => $item->id, 'qty' => $qty, 'unit_cost' => $cost]],
        ]);
    }

    private function issue(Item $item, float $qty)
    {
        return $this->actingAs($this->entry)->post(route('docs.store', 'issuance'), [
            'recipient' => 'Budi', 'department' => 'Maintenance', 'lines' => [['item_id' => $item->id, 'qty' => $qty]],
        ]);
    }

    public function test_login_redirects_by_role_and_rejects_wrong_password(): void
    {
        $this->post(route('login.attempt'), ['login' => 'sup', 'password' => 'password'])->assertRedirect(route('home'));
        auth()->logout();
        $this->post(route('login.attempt'), ['login' => $this->entry->email, 'password' => 'password'])->assertRedirect(route('home', ['open' => 'RCV']));
        auth()->logout();
        $this->from(route('login'))->post(route('login.attempt'), ['login' => 'sup', 'password' => 'nope'])->assertRedirect(route('login'))->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $this->entry->update(['is_active' => false]);
        $this->post(route('login.attempt'), ['login' => 'ent', 'password' => 'password'])->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_receiving_and_issuance_update_balance_and_moving_average(): void
    {
        $item = $this->item();
        $this->receive($item, 100, 10)->assertRedirect();
        $this->receive($item, 100, 20);
        $item->refresh();
        $this->assertSame(200.0, $item->stock_qty);
        $this->assertEqualsWithDelta(15.0, $item->avg_cost, 0.0001);

        $this->issue($item, 50)->assertRedirect();
        $item->refresh();
        $this->assertSame(150.0, $item->stock_qty);
        $this->assertEqualsWithDelta(15.0, $item->avg_cost, 0.0001);
        $this->assertSame(3, $item->movements()->count());
        $this->assertSame(150.0, $item->movements()->latest('id')->first()->balance_qty);
    }

    public function test_issuance_cannot_make_stock_negative(): void
    {
        $item = $this->item();
        $this->receive($item, 10, 5);
        $this->issue($item, 11)->assertSessionHasErrors('lines');
        $this->assertSame(10.0, $item->fresh()->stock_qty);
        $this->assertSame(0, StockDocument::where('type', 'OUT')->count());  // dokumen keluar dibatalkan seluruhnya
    }

    public function test_failed_save_keeps_the_typed_lines_with_item_labels(): void
    {
        $item = $this->item('I9');
        $this->receive($item, 1, 5);
        $this->from(route('docs.create', 'issuance'))->post(route('docs.store', 'issuance'), ['recipient' => 'Budi', 'lines' => [['item_id' => $item->id, 'qty' => 5]]])->assertSessionHasErrors('lines');

        $this->get(route('docs.create', 'issuance'))->assertOk()->assertSee('item_id_label', false)->assertSee('Item I9', false);
    }

    public function test_issuance_reaching_reorder_point_flashes_a_warning(): void
    {
        $item = $this->item('I2', rop: 5);
        $this->receive($item, 10, 5);
        $this->issue($item, 5)->assertSessionHas('reorder');
        $this->assertTrue($item->fresh()->isBelowReorderPoint());
        $this->actingAs($this->supervisor)->get(route('home'))->assertOk()->assertSee('Reorder Point Reached');
    }

    public function test_data_entry_cannot_open_supervisor_screens(): void
    {
        $this->actingAs($this->entry);
        $this->get(route('docs.index', 'adjustment'))->assertForbidden();
        $this->get(route('docs.create', 'opening'))->assertForbidden();
        $this->get(route('items.create'))->assertForbidden();
        $this->get(route('reports.adjustment'))->assertForbidden();
        $this->post(route('docs.store', 'adjustment'), [])->assertForbidden();
    }

    public function test_supervisor_manages_item_parameters(): void
    {
        $category = Category::create(['code' => 'ATK', 'name' => 'ATK']);
        $payload = ['code' => 'x-1', 'name' => 'Pen', 'category_id' => $category->id, 'unit' => 'box', 'unit_price' => 1500, 'reorder_point' => 5, 'reorder_qty' => 20, 'lead_time_days' => 4];
        $this->actingAs($this->supervisor)->post(route('items.store'), $payload)->assertRedirect();
        $item = Item::where('code', 'X-1')->firstOrFail();

        $this->put(route('items.update', $item), ['_action' => 'save', ...$payload, 'reorder_point' => 9])->assertRedirect();
        $this->assertSame(9.0, $item->fresh()->reorder_point);
        $this->put(route('items.update', $item), ['_action' => 'deactivate'])->assertRedirect();
        $this->assertFalse($item->fresh()->is_active);

        $this->actingAs($this->entry)->put(route('items.update', $item), ['_action' => 'save'] + $payload)->assertForbidden();
    }

    public function test_opening_balance_only_for_items_without_movements(): void
    {
        $fresh = $this->item('N1');
        $used = $this->item('N2');
        $this->receive($used, 5, 1);

        $this->actingAs($this->supervisor)->post(route('docs.store', 'opening'), ['doc_at' => today()->toDateString(), 'lines' => [['item_id' => $used->id, 'qty' => 3, 'unit_cost' => 2]]])->assertSessionHasErrors('lines');
        $this->post(route('docs.store', 'opening'), ['doc_at' => today()->toDateString(), 'lines' => [['item_id' => $fresh->id, 'qty' => 3, 'unit_cost' => 2]]])->assertRedirect();
        $this->assertSame(3.0, $fresh->fresh()->stock_qty);
        $this->assertEqualsWithDelta(2.0, $fresh->fresh()->avg_cost, 0.0001);
    }

    public function test_adjustment_needs_approval_before_stock_changes(): void
    {
        $item = $this->item();
        $this->receive($item, 100, 10);

        $this->actingAs($this->supervisor)->post(route('docs.store', 'adjustment'), ['reason' => 'Opname', 'lines' => [['item_id' => $item->id, 'counted_qty' => 90]]])->assertRedirect();
        $doc = StockDocument::where('type', 'ADJ')->firstOrFail();
        $this->assertSame('draft', $doc->status);
        $this->assertSame(-10.0, $doc->lines()->first()->qty);
        $this->assertSame(100.0, $item->fresh()->stock_qty);

        $this->put(route('docs.update', ['adjustment', $doc]), ['_action' => 'confirm', 'reason' => 'Opname', 'lines' => [['item_id' => $item->id, 'counted_qty' => 90]]]);
        $this->assertSame('approval', $doc->fresh()->status);
        $this->assertSame(100.0, $item->fresh()->stock_qty);

        $this->put(route('docs.update', ['adjustment', $doc]), ['_action' => 'approve'])->assertRedirect();
        $this->assertSame('approved', $doc->fresh()->status);
        $this->assertSame($this->supervisor->id, $doc->fresh()->approved_by);
        $this->assertSame(90.0, $item->fresh()->stock_qty);
    }

    public function test_adjustment_is_blocked_when_stock_moved_after_the_count(): void
    {
        $item = $this->item();
        $this->receive($item, 100, 10);
        $this->actingAs($this->supervisor)->post(route('docs.store', 'adjustment'), ['reason' => 'Opname', 'lines' => [['item_id' => $item->id, 'counted_qty' => 95]]]);
        $doc = StockDocument::where('type', 'ADJ')->firstOrFail();
        $this->put(route('docs.update', ['adjustment', $doc]), ['_action' => 'confirm', 'reason' => 'Opname', 'lines' => [['item_id' => $item->id, 'counted_qty' => 95]]]);

        $this->issue($item, 5);
        $this->actingAs($this->supervisor)->put(route('docs.update', ['adjustment', $doc]), ['_action' => 'approve'])->assertSessionHasErrors('lines');
        $this->assertSame('approval', $doc->fresh()->status);
        $this->assertSame(95.0, $item->fresh()->stock_qty);

        $this->put(route('docs.update', ['adjustment', $doc]), ['_action' => 'reject'])->assertRedirect();
        $this->assertSame('rejected', $doc->fresh()->status);
    }

    public function test_reports_render_and_hide_valuation_from_data_entry(): void
    {
        $item = $this->item();
        $this->receive($item, 10, 1234);

        foreach (['reports.summary', 'reports.receiving', 'reports.issuance', 'reports.stock-card'] as $route) {
            $this->actingAs($this->entry)->get(route($route, ['item_id' => $item->id]))->assertOk()->assertDontSee('Stock Value')->assertDontSee('Purchase Price')->assertDontSee('1234');
        }
        $this->actingAs($this->supervisor)->get(route('reports.summary'))->assertOk()->assertSee('Total Stock Value');
        $this->get(route('reports.receiving'))->assertOk()->assertSee('Purchase Price');
        $this->get(route('reports.adjustment'))->assertOk();
        $this->get(route('reports.stock-card', ['item_id' => $item->id, 'from' => today()->subDay()->toDateString(), 'to' => today()->toDateString()]))->assertOk()->assertSee('Item I1');
    }
}
