<?php

namespace App\Http\Controllers;

use App\Enums\StockDocumentType;
use App\Models\Item;
use App\Models\StockDocument;
use App\Support\Menu;
use Illuminate\Http\Request;

/* REMARK
 * Tujuan       : Layar utama setelah login: Layer Shell (menu per peran) dengan dashboard KPI dan peringatan Reorder Point.
 * Sumber hulu  : items (stock_qty, reorder_point), stock_documents (hari ini, menunggu persetujuan).
 * Output       : view home; peringatan item bawah ROP tampil sebagai kartu alert di dashboard (tanpa popup).
 * Dipakai oleh : route home (tujuan redirect login).
 * Status       : Aktif.
 */
class HomeController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $below = Item::query()->belowReorderPoint()->with('category')->orderByRaw('stock_qty / reorder_point')->get();

        return view('home', [
            'modules' => Menu::modules($user),
            'user' => ['name' => $user->name, 'email' => $user->email, 'role' => $user->roleLabel()],
            'open' => $request->query('open'),
            'fresh' => (bool) session('fresh'),
            'below' => $below,
            'itemCount' => Item::query()->where('is_active', true)->count(),
            'stockValue' => $user->isSupervisor() ? round(Item::query()->selectRaw('SUM(stock_qty * avg_cost) v')->value('v') ?? 0, 2) : null,
            'todayCount' => StockDocument::query()->whereIn('type', [StockDocumentType::Receiving, StockDocumentType::Issuance])->whereDate('doc_at', today())->count(),
            'pending' => $user->isSupervisor() ? StockDocument::query()->where('status', 'approval')->count() : null,
        ]);
    }
}
