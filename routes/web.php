<?php

/* REMARK
 * Tujuan       : Rute web Simple Stock: login, layar utama, master (item, supplier), dokumen stok, laporan.
 * Hak akses    : semua di grup auth; Item create/ubah, master Satuan, Opening / Adjustment / laporan penyesuaian hanya Supervisor.
 * Dipakai oleh : bootstrap/app.php.
 * Status       : Aktif.
 */

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockDocumentController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'show'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::get('/', HomeController::class)->name('home');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    // Master Item: semua boleh melihat; ubah hanya Supervisor.
    Route::middleware('supervisor')->group(function () {
        Route::get('items/create', [ItemController::class, 'create'])->name('items.create');
        Route::post('items', [ItemController::class, 'store'])->name('items.store');
        Route::put('items/{item}', [ItemController::class, 'update'])->name('items.update');
        Route::post('items/{item}/toggle', [ItemController::class, 'toggle'])->name('items.toggle');

        Route::get('units', [UnitController::class, 'index'])->name('units.index');
        Route::get('units/create', [UnitController::class, 'create'])->name('units.create');
        Route::post('units', [UnitController::class, 'store'])->name('units.store');
        Route::get('units/{unit}', [UnitController::class, 'show'])->name('units.show');
        Route::put('units/{unit}', [UnitController::class, 'update'])->name('units.update');
        Route::post('units/{unit}/toggle', [UnitController::class, 'toggle'])->name('units.toggle');
    });
    Route::get('items', [ItemController::class, 'index'])->name('items.index');
    Route::get('items/{item}', [ItemController::class, 'show'])->name('items.show');

    Route::get('suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
    Route::post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::get('suppliers/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');
    Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    Route::post('suppliers/{supplier}/toggle', [SupplierController::class, 'toggle'])->name('suppliers.toggle');

    // Dokumen stok: {type} = opening | receiving | issuance | adjustment (hak per jenis dicek di controller / request).
    Route::prefix('docs/{type}')->whereIn('type', ['opening', 'receiving', 'issuance', 'adjustment'])->controller(StockDocumentController::class)->group(function () {
        Route::get('/', 'index')->name('docs.index');
        Route::get('create', 'create')->name('docs.create');
        Route::post('/', 'store')->name('docs.store');
        Route::get('{document}', 'show')->name('docs.show');
        Route::put('{document}', 'update')->name('docs.update');
    });

    Route::prefix('reports')->controller(ReportController::class)->group(function () {
        Route::get('stock-card', 'stockCard')->name('reports.stock-card');
        Route::get('summary', 'summary')->name('reports.summary');
        Route::get('receiving', 'receiving')->name('reports.receiving');
        Route::get('issuance', 'issuance')->name('reports.issuance');
        Route::get('adjustment', 'adjustment')->middleware('supervisor')->name('reports.adjustment');
    });
});
