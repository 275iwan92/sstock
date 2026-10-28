// Cari Barang tampilan HP: kolom cari memilih seluruh isinya saat fokus, jadi hasil pindai berikutnya menimpa isian lama (pemindai mengetik seperti keyboard).
const box = document.querySelector('[data-hp-find] input[name="q"]');
if (box) {
    box.addEventListener('focus', () => box.select());
    if (document.activeElement === box) box.select();
}
