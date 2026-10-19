<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/* REMARK
 * Tujuan       : Validasi simpan master Item (tambah / ubah); hanya Supervisor (rute ber-middleware supervisor).
 * Dipakai oleh : ItemController@store, @update.
 * Status       : Aktif.
 */
class ItemRequest extends FormRequest
{
    public const UNITS = ['pcs', 'box', 'set', 'pack', 'roll', 'pair', 'unit', 'sheet', 'kg', 'gram', 'meter', 'liter', 'can', 'bottle'];

    public function authorize(): bool
    {
        return (bool) $this->user()?->isSupervisor();
    }

    public function rules(): array
    {
        // Aktifkan / nonaktifkan lewat tombol panel form tidak mengirim isian.
        if (in_array($this->input('_action'), ['activate', 'deactivate'], true)) {
            return [];
        }

        return [
            'code' => ['required', 'string', 'max:40', Rule::unique('items', 'code')->ignore($this->route('item'))],
            'name' => ['required', 'string', 'max:150'],
            'category_id' => ['required', 'exists:categories,id'],
            'unit' => ['required', Rule::in(self::UNITS)],
            'location' => ['nullable', 'string', 'max:50'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'reorder_point' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'reorder_qty' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'lead_time_days' => ['required', 'integer', 'min:0', 'max:365'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
    }
}
