{{-- REMARK
 Tujuan       : Satu pilihan Penerima (dropdown master) yang dipakai di form Receiving, Issuance dan Adjustment (wajib hanya di Issuance).
 Sumber hulu  : StockDocumentController@formData (recipients aktif), dokumen ($doc; penerima nonaktif tetap tampil di dokumen lama).
 Input        : doc, recipients, t, v, e (dari docs/form).
 Output       : x-dl::field.select name=recipient_id.
 Dipakai oleh : docs/form.blade.php.
 Status       : Aktif.
--}}
<x-dl::field.select name="recipient_id" label="Recipient" :options="$recipients + ($doc->recipient_id && ! isset($recipients[$doc->recipient_id]) ? [$doc->recipient_id => $doc->recipient] : [])" :value="$v('recipient_id')" :required="$t === \App\Enums\StockDocumentType::Issuance" :error="$e('recipient_id')" />
