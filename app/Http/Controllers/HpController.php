<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Product;
use App\Models\StockDocument;
use App\Enums\StockDocumentType;
use App\Services\CountSchedule;
use App\Support\HpShell;
use App\Support\Modules;
use Devlib\Device;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/* REMARK
 * Tujuan       : Tampilan HP (smartphone potret) selain Hitung Opname: Beranda ringkas, daftar modul satu kelompok, modul yang belum punya tampilan HP dibuka (halaman desktop) di dalam kerangka HP, dan penyimpan pilihan tampilan.
 *                Logika dan hak akses tidak berubah: layar desktop tetap jalan; ini hanya tampilan lain untuk perangkat yang berbeda.
 * Sumber hulu  : HpShell (tab / modul), Modules (kode modul, hak), Device (cookie ui).
 * Output       : hp.home (dipanggil HomeController bila perangkat = hp), hp.group, hp.frame; cookie ui.
 * Dipakai oleh : routes/web.php (hp.group, hp.open, ui.set), HomeController.
 * Status       : Aktif.
 */
class HpController extends Controller
{
    // Beranda HP: ringkasan sebagai kartu yang membuka modulnya. Angka sama dengan Beranda desktop (kecuali nilai stok & menunggu persetujuan hanya Supervisor).
    public function home(Request $request)
    {
        $user = $request->user();
        $below = Product::query()->belowReorderPoint()->withAvailable()->count();
        $expiring = Item::query()->nearExpiry()->get();
        $docs = fn (StockDocumentType $t) => StockDocument::query()->where('type', $t)->whereIn('status', ['confirmed', 'cancel_pending'])->whereDate('doc_at', today())->count();
        $cards = array_values(array_filter([
            ['label' => 'Barang aktif', 'value' => Product::query()->where('is_active', true)->count(), 'icon' => 'fa-solid fa-boxes-stacked', 'code' => 'ITM'],
            ['label' => 'Di / bawah titik pesan ulang', 'value' => $below, 'icon' => 'fa-solid fa-triangle-exclamation', 'code' => 'ITM', 'tone' => $below ? 'danger' : ''],
            ['label' => 'Hampir / sudah kedaluwarsa', 'value' => $expiring->count(), 'icon' => 'fa-solid fa-hourglass-end', 'code' => 'R-EXP', 'tone' => $expiring->isNotEmpty() ? 'warn' : ''],
            ['label' => 'Penerimaan hari ini', 'value' => $docs(StockDocumentType::Receiving), 'icon' => 'fa-solid fa-truck-ramp-box', 'code' => 'RCV'],
            ['label' => 'Pengeluaran hari ini', 'value' => $docs(StockDocumentType::Issuance), 'icon' => 'fa-solid fa-dolly', 'code' => 'ISS'],
            $user->isSupervisor() ? ['label' => 'Menunggu persetujuan', 'value' => StockDocument::query()->whereIn('status', ['approval', 'cancel_pending'])->count(), 'icon' => 'fa-solid fa-scale-balanced', 'code' => 'ADJ'] : null,
        ]));

        return view('hp.home', [
            'title' => 'Beranda', 'active' => 'home',
            'cards' => array_values(array_filter($cards, fn ($c) => $user->canAccess($c['code']))),
            'countReminder' => $user->canAccess('SO') && CountSchedule::available() ? CountSchedule::state() : null,
        ]);
    }

    // Tab Transaksi (dan kelompok lain): daftar modul yang boleh dibuka.
    public function group(Request $request, string $parent)
    {
        abort_unless(isset(Modules::PARENTS[$parent]), 404);
        $items = collect(HpShell::modules($request->user()))->firstWhere('code', $parent)['children'] ?? [];

        return view('hp.group', ['title' => Modules::PARENTS[$parent][0], 'active' => ['TRX' => 'trx', 'RPT' => 'report'][$parent] ?? '', 'items' => $items]);
    }

    // Modul tanpa tampilan HP: halaman desktop modul itu di dalam kerangka HP. Hak akses dijaga dua kali: di sini (modul boleh dibuka) dan oleh rute halaman desktop itu sendiri.
    public function open(Request $request, string $code)
    {
        $module = Modules::all()[$code] ?? null;
        abort_if($module === null, 404);
        abort_unless($request->user()->canAccess($code), 403, 'Anda tidak punya akses ke modul ini.');

        return view('hp.frame', [
            'title' => $module[1], 'active' => HpShell::tabFor($code),
            'frame' => is_array($module[4]) ? route($module[4][0], $module[4][1]) : route($module[4]),
        ]);
    }

    // Simpan pilihan tampilan (hp | desktop) di cookie `ui`; pilihan pengguna selalu menang atas tebakan perangkat.
    public function setDevice(Request $request): RedirectResponse
    {
        $ui = $request->validate(['ui' => ['required', Rule::in(Device::CHOICES)]])['ui'];

        return redirect()->route('home')->withCookie(cookie(Device::COOKIE, $ui, 60 * 24 * 365));
    }
}
