{{-- REMARK
 Tujuan       : Pesan hasil aksi di atas layar: sukses (status), peringatan Reorder Point (reorder), dan galat validasi.
 Sumber hulu  : session status / reorder (redirect()->with), $errors.
 Input        : tanpa prop.
 Output       : x-dl::alert (kosong = tersembunyi).
 Dipakai oleh : semua form layar (items, suppliers, dokumen stok).
 Status       : Aktif.
--}}
@if (session('status'))
    <x-dl::alert tone="success" :message="raw(session('status'))" />
@endif
@if (session('reorder'))
    <x-dl::alert tone="warn" :message="raw('Reorder point reached: '.session('reorder'))" />
@endif
@if ($errors->any())
    <x-dl::alert tone="danger" :message="raw($errors->first())" />
@endif
