<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Location;
use App\Models\StockCount;
use App\Services\StockCounts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/* REMARK
 * Tujuan       : Layar Stok Opname (penghitungan fisik rutin): daftar, mulai, lembar input hasil hitung, tinjau selisih, setujui, cetak lembar hitung dan Berita Acara.
 *                Hak: lihat / isi hasil hitung / cetak lembar = akses modul SO; mulai, tinjau, setujui, kembalikan, batalkan, buka kunci = Supervisor (rute).
 * Sumber hulu  : StockCounts (logika), StockCount / StockCountLine.
 * Output       : stock-counts.index (grid), stock-counts.form (mulai), stock-counts.show (header + input / tinjau), cetakan.
 * Dipakai oleh : routes/web.php (stock-counts.*).
 * Status       : Aktif.
 */
class StockCountController extends Controller
{
    public function __construct(private readonly StockCounts $counts) {}

    public function index(Request $request)
    {
        $filled = fn ($q) => $q->where(fn ($w) => $w->where('skipped', true)->orWhereNotNull('counted_qty')->orWhereNotNull('recount_qty'));
        $rows = StockCount::query()->withCount(['lines', 'lines as filled_lines_count' => $filled])->with(['creator', 'adjustmentDocument'])->latest('count_date')->latest('id')->limit(1000)->get()
            ->map(fn (StockCount $c) => [
                'id' => $c->id, 'doc_no' => $c->doc_no, 'count_date' => $c->count_date->format('Y-m-d'), 'scope' => $c->scopeLabel(),
                'progress' => $c->filled_lines_count.' / '.$c->lines_count, 'adjustment' => $c->adjustmentDocument?->doc_no ?? '—',
                'status' => $c->statusLabel(), 'user' => $c->creator->name, 'active' => true,
            ])->all();

        return view('stock-counts.index', ['rows' => $rows, 'sup' => $request->user()->isSupervisor()]);
    }

    public function create()
    {
        return view('stock-counts.form', [
            'categories' => Category::query()->orderBy('code')->get()->mapWithKeys(fn (Category $c) => [$c->code => "{$c->code} · {$c->name}"])->all(),
            'locations' => Location::query()->where('is_active', true)->orderBy('code')->get()->mapWithKeys(fn (Location $l) => [$l->code => "{$l->code} · {$l->name}"])->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'count_date' => ['required', 'date'],
            'scope_type' => ['required', Rule::in(array_keys(StockCount::SCOPE))],
            'category_code' => ['nullable', 'required_if:scope_type,category', 'exists:categories,code'],
            'location_code' => ['nullable', 'required_if:scope_type,location', 'exists:locations,code'],
            'threshold_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], ['category_code.required_if' => 'Pilih kategori untuk cakupan per Kategori.', 'location_code.required_if' => 'Pilih lokasi untuk cakupan per Lokasi.'], ['category_code' => 'kategori', 'location_code' => 'lokasi', 'count_date' => 'tanggal', 'scope_type' => 'cakupan', 'threshold_pct' => 'ambang selisih']);

        $count = $this->counts->start($request->user(), [
            'count_date' => $data['count_date'], 'scope_type' => $data['scope_type'], 'scope_value' => match ($data['scope_type']) {
                'category' => $data['category_code'], 'location' => $data['location_code'], default => null,
            },
            'blind' => $request->boolean('blind'), 'lock_transactions' => $request->boolean('lock_transactions'), 'threshold_pct' => $data['threshold_pct'], 'note' => $data['note'] ?? null,
        ]);

        return redirect()->route('stock-counts.show', $count)->with('status', "{$count->doc_no} dimulai: {$count->lines()->count()} batch masuk lembar hitung. Stok belum berubah sebelum opname disetujui.");
    }

    public function show(Request $request, StockCount $count)
    {
        return view('stock-counts.show', ['count' => $count->load(['creator', 'unlocker', 'adjustmentDocument']), 'sup' => $request->user()->isSupervisor(), 'summary' => $this->counts->summary($count)]);
    }

    public function cancel(StockCount $count): RedirectResponse
    {
        $this->counts->cancel($count, request()->user());

        return redirect()->route('stock-counts.show', $count)->with('status', "{$count->doc_no} dibatalkan. Kunci transaksi dilepas, stok tidak berubah.");
    }

    public function unlock(StockCount $count): RedirectResponse
    {
        $this->counts->unlock($count, request()->user());

        return redirect()->route('stock-counts.show', $count)->with('status', "Kunci transaksi {$count->doc_no} dibuka. Transaksi stok boleh diproses lagi; stok yang bergeser sejak cut-off akan ditolak saat persetujuan.");
    }
}
