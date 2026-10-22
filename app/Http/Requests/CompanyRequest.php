<?php

namespace App\Http\Requests;

use App\Support\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/* REMARK
 * Tujuan       : Validasi simpan data Perusahaan (kop cetakan); hanya Supervisor. Logo: PNG / JPG / WebP menurut ISI berkas (bukan ekstensi; SVG ditolak), maks. 1 MB, sisi 32 sampai 4000 px.
 * Dipakai oleh : CompanyController@update.
 * Status       : Aktif.
 */
class CompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isSupervisor();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'legal_name' => ['nullable', 'string', 'max:150'],
            'npwp' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:60'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'kop_style' => ['required', Rule::in(Company::STYLES)],
            'logo' => ['nullable', 'file', 'max:1024', 'mimetypes:image/png,image/jpeg,image/webp', 'dimensions:min_width=32,min_height=32,max_width=4000,max_height=4000'],
            'remove_logo' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nama perusahaan', 'legal_name' => 'nama PT / CV', 'npwp' => 'NPWP', 'phone' => 'telepon', 'email' => 'e-mail', 'address' => 'alamat', 'kop_style' => 'tampilan kop', 'logo' => 'logo'];
    }

    public function messages(): array
    {
        return [
            'logo.max' => 'Logo terlalu besar: paling banyak 1 MB.',
            'logo.mimetypes' => 'Logo harus berupa gambar PNG, JPG, atau WebP (SVG tidak bisa dipakai).',
            'logo.dimensions' => 'Ukuran logo harus antara 32 dan 4000 piksel di tiap sisi.',
            'logo.uploaded' => 'Logo gagal diunggah. Coba lagi.',
        ];
    }
}
