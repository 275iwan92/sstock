{{-- REMARK
 Tujuan       : Kerangka HTML semua halaman SStock (Simple Stock): aset DevLib + tema + Tailwind.
 Sumber hulu  : ../devlib (devlib.css, devlib.js, x-dl::theme-boot), resources/css/app.css.
 Input        : title, bodyClass dari view anak.
 Dipakai oleh : @extends oleh semua view SStock.
 Status       : Aktif.
--}}
<!DOCTYPE html>
<html lang="{{ \Devlib\Locale::lang() }}" data-dl-tz="{{ config('devlib.timezone') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Stoku' }} — Stoku</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ \Devlib\DevlibServiceProvider::asset('devlib.css') }}">
    <x-dl::theme-boot />
    <script src="{{ \Devlib\DevlibServiceProvider::asset('devlib.js') }}" defer></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="dl-body {{ $bodyClass ?? '' }}">
    @yield('body')
</body>
</html>
