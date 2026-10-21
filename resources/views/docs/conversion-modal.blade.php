{{-- REMARK
 Tujuan       : Jendela "Konversi satuan belum ada" di form Penerimaan. Muncul otomatis setelah Simpan / Confirm ditolak karena satuan baris berbeda dari satuan stok barang dan konversinya belum ada.
                Tiap pasangan diisi sekali (1 {dari} = ... {ke}); disimpan ke master konversi (berlaku untuk semua barang, arah sebaliknya dihitung), lalu pengguna menekan Simpan / Confirm lagi.
 Sumber hulu  : flash `missing_conversions` dari StockDocuments::assertConversions (from, to, items).
 Input        : tanpa prop (membaca session).
 Output       : x-dl::modal (dipindah ke <body> di luar <form> dokumen) + fetch POST docs.conversion.
 Dipakai oleh : docs/form.blade.php (Penerimaan, dokumen belum terkunci).
 Status       : Aktif.
--}}
@php $missing = session('missing_conversions', []); @endphp
@if ($missing)
    <x-dl::modal id="conv-missing" title="Konversi satuan belum ada" icon="fa-scale-balanced" size="sm" :open="true">
        <p class="dl-note">Satuan di baris berbeda dari satuan stok barang. Isi konversinya sekali; berlaku untuk semua barang, dan arah sebaliknya dihitung otomatis.</p>
        @foreach ($missing as $m)
            <div class="sstock-conv" data-conv-from="{{ $m['from'] }}" data-conv-to="{{ $m['to'] }}">
                <x-dl::field.text name="conv_factor_{{ $loop->index }}" :label="'1 '.$m['from'].' = … '.$m['to']" placeholder="Contoh: 12" required :hint="'Barang: '.$m['items']" />
            </div>
        @endforeach
        <p class="sstock-quickadd__err" data-conv-error hidden></p>
        <x-slot:footer><x-dl::button type="button" icon="save" label="Simpan Konversi" data-conv-save /></x-slot:footer>
    </x-dl::modal>
    <script>
        (() => {
            const layer = document.querySelector('[data-dl-modal-id="conv-missing"]');
            document.body.append(layer);   // di luar <form> dokumen: isiannya tidak ikut divalidasi / dikirim bersama dokumen
            const err = layer.querySelector('[data-conv-error]');
            layer.querySelector('[data-conv-save]').addEventListener('click', async () => {
                err.hidden = true;
                const rows = [...layer.querySelectorAll('[data-conv-from]')];
                const bodies = rows.map(r => ({from: r.dataset.convFrom, to: r.dataset.convTo, factor: parseFloat(r.querySelector('input').value.replace(',', '.'))}));
                if (bodies.some(b => !(b.factor > 0))) { err.textContent = 'Isi faktor konversi dengan angka lebih dari 0.'; err.hidden = false; return; }
                for (const b of bodies) {
                    const res = await fetch(@json(route('docs.conversion', request()->route('type'))), {method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token())}, body: JSON.stringify(b)});
                    if (!res.ok) { const d = await res.json(); err.textContent = d.errors ? Object.values(d.errors)[0][0] : (d.message || 'Gagal menyimpan.'); err.hidden = false; return; }
                }
                layer.classList.remove('is-on');
                Devlib.toast('Konversi tersimpan. Tekan Simpan atau Confirm lagi.', 'success');
            });
        })();
    </script>
@endif
