<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ReorderOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/* REMARK
 * Tujuan       : Mencatat bahwa barang di bawah Reorder Point sudah dipesan ke supplier (tanggal + nomor referensi PO). Pesanan ditutup otomatis oleh Receiving.
 * Sumber hulu  : form di opening screen (item_id, ordered_at, ref_no).
 * Output       : reorder_orders; kembali ke opening screen.
 * Dipakai oleh : routes/web.php (reorder-orders.store).
 * Status       : Aktif.
 */
class ReorderOrderController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'ordered_at' => ['required', 'date', 'before_or_equal:today'],
            'ref_no' => ['required', 'string', 'max:60'],
        ]);
        $item = Item::findOrFail($data['item_id']);
        abort_unless($item->isBelowReorderPoint(), 422, 'Barang ini tidak berada di bawah titik pesan ulang.');
        abort_if($item->openOrder()->exists(), 422, 'Barang ini sudah dicatat dipesan dan belum diterima.');

        ReorderOrder::create($data + ['created_by' => $request->user()->id]);

        return redirect()->route('home')->with('status', "Pemesanan {$item->name} dicatat ({$data['ref_no']}).");
    }
}
