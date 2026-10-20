{{-- REMARK
 Tujuan       : Menyisipkan satu diagram SVG HELP secara inline (supaya mewarisi token tema terang/gelap) dari resources/help/diagrams/{name}.svg, dengan keterangan di bawahnya.
                Berkas ada di resources/ (bukan public/), jadi diagram TG ikut terlindung hak akses Administrator.
 Input        : name (nama berkas tanpa .svg), caption.
 Dipakai oleh : help/user-guide.blade.php (diagram ug-*), help/technical-guide.blade.php (diagram tg-*).
 Status       : Aktif.
--}}
<figure class="g-fig">
    {!! file_get_contents(resource_path('help/diagrams/'.$name.'.svg')) !!}
    <figcaption>{{ $caption }}</figcaption>
</figure>
