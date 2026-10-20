<?php

namespace App\Http\Controllers;

use App\Http\Requests\ItemRequest;
use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use App\Models\Unit;
use App\Support\Palette;
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
            'id' => $i->id, 'code' => $i->code, 'name' => $i->name, 'category' => $i->category->name, 'unit' => $i->unit, 'location' => $i->location, 'expiry' => $i->expiry_date?->format('Y-m-d'),
            'stock' => $i->stock_qty, 'rop' => $i->reorder_point, 'roq' => $i->reorder_qty, 'lead' => $i->lead_time_days,
            'price' => $sup ? $i->unit_price : null, 'value' => $sup ? $i->stockValue() : null,
            'hue' => Palette::item($i->category->code, $i->code), 'level' => $i->isBelowReorderPoint() ? 'PESAN ULANG' : 'AMAN', 'tone' => $i->isBelowReorderPoint() ? 'danger' : null, 'active' => $i->is_active,
        ])->all();

        return view('items.index', ['rows' => $rows, 'sup' => $sup, 'categories' => Category::orderBy('name')->pluck('name', 'name')->all()]);
    }

    public function create()
    {
        return view('items.form', ['item' => new Item(['unit' => 'pcs']), 'categories' => $this->categories(), 'units' => $this->units(), 'locations' => $this->locations(), 'nextCodes' => $this->nextCodes()]);
    }

    public function show(Item $item)
    {
        return view('items.form', ['item' => $item, 'categories' => $this->categories(false), 'units' => $this->units($item->unit), 'locations' => $this->locations($item->location), 'nextCodes' => []]);
    }

    public function store(ItemRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['code'] = Item::nextCode(Category::findOrFail($data['category_id']), $data['expiry_date'] ?? null);
        $item = Item::create($data);

        return redirect()->route('items.show', $item)->with('status', 'Barang disimpan.');
    }

    public function update(ItemRequest $request, Item $item): RedirectResponse
    {
        $action = $request->input('_action');
        if (in_array($action, ['activate', 'deactivate'], true)) {
            $item->update(['is_active' => $action === 'activate']);

            return redirect()->route('items.show', $item)->with('status', $action === 'activate' ? 'Barang diaktifkan.' : 'Barang dinonaktifkan.');
        }

        $item->update($request->validated());  // kode, kategori, dan tanggal kedaluwarsa tidak berubah setelah disimpan

        return redirect()->route('items.show', $item)->with('status', 'Barang disimpan.');
    }

    // Kategori aktif untuk barang baru; barang yang sudah ada menampilkan kategorinya sendiri (terkunci, kode memakai awalannya).
    private function categories(bool $activeOnly = true): array
    {
        return Category::query()->when($activeOnly, fn ($q) => $q->where('is_active', true))->orderBy('name')->pluck('name', 'id')->all();
    }

    // Kode berikutnya (tanpa kedaluwarsa) per kategori, untuk pratinjau di form: [category_id => 'ATK-0008'].
    private function nextCodes(): array
    {
        return Category::where('is_active', true)->get()->mapWithKeys(fn (Category $c) => [$c->id => Item::nextCode($c)])->all();
    }

    // Pilihan lokasi = master Lokasi aktif (+ lokasi barang ini sendiri bila sudah dinonaktifkan).
    private function locations(?string $keep = null): array
    {
        return Location::query()->where('is_active', true)->orWhere('code', $keep)->orderBy('code')->get()->mapWithKeys(fn (Location $l) => [$l->code => $l->label()])->all();
    }

    // Pilihan satuan = master Units aktif (+ satuan item ini sendiri bila sudah dinonaktifkan).
    private function units(?string $keep = null): array
    {
        return Unit::query()->where('is_active', true)->orWhere('code', $keep)->orderBy('code')->get()->mapWithKeys(fn (Unit $u) => [$u->code => "{$u->code} · {$u->name}"])->all();
    }

    // Dipanggil saklar Aktif/Nonaktif di grid (POST, balasan JSON {active}).
    public function toggle(Item $item): JsonResponse
    {
        $item->update(['is_active' => ! $item->is_active]);

        return response()->json(['active' => $item->is_active]);
    }
}
