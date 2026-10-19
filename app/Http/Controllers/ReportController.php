<?php

namespace App\Http\Controllers;

use App\Enums\StockDocumentType;
use App\Models\Category;
use App\Models\Item;
use App\Models\StockDocumentLine;
use App\Models\StockMovement;
use App\Models\Supplier;
use Devlib\Locale;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/* REMARK
 * Tujuan       : Laporan utama: Kartu Stok, Summary Stock, Penerimaan, Pengeluaran, Opname & Penyesuaian. Semua tampil sebagai x-dl::report (kop, kriteria, ringkasan, kelompok, cetak).
 * Sumber hulu  : stock_movements (kartu stok), items (summary), stock_document_lines + stock_documents (laporan dokumen). Kolom harga / nilai hanya untuk Supervisor.
 * Output       : view reports.* dengan filter GET (periode, item, dst) + x-dl::report.
 * Dipakai oleh : routes/web.php (reports.*).
 * Status       : Aktif.
 */
class ReportController extends Controller
{
    private const TYPE_LABEL = ['OPENING' => 'OPN', 'IN' => 'IN', 'OUT' => 'OUT', 'ADJ' => 'ADJ'];

    public function stockCard(Request $request)
    {
        $sup = $request->user()->isSupervisor();
        [$from, $to] = $this->period($request);
        $item = $request->filled('item_id') ? Item::with('category')->find($request->query('item_id')) : null;
        $rows = [];
        $opening = 0.0;

        if ($item) {
            $before = StockMovement::query()->where('item_id', $item->id)->where('moved_at', '<', $from)->orderByDesc('moved_at')->orderByDesc('id')->first();
            $opening = $before?->balance_qty ?? 0.0;
            $rows = StockMovement::query()->where('item_id', $item->id)->whereBetween('moved_at', [$from, $to])->with(['document.supplier', 'user'])
                ->orderBy('moved_at')->orderBy('id')->get()->map(fn (StockMovement $m) => [
                    'date' => Locale::stamp($m->moved_at), 'month' => $m->moved_at->format('Y-m'),
                    'doc' => $m->document->doc_no, 'src' => $m->document->supplier?->name ?? $m->document->recipient ?? $m->document->reason,
                    'type' => self::TYPE_LABEL[$m->type], 'ref' => $m->document->ref_no ?? $m->document->department ?? $m->document->reason ?? '', 'user' => $m->user->name,
                    'in' => in_array($m->type, ['IN', 'OPENING'], true) ? $m->qty : 0, 'out' => $m->type === 'OUT' ? abs($m->qty) : 0, 'adj' => $m->type === 'ADJ' ? $m->qty : 0,
                    'cost' => $sup ? $m->unit_cost : null, 'bal_value' => $sup ? round($m->balance_qty * $m->balance_avg_cost, 2) : null,
                ])->all();
        }

        return view('reports.stock-card', [
            'sup' => $sup, 'from' => $from, 'to' => $to, 'item' => $item, 'rows' => $rows, 'opening' => $opening,
            'items' => Item::query()->orderBy('code')->get()->mapWithKeys(fn ($i) => [$i->id => "{$i->code} · {$i->name}"])->all(),
        ]);
    }

    public function summary(Request $request)
    {
        $sup = $request->user()->isSupervisor();
        $query = Item::query()->with('category')->orderBy('code');
        $query->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->query('category_id')));
        $query->when($request->boolean('only_reorder'), fn ($q) => $q->belowReorderPoint());
        $query->when(! $request->boolean('inactive'), fn ($q) => $q->where('is_active', true));
        $items = $query->get();

        return view('reports.summary', [
            'sup' => $sup, 'categories' => Category::orderBy('name')->pluck('name', 'id')->all(), 'items' => $items,
            'rows' => $items->map(fn (Item $i) => [
                'code' => $i->code, 'name' => $i->name, 'loc' => $i->location, 'cat' => $i->category->name, 'unit' => $i->unit,
                'stock' => $i->stock_qty, 'rop' => $i->reorder_point, 'roq' => $i->reorder_qty, 'lead' => $i->lead_time_days,
                'status' => $i->isBelowReorderPoint() ? 'REORDER' : 'OK',
                'cost' => $sup ? $i->avg_cost : null, 'value' => $sup ? $i->stockValue() : null,
            ])->all(),
        ]);
    }

    public function receiving(Request $request)
    {
        return $this->documentReport($request, StockDocumentType::Receiving, 'reports.receiving', fn ($q) => $q
            ->when($request->filled('supplier_id'), fn ($w) => $w->whereHas('document', fn ($d) => $d->where('supplier_id', $request->query('supplier_id')))),
            ['suppliers' => Supplier::orderBy('name')->pluck('name', 'id')->all()]);
    }

    public function issuance(Request $request)
    {
        return $this->documentReport($request, StockDocumentType::Issuance, 'reports.issuance', fn ($q) => $q
            ->when($request->filled('who'), fn ($w) => $w->whereHas('document', fn ($d) => $d->where(fn ($x) => $x
                ->where('recipient', 'like', '%'.$request->query('who').'%')->orWhere('department', 'like', '%'.$request->query('who').'%')))));
    }

    public function adjustment(Request $request)
    {
        return $this->documentReport($request, StockDocumentType::Adjustment, 'reports.adjustment', fn ($q) => $q
            ->when($request->filled('status'), fn ($w) => $w->whereHas('document', fn ($d) => $d->where('status', $request->query('status')))));
    }

    private function documentReport(Request $request, StockDocumentType $type, string $view, \Closure $filter, array $extra = [])
    {
        $sup = $request->user()->isSupervisor();
        [$from, $to] = $this->period($request);

        $lines = StockDocumentLine::query()->with(['item', 'document.supplier', 'document.creator', 'document.approver'])
            ->whereHas('document', fn ($d) => $d->where('type', $type)->whereBetween('doc_at', [$from, $to]));
        $lines = $filter($lines)->get()->sortBy([fn ($a, $b) => $a->document->doc_at <=> $b->document->doc_at, fn ($a, $b) => $a->id <=> $b->id]);

        $rows = $lines->map(function (StockDocumentLine $l) use ($sup) {
            $d = $l->document;
            $cost = (float) ($l->unit_cost ?? $l->item->avg_cost);

            return [
                'date' => Locale::stamp($d->doc_at), 'month' => $d->doc_at->format('Y-m'), 'doc' => $d->doc_no, 'status' => ucfirst($d->status),
                'party' => $d->supplier?->name ?? $d->recipient ?? '', 'dept' => $d->department ?? '', 'ref' => $d->ref_no ?? '', 'reason' => $d->reason ?? '',
                'code' => $l->item->code, 'item' => $l->item->name, 'unit' => $l->item->unit, 'qty' => $l->qty,
                'entered' => $l->entered_unit ? Locale::decimal($l->entered_qty, 3).' '.$l->entered_unit : '',
                'system' => $l->system_qty, 'counted' => $l->counted_qty,
                'cost' => $sup ? $cost : null, 'amount' => $sup ? round(abs($l->qty) * $cost, 2) : null, 'effect' => $sup ? round($l->qty * $cost, 2) : null,
                'user' => $d->creator->name, 'approver' => $d->approver?->name ?? '',
            ];
        })->values()->all();

        return view($view, ['sup' => $sup, 'from' => $from, 'to' => $to, 'rows' => $rows] + $extra);
    }

    // Periode laporan dari query from / to (Y-m-d); bawaan awal bulan ini s.d. hari ini.
    private function period(Request $request): array
    {
        $parse = fn (?string $v, Carbon $default) => rescue(fn () => $v ? Carbon::parse($v) : $default, $default, false);
        $from = $parse($request->query('from'), now()->startOfMonth())->startOfDay();
        $to = $parse($request->query('to'), now())->endOfDay();

        return [$from, $to->lt($from) ? $from->copy()->endOfDay() : $to];
    }
}
