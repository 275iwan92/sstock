// Layar tinjau Stok Opname: ringkasan (jumlah / nilai lebih-kurang) dihitung server, jadi halaman dimuat ulang setelah sel tersimpan.
document.addEventListener('dl:sheet-save', (e) => {
    if (e.target.closest('[data-reload-on-save]')) setTimeout(() => location.reload(), 600);
});
