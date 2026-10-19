<?php

namespace App\Http\Requests;

use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/* REMARK
 * Tujuan       : Validasi simpan master Satuan (tambah / ubah) beserta daftar konversinya (1 satuan ini = faktor x satuan lain); hanya Supervisor.
 * Dipakai oleh : UnitController@store, @update.
 * Status       : Aktif.
 */
class UnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isSupervisor();
    }

    public function rules(): array
    {
        if (in_array($this->input('_action'), ['activate', 'deactivate'], true)) {
            return [];
        }
        $own = $this->route('unit')?->code ?? $this->input('code');

        return [
            'code' => ['required', 'string', 'max:20', 'regex:/^[a-z0-9._\/-]+$/', Rule::unique('units', 'code')->ignore($this->route('unit'))],
            'name' => ['required', 'string', 'max:100'],
            'kind' => ['nullable', Rule::in(array_keys(Unit::KINDS))],
            'conversions' => ['nullable', 'array', 'max:50'],
            'conversions.*.to_unit' => ['required', 'distinct', Rule::notIn([$own]), Rule::exists('units', 'code')],
            'conversions.*.factor' => ['required', 'numeric', 'gt:0', 'max:999999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'Use lowercase letters, digits, and . _ / - only (for example pcs, box, m2).',
            'conversions.*.to_unit.required' => 'Choose the unit to convert to on every conversion line.',
            'conversions.*.to_unit.distinct' => 'The same target unit appears on more than one conversion line.',
            'conversions.*.to_unit.not_in' => 'A unit cannot be converted to itself.',
            'conversions.*.factor.required' => 'Enter a factor on every conversion line.',
            'conversions.*.factor.gt' => 'The factor must be greater than 0.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => strtolower(trim((string) $this->input('code')))]);
    }
}
