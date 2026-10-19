<?php

namespace App\Http\Controllers;

use App\Enums\StockDocumentType;
use App\Http\Requests\StockDocumentRequest;
use App\Models\Department;
use App\Models\Item;
use App\Models\Recipient;
use App\Models\StockDocument;
use App\Models\Supplier;
use App\Models\Unit;
use App\Services\StockDocuments;
use Devlib\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/* REMARK
 * Tujuan       : Satu pengendali untuk empat jenis dokumen stok (rute docs/{type}): daftar, buat, lihat, simpan, dan siklus persetujuan penyesuaian.
 * Sumber hulu  : StockDocuments (logika), StockDocumentRequest (validasi + hak jenis), Lookup 'item_in|item_out|item_opn|item_adj'.
 * Output       : grid dokumen (docs.index) dan form dokumen (docs.form) berisi x-dl::lines.
 * Dipakai oleh : routes/web.php (docs.*).
 * Status       : Aktif.
 */
class StockDocumentController extends Controller
{
    private const STATUS = ['confirmed' => 'Terkonfirmasi', 'draft' => 'Draf', 'approval' => 'Menunggu Persetujuan', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'];

    public function __construct(private readonly StockDocuments $documents) {}

    public function index(Request $request, string $type)
    {
        $t = $this->type($request, $type);
        $sup = $request->user()->isSupervisor();

        $rows = StockDocument::query()->where('type', $t)->with(['lines.item', 'supplier', 'creator'])->latest('doc_at')->latest('id')->limit(1000)->get()
            ->map(fn (StockDocument $d) => [
                'id' => $d->id, 'doc_no' => $d->doc_no, 'doc_at' => $d->doc_at->format('Y-m-d H:i:s'),
                'party' => $d->supplier?->name ?? $d->recipient ?? $d->reason ?? '—',
                'ref' => $d->ref_no ?? $d->department ?? ($t === StockDocumentType::Adjustment ? $d->reason : null),
                'value' => $sup ? round($d->lines->sum(fn ($l) => abs($l->qty) * ($l->unit_cost ?? $l->item->avg_cost)), 2) : null,
                'status' => self::STATUS[$d->status] ?? $d->status, 'user' => $d->creator->name, 'active' => true,
                'lines' => $d->lines->map(fn ($l) => ['name' => $l->item->name, 'code' => $l->item->code, 'qty' => $l->qty, 'unit' => $l->item->unit,
                    'cost' => $sup ? ($l->unit_cost ?? $l->item->avg_cost) : null])->all(),
            ])->all();

        return view('docs.index', ['t' => $t, 'rows' => $rows, 'sup' => $sup]);
    }

    public function create(Request $request, string $type)
    {
        return view('docs.form', $this->formData($this->type($request, $type), new StockDocument(['status' => 'new'])));
    }

    public function show(Request $request, string $type, StockDocument $document)
    {
        $t = $this->type($request, $type);
        abort_unless($document->type === $t, 404);

        return view('docs.form', $this->formData($t, $document->load(['lines.item', 'creator', 'approver'])));
    }

    public function store(StockDocumentRequest $request, string $type): RedirectResponse
    {
        $t = $request->type();
        [$doc, $below] = $this->documents->create($t, $request->safe()->only(['supplier_id', 'ref_no', 'recipient_id', 'department_id', 'reason', 'note', 'doc_at']), array_values($request->validated('lines')), $request->user());

        $redirect = redirect()->route('docs.show', [$t->slug(), $doc])->with('status', $t === StockDocumentType::Adjustment ? "{$doc->doc_no} disimpan sebagai draf." : "{$doc->doc_no} disimpan dan diposting ke stok.");
        if ($below) {
            $redirect->with('reorder', collect($below)->map(fn (Item $i) => "{$i->code} {$i->name} (stok ".Locale::number($i->stock_qty, 2).', titik pesan ulang '.Locale::number($i->reorder_point, 2).')')->implode('; '));
        }

        return $redirect;
    }

    // Hanya penyesuaian yang punya siklus: save, confirm (ajukan), approve, reject, delete.
    public function update(StockDocumentRequest $request, string $type, StockDocument $document): RedirectResponse
    {
        abort_unless($document->type === StockDocumentType::Adjustment && $request->type() === $document->type, 404);
        $action = $request->input('_action', 'save');

        if (in_array($action, ['save', 'confirm'], true)) {
            $this->documents->update($document, $request->safe()->only(['reason', 'note', 'recipient_id', 'department_id']), array_values($request->validated('lines')));
        }
        $message = match ($action) {
            'save' => 'Draf disimpan.',
            'confirm' => $this->submit($document),
            'approve' => $this->approve($document, $request),
            'reject' => $this->reject($document, $request),
            'delete' => $this->delete($document),
            default => abort(422, 'Aksi tidak dikenal.'),
        };

        return ($action === 'delete' ? redirect()->route('docs.index', $type) : redirect()->route('docs.show', [$type, $document]))->with('status', $message);
    }

    private function submit(StockDocument $document): string
    {
        $this->documents->submit($document);

        return "{$document->doc_no} dikonfirmasi dan dikirim untuk persetujuan.";
    }

    private function approve(StockDocument $document, Request $request): string
    {
        $this->documents->approve($document, $request->user());

        return "{$document->doc_no} disetujui; penyesuaian diposting ke stok.";
    }

    private function reject(StockDocument $document, Request $request): string
    {
        $this->documents->reject($document, $request->user());

        return "{$document->doc_no} ditolak; stok tidak berubah.";
    }

    private function delete(StockDocument $document): string
    {
        $this->documents->delete($document);

        return "{$document->doc_no} dihapus.";
    }

    private function type(Request $request, string $slug): StockDocumentType
    {
        $t = StockDocumentType::fromSlug($slug);
        abort_if($t->supervisorOnly() && ! $request->user()->isSupervisor(), 403, 'Hanya untuk Supervisor.');

        return $t;
    }

    private function formData(StockDocumentType $t, StockDocument $doc): array
    {
        $log = [];
        if ($doc->exists) {
            if (in_array($doc->status, ['approved', 'rejected'], true)) {
                $log[] = ['at' => $doc->approved_at->format('Y-m-d H:i:s'), 'by' => $doc->approver->name, 'action' => self::STATUS[$doc->status] ?? $doc->status, 'note' => null];
            }
            $log[] = ['at' => $doc->created_at->format('Y-m-d H:i:s'), 'by' => $doc->creator->name, 'action' => 'Dibuat', 'note' => null];
        }

        return [
            't' => $t, 'doc' => $doc, 'log' => $log,
            'recipients' => Recipient::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all(),
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all(),
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all(),
            'units' => Unit::query()->where('is_active', true)->orderBy('code')->get()->mapWithKeys(fn (Unit $u) => [$u->code => "{$u->code} · {$u->name}"])->all(),
            'lines' => $this->lineRows($doc),
        ];
    }

    // Baris tabel item: dari input lama (validasi gagal) atau dari dokumen. Kolom pilihan item butuh label teksnya (item_id_label).
    private function lineRows(StockDocument $doc): array
    {
        $old = old('lines');
        if (is_array($old)) {
            $items = Item::query()->whereIn('id', array_filter(array_column($old, 'item_id')))->get()->keyBy('id');

            return array_map(fn ($r) => $this->lineRow($r, $items[$r['item_id'] ?? 0] ?? null), array_values($old));
        }

        return $doc->exists ? $doc->lines->map(fn ($l) => $this->lineRow([
            'item_id' => $l->item_id, 'qty' => $l->entered_qty ?? $l->qty, 'unit' => $l->entered_unit ?? $l->item->unit, 'unit_cost' => $l->entered_unit_cost ?? $l->unit_cost,
            'stock_qty' => $l->qty, 'system_qty' => $l->system_qty, 'counted_qty' => $l->counted_qty, 'note' => $l->note,
        ], $l->item))->all() : [];
    }

    private function lineRow(array $row, ?Item $item): array
    {
        return $row + [
            'item_id_label' => $item ? "{$item->code} · {$item->name}" : '',
            'unit' => $item?->unit, 'available' => $item?->stock_qty, 'system_qty' => $item?->stock_qty,
        ];
    }
}
