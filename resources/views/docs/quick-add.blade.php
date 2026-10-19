{{-- REMARK
 Tujuan       : Tambah Penerima / Departemen baru langsung dari form Issuance (tanpa meninggalkan layar): baris "Penerima baru" / "Departemen baru" di atas daftar dropdown membuka modal kecil, simpan lewat fetch ke master, lalu pilihan langsung terpilih di dropdown.
 Sumber hulu  : recipients.store / departments.store (menjawab JSON bila diminta; kode departemen dibuat otomatis dari nama).
 Input        : tanpa prop (memakai field recipient_id (hanya Issuance) dan department_id di form).
 Output       : dua x-dl::modal + script kecil yang menambah item ke dropdown x-dl::field.select.
 Dipakai oleh : docs/form.blade.php (Issuance, dokumen belum terkunci).
 Status       : Aktif.
--}}
<x-dl::modal id="qa-recipient" title="Add Recipient" icon="fa-user-tag" size="sm">
    <x-dl::field.text name="qa_recipient_name" label="Recipient Name" maxlength="100" />
    <p class="dl-note">{{ \Devlib\Lang::text('The department chosen in this form becomes the recipient\'s default department.') }}</p>
    <p class="sstock-quickadd__err" data-qa-error hidden></p>
    <x-slot:footer><x-dl::button type="button" icon="save" label="Save" data-qa-save="recipient" /></x-slot:footer>
</x-dl::modal>
<x-dl::modal id="qa-department" title="Add Department" icon="fa-building" size="sm">
    <x-dl::field.text name="qa_department_name" label="Department Name" maxlength="100" />
    <p class="sstock-quickadd__err" data-qa-error hidden></p>
    <x-slot:footer><x-dl::button type="button" icon="save" label="Save" data-qa-save="department" /></x-slot:footer>
</x-dl::modal>
<script>
    (() => {
        const token = @json(csrf_token());
        const urls = {recipient: @json(route('recipients.store')), department: @json(route('departments.store'))};
        const layer = id => document.querySelector(`[data-dl-modal-id="${id}"]`);
        const field = name => document.querySelector(`[data-dl-select] [name="${name}"]`)?.closest('[data-dl-select]');
        const choose = (name, data) => {
            const sel = field(name), list = sel.querySelector('[data-dl-dd-list]');
            const it = document.createElement('div');
            it.className = 'dl-dd__item'; it.dataset.value = data.id; it.textContent = data.name;
            list.appendChild(it);
            it.click();
        };
        // Baris pertama dropdown: memilihnya membuka dialog nama baru (bukan memilih nilai).
        [['recipient_id', 'qa-recipient', @json(\Devlib\Lang::text('New Recipient'))], ['department_id', 'qa-department', @json(\Devlib\Lang::text('New Department'))]].forEach(([name, modal, label]) => {
            if (!field(name)) return;
            const row = document.createElement('div');
            row.className = 'dl-dd__item sstock-dd-new'; row.dataset.value = ''; row.innerHTML = '<i class="fa-solid fa-plus"></i> ' + label;
            row.addEventListener('click', e => { e.stopPropagation(); field(name).querySelector('[data-dl-dd-toggle]').click(); const m = layer(modal); m.classList.add('is-on'); setTimeout(() => m.querySelector('input[type=text]').focus(), 50); });
            field(name).querySelector('[data-dl-dd-list]').prepend(row);
        });
        // Enter di kotak modal menyimpan penerima / departemen baru, bukan mengirim form dokumen.
        document.querySelectorAll('[data-dl-modal-id="qa-recipient"] input, [data-dl-modal-id="qa-department"] input').forEach(i => i.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); i.closest('.dl-mov').querySelector('[data-qa-save]').click(); } }));
        document.querySelectorAll('[data-qa-save]').forEach(btn => btn.addEventListener('click', async () => {
            const kind = btn.dataset.qaSave, modal = btn.closest('.dl-mov'), err = modal.querySelector('[data-qa-error]');
            const body = {name: modal.querySelector('input[type=text]').value.trim()};
            if (kind === 'recipient') body.department_id = document.querySelector('[name="department_id"]')?.value || null;
            err.hidden = true;
            const res = await fetch(urls[kind], {method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token}, body: JSON.stringify(body)});
            const data = await res.json();
            if (!res.ok) { err.textContent = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Gagal menyimpan.'); err.hidden = false; return; }
            choose(kind + '_id', data);
            if (kind === 'recipient' && data.department_id && !document.querySelector('[name="department_id"]').value) {
                const dd = field('department_id'), item = dd.querySelector(`.dl-dd__item[data-value="${data.department_id}"]`);
                item?.click();
            }
            modal.querySelector('input[type=text]').value = '';
            modal.classList.remove('is-on');
        }));
    })();
</script>
