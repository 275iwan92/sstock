{{-- REMARK
 Tujuan       : Kerangka semua layar tampilan HP Stoku: x-dl::phone-shell (bilah atas, tab bawah Beranda / Stok / Transaksi / Opname / Menu) dengan isi dari @yield('content').
 Sumber hulu  : App\Support\HpShell (tab, modul lembar Menu menurut akses pengguna).
 Input        : title, back (alamat tombol kembali, kosong = layar utama), active (kunci tab), frame (alamat layar desktop yang dibuka di dalam kerangka).
 Output       : halaman penuh dengan x-dl::phone-shell; pilihan Tampilan HP / Desktop dan Keluar ada di lembar Menu.
 Dipakai oleh : hp.home, hp.group, hp.frame, hp.counts, hp.count (@extends).
 Status       : Aktif.
--}}
@extends('layout', ['title' => $title ?? 'Stoku', 'bodyClass' => 'sstock-bare sstock-hp'])
@section('body')
    @php $me = auth()->user(); @endphp
    <x-dl::phone-shell :title="$title ?? 'Stoku'" :back="$back ?? null" :active="$active ?? ''" :tabs="\App\Support\HpShell::tabs($me)" :modules="\App\Support\HpShell::modules($me)"
        :user="['name' => $me->name, 'role' => $me->roleLabel()]" :logout-action="route('logout')" :device-action="route('ui.set')" :links="[['label' => 'Bantuan', 'icon' => 'fa-solid fa-circle-question', 'url' => route('help.user-guide')]]" :frame="$frame ?? null">
        @yield('content')
    </x-dl::phone-shell>
@endsection
