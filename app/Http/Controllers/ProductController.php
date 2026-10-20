<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Unit;
use App\Support\Palette;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/* REMARK
 * Tujuan       : Master Barang (produk / group). Semua pengguna boleh melihat dan menambah barang baru; hanya Supervisor mengubah parameter (harga, titik pesan ulang, lead time) dan mengaktifkan / menonaktifkan.
 *                Kode otomatis {AWALAN KATEGORI}-{URUT}. Stok ada di batch (items): barang biasa satu batch, barang berkedaluwarsa satu batch per tanggal (dibuat otomatis oleh Penerimaan / Saldo Awal).
 *                Parameter yang diubah diteruskan ke semua batch (Product::propagate).
 * Sumber hulu  : products, items (stok, biaya rata-rata, kedaluwarsa), categories. Harga & nilai stok disembunyikan dari Data Entry.
 * Output       : grid Barang, form Barang (x-dl::form panel) dengan daftar batch.
 * Dipakai oleh : routes/web.php (products.*).
 * Status       : Aktif.
 */
class ProductController extends Controller
{
    public function index(Request $request)
    {
        $sup = $request->user()->isSupervisor();
        $rows = Product::query()->with('category')->withAvailable()
            ->selectRaw('(select coalesce(sum(stock_qty), 0) from items where items.product_id = products.id) as stock_total')
            ->selectRaw('(select coalesce(sum(stock_qty * avg_cost), 0) from items where items.product_id = products.id) as stock_value')
            ->selectRaw('(select min(expiry_date) from items where items.product_id = products.id and stock_qty > 0) as next_expiry')
            ->selectRaw('(select count(*) from items where items.product_id = products.id) as batch_count')
            ->orderBy('code')->get()->map(function (Product $p) use ($sup) {
                $below = $p->is_active && $p->reorder_point > 0 && (float) $p->available_qty <= $p->reorder_point;

                return [
                    'id' => $p->id, 'code' => $p->code, 'name' => $p->name, 'category' => $p->category->name, 'unit' => $p->unit, 'location' => $p->location,
                    'batches' => $p->tracks_expiry ? (int) $p->batch_count : null, 'expiry' => $p->next_expiry,
                    'stock' => (float) $p->stock_total, 'available' => (float) $p->available_qty, 'rop' => $p->reorder_point, 'roq' => $p->reorder_qty, 'lead' => $p->lead_time_days,
                    'price' => $sup ? $p->unit_price : null, 'value' => $sup ? round((float) $p->stock_value, 2) : null,
                    'hue' => Palette::item($p->category->code, $p->code), 'level' => $below ? 'PESAN ULANG' : 'AMAN', 'tone' => $below ? 'danger' : null, 'active' => $p->is_active,
                ];
            })->all();

        return view('products.index', ['rows' => $rows, 'sup' => $sup]);
    }

    public function create()
    {
        return view('products.form', ['product' => new Product(['unit' => 'pcs']), 'batches' => collect(), 'categories' => $this->categories(), 'units' => $this->units(), 'locations' => $this->locations(), 'nextCodes' => $this->nextCodes()]);
    }

    public function show(Product $product)
    {
        return view('products.form', [
            'product' => $product, 'batches' => $product->items()->orderByRaw('expiry_date is null')->orderBy('expiry_date')->get(), 'categories' => $this->categories(false),
            'units' => $this->units($product->unit), 'locations' => $this->locations($product->location), 'nextCodes' => [],
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = Product::register($request->validated());

        return redirect()->route('products.show', $product)->with('status', 'Barang disimpan.');
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $action = $request->input('_action');
        if (in_array($action, ['activate', 'deactivate'], true)) {
            $this->setActive($product, $action === 'activate');

            return redirect()->route('products.show', $product)->with('status', $action === 'activate' ? 'Barang diaktifkan.' : 'Barang dinonaktifkan.');
        }

        DB::transaction(function () use ($request, $product) {
            $product->update($request->validated());  // kode, kategori, dan tanda kedaluwarsa tidak berubah setelah disimpan
            $product->propagate();
        });

        return redirect()->route('products.show', $product)->with('status', 'Barang disimpan.');
    }

    // Dipanggil saklar Aktif/Nonaktif di grid (POST, balasan JSON {active}).
    public function toggle(Product $product): JsonResponse
    {
        $this->setActive($product, ! $product->is_active);

        return response()->json(['active' => $product->is_active]);
    }

    private function setActive(Product $product, bool $active): void
    {
        DB::transaction(function () use ($product, $active) {
            $product->update(['is_active' => $active]);
            $product->propagate();
        });
    }

    // Kategori aktif untuk barang baru; barang yang sudah ada menampilkan kategorinya sendiri (terkunci, kode memakai awalannya).
    private function categories(bool $activeOnly = true): array
    {
        return Category::query()->when($activeOnly, fn ($q) => $q->where('is_active', true))->orderBy('name')->pluck('name', 'id')->all();
    }

    // Kode berikutnya per kategori, untuk pratinjau di form: [category_id => 'ATK-0008'].
    private function nextCodes(): array
    {
        return Category::where('is_active', true)->get()->mapWithKeys(fn (Category $c) => [$c->id => Product::nextCode($c)])->all();
    }

    // Pilihan satuan = master Units aktif (+ satuan barang ini sendiri bila sudah dinonaktifkan).
    private function units(?string $keep = null): array
    {
        return Unit::query()->where('is_active', true)->orWhere('code', $keep)->orderBy('code')->get()->mapWithKeys(fn (Unit $u) => [$u->code => "{$u->code} · {$u->name}"])->all();
    }

    // Pilihan lokasi = master Lokasi aktif (+ lokasi barang ini sendiri bila sudah dinonaktifkan).
    private function locations(?string $keep = null): array
    {
        return Location::query()->where('is_active', true)->orWhere('code', $keep)->orderBy('code')->get()->mapWithKeys(fn (Location $l) => [$l->code => $l->label()])->all();
    }
}
