<?php

namespace App\Http\Controllers;

use App\Enums\StockDocumentType;
use App\Models\Item;
use App\Models\StockDocument;
use App\Services\StockValuation;
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
        $below = Item::query()->belowReorderPoint()->with('category', 'openOrder')->orderByRaw('stock_qty / reorder_point')->get();

        return view('home', [
            'modules' => Menu::modules($user),
            'user' => ['name' => $user->name, 'email' => $user->email, 'role' => $user->roleLabel()],
            'admin' => $user->isAdministrator(),
            'open' => $request->query('open'),
            'fresh' => (bool) session('fresh'),
            'below' => $below,
            'expiring' => Item::query()->nearExpiry()->orderBy('expiry_date')->get(),
            'itemCount' => Item::query()->where('is_active', true)->count(),
            'stockValue' => $user->isSupervisor() ? round(Item::query()->selectRaw('SUM(stock_qty * avg_cost) v')->value('v') ?? 0, 2) : null,
            'receivingToday' => StockDocument::query()->where('type', StockDocumentType::Receiving)->whereIn('status', ['confirmed', 'cancel_pending'])->whereDate('doc_at', today())->count(),
            'issuanceToday' => StockDocument::query()->where('type', StockDocumentType::Issuance)->whereIn('status', ['confirmed', 'cancel_pending'])->whereDate('doc_at', today())->count(),
            'valuation' => $user->isSupervisor() ? StockValuation::series($request->query('month')) : null,
            'pending' => $user->isSupervisor() ? StockDocument::query()->whereIn('status', ['approval', 'cancel_pending'])->count() : null,
        ]);
    }
}
