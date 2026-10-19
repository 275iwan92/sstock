<?php

namespace App\Http\Requests;

use App\Enums\StockDocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/* REMARK
 * Tujuan       : Validasi simpan dokumen stok menurut jenisnya (Receiving, Issuance, Opening Balance, Stock Adjustment) + hak akses jenis itu.
 * Sumber hulu  : parameter rute {type} (slug), peran user (Opening / Adjustment hanya Supervisor).
 * Dipakai oleh : StockDocumentController@store, @update. Aksi approve / reject / delete tidak mengirim isian, jadi tanpa aturan.
 * Status       : Aktif.
 */
class StockDocumentRequest extends FormRequest
{
    public function type(): StockDocumentType
    {
        return StockDocumentType::fromSlug($this->route('type'));
    }

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && (! $this->type()->supervisorOnly() || $user->isSupervisor());
    }

    public function rules(): array
    {
        if (in_array($this->input('_action'), ['approve', 'reject', 'delete'], true)) {
            return [];
        }

        $qty = ['required', 'numeric', 'gt:0', 'max:999999999'];
        $common = [
            'note' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'distinct', Rule::exists('items', 'id')->where('is_active', true)],
            'lines.*.note' => ['nullable', 'string', 'max:255'],
        ];

        return $common + match ($this->type()) {
            StockDocumentType::Receiving => [
                'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('is_active', true)],
                'ref_no' => ['nullable', 'string', 'max:60'],
                'lines.*.qty' => $qty,
                'lines.*.unit_cost' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            ],
            StockDocumentType::Issuance => [
                'recipient' => ['required', 'string', 'max:100'],
                'department' => ['nullable', 'string', 'max:100'],
                'reason' => ['nullable', 'string', 'max:255'],
                'lines.*.qty' => $qty,
            ],
            StockDocumentType::Opening => [
                'doc_at' => ['required', 'date', 'before_or_equal:today'],
                'lines.*.qty' => $qty,
                'lines.*.unit_cost' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            ],
            StockDocumentType::Adjustment => [
                'reason' => ['required', 'string', 'max:255'],
                'lines.*.counted_qty' => ['required', 'numeric', 'min:0', 'max:999999999'],
            ],
        };
    }

    public function messages(): array
    {
        return [
            'lines.required' => 'Add at least one item line.',
            'lines.min' => 'Add at least one item line.',
            'lines.*.item_id.required' => 'Choose an item on every line.',
            'lines.*.item_id.distinct' => 'The same item appears on more than one line.',
            'lines.*.item_id.exists' => 'A chosen item does not exist or is inactive.',
            'lines.*.qty.gt' => 'Quantity must be greater than 0.',
        ];
    }
}
