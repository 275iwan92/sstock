{{-- REMARK
 Tujuan       : Sapaan Beranda: SATU baris "Selamat siang, <nama depan>" (x-dl::greeting, tanpa emoji) + di bawahnya tanggal dan jam kecil (x-dl::clock mode=line; di HP tanggal singkat).
                Cuaca (x-dl::weather) mengikuti posisi perangkat; tempat CADANGAN dari layar Perusahaan (Company::weather). Tanpa lokasi dan tanpa cadangan kartu cuaca tidak tampil dan sapaan tetap rapi.
                Desktop: kartu cuaca di ruang kosong kanan sapaan. HP (body.dl-on-phone, view phone): chip kecil di samping tanggal.
 Sumber hulu  : App\Support\Company::weather, Devlib\Device::view.
 Input        : name (nama lengkap pengguna; diambil nama depannya).
 Dipakai oleh : home (desktop), hp.home.
 Status       : Aktif.
--}}
@php
    $phone = \Devlib\Device::view(request()) === \Devlib\Device::PHONE;
    $wx = \App\Support\Company::weather();
    $wxProps = ['city' => $wx['city'] ?? null, 'lat' => $wx['lat'] ?? null, 'lon' => $wx['lon'] ?? null];
@endphp
@if ($phone)
    <div class="sstock-hello sstock-hello--hp">
        <x-dl::greeting :name="strtok(trim($name), ' ')" />
        <div class="sstock-hello__meta">
            <x-dl::clock mode="line" />
            <x-dl::weather variant="chip" :city="$wxProps['city']" :lat="$wxProps['lat']" :lon="$wxProps['lon']" />
        </div>
    </div>
@else
    <div class="sstock-hello">
        <div>
            <x-dl::greeting :name="strtok(trim($name), ' ')" />
            <x-dl::clock mode="line" />
        </div>
        <x-dl::weather variant="card" :city="$wxProps['city']" :lat="$wxProps['lat']" :lon="$wxProps['lon']" />
    </div>
@endif
