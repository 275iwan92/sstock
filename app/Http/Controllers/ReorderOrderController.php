<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ReorderOrder;
use App\Services\ReorderOrders;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/* REMARK
 * Tujuan       : Mencatat bahwa barang di bawah Reorder Point sudah dipesan (catatan sederhana, bukan PO): supplier, jumlah dalam satuan stok, tanggal, referensi dan catatan opsional. Pesanan ditutup otomatis bila jumlah yang diterima (Receiving) memenuhi jumlah dipesan, atau ditutup manual bila tidak akan dipenuhi penuh.
 * Sumber hulu  : form di opening screen (product_id, supplier_id, qty, ordered_at, ref_no, note); popup Tutup Pesanan (alasan opsional).
 * Output       : reorder_orders; kembali ke opening screen.
 * Dipakai oleh : routes/web.php (reorder-orders.store, reorder-orders.close).
 * Status       : Aktif.
 */
class ReorderOrderController extends Controller
{
    public function __construct(private readonly ReorderOrders $orders) {}

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('is_active', true)],
            'qty' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'ordered_at' => ['required', 'date', 'before_or_equal:today'],
            'ref_no' => ['nullable', 'string', 'max:60'],
            'note' => ['nullable', 'string', 'max:255'],
        ], ['qty.gt' => 'Jumlah dipesan harus lebih dari 0.']);
        $product = Product::findOrFail($data['product_id']);
        abort_unless($product->isBelowReorderPoint(), 422, 'Barang ini tidak berada di bawah titik pesan ulang.');
        abort_if($product->openOrder()->exists(), 422, 'Barang ini sudah dicatat dipesan dan belum diterima.');

        $data['ref_no'] ??= '';   // kolom ref_no tidak boleh null; kosong = tanpa referensi
        ReorderOrder::create($data + ['created_by' => $request->user()->id]);
        $this->orders->reconcile($product->id);   // penerimaan sejak tanggal pesan (termasuk hari yang sama) langsung dihitung

        return redirect()->route('home')->with('status', "Pemesanan {$product->name} dicatat.");
    }

    // Pesanan yang tidak akan dipenuhi penuh (supplier mengirim kurang): ditutup tanpa menghapus apa pun; hanya yang masih terbuka.
    public function close(Request $request, ReorderOrder $order): RedirectResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:150']]);
        $this->orders->closeManually($order, $data['note'] ?? null);

        return redirect()->route('home')->with('status', "Pesanan {$order->product->name} ditutup.");
    }
}
