{{-- REMARK
 Tujuan       : Layar Administrator "Data Contoh": isi database saat ini, apa yang terjadi saat disegarkan, dan tombol Segarkan (konfirmasi dengan mengetik SEGARKAN).
 Sumber hulu  : SampleDataController@index (enabled, counts, last, summary).
 Input        : enabled, counts, last, summary.
 Output       : POST sample.refresh (semua data diganti data contoh, pengguna keluar lalu masuk ulang).
 Dipakai oleh : route sample.index (menu Keamanan > Data Contoh, Administrator).
 Status       : Aktif.
--}}
@extends('layout', ['title' => 'Data Contoh'])
@section('body')
    @include('partials.flash')
    <div style="max-width: 900px">
        <h2 style="margin: 4px 0 12px">Data Contoh</h2>
        <x-dl::kpi-row>
            @foreach ($counts as $label => $n)
                <x-dl::stat-box :label="$label" :value="number_format($n, 0, ',', '.')" tone="neutral" />
            @endforeach
            <x-dl::stat-box label="Terakhir disegarkan" :value="$last" tone="accent" />
        </x-dl::kpi-row>

        <x-dl::divider label="Yang terjadi saat disegarkan" />
        <p class="dl-note"><b>Dihapus:</b> seluruh isi database (barang, transaksi, supplier, akun, grup, dan pengaturan yang pernah diubah), lalu dibuat ulang dari migrasi dan diisi data contoh baru:</p>
        <ul class="dl-note" style="margin: 0 0 8px 18px; list-style: disc">
            @foreach ($summary as $line)
                <li>{{ $line }}</li>
            @endforeach
        </ul>
        <p class="dl-note"><b>Dipertahankan:</b> data wilayah (negara sampai kelurahan) dan berkas gambar latar login.</p>

        <x-dl::divider label="Segarkan" />
        @if ($enabled)
            <x-dl::alert tone="danger" message="Semua data saat ini akan hilang dan tidak bisa dikembalikan. Anda akan keluar dan harus masuk lagi dengan akun contoh (admin / supervisor / entry)." />
            <x-dl::action-form :action="route('sample.refresh')">
                <x-dl::form.row :cols="3">
                    <x-dl::field.text name="confirm" label="Ketik SEGARKAN untuk melanjutkan" required maxlength="20" :case-toggle="false" :error="$errors->first('confirm') ?: null" />
                </x-dl::form.row>
                <x-dl::action-bar>
                    <x-dl::button type="submit" variant="danger" icon="fa-rotate" label="Segarkan Data Contoh" />
                </x-dl::action-bar>
            </x-dl::action-form>
        @else
            <x-dl::alert tone="warn" message="Dimatikan di lingkungan ini (production). Untuk menyalakannya, isi SSTOCK_SAMPLE_REFRESH=true di .env, lalu muat ulang konfigurasi." />
        @endif
    </div>
@endsection
