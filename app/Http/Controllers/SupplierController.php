<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/* REMARK
 * Tujuan       : Master Supplier (dipilih di transaksi Receiving). Data Entry dan Supervisor sama-sama boleh mengelola.
 * Sumber hulu  : suppliers.
 * Output       : grid Suppliers, form Supplier.
 * Dipakai oleh : routes/web.php (suppliers.*).
 * Status       : Aktif.
 */
class SupplierController extends Controller
{
    public function index()
    {
        $rows = Supplier::query()->orderBy('code')->get()->map(fn (Supplier $s) => [
            'id' => $s->id, 'code' => $s->code, 'name' => $s->name, 'phone' => $s->phone, 'address' => $s->address, 'active' => $s->is_active,
        ])->all();

        return view('suppliers.index', ['rows' => $rows]);
    }

    public function create()
    {
        return view('suppliers.form', ['supplier' => new Supplier]);
    }

    public function show(Supplier $supplier)
    {
        return view('suppliers.form', ['supplier' => $supplier]);
    }

    public function store(SupplierRequest $request): RedirectResponse
    {
        $supplier = Supplier::create($request->validated());

        return redirect()->route('suppliers.show', $supplier)->with('status', 'Supplier saved.');
    }

    public function update(SupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $action = $request->input('_action');
        if (in_array($action, ['activate', 'deactivate'], true)) {
            $supplier->update(['is_active' => $action === 'activate']);

            return redirect()->route('suppliers.show', $supplier)->with('status', $action === 'activate' ? 'Supplier activated.' : 'Supplier deactivated.');
        }

        $supplier->update($request->validated());

        return redirect()->route('suppliers.show', $supplier)->with('status', 'Supplier saved.');
    }

    public function toggle(Supplier $supplier): JsonResponse
    {
        $supplier->update(['is_active' => ! $supplier->is_active]);

        return response()->json(['active' => $supplier->is_active]);
    }
}
