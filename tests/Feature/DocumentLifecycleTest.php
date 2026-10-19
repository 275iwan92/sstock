<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\Item;
use App\Models\Recipient;
use App\Models\ReorderOrder;
use App\Models\StockDocument;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga siklus dokumen stok: draf belum menyentuh stok (boleh Delete), Confirm memproses ke stok, Cancel (Supervisor, alasan wajib) membalik stok lewat baris pembalik, dan yang dibatalkan tidak dihitung di laporan / KPI.
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class DocumentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $entry;

    private User $sup;

    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entry = User::factory()->create();
        $this->sup = User::factory()->supervisor()->create();
        $cat = Category::create(['code' => 'ATK', 'name' => 'ATK']);
        $this->item = Item::create(['code' => 'L1', 'name' => 'Item L1', 'category_id' => $cat->id, 'unit' => 'pcs', 'unit_price' => 10, 'reorder_point' => 5, 'reorder_qty' => 20]);
    }

    private function receivePayload(float $qty, float $cost = 10): array
    {
        return ['supplier_id' => Supplier::firstOrCreate(['code' => 'S1'], ['name' => 'S'])->id, 'lines' => [['item_id' => $this->item->id, 'qty' => $qty, 'unit_cost' => $cost]]];
    }

    private function issuePayload(float $qty): array
    {
        $dept = Department::firstOrCreate(['code' => 'D1'], ['name' => 'Dept']);

        return ['recipient_id' => Recipient::firstOrCreate(['name' => 'R'], ['department_id' => $dept->id])->id, 'lines' => [['item_id' => $this->item->id, 'qty' => $qty]]];
    }

    private function draft(string $slug, array $payload, User $by): StockDocument
    {
        $this->actingAs($by)->post(route('docs.store', $slug), $payload)->assertSessionHasNoErrors();

        return StockDocument::latest('id')->firstOrFail();
    }

    private function act(StockDocument $doc, string $action, array $payload = [], ?User $by = null, string $reason = '')
    {
        return $this->actingAs($by ?? $this->entry)->put(route('docs.update', [$doc->type->slug(), $doc]), $payload + ['_action' => $action, '_reason' => $reason]);
    }

    public function test_a_new_document_is_a_draft_that_does_not_touch_stock_until_confirm(): void
    {
        $doc = $this->draft('receiving', $this->receivePayload(10), $this->entry);
        $this->assertSame('draft', $doc->status);
        $this->assertSame(0.0, $this->item->fresh()->stock_qty);
        $this->assertSame(0, $doc->movements()->count());

        $this->act($doc, 'confirm', $this->receivePayload(10))->assertRedirect()->assertSessionHas('status');
        $this->assertSame(['confirmed', 10.0], [$doc->fresh()->status, $this->item->fresh()->stock_qty]);
        $this->assertNotNull($doc->fresh()->confirmed_at);

        $this->act($doc, 'confirm', $this->receivePayload(10))->assertStatus(422);   // tidak bisa dua kali
        $this->act($doc, 'save', $this->receivePayload(99))->assertStatus(422);       // sudah Confirm: tidak bisa diubah
        $this->assertSame(10.0, $this->item->fresh()->stock_qty);
    }

    public function test_a_draft_can_be_deleted_and_a_confirmed_document_cannot(): void
    {
        $draft = $this->draft('issuance', $this->issuePayload(1), $this->entry);
        $this->act($draft, 'delete')->assertRedirect(route('docs.index', 'issuance'));
        $this->assertNull(StockDocument::find($draft->id));

        $this->act($this->draft('receiving', $this->receivePayload(3), $this->entry), 'confirm', $this->receivePayload(3));
        $this->act(StockDocument::latest('id')->first(), 'delete')->assertStatus(422);
    }

    public function test_cancel_needs_a_supervisor_and_a_reason_and_restores_stock_with_reversal_rows(): void
    {
        $in = $this->draft('receiving', $this->receivePayload(10, 10), $this->entry);
        $this->act($in, 'confirm', $this->receivePayload(10, 10));
        $out = $this->draft('issuance', $this->issuePayload(4), $this->entry);
        $this->act($out, 'confirm', $this->issuePayload(4));
        $this->assertSame(6.0, $this->item->fresh()->stock_qty);

        $this->act($out, 'cancel', [], $this->entry, 'salah input')->assertForbidden();           // Data Entry tidak boleh
        $this->act($out, 'cancel', [], $this->sup, '')->assertStatus(422);                        // alasan wajib
        $this->assertSame(6.0, $this->item->fresh()->stock_qty);

        $this->act($out, 'cancel', [], $this->sup, 'salah input')->assertRedirect()->assertSessionHas('status');
        $out->refresh();
        $this->assertSame(['cancelled', 'salah input', $this->sup->id], [$out->status, $out->cancel_reason, $out->cancelled_by]);
        $this->assertSame(10.0, $this->item->fresh()->stock_qty);
        $this->assertEqualsWithDelta(10.0, $this->item->fresh()->avg_cost, 0.0001);
        $this->assertSame(2, $out->movements()->count());                                           // baris asal tetap + satu baris pembalik
        $this->assertSame(1, $out->movements()->whereNotNull('reversal_of')->count());

        $this->act($out, 'cancel', [], $this->sup, 'lagi')->assertStatus(422);                    // tidak bisa dibatalkan dua kali
    }

    public function test_cancelling_a_receipt_is_blocked_when_the_stock_was_already_used(): void
    {
        $in = $this->draft('receiving', $this->receivePayload(10), $this->entry);
        $this->act($in, 'confirm', $this->receivePayload(10));
        $out = $this->draft('issuance', $this->issuePayload(8), $this->entry);
        $this->act($out, 'confirm', $this->issuePayload(8));

        $this->act($in, 'cancel', [], $this->sup, 'salah supplier')->assertSessionHasErrors('lines');
        $this->assertSame(['confirmed', 2.0], [$in->fresh()->status, $this->item->fresh()->stock_qty]);
    }

    public function test_cancelled_documents_drop_out_of_reports_and_the_reopened_reorder_order_returns(): void
    {
        $item = $this->item;
        $order = ReorderOrder::create(['item_id' => $item->id, 'ordered_at' => today(), 'ref_no' => 'PO-1']);

        $in = $this->draft('receiving', $this->receivePayload(10), $this->entry);
        $this->act($in, 'confirm', $this->receivePayload(10));
        $this->assertNotNull($order->fresh()->closed_at);
        $this->actingAs($this->sup)->get(route('reports.receiving'))->assertOk()->assertSee($in->doc_no);

        $this->act($in, 'cancel', [], $this->sup, 'barang tidak jadi datang');
        $this->assertNull($order->fresh()->closed_at);                                               // pemesanan terbuka lagi
        $this->actingAs($this->sup)->get(route('reports.receiving'))->assertOk()->assertDontSee($in->doc_no);
        $this->get(route('reports.stock-card', ['item_id' => $item->id, 'from' => today()->subDay()->toDateString(), 'to' => today()->toDateString()]))->assertOk()->assertSee('(BATAL)');
        $this->get(route('home'))->assertOk();
    }

    public function test_adjustment_cancel_reverses_an_approved_one_and_withdraws_a_pending_one(): void
    {
        $in = $this->draft('receiving', $this->receivePayload(10), $this->entry);
        $this->act($in, 'confirm', $this->receivePayload(10));

        $payload = ['reason' => 'Opname', 'lines' => [['item_id' => $this->item->id, 'counted_qty' => 7]]];
        $adj = $this->draft('adjustment', $payload, $this->sup);
        $this->act($adj, 'confirm', $payload, $this->sup);                                           // diajukan
        $this->assertSame('approval', $adj->fresh()->status);
        $this->assertSame(10.0, $this->item->fresh()->stock_qty);

        $this->act($adj, 'cancel', [], $this->sup, 'dihitung ulang')->assertRedirect();             // ditarik: stok tak berubah
        $this->assertSame(['cancelled', 10.0], [$adj->fresh()->status, $this->item->fresh()->stock_qty]);

        $adj2 = $this->draft('adjustment', $payload, $this->sup);
        $this->act($adj2, 'confirm', $payload, $this->sup);
        $this->act($adj2, 'approve', [], $this->sup);
        $this->assertSame(7.0, $this->item->fresh()->stock_qty);
        $this->act($adj2, 'cancel', [], $this->sup, 'opname keliru');
        $this->assertSame(10.0, $this->item->fresh()->stock_qty);
    }

    public function test_the_form_shows_delete_while_draft_and_cancel_after_confirm(): void
    {
        $doc = $this->draft('issuance', $this->issuePayload(1), $this->entry);
        $html = $this->actingAs($this->entry)->get(route('docs.show', ['issuance', $doc]))->assertOk()->getContent();
        $this->assertStringContainsString('data-f-act="delete"', $html);
        $this->assertStringContainsString('data-f-act="confirm"', $html);
        $this->assertStringNotContainsString('data-f-act="cancel"', $html);

        $this->act($this->draft('receiving', $this->receivePayload(5), $this->entry), 'confirm', $this->receivePayload(5));
        $this->act($doc, 'confirm', $this->issuePayload(1));
        $confirmed = $this->actingAs($this->sup)->get(route('docs.show', ['issuance', $doc]))->getContent();
        $this->assertStringContainsString('data-f-act="cancel"', $confirmed);
        $this->assertStringNotContainsString('data-f-act="cancel"', $this->actingAs($this->entry)->get(route('docs.show', ['issuance', $doc]))->getContent());   // Data Entry tidak melihat Cancel
    }
}
