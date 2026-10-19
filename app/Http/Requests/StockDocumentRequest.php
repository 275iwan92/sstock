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

        $recipient = ['nullable', Rule::exists('recipients', 'id')->where('is_active', true)];
        $department = ['nullable', Rule::exists('departments', 'id')->where('is_active', true)];
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
                'department_id' => $department,
                'recipient_id' => $recipient,
                'lines.*.qty' => $qty,
                'lines.*.unit' => ['nullable', Rule::exists('units', 'code')->where('is_active', true)],
                'lines.*.unit_cost' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            ],
            StockDocumentType::Issuance => [
                'recipient_id' => ['required', Rule::exists('recipients', 'id')->where('is_active', true)],
                'department_id' => $department,
                'reason' => ['nullable', 'string', 'max:255'],
                'lines.*.qty' => $qty,
            ],
            StockDocumentType::Opening => [
                'doc_at' => ['required', 'date', 'before_or_equal:today'],
                'lines.*.qty' => $qty,
                'lines.*.unit_cost' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            ],
            StockDocumentType::Adjustment => [
                'department_id' => $department,
                'recipient_id' => $recipient,
                'reason' => ['required', 'string', 'max:255'],
                'lines.*.counted_qty' => ['required', 'numeric', 'min:0', 'max:999999999'],
            ],
        };
    }

    public function messages(): array
    {
        return [
            'lines.required' => 'Tambahkan minimal satu baris barang.',
            'lines.min' => 'Tambahkan minimal satu baris barang.',
            'lines.*.item_id.required' => 'Pilih barang di setiap baris.',
            'lines.*.item_id.distinct' => 'Barang yang sama muncul di lebih dari satu baris.',
            'lines.*.item_id.exists' => 'Barang yang dipilih tidak ada atau nonaktif.',
            'lines.*.qty.gt' => 'Jumlah harus lebih dari 0.',
        ];
    }
}
