<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Support\Palette;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/* REMARK
 * Tujuan       : Master Kategori barang. Awalan 3 karakter kategori menjadi dasar kode barang otomatis (Item::nextCode). Hanya Supervisor.
 *                Awalan tidak berubah setelah disimpan (kode barang lama memakainya); kategori tidak dihapus, hanya dinonaktifkan.
 * Sumber hulu  : categories (+ jumlah barang).
 * Output       : grid Kategori, form Kategori.
 * Dipakai oleh : routes/web.php (categories.*, grup supervisor).
 * Status       : Aktif.
 */
class CategoryController extends Controller
{
    public function index()
    {
        $rows = Category::query()->withCount('items')->orderBy('code')->get()->map(fn (Category $c) => [
            'id' => $c->id, 'code' => $c->code, 'name' => $c->name, 'hue' => Palette::item($c->code, $c->code), 'items' => $c->items_count, 'active' => $c->is_active,
        ])->all();

        return view('categories.index', ['rows' => $rows]);
    }

    public function create()
    {
        return view('categories.form', ['category' => new Category]);
    }

    public function show(Category $category)
    {
        return view('categories.form', ['category' => $category->loadCount('items')]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = Category::create($request->validated());

        return redirect()->route('categories.show', $category)->with('status', 'Kategori disimpan.');
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $action = $request->input('_action');
        if (in_array($action, ['activate', 'deactivate'], true)) {
            $category->update(['is_active' => $action === 'activate']);

            return redirect()->route('categories.show', $category)->with('status', $action === 'activate' ? 'Kategori diaktifkan.' : 'Kategori dinonaktifkan.');
        }
        $category->update($request->safe()->only('name'));

        return redirect()->route('categories.show', $category)->with('status', 'Kategori disimpan.');
    }

    public function toggle(Category $category): JsonResponse
    {
        $category->update(['is_active' => ! $category->is_active]);

        return response()->json(['active' => $category->is_active]);
    }
}
