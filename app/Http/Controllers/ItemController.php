<?php

namespace App\Http\Controllers;

use App\Http\Requests\ItemRequest;
use App\Models\Category;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/* REMARK
 * Tujuan       : Master Item/Barang. Semua user boleh melihat; hanya Supervisor menambah, mengubah parameter (harga, ROP, ROQ, lead time) dan mengaktifkan/menonaktifkan.
 * Sumber hulu  : items, categories. Harga & nilai stok disembunyikan dari Data Entry.
 * Output       : grid Items, form Item (x-dl::form panel).
 * Dipakai oleh : routes/web.php (items.*).
 * Status       : Aktif.
 */
class ItemController extends Controller
{
    public function index(Request $request)
    {
        $sup = $request->user()->isSupervisor();
        $rows = Item::query()->with('category')->orderBy('code')->get()->map(fn (Item $i) => [
            'id' => $i->id, 'code' => $i->code, 'name' => $i->name, 'category' => $i->category->name, 'unit' => $i->unit, 'location' => $i->location,
            'stock' => $i->stock_qty, 'rop' => $i->reorder_point, 'roq' => $i->reorder_qty, 'lead' => $i->lead_time_days,
            'price' => $sup ? $i->unit_price : null, 'value' => $sup ? $i->stockValue() : null,
            'level' => $i->isBelowReorderPoint() ? 'REORDER' : 'OK', 'tone' => $i->isBelowReorderPoint() ? 'danger' : null, 'active' => $i->is_active,
        ])->all();

        return view('items.index', ['rows' => $rows, 'sup' => $sup, 'categories' => Category::orderBy('name')->pluck('name', 'name')->all()]);
    }

    public function create()
    {
        return view('items.form', ['item' => new Item(['unit' => 'pcs']), 'categories' => Category::orderBy('name')->pluck('name', 'id')->all()]);
    }

    public function show(Item $item)
    {
        return view('items.form', ['item' => $item, 'categories' => Category::orderBy('name')->pluck('name', 'id')->all()]);
    }

    public function store(ItemRequest $request): RedirectResponse
    {
        $item = Item::create($request->validated());

        return redirect()->route('items.show', $item)->with('status', 'Item saved.');
    }

    public function update(ItemRequest $request, Item $item): RedirectResponse
    {
        $action = $request->input('_action');
        if (in_array($action, ['activate', 'deactivate'], true)) {
            $item->update(['is_active' => $action === 'activate']);

            return redirect()->route('items.show', $item)->with('status', $action === 'activate' ? 'Item activated.' : 'Item deactivated.');
        }

        $item->update($request->validated());

        return redirect()->route('items.show', $item)->with('status', 'Item saved.');
    }

    // Dipanggil saklar Aktif/Nonaktif di grid (POST, balasan JSON {active}).
    public function toggle(Item $item): JsonResponse
    {
        $item->update(['is_active' => ! $item->is_active]);

        return response()->json(['active' => $item->is_active]);
    }
}
