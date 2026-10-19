<?php

namespace App\Http\Requests;

use App\Support\GeoArea;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/* REMARK
 * Tujuan       : Validasi simpan master Supplier (tambah / ubah): kode, nama, telepon kantor (PSTN), kontak + HP, alamat jalan, wilayah (wajib, minimal sampai kota; kode pos ikut dari wilayah).
 * Sumber hulu  : field "area" = "LEVEL:id" dari lookup geo_area; dicek ada di tabel geo_*. validated() mengembalikan area_level + area_id (bukan area).
 * Dipakai oleh : SupplierController@store, @update.
 * Status       : Aktif.
 */
class SupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        if (in_array($this->input('_action'), ['activate', 'deactivate'], true)) {
            return [];
        }

        return [
            'code' => ['required', 'string', 'max:20', Rule::unique('suppliers', 'code')->ignore($this->route('supplier'))],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:40'],
            'contact_name' => ['nullable', 'string', 'max:100'],
            'contact_mobile' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
            'area' => ['required', 'string', fn ($attr, $value, $fail) => GeoArea::find($value) ? null : $fail('Choose a region from the list (at least down to the city).')],
        ];
    }

    public function messages(): array
    {
        return ['area.required' => 'Region is required: search and choose at least the city.'];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated();
        if (isset($data['area'])) {
            [$data['area_level'], $data['area_id']] = explode(':', $data['area']);
            $data['postal_code'] = GeoArea::find($data['area'])['postal_code'] ?? null;  // kode pos selalu dari wilayah terpilih
            unset($data['area']);
        }

        return $key ? data_get($data, $key, $default) : $data;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
    }
}
