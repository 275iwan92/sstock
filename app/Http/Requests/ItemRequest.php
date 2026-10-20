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

        $new = ! $this->route('item');

        return [
            'name' => ['required', 'string', 'max:150'],
            // Kode dibuat otomatis; kategori dan tanggal kedaluwarsa (bagian dari kode) hanya diisi saat barang baru.
            ...($new ? [
                'category_id' => ['required', Rule::exists('categories', 'id')->where('is_active', true)],
                'expiry_date' => ['nullable', 'date'],
            ] : []),
            // Satuan dari master Units yang aktif (satuan lama item yang sudah nonaktif tetap boleh dipertahankan).
            'unit' => ['required', Rule::exists('units', 'code')->where(fn ($q) => $q->where('is_active', true)->orWhere('code', $this->route('item')?->unit))],
            'location' => ['nullable', Rule::exists('locations', 'code')->where(fn ($q) => $q->where('is_active', true)->orWhere('code', $this->route('item')?->location))],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'reorder_point' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'reorder_qty' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'lead_time_days' => ['required', 'integer', 'min:0', 'max:365'],
        ];
    }
}
