{{-- REMARK
 Tujuan       : User's Guide (UG) Stoku, Bahasa Indonesia, untuk SEMUA pengguna yang sudah login: cara memakai grid dan form, daftar isi modul, dan fitur tiap modul (apa yang bisa dilakukan, siapa yang boleh).
                Halaman dokumentasi murni: HTML biasa (kelas sstock-guide di resources/css/app.css), tidak lewat kamus Indonesian; istilah ditulis sama persis dengan label di layar.
 Sumber hulu  : App\Support\Modules (daftar modul + peran minimal), User::canAccess (bagian modul hanya tampil bila pengguna boleh membukanya), REMARK di view/controller, HANDOVER.md.
 Output       : tab "Panduan Pengguna" di Bantuan & dukungan (menu profil).
 Dipakai oleh : route help.user-guide (grup auth). Technical Guide (English, Administrator) ada di help/technical-guide.blade.php; ubah keduanya bersama bila perilaku berubah.
 Status       : Aktif.
--}}
@extends('layout', ['title' => 'Panduan Pengguna', 'bodyClass' => 'sstock-bare'])
@section('body')
@php
    $user = auth()->user();
    $can = fn (string $code) => $user->canAccess($code);
    $sup = $user->isSupervisor();
    $anyMaster = collect(['ITM', 'SUP', 'DEP', 'RCP', 'UOM', 'CAT', 'LOC'])->contains($can);
    $anyTrx = collect(['OPN', 'RCV', 'ISS', 'ADJ'])->contains($can);
    $anyRpt = collect(['R-CARD', 'R-SUM', 'R-IN', 'R-OUT', 'R-EXP', 'R-ADJ'])->contains($can);
    $anySec = collect(['USR', 'GRP', 'SMP', 'MOD'])->contains($can);
@endphp
@include('help.tabs', ['active' => 'ug'])
<div class="sstock-guide">
    <nav class="g-toc" aria-label="Daftar isi">
        <strong>Panduan Pengguna</strong>
        <a href="#pengenalan">1. Pengenalan</a>
        <a href="#masuk">2. Masuk dan Menu</a>
        <a href="#grid">3. Cara memakai Grid</a>
        <a href="#form">4. Cara memakai Form</a>
        @if ($anyMaster)
            <a href="#master">5. Data Master</a>
            @if ($can('ITM'))<a class="g-toc-sub" href="#m-itm">Barang</a>@endif
            @if ($can('SUP'))<a class="g-toc-sub" href="#m-sup">Suppliers</a>@endif
            @if ($can('DEP') || $can('RCP'))<a class="g-toc-sub" href="#m-dep">Departemen dan Penerima</a>@endif
            @if ($can('UOM'))<a class="g-toc-sub" href="#m-uom">Satuan</a>@endif
            @if ($can('CAT'))<a class="g-toc-sub" href="#m-cat">Kategori</a>@endif
            @if ($can('LOC'))<a class="g-toc-sub" href="#m-loc">Lokasi Penyimpanan</a>@endif
        @endif
        @if ($anyTrx)
            <a href="#transaksi">6. Transaksi</a>
            @if ($can('OPN'))<a class="g-toc-sub" href="#t-opn">Saldo Awal</a>@endif
            @if ($can('RCV'))<a class="g-toc-sub" href="#t-rcv">Penerimaan Barang</a>@endif
            @if ($can('ISS'))<a class="g-toc-sub" href="#t-iss">Pengeluaran Barang</a>@endif
            @if ($can('ADJ'))<a class="g-toc-sub" href="#t-adj">Penyesuaian Stok</a>@endif
            <a class="g-toc-sub" href="#t-batal">Membatalkan dokumen</a>
        @endif
        @if ($anyRpt)
            <a href="#laporan">7. Laporan</a>
        @endif
        @if ($anySec)
            <a href="#keamanan">8. Keamanan</a>
        @endif
        <a href="#tanya">9. Pertanyaan umum</a>
    </nav>

    <main class="g-body">
        <h1>Panduan Pengguna Stoku</h1>
        <p class="g-lead">Tuntunan memakai Stoku langkah demi langkah. Bagian yang hanya bisa dipakai peran tertentu ditandai <span class="g-role">hanya Supervisor</span>. Panduan ini hanya menampilkan modul yang boleh Anda buka (peran Anda: <b>{{ $user->roleLabel() }}</b>).</p>

        {{-- 1 --}}
        <h2 id="pengenalan">1. Pengenalan</h2>
        <p>Stoku adalah aplikasi persediaan barang. Urutan kerjanya sederhana:</p>
        <ol class="g-steps">
            <li><b>Data Master</b> diisi sekali: barang, supplier, satuan, kategori, lokasi, departemen, penerima.</li>
            <li><b>Transaksi</b> mencatat setiap perubahan stok: Saldo Awal, Penerimaan Barang, Pengeluaran Barang, Penyesuaian Stok. Stok baru berubah setelah dokumen <b>Konfirmasi</b>.</li>
            <li><b>Laporan</b> dan <b>Beranda</b> membaca hasilnya: stok sekarang, riwayat, barang yang harus dipesan ulang, barang hampir kedaluwarsa.</li>
        </ol>
        @include('help.diagram', ['name' => 'ug-alur-kerja', 'caption' => 'Alur kerja Stoku: dari Data Master sampai Laporan.'])
        <h3>Peran pengguna</h3>
        <table class="g-table">
            <thead><tr><th>Peran</th><th>Bisa mengerjakan</th></tr></thead>
            <tbody>
                <tr><td>Data Entry</td><td>Penerimaan Barang dan Pengeluaran Barang; mengelola Supplier, Departemen, Penerima; menambah Barang baru dan melihat Barang, serta kebanyakan laporan. Tidak melihat harga dan nilai stok.</td></tr>
                <tr><td>Supervisor</td><td>Semua fungsi stok: mengubah Barang, Satuan, Kategori, Lokasi; Saldo Awal; Penyesuaian Stok; <b>Setujui</b> / <b>Tolak</b>; melihat harga, nilai, dan semua laporan.</td></tr>
                <tr><td>Administrator</td><td>Semua yang bisa dikerjakan Supervisor, ditambah menu <b>Keamanan</b> (pengguna, grup, data contoh) dan Technical Guide.</td></tr>
            </tbody>
        </table>
        <p class="g-note">Administrator dapat mengatur modul apa saja yang boleh dibuka tiap orang lewat <b>Grup</b>. Bila menu yang Anda perlukan tidak tampil, hubungi Administrator.</p>

        {{-- 2 --}}
        <h2 id="masuk">2. Masuk dan Menu</h2>
        <h3>Masuk</h3>
        <ol class="g-steps">
            <li>Buka alamat Stoku di browser.</li>
            <li>Isi <b>username atau e-mail</b> dan <b>password</b>, lalu tekan tombol masuk.</li>
            <li>Lupa password? Tekan <b>Lupa password?</b>, isi username atau e-mail, lalu tekan <b>Kirim tautan reset</b>. Tautan dikirim ke e-mail Anda, berlaku 60 menit, dan hanya bisa dipakai sekali. Pilih password baru (minimal 8 karakter), lalu <b>Simpan password baru</b>.</li>
        </ol>
        @include('help.diagram', ['name' => 'ug-masuk', 'caption' => 'Masuk, lupa password, dan membuka menu.'])
        <p class="g-note">Akun yang dinonaktifkan tidak bisa masuk, dan bila dinonaktifkan saat sedang memakai Stoku akun itu langsung keluar pada klik berikutnya. Percobaan masuk yang salah berulang akan ditahan sementara.</p>
        <p class="g-note">Tidak ada pilihan &quot;ingat saya&quot;: Anda harus masuk lagi setelah menutup browser atau bila sesi habis. Bila tidak ada aktivitas selama <b>30 menit</b>, Stoku keluar otomatis. Dua menit sebelumnya muncul peringatan di pojok kanan bawah dengan hitung mundur; tekan <b>Tetap masuk</b> untuk melanjutkan, atau <b>Keluar sekarang</b>. Mengetik, menggerakkan mouse, dan menggulir dihitung sebagai aktivitas.</p>

        <h3>Beranda</h3>
        <p>Setelah masuk Anda melihat <b>Beranda</b>: kata motivasi, deretan kotak ringkasan, dan peringatan.</p>
        <ul class="g-list">
            <li><b>Kotak ringkasan</b> (bisa diklik, membuka menu terkait): Barang aktif, Di / bawah titik pesan ulang, Hampir kedaluwarsa / kedaluwarsa, Penerimaan hari ini, Pengeluaran hari ini. <span class="g-role">hanya Supervisor</span> Nilai stok dan Dokumen menunggu persetujuan.</li>
            <li><b>Peringatan titik pesan ulang:</b> daftar barang yang <b>stok tersedia</b>-nya (jumlah semua batch yang belum kedaluwarsa) sama dengan atau di bawah <b>Titik Pesan Ulang</b>, dengan kolom <b>Status Pesanan</b>: merah <b>Belum dipesan</b>, hijau <b>Dipesan tanggal · nomor PO</b>. Di bawah lencana hijau ada keterangan: <i>Sudah dipesan {jumlah} {satuan stok} ke {supplier} pada {tanggal}, ref {nomor PO} oleh {nama}. Diterima sejak dipesan: {jumlah} {satuan stok}.</i> (Catatan lama yang dibuat sebelum ada supplier dan jumlah tampil tanpa dua bagian itu.) Angka "diterima sejak dipesan" adalah jumlah Penerimaan Barang yang sudah dikonfirmasi (yang dibatalkan tidak dihitung) untuk barang itu sejak tanggal pesan. Ini hanya informasi: pesanan tetap tertutup oleh penerimaan apa pun, berapa pun jumlah yang datang. Keterangan yang sama tampil di laporan <b>Ringkasan Stok</b> (kolom <b>Supplier Pesanan</b>, <b>Jumlah Dipesan</b>, <b>Info Pemesanan</b>) dan di form barang.
                <ol class="g-steps">
                    <li>Setelah memesan ke supplier, tekan <b>Catat Pemesanan</b>.</li>
                    <li>Pilih barang dan <b>Supplier</b>, isi <b>Jumlah Dipesan</b> (dalam satuan stok barang; satuannya tampil di bawah isian, harus lebih dari 0) dan <b>Tanggal Pesan</b> (bawaan hari ini). <b>No. Referensi Pemesanan (PO)</b> dan <b>Catatan</b> boleh kosong. Lalu <b>Simpan</b>. Ini catatan sederhana agar bagian administrasi tahu pesanan ke mana, berapa, dan kapan; bukan dokumen PO (tanpa harga, persetujuan, atau cetak).</li>
                    <li>Saat barangnya diterima dan Penerimaan Barang dikonfirmasi, catatan pesanan itu tertutup otomatis. Tombol <b>Buka Penerimaan</b> membuka layar Penerimaan Barang.</li>
                </ol>
            </li>
            <li><b>Peringatan kedaluwarsa:</b> batch barang yang masih ada stoknya dan sudah atau hampir kedaluwarsa (kurang dari 30 hari), mulai yang paling dulu.</li>
            <li><span class="g-role">hanya Supervisor</span> <b>Nilai Stok:</b> grafik nilai stok per minggu dalam satu bulan; tombol <b>Bulan lalu</b> / <b>Bulan depan</b> berpindah bulan.</li>
        </ul>

        @include('help.diagram', ['name' => 'ug-pesan-ulang', 'caption' => 'Dari peringatan titik pesan ulang sampai pesanan tertutup oleh Penerimaan Barang.'])
        <h3>Menu dan tab</h3>
        <ul class="g-list">
            <li>Menu ada di panel kiri, dikelompokkan: <b>Data Master</b>, <b>Transaksi</b>, <b>Laporan</b>, dan <b>Keamanan</b> (menempel di bawah). Hanya menu yang boleh Anda buka yang tampil.</li>
            <li>Setiap menu yang Anda pilih terbuka sebagai <b>tab</b> kerja. Anda bisa membuka beberapa tab sekaligus dan berpindah di antaranya tanpa kehilangan isian.</li>
            <li>Klik nama atau foto Anda untuk <b>menu profil</b>: <b>Notifikasi</b>, <b>Edit profil</b>, <b>Bantuan &amp; dukungan</b> (panduan ini), <b>Bahasa</b>, <b>Ubah password</b>, dan <b>Keluar aplikasi</b>.</li>
            <li>Tombol <b>F1</b> di layar mana pun membuka panduan ini.</li>
        </ul>

        {{-- 3 --}}
        <h2 id="grid">3. Cara memakai Grid</h2>
        <p><b>Grid</b> adalah daftar data (barang, supplier, dokumen, dan seterusnya). Semua grid bekerja dengan cara yang sama.</p>
        <table class="g-table">
            <thead><tr><th>Bagian</th><th>Fungsi</th></tr></thead>
            <tbody>
                <tr><td>Kotak hitung di atas (mis. <b>Semua barang</b>, <b>Pesan ulang</b>, <b>Nonaktif</b>)</td><td>Menunjukkan jumlah data tiap kelompok. Klik satu kotak untuk menyaring grid menurut kelompok itu; klik <b>Semua ...</b> untuk kembali.</td></tr>
                <tr><td><b>Cari</b></td><td>Ketik sebagian kata, grid langsung menyempit. Tulisan di kotak (mis. &quot;Cari kode, nama, lokasi...&quot;) menyebut kolom yang dicari.</td></tr>
                <tr><td><span class="g-btn">Saring</span></td><td>Membuka pilihan saringan (mis. Kategori, Satuan). Angka di tombol = jumlah saringan aktif. <b>Reset saringan</b> menghapus semuanya.</td></tr>
                <tr><td><span class="g-btn">Kolom</span></td><td>Centang atau cabut centang kolom yang ingin ditampilkan. Pilihan ini hanya mengubah tampilan Anda.</td></tr>
                <tr><td><span class="g-btn">Ekspor</span></td><td>Mengunduh isi grid (sesuai pencarian, saringan, dan kolom yang tampil) sebagai berkas CSV untuk Excel.</td></tr>
                <tr><td><span class="g-btn">+ Data Baru</span></td><td>Membuka form kosong untuk menambah data. Tombol ini tidak tampil bila Anda tidak berhak menambah.</td></tr>
                <tr><td>Judul kolom</td><td>Klik untuk mengurutkan naik / turun. Baris jumlah (bila ada) ikut hasil saringan.</td></tr>
                <tr><td>Bagian bawah</td><td>Baris <b>Total (semua hasil)</b> menjumlahkan kolom angka sesuai saringan. <b>Ke halaman</b>, <b>Tampil</b> 10 / 25 / 50 / 100 baris, dan tombol halaman mengatur halaman yang tampil; di kanan ada hitungan mis. [1 – 2 dari 2].</td></tr>
                <tr><td>Klik 2x pada baris</td><td>Membuka datanya di form. Hasilnya sama dengan ikon pensil (<b>Edit</b>) atau <b>Buka</b>.</td></tr>
            </tbody>
        </table>
        <h3>Kolom Aksi dan Aktif / Nonaktif</h3>
        <ul class="g-list">
            <li>Grid <b>master</b> punya ikon di awal baris: pensil = <b>Edit</b>; ikon lingkaran coret = <b>Nonaktifkan</b> (untuk data nonaktif berubah menjadi <b>Aktifkan</b>).</li>
            <li>Data <b>tidak pernah dihapus</b>. Data yang tidak dipakai lagi dinonaktifkan: hilang dari pilihan di form baru tetapi riwayatnya tetap utuh. Lihat yang nonaktif lewat kotak <b>Nonaktif</b> di atas grid.</li>
            <li>Grid <b>transaksi</b> (dokumen stok) hanya punya ikon <b>Buka</b> (kotak dengan panah) di awal baris. Dokumen tidak dinonaktifkan; <b>Konfirmasi</b>, <b>Setujui</b>, <b>Batalkan</b>, <b>Hapus</b> hanya ada di form dokumen.</li>
        </ul>
        <h3>Warna di grid</h3>
        <ul class="g-list">
            <li><b>Kotak warna di master:</b> kolom pertama grid master memakai kotak berwarna berisi inisial, dengan nama tebal dan kode kecil di bawahnya. Warna hanya membantu membedakan (mis. barang menurut kategori); tidak ada artinya selain itu.</li>
            <li><b>Grid transaksi polos:</b> nomor dokumen tebal, tanpa kotak warna. Satu-satunya warna adalah kolom <b>Status</b>:
                <table class="g-table">
                    <tbody>
                        <tr><td><span class="g-st g-green">Terkonfirmasi</span> <span class="g-st g-green">Disetujui</span></td><td>Sudah berlaku dan masuk ke stok.</td></tr>
                        <tr><td><span class="g-st g-grey">Draf</span></td><td>Baru disimpan, stok belum berubah.</td></tr>
                        <tr><td><span class="g-st g-yellow">Menunggu Persetujuan</span> <span class="g-st g-yellow">Pembatalan Menunggu Persetujuan</span></td><td>Menunggu keputusan Supervisor.</td></tr>
                        <tr><td><span class="g-st g-red">Dibatalkan</span> <span class="g-st g-red">Ditolak</span></td><td>Tidak berlaku; stok sudah dikembalikan atau tidak pernah berubah.</td></tr>
                    </tbody>
                </table>
            </li>
            <li>Di grid <b>Barang</b>, baris yang <b>merah muda</b> dan berstatus <b>PESAN ULANG</b> menandakan stok sudah di / bawah Titik Pesan Ulang; <b>AMAN</b> hijau berarti stok cukup.</li>
            <li>Pada grid dokumen, tanda panah <b>&gt;</b> di awal baris membuka daftar <b>Barang</b> di dokumen itu tanpa membuka form; di bawah nomor dokumen tertulis pihaknya (supplier, penerima, atau alasan).</li>
        </ul>

        {{-- 4 --}}
        <h2 id="form">4. Cara memakai Form</h2>
        <p>Form dipakai untuk menambah atau mengubah data. Ada dua jenis: <b>form master</b> (barang, supplier, dst.) dan <b>form dokumen</b> (transaksi stok).</p>
        <h3>Tombol umum</h3>
        <table class="g-table">
            <thead><tr><th>Tombol</th><th>Fungsi</th></tr></thead>
            <tbody>
                <tr><td><span class="g-btn">Simpan</span></td><td>Menyimpan isian. Di form dokumen, Simpan membuat <b>Draf</b>: stok belum berubah.</td></tr>
                <tr><td><span class="g-btn">Urungkan</span></td><td>Mengembalikan isian ke keadaan terakhir tersimpan.</td></tr>
                <tr><td><span class="g-btn">Kembali</span></td><td>Menuju <b>grid</b> modul tersebut (bukan layar sebelumnya). Isian yang belum disimpan tidak tersimpan.</td></tr>
                <tr><td><span class="g-btn">Baru</span></td><td>Membuka form kosong baru dari modul yang sama.</td></tr>
                <tr><td><span class="g-btn">Nonaktifkan</span> / <span class="g-btn">Aktifkan</span></td><td>Khusus form master: mematikan atau menghidupkan lagi data.</td></tr>
                <tr><td>Tab <b>Data</b> dan <b>Riwayat</b></td><td>Data = isian. Riwayat = jejak: siapa membuat, mengubah, dan (di dokumen) mengonfirmasi, mengajukan pembatalan, atau membatalkan, lengkap dengan alasan.</td></tr>
            </tbody>
        </table>
        <h3>Mengisi form</h3>
        <ol class="g-steps">
            <li>Isian bertanda <b>bintang merah (*)</b> wajib diisi. Bila ada yang kosong atau salah, Stoku menampilkan pesan merah di bawah isian tersebut dan tidak menyimpan.</li>
            <li>Kolom berwarna abu-abu / terkunci (mis. <b>Kode Barang</b>, <b>No. Dokumen</b>) diisi otomatis atau tidak bisa diubah setelah disimpan.</li>
            <li>Tekan <b>Simpan</b>. Pesan hijau di atas layar menandakan berhasil.</li>
            <li>Tombol hanya bereaksi sekali: tidak perlu menekan dua kali, dan data tidak akan tersimpan ganda.</li>
        </ol>
        <h3>Baris detail (tabel Barang di form dokumen)</h3>
        <ol class="g-steps">
            <li>Tekan <span class="g-btn">+ Tambah Barang</span> untuk menambah baris.</li>
            <li>Pada kolom <b>Barang</b>, ketik sebagian kode atau nama lalu pilih dari daftar. Satu barang hanya boleh muncul sekali dalam satu dokumen.</li>
            <li>Isi kolom lain pada baris itu. Kolom abu-abu (<b>Satuan</b>, kecuali di Penerimaan Barang yang satuannya dipilih; <b>Tersedia</b>, <b>Jml Sistem</b>, <b>Subtotal</b>, <b>Selisih</b>) dihitung otomatis. Pengeluaran Barang dan Penyesuaian Stok <b>selalu dalam satuan stok barang</b>: tidak ada pilihan satuan di sana. Baris <b>Jumlah</b> di bawah tabel menjumlahkan kolom tertentu.</li>
            <li>Setiap baris punya dua keadaan. <b>Baru diisi</b>: di ujung baris ada ikon <b>Simpan baris</b> (disket) dan <b>Batalkan isian baris</b> (panah putar). Simpan menuntaskan baris (baris terkunci); Batalkan membuang baris baru. <b>Sudah ditulis</b>: ikon <b>Ubah baris</b> (pensil) dan <b>Hapus baris</b> (tong sampah). Ubah membuka baris lagi (Batalkan mengembalikan isi sebelumnya). Setelah dokumen <b>Terkonfirmasi</b> ikon-ikon ini hilang karena baris tidak bisa diubah; selama <b>Draf</b> ikon selalu ada.</li>
            <li>Formulir baru sudah berisi satu baris (jumlah 1); dokumen wajib punya minimal satu baris terisi. Menekan <b>Simpan</b> / <b>Konfirmasi</b> di atas form juga ikut menyimpan baris yang sedang terbuka dan sudah lengkap.</li>
        </ol>
        <h3>Siklus dokumen: Simpan, Konfirmasi, Hapus, Batalkan</h3>
        <p>Setiap dokumen stok melalui tahap yang sama. Di bagian atas form ada penunjuk tahap (<b>Draf</b> > <b>Terkonfirmasi</b> > <b>Ditutup</b>) yang menyala sesuai posisi dokumen.</p>
        <table class="g-table">
            <thead><tr><th>Tahap / tombol</th><th>Artinya</th></tr></thead>
            <tbody>
                <tr><td><span class="g-st g-grey">Draf</span> lewat <span class="g-btn">Simpan</span></td><td>Dokumen tersimpan dan nomornya dibuat otomatis (mis. RCV-2610-0001). <b>Stok belum berubah.</b> Draf masih boleh diubah.</td></tr>
                <tr><td><span class="g-btn">Hapus</span></td><td>Hanya untuk Draf: dokumen dibuang permanen. Tombol ini hilang setelah Konfirmasi.</td></tr>
                <tr><td><span class="g-btn">Konfirmasi</span></td><td>Dokumen diproses ke stok. Setelah itu dokumen <b>terkunci</b> (tidak bisa diubah). Syaratnya minimal satu baris. Tanggal dan jam dokumen = saat Konfirmasi.</td></tr>
                <tr><td><span class="g-btn">Batalkan</span></td><td>Membatalkan dokumen yang sudah dikonfirmasi; <b>alasan wajib</b> diisi (lihat <a href="#t-batal">Membatalkan dokumen</a>).</td></tr>
                <tr><td><span class="g-btn">Setujui</span> / <span class="g-btn">Tolak</span> <span class="g-role">hanya Supervisor</span></td><td>Memutuskan Penyesuaian Stok yang diajukan, atau pengajuan pembatalan.</td></tr>
            </tbody>
        </table>
        @include('help.diagram', ['name' => 'ug-siklus-dokumen', 'caption' => 'Siklus dokumen Saldo Awal, Penerimaan Barang, dan Pengeluaran Barang.'])
        <div class="g-tip">Salah ketik pada dokumen yang sudah dikonfirmasi? Dokumen terkunci. Batalkan lalu buat dokumen baru, atau koreksi selisih stoknya lewat <b>Penyesuaian Stok</b>.</div>

        @if ($anyMaster)
        {{-- 5 --}}
        <h2 id="master">5. Data Master</h2>
        <p>Data Master adalah data acuan yang dipilih di form lain. Semuanya berupa grid + form master (lihat bagian 3 dan 4). Data tidak dihapus, hanya dinonaktifkan.</p>
        <table class="g-table">
            <thead><tr><th>Modul</th><th>Isi singkat</th><th>Siapa yang boleh mengubah</th></tr></thead>
            <tbody>
                @if ($can('ITM'))<tr><td><a href="#m-itm">Barang</a></td><td>Data barang, stok, titik pesan ulang, harga</td><td>Supervisor (Data Entry hanya melihat)</td></tr>@endif
                @if ($can('SUP'))<tr><td><a href="#m-sup">Suppliers</a></td><td>Pemasok barang</td><td>Data Entry dan Supervisor</td></tr>@endif
                @if ($can('DEP'))<tr><td><a href="#m-dep">Departemen</a></td><td>Bagian / unit penerima barang</td><td>Data Entry dan Supervisor</td></tr>@endif
                @if ($can('RCP'))<tr><td><a href="#m-dep">Penerima</a></td><td>Orang yang menerima barang keluar</td><td>Data Entry dan Supervisor</td></tr>@endif
                @if ($can('UOM'))<tr><td><a href="#m-uom">Satuan</a></td><td>pcs, box, kg, dan konversinya</td><td><span class="g-role">hanya Supervisor</span></td></tr>@endif
                @if ($can('CAT'))<tr><td><a href="#m-cat">Kategori</a></td><td>Pengelompokan barang + awalan kode</td><td><span class="g-role">hanya Supervisor</span></td></tr>@endif
                @if ($can('LOC'))<tr><td><a href="#m-loc">Lokasi Penyimpanan</a></td><td>Rak / gudang</td><td><span class="g-role">hanya Supervisor</span></td></tr>@endif
            </tbody>
        </table>
        <div class="g-tip"><b>Urutan menyiapkan data baru:</b> Kategori, Lokasi Penyimpanan, Satuan, Supplier, Departemen, Penerima, baru Barang. Barang butuh kategori, satuan, dan (opsional) lokasi yang sudah ada.</div>

        @if ($can('ITM'))
        <h3 id="m-itm">Barang</h3>
        <p><b>Tujuan:</b> daftar semua barang beserta stok sekarang. Satu baris = satu barang (bukan satu batch). Grid menampilkan Kategori, Satuan, Lokasi, <b>Batch</b>, <b>Kedaluwarsa Terdekat</b>, <b>Stok</b>, <b>Stok Tersedia</b> (batch yang belum kedaluwarsa), Titik Pesan Ulang, Lead Time (hari), Status stok (<b>AMAN</b> / <b>PESAN ULANG</b>); <span class="g-role">hanya Supervisor</span> juga Harga Satuan dan Nilai Stok. Saringan: Kategori, Satuan. Kotak hitung: Semua barang, Pesan ulang, Nonaktif.</p>
        <h4>Menambah barang</h4>
        <ol class="g-steps">
            <li>Tekan <b>+ Data Baru</b>. Data Entry maupun Supervisor boleh menambah barang baru.</li>
            <li>Isi <b>Nama Barang</b>, pilih <b>Kategori</b>, <b>Satuan</b>, dan (bila ada) <b>Lokasi Rak / Penyimpanan</b>.</li>
            <li>Centang <b>Barang berkedaluwarsa</b> bila barangnya punya tanggal kedaluwarsa (lem, oli, gemuk, dan sejenisnya). Tanggalnya <b>tidak diisi di sini</b>, melainkan di setiap baris Penerimaan Barang.</li>
            <li><span class="g-role">hanya Supervisor</span> Isi <b>Titik Pesan Ulang</b> (peringatan muncul bila stok tersedia sama dengan atau di bawah angka ini; 0 = tanpa peringatan), <b>Jml Pesan Ulang</b> (jumlah yang disarankan dipesan), <b>Lead Time (hari)</b> (lama dari pesan sampai barang datang), dan <b>Harga Satuan (Rp)</b> (harga acuan). Bila Data Entry yang menambah barang, bagian ini kosong (0) dan dilengkapi Supervisor kemudian.</li>
            <li>Tekan <b>Simpan</b>.</li>
        </ol>
        <p>Mengubah barang yang sudah ada, mengaktifkan, atau menonaktifkannya hanya untuk Supervisor; Data Entry melihat form terkunci.</p>
        <h4>Satuan masuk (kemasan saat barang diterima)</h4>
        <p>Di bagian <b>Satuan masuk</b> pada form barang (barang yang sudah disimpan) tercatat kemasan yang sah saat barang itu <b>diterima</b>, beserta faktornya ke satuan stok barang, mis. 1 dus = 12 pcs. Tabelnya menunjukkan <b>Satuan masuk</b>, <b>Faktor ke {satuan stok}</b>, <b>Dipakai</b> (jumlah dokumen Penerimaan terkonfirmasi yang memakainya), dan <b>Diubah oleh</b> (nama dan waktu).</p>
        <ol class="g-steps">
            <li>Tekan <b>Tambah Satuan Masuk</b>, pilih <b>Satuan</b>, isi <b>Faktor</b> (berapa satuan stok dalam 1 satuan ini), lalu <b>Simpan</b>. Data Entry maupun Supervisor boleh menambah.</li>
            <li>Salah ketik? Tekan <b>Ubah</b> pada barisnya. Faktor yang <b>belum pernah dipakai</b> di dokumen terkonfirmasi boleh diperbaiki siapa pun.</li>
            <li>Faktor yang <b>sudah dipakai</b> hanya bisa diubah Supervisor. Data Entry tidak melihat tombol Ubah; bila tetap ditolak, muncul pesan <i>Faktor ini sudah dipakai di dokumen; hubungi Supervisor untuk mengubahnya.</i></li>
        </ol>
        <p class="g-note">Perubahan faktor hanya berlaku untuk penerimaan berikutnya, dokumen lama tidak berubah (dokumen menyimpan jumlah yang diketik dan hasil konversinya). Satuan stok barang tidak bisa diganti selama barang itu masih punya satuan masuk.</p>
        <ul class="g-list">
            <li><b>Kode Barang dibuat otomatis:</b> <code>AWALAN-URUTAN</code> (mis. <code>ATK-0001</code>), tanpa tanggal. Awalan diambil dari Kategori. Kode, Kategori, dan tanda <b>berkedaluwarsa</b> <b>terkunci</b> setelah disimpan.</li>
            <li><b>Barang berkedaluwarsa dipecah menjadi batch.</b> Satu tanggal kedaluwarsa = satu batch dengan kode <code>KODEBARANG-TTBBHH</code> (mis. <code>ATK-0008-261101</code> = kedaluwarsa 01 November 2026). Batch dibuat otomatis saat Penerimaan Barang atau Saldo Awal memakai tanggal yang belum ada; tanggal yang sama menambah stok batch yang sama. Daftar batch (kode, tanggal, stok, status) tampil di bagian <b>Batch</b> pada form barang.</li>
            <li>Kotak di atas form (di barang yang sudah ada) menunjukkan <b>Stok saat ini</b> (semua batch) dan <b>Tersedia</b> (hanya yang belum kedaluwarsa); <span class="g-role">hanya Supervisor</span> juga <b>Nilai stok</b>. Stok <b>tidak bisa diketik di sini</b>: stok hanya berubah lewat dokumen transaksi.</li>
            <li><b>Titik Pesan Ulang dihitung per barang</b> dari stok tersedia seluruh batch. Batch yang sudah kedaluwarsa tidak dihitung, jadi barang bisa berstatus <b>PESAN ULANG</b> walau di rak masih ada stok yang sudah kedaluwarsa.</li>
            <li><b>Harga Satuan</b> hanya acuan. Nilai stok memakai rata-rata bergerak dari harga beli di Penerimaan Barang.</li>
        </ul>
        @include('help.diagram', ['name' => 'ug-barang-batch', 'caption' => 'Barang, batch per tanggal kedaluwarsa, urutan keluar, dan titik pesan ulang.'])
        @endif

        @if ($can('SUP'))
        <h3 id="m-sup">Suppliers</h3>
        <p><b>Tujuan:</b> daftar pemasok yang dipilih di Penerimaan Barang. Boleh dikelola Data Entry dan Supervisor.</p>
        <ol class="g-steps">
            <li>Tekan <b>+ Data Baru</b>.</li>
            <li>Isi <b>Kode Supplier</b> (unik) dan <b>Nama Supplier</b>.</li>
            <li>Isi kontak bila ada: <b>Telepon Kantor (PSTN)</b>, <b>Nama Kontak</b>, <b>HP Kontak (smartphone)</b> (juga dipakai untuk WhatsApp), dan <b>Alamat Jalan</b>.</li>
            <li>Pada <b>Kecamatan, Kota / Kabupaten, Provinsi, Negara</b> ketik kecamatan, kota, atau kode pos lalu pilih dari daftar; satu pilihan mengisi kecamatan, kota, provinsi, negara, dan kode pos. Wilayah wajib dipilih dari daftar (minimal sampai kota) bila data wilayah sudah ada; bila daftar wilayah masih kosong, isian ini boleh dikosongkan.</li>
            <li>Tekan <b>Simpan</b>.</li>
        </ol>
        @endif

        @if ($can('DEP') || $can('RCP'))
        <h3 id="m-dep">Departemen dan Penerima</h3>
        <p><b>Tujuan:</b> mencatat siapa dan bagian mana yang menerima barang, dipakai di Penerimaan, Pengeluaran, dan Penyesuaian, serta laporan Pengeluaran per penerima atau departemen. Boleh dikelola Data Entry dan Supervisor.</p>
        <ul class="g-list">
            @if ($can('DEP'))<li><b>Departemen:</b> isi <b>Kode Departemen</b> dan <b>Nama Departemen</b>.</li>@endif
            @if ($can('RCP'))<li><b>Penerima:</b> isi <b>Nama Penerima</b> dan (opsional) <b>Departemen Bawaan</b>. Departemen bawaan dipakai di dokumen bila Anda tidak memilih departemen.</li>@endif
            <li><b>Tambah cepat dari dokumen:</b> saat mengisi Penerimaan, Pengeluaran, atau Penyesuaian, buka pilihan <b>Penerima</b> atau <b>Departemen</b> dan pilih baris <b>Penerima baru</b> / <b>Departemen baru</b> di bagian atas. Kotak kecil terbuka; isi nama lalu <b>Simpan</b>, dan pilihan langsung terisi tanpa meninggalkan dokumen.</li>
        </ul>
        @endif

        @if ($can('UOM'))
        <h3 id="m-uom">Satuan <span class="g-role">hanya Supervisor</span></h3>
        <p><b>Tujuan:</b> daftar satuan (pcs, box, kg, ...) dan konversinya. Grid menampilkan jenis, jumlah konversi, dan jumlah barang pemakai.</p>
        <ol class="g-steps">
            <li>Tekan <b>+ Data Baru</b>, isi <b>Kode Satuan</b> (singkat, huruf kecil, mis. <code>box</code>; terkunci setelah disimpan) dan <b>Nama Satuan</b>.</li>
            <li>Pilih <b>Jenis</b> (hanya menentukan warna satuan di daftar).</li>
            <li>Untuk konversi, tekan <b>+ Tambah Konversi</b>, pilih satuan tujuan dan isi <b>Faktor</b> (mis. 1 box = 12 pcs: faktor 12). Arah sebaliknya dihitung otomatis; isi tiap pasangan satu kali saja.</li>
            <li>Tekan <b>Simpan</b>.</li>
        </ol>
        <p class="g-note">Konversi di sini berlaku umum untuk semua barang dan hanya dipakai di Penerimaan Barang sebagai <b>cadangan</b>: bila barang belum punya faktor sendiri (bagian <b>Satuan masuk</b> di form barang) untuk satuan yang dipilih, sistem memakai pasangan di sini. Faktor milik barang selalu didahulukan.</p>
        <p class="g-note">Pengguna tanpa akses RW ke Satuan tidak mengubah konversi umum ini. Faktor per barang dibuat dari form barang atau dari jendela <b>Faktor satuan belum ada</b> di Penerimaan Barang (aturan ubahnya ada di bagian Barang).</p>
        @endif

        @if ($can('CAT'))
        <h3 id="m-cat">Kategori <span class="g-role">hanya Supervisor</span></h3>
        <p><b>Tujuan:</b> pengelompokan barang. Isi <b>Awalan Kode</b> (tepat 3 huruf atau angka, unik, mis. <code>ATK</code>) dan <b>Nama Kategori</b>. Awalan menjadi dasar kode barang dan <b>tidak bisa diubah</b> setelah disimpan.</p>
        @endif

        @if ($can('LOC'))
        <h3 id="m-loc">Lokasi Penyimpanan <span class="g-role">hanya Supervisor</span></h3>
        <p><b>Tujuan:</b> rak atau gudang yang dipilih di form Barang (<b>Lokasi Rak / Penyimpanan</b>). Isi <b>Kode Lokasi</b> (unik, mis. <code>A1-01</code>; terkunci setelah disimpan) dan <b>Nama Lokasi</b>.</p>
        @endif
        @endif

        @if ($anyTrx)
        {{-- 6 --}}
        <h2 id="transaksi">6. Transaksi</h2>
        <p>Semua perubahan stok dicatat sebagai dokumen. Setiap dokumen punya nomor otomatis berawalan jenisnya (OPN, RCV, ISS, ADJ) lalu tahun-bulan dan urutan, serta mengikuti siklus <b>Draf &gt; Konfirmasi</b> (bagian 4). Stok tidak bisa menjadi minus.</p>
        <table class="g-table">
            <thead><tr><th>Modul</th><th>Dipakai untuk</th><th>Siapa</th></tr></thead>
            <tbody>
                @if ($can('OPN'))<tr><td><a href="#t-opn">Saldo Awal</a></td><td>Memasukkan stok awal saat mulai memakai Stoku</td><td><span class="g-role">hanya Supervisor</span></td></tr>@endif
                @if ($can('RCV'))<tr><td><a href="#t-rcv">Penerimaan Barang</a></td><td>Barang masuk dari supplier</td><td>Data Entry, Supervisor</td></tr>@endif
                @if ($can('ISS'))<tr><td><a href="#t-iss">Pengeluaran Barang</a></td><td>Barang keluar ke penerima</td><td>Data Entry, Supervisor</td></tr>@endif
                @if ($can('ADJ'))<tr><td><a href="#t-adj">Penyesuaian Stok</a></td><td>Mencocokkan stok sistem dengan hitungan fisik (opname)</td><td><span class="g-role">hanya Supervisor</span></td></tr>@endif
            </tbody>
        </table>

        @if ($can('OPN'))
        <h3 id="t-opn">Saldo Awal <span class="g-role">hanya Supervisor</span></h3>
        <ol class="g-steps">
            <li>Buka <b>Saldo Awal</b>, tekan <b>+ Data Baru</b>.</li>
            <li>Isi <b>Saldo per Tanggal (awal periode)</b>; tanggal ini tidak boleh lebih dari hari ini.</li>
            <li>Tekan <b>+ Tambah Barang</b>, pilih barang, isi <b>Jml Awal</b> dan <b>Nilai Satuan</b>. <b>Valuasi</b> dihitung otomatis.</li>
            <li><b>Simpan</b>, periksa, lalu <b>Konfirmasi</b>.</li>
        </ol>
        <p class="g-note">Saldo Awal hanya untuk barang yang <b>belum punya pergerakan stok</b>. Untuk barang yang sudah bergerak, pakai Penyesuaian Stok.</p>
        @endif

        @if ($can('RCV'))
        <h3 id="t-rcv">Penerimaan Barang</h3>
        <ol class="g-steps">
            <li>Buka <b>Penerimaan Barang</b>, tekan <b>+ Data Baru</b>.</li>
            <li>Pilih <b>Supplier</b> (wajib). Isi <b>No. Referensi (surat jalan / PO / faktur)</b> bila ada. <b>Penerima</b> dan <b>Departemen</b> opsional.</li>
            <li>Tekan <b>+ Tambah Barang</b>, pilih barang, lalu isi <b>Tanggal Kedaluwarsa</b> (wajib untuk barang berkedaluwarsa; barang lain mengabaikannya), <b>Jml</b>, <b>Satuan</b>, dan <b>Harga Beli / Satuan</b>. Satu kedatangan biasanya satu tanggal, jadi cukup satu baris. Kolom <b>Satuan</b> menawarkan satuan stok barang itu dan <b>Satuan masuk</b>-nya (mis. dus = 12); satuan lain ada di grup <b>Satuan lain</b>. Setelah mengisi Jml dan Satuan, kolom <b>= Dalam satuan stok</b> langsung menghitung hasilnya (mis. 5 dus = 60 pcs) sebelum Konfirmasi; <b>Subtotal</b> juga terisi otomatis. Yang Anda ketik (jumlah, satuan, harga per satuan) tercatat di dokumen, sedangkan stok dan biaya rata-rata memakai satuan stok. Bila satuan yang dipilih belum punya faktor (tidak ada di Satuan masuk barang itu maupun di konversi umum), saat <b>Simpan</b> / <b>Konfirmasi</b> muncul jendela <b>Faktor satuan belum ada</b>: isi angka pada "1 dus = ... pcs" untuk tiap barang, tekan <b>Simpan Faktor</b>, lalu tekan Simpan / Konfirmasi lagi. Faktor itu tersimpan sebagai Satuan masuk milik <b>barang tersebut</b> saja.</li>
            <li>Isi <b>Catatan</b> bila perlu, tekan <b>Simpan</b> (menjadi Draf), periksa, lalu <b>Konfirmasi</b>.</li>
        </ol>
        <ul class="g-list">
            <li>Setelah Konfirmasi, stok batch bertambah dan <b>biaya rata-rata</b> batch dihitung ulang dari harga beli. Tanggal kedaluwarsa yang belum pernah dipakai membuat <b>batch baru</b> otomatis; tanggal yang sama menambah batch yang ada.</li>
            <li>Penerimaan yang dikonfirmasi otomatis <b>menutup pesanan ulang</b> yang tercatat untuk barang-barang itu (lihat Beranda, <b>Catat Pemesanan</b>).</li>
            <li>Bila barang berada di bawah Titik Pesan Ulang setelah transaksi, Stoku menampilkan peringatan.</li>
        </ul>
        @endif

        @if ($can('ISS'))
        <h3 id="t-iss">Pengeluaran Barang</h3>
        <ol class="g-steps">
            <li>Buka <b>Pengeluaran Barang</b>, tekan <b>+ Data Baru</b>.</li>
            <li>Pilih <b>Penerima</b> (wajib), <b>Departemen</b> (opsional; kosong = departemen bawaan penerima), dan isi <b>Keperluan (maintenance / ATK ...)</b>.</li>
            <li>Tekan <b>+ Tambah Barang</b>, pilih barang. Barang berkedaluwarsa muncul per batch, dengan yang <b>kedaluwarsanya paling cepat di atas</b> (FEFO); kolom <b>Tersedia</b> menunjukkan stok batch itu. Isi <b>Jml</b>; satuannya selalu satuan stok barang (ditampilkan di kolom <b>Satuan</b>, tidak bisa dipilih). Bila melebihi stok batch yang dipilih, sisanya otomatis diambil dari batch berikutnya (kedaluwarsa paling cepat dulu). Batch yang sudah kedaluwarsa tidak ditawarkan.</li>
            <li>Bila Anda memilih batch yang kedaluwarsanya lebih jauh padahal ada batch lebih cepat yang masih punya stok, form menampilkan <b>peringatan kuning</b> (hanya peringatan, tidak memblokir).</li>
            <li><b>Simpan</b>, periksa, lalu <b>Konfirmasi</b>. Stok berkurang pada nilai biaya rata-rata.</li>
        </ol>
        @endif

        @if ($can('ADJ'))
        <h3 id="t-adj">Penyesuaian Stok <span class="g-role">hanya Supervisor</span></h3>
        <p>Dipakai saat opname (hitung fisik) atau untuk mengoreksi selisih. Penyesuaian <b>tidak langsung mengubah stok</b>: harus disetujui lebih dulu.</p>
        <ol class="g-steps">
            <li>Buka <b>Penyesuaian Stok</b>, tekan <b>+ Data Baru</b>.</li>
            <li>Isi <b>Alasan penyesuaian / opname</b> (wajib).</li>
            <li>Tekan <b>+ Tambah Barang</b>, pilih barang: <b>Jml Sistem</b> terisi otomatis. Isi <b>Jml Hitung (fisik)</b>; <b>Selisih</b> dihitung otomatis (plus = stok bertambah, minus = berkurang).</li>
            <li><b>Simpan</b>, lalu <b>Konfirmasi</b>: dokumen menjadi <b>Menunggu Persetujuan</b> (stok belum berubah).</li>
            <li>Supervisor menekan <b>Setujui</b> (stok diperbarui, status <b>Disetujui</b>) atau <b>Tolak</b> (stok tidak berubah, status <b>Ditolak</b>).</li>
        </ol>
        @include('help.diagram', ['name' => 'ug-penyesuaian', 'caption' => 'Alur Penyesuaian Stok.'])
        <div class="g-warn"><b>Setujui ditolak bila stok barang berubah</b> sejak dihitung (ada penerimaan / pengeluaran baru). Tolak dokumennya lalu hitung ulang di dokumen baru.</div>
        <p class="g-note">Di grid tersedia kotak <b>Menunggu persetujuan</b> dan <b>Draf</b> untuk menyaring cepat.</p>
        @endif

        <h3 id="t-batal">Membatalkan dokumen yang sudah dikonfirmasi</h3>
        <ol class="g-steps">
            <li>Buka dokumennya (klik 2x di grid), tekan <b>Batalkan</b>.</li>
            <li>Kotak <b>Konfirmasi</b> meminta <b>Alasan</b> (wajib, minimal 3 huruf, maksimal 255). Isi lalu tekan <b>Ya, lanjutkan</b>. Tombol <b>Cancel</b> di kotak itu hanya menutup kotak, dokumen tidak berubah.</li>
            <li><b>Supervisor</b> (dan Administrator): dokumen langsung <b>Dibatalkan</b> dan stok dikembalikan.</li>
            <li><b>Data Entry:</b> membuat <b>pengajuan pembatalan</b>. Status menjadi <b>Pembatalan Menunggu Persetujuan</b> dan stok belum berubah. Supervisor akan <b>Setujui</b> (dokumen Dibatalkan, stok dikembalikan) atau <b>Tolak</b> (dokumen kembali berlaku).</li>
            <li>Pengaju boleh <b>menarik</b> pengajuannya selama masih menunggu (tekan <b>Batalkan</b> lagi). Hanya pengaju atau Supervisor yang boleh.</li>
        </ol>
        @include('help.diagram', ['name' => 'ug-pembatalan', 'caption' => 'Alur membatalkan dokumen yang sudah dikonfirmasi.'])
        <div class="g-warn"><b>Pembatalan bisa ditolak otomatis bila stok tidak cukup.</b> Contoh: Penerimaan sudah sebagian dikeluarkan, sehingga stok yang ditarik kembali akan menjadi minus. Peringatannya tampil di form sebelum Anda menekan tombol. Solusinya: buat <b>Penyesuaian Stok</b> untuk mengoreksi.</div>
        <ul class="g-list">
            <li>Setiap pembatalan dokumen yang sudah diproses otomatis membuat dokumen <b>Penyesuaian Stok pembalik</b> (langsung berstatus Disetujui, alasan &quot;Pembalik otomatis pembatalan ...&quot;); nomornya tampil di Riwayat dokumen asal. Dokumen pembalik tidak bisa dibatalkan lagi.</li>
            <li>Riwayat stok <b>tidak dihapus</b>: pembatalan menambah baris pembalik. Di <b>Kartu Stok</b> baris itu ditandai <b>(BATAL)</b>.</li>
            <li>Pembatalan Penerimaan membuka kembali pesanan ulang yang tadinya ditutupnya.</li>
            <li>Dokumen <b>Draf</b> tidak dibatalkan melainkan <b>Hapus</b>.</li>
        </ul>
        @endif

        @if ($anyRpt)
        {{-- 7 --}}
        <h2 id="laporan">7. Laporan</h2>
        <p>Laporan hanya membaca data. Cara pakai yang sama untuk semua laporan:</p>
        <ol class="g-steps">
            <li>Buka laporan dari menu <b>Laporan</b>.</li>
            <li>Atur <b>Dari tanggal</b> / <b>Sampai tanggal</b> dan saringan lain yang ada.</li>
            <li>Tekan <b>Terapkan</b>.</li>
            <li>Tekan <b>Cetak</b> untuk mencetak (atau menyimpan PDF dari dialog cetak browser). <b>Hemat Tinta</b> mencetak tanpa warna latar.</li>
        </ol>
        <p class="g-note">Harga, biaya, dan nilai hanya tampil untuk Supervisor.</p>
        <div class="g-tip"><b>Cetak dokumen:</b> tombol <b>Cetak</b> ada di form semua dokumen stok (Draf, Terkonfirmasi, Dibatalkan, dan seterusnya). Hasil cetak selalu memuat <b>mode</b> dokumen (DRAFT / CONFIRMED / CANCELLED, penyesuaian juga WAITING APPROVAL / APPROVED / REJECTED); dokumen yang belum atau tidak sah diberi tanda air.</div>
        <table class="g-table">
            <thead><tr><th>Laporan</th><th>Isi dan saringan</th></tr></thead>
            <tbody>
                @if ($can('R-CARD'))<tr><td>Kartu Stok</td><td>Riwayat pergerakan satu barang: saldo awal, masuk, keluar, penyesuaian, saldo berjalan, dokumen, dan user. Wajib memilih <b>Barang</b>. Pembatalan tampil sebagai baris <b>(BATAL)</b>.</td></tr>@endif
                @if ($can('R-SUM'))<tr><td>Ringkasan Stok</td><td>Per barang: saldo awal, masuk, keluar, penyesuaian, saldo akhir periode, dan status titik pesan ulang (dihitung dari saldo akhir batch yang belum kedaluwarsa). Saringan: <b>Kategori</b>, <b>Hanya barang di / bawah titik pesan ulang</b>, <b>Termasuk barang nonaktif</b>. Supervisor juga melihat valuasi dan total nilai stok.</td></tr>@endif
                @if ($can('R-IN'))<tr><td>Laporan Penerimaan</td><td>Barang masuk per periode: tanggal, jam, supplier, no. referensi, barang, jumlah, user. Saringan: <b>Supplier</b>. Harga beli dan jumlah nilai untuk Supervisor.</td></tr>@endif
                @if ($can('R-OUT'))<tr><td>Laporan Pengeluaran</td><td>Barang keluar per periode: tanggal, jam, penerima, departemen, keperluan, barang, jumlah, user. Saringan: <b>Penerima / Departemen</b>. Hanya dokumen yang berlaku; draf dan yang dibatalkan tidak dihitung.</td></tr>@endif
                @if ($can('R-EXP'))<tr><td>Laporan Kedaluwarsa</td><td>Barang yang masih ada stoknya dan sudah atau hampir kedaluwarsa, mulai yang paling dulu. Saringan: <b>Kedaluwarsa dalam (hari)</b> (bawaan 30) dan <b>Kategori</b>; kolom <b>Sisa Hari</b>.</td></tr>@endif
                @if ($can('R-ADJ'))<tr><td>Laporan Opname &amp; Penyesuaian <span class="g-role">hanya Supervisor</span></td><td>Hasil opname: stok sistem vs fisik, selisih, alasan, status persetujuan, dan dampak nilai. Saringan: periode dan <b>Status</b>.</td></tr>@endif
            </tbody>
        </table>
        @endif

        @if ($anySec)
        {{-- 8 --}}
        <h2 id="keamanan">8. Keamanan <span class="g-role">hanya Administrator</span></h2>
        <p>Menu Keamanan (menempel di bawah panel kiri) untuk mengelola siapa boleh apa.</p>
        @if ($can('USR'))
        <h3>Pengguna</h3>
        <ol class="g-steps">
            <li>Buka <b>Pengguna</b>, tekan <b>+ Data Baru</b>.</li>
            <li>Isi <b>Nama Lengkap</b>, <b>Username</b> (huruf kecil, angka, titik, garis; tidak bisa diubah setelah disimpan), <b>Email</b>, pilih <b>Peran</b> (Data Entry / Supervisor / Administrator), dan <b>Grup</b> (opsional).</li>
            <li>Isi <b>Password</b> (minimal 8 karakter). Saat mengubah pengguna lama, kosongkan password bila tidak diganti.</li>
            <li><b>Simpan</b>. Gunakan <b>Nonaktifkan</b> untuk mencabut akses seseorang tanpa menghapus riwayatnya.</li>
        </ol>
        <p class="g-note">Demi keamanan, Anda tidak bisa menonaktifkan atau mengubah peran akun Anda sendiri.</p>
        @endif
        @if ($can('GRP'))
        <h3>Grup</h3>
        <p>Grup menentukan modul mana yang boleh dibuka anggotanya, dengan tiga tingkat per modul: <b>Tidak ada</b>, <b>Hanya baca (RO)</b> (boleh membuka layar tetapi tidak boleh menyimpan atau mengubah), dan <b>Baca &amp; tulis (RW)</b>.</p>
        <ol class="g-steps">
            <li>Buka <b>Grup</b>, tekan <b>+ Data Baru</b>, isi <b>Kode Grup</b> dan <b>Nama Grup</b>.</li>
            <li>Pada tabel <b>Akses per modul</b>, pilih <b>Hak akses</b> tiap modul (kolom <b>Peran Minimal</b> menunjukkan peran terendah yang masih bisa memakainya), lalu <b>Simpan</b>.</li>
            <li>Di form <b>Pengguna</b>, pilih Grup untuk orang yang bersangkutan. Pengguna tanpa Grup memakai modul bawaan perannya.</li>
            <li>Grup tidak dihapus: gunakan <b>Nonaktifkan</b> / <b>Aktifkan</b> (tombol di form atau ikon di grid). Grup nonaktif tidak muncul di pilihan Grup untuk pengguna; anggota yang sudah ada tidak berubah.</li>
        </ol>
        <div class="g-tip">Akses nyata = modul diberikan Grup <b>dan</b> peran pengguna cukup tinggi. Modul khusus Supervisor tetap tertutup bagi Data Entry walau dicentang. Administrator selalu penuh.</div>
        @endif
        @if ($can('MOD'))
        <h3>Daftar Modul</h3>
        <p>Daftar baca-saja semua modul aplikasi, peran minimal, dan grup yang boleh membukanya. Pengaturannya dilakukan di <b>Grup</b>.</p>
        @endif
        @if ($can('SMP'))
        <h3>Data Contoh</h3>
        <p>Menyegarkan seluruh data dengan data contoh (untuk percobaan atau pelatihan). Layar menampilkan isi database saat ini dan apa yang akan terjadi.</p>
        <ol class="g-steps">
            <li>Buka <b>Data Contoh</b> dan baca daftarnya.</li>
            <li>Ketik <b>SEGARKAN</b> pada kotak konfirmasi lalu tekan <b>Segarkan Data Contoh</b>.</li>
            <li>Anda keluar otomatis; masuk lagi dengan akun contoh (<code>admin</code>, <code>supervisor</code>, <code>entry</code>).</li>
        </ol>
        <div class="g-warn"><b>Seluruh data saat ini hilang</b> (barang, transaksi, supplier, akun, grup) dan tidak bisa dikembalikan. Data wilayah dan gambar latar login dipertahankan. Fitur ini dimatikan di server produksi.</div>
        @endif
        @endif

        {{-- 9 --}}
        <h2 id="tanya">9. Pertanyaan umum</h2>
        <table class="g-table">
            <thead><tr><th>Masalah</th><th>Jawaban</th></tr></thead>
            <tbody>
                <tr><td>Menu atau tombol tidak ada</td><td>Peran atau Grup Anda belum diberi akses. Contoh: mengubah Barang hanya untuk Supervisor. Hubungi Administrator.</td></tr>
                <tr><td>Muncul pesan &quot;akses hanya-baca (RO)&quot;</td><td>Grup Anda hanya boleh membuka modul itu tanpa mengubah. Minta Administrator memberi RW.</td></tr>
                <tr><td>Stok tidak berkurang / bertambah setelah Simpan</td><td>Simpan hanya membuat Draf. Tekan <b>Konfirmasi</b> agar diproses ke stok (Penyesuaian: tunggu <b>Setujui</b> Supervisor).</td></tr>
                <tr><td>Pengeluaran ditolak: stok tidak cukup</td><td>Jumlah melebihi stok. Periksa kolom <b>Tersedia</b>; bila stok fisik memang ada, lakukan Penerimaan atau Penyesuaian Stok lebih dulu.</td></tr>
                <tr><td>Pembatalan ditolak: stok tidak cukup</td><td>Dokumen itu tidak bisa ditarik kembali karena akan membuat stok minus. Koreksi lewat Penyesuaian Stok.</td></tr>
                <tr><td>Kode barang tidak bisa diubah</td><td>Memang terkunci supaya riwayat tidak rusak. Bila salah, nonaktifkan barangnya lalu buat barang baru.</td></tr>
                <tr><td>Barang tidak muncul di pilihan dokumen</td><td>Barang atau data master itu nonaktif, atau belum ada. Aktifkan lewat grid master (Supervisor).</td></tr>
                <tr><td>Tiba-tiba keluar ke halaman masuk</td><td>Tidak ada aktivitas 30 menit (Stoku keluar otomatis), atau akun Anda dinonaktifkan. Masuk lagi; data yang belum disimpan tidak tersimpan otomatis.</td></tr>
                <tr><td>Nilai persediaan dan harga rata-rata</td><td>Dihitung presisi (bukan angka pecahan komputer), dibulatkan sekali di akhir, sehingga jumlah di laporan konsisten dengan Kartu Stok.</td></tr>
                <tr><td>Takut menyimpan dua kali</td><td>Tombol hanya bereaksi sekali; data tidak akan ganda.</td></tr>
            </tbody>
        </table>
    </main>
</div>
@endsection
