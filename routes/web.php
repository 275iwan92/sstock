<?php

/* REMARK
 * Tujuan       : Rute web Simple Stock: login, layar utama, master (item, supplier), dokumen stok, laporan.
 * Hak akses    : semua di grup auth; Item create/ubah, master Satuan, Opening / Adjustment / laporan penyesuaian hanya Supervisor.
 * Dipakai oleh : bootstrap/app.php.
 * Status       : Aktif.
 */

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RecipientController;
use App\Http\Controllers\ReorderOrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SampleDataController;
use App\Http\Controllers\StockDocumentController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'show'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.attempt');

    // Lupa password: tautan reset lewat e-mail (maks 5 permintaan / menit / IP).
    Route::get('forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:5,1')->name('password.update');
});

Route::middleware(['auth', 'active', 'module'])->group(function () {
    Route::get('/', HomeController::class)->name('home');
    Route::post('reorder-orders', [ReorderOrderController::class, 'store'])->name('reorder-orders.store');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    // Ping x-dl::idle-logout: permintaan ini memperpanjang sesi; ikut EnsureActive (akun nonaktif ditolak), tanpa kode modul.
    Route::get('session/ping', fn () => response()->noContent())->name('session.ping');
    // HELP: User's Guide (Indonesia) untuk semua yang login; Technical Guide (English) hanya Administrator (grup di bawah).
    Route::view('help', 'help.user-guide')->name('help.user-guide');

    // Keamanan: kelola pengguna, hanya Administrator.
    Route::middleware('administrator')->prefix('users')->controller(UserController::class)->group(function () {
        Route::get('/', 'index')->name('users.index');
        Route::get('create', 'create')->name('users.create');
        Route::post('/', 'store')->name('users.store');
        Route::get('{user}', 'show')->name('users.show');
        Route::put('{user}', 'update')->name('users.update');
        Route::post('{user}/toggle', 'toggle')->name('users.toggle');
    });

    Route::middleware('administrator')->group(function () {
        Route::get('groups', [GroupController::class, 'index'])->name('groups.index');
        Route::get('groups/create', [GroupController::class, 'create'])->name('groups.create');
        Route::post('groups', [GroupController::class, 'store'])->name('groups.store');
        Route::get('groups/{group}', [GroupController::class, 'show'])->name('groups.show');
        Route::put('groups/{group}', [GroupController::class, 'update'])->name('groups.update');
        Route::post('groups/{group}/toggle', [GroupController::class, 'toggle'])->name('groups.toggle');
        Route::get('modules', [ModuleController::class, 'index'])->name('modules.index');
        Route::get('sample', [SampleDataController::class, 'index'])->name('sample.index');
        Route::post('sample', [SampleDataController::class, 'refresh'])->name('sample.refresh');
        Route::view('help/technical-guide', 'help.technical-guide')->name('help.technical-guide');
    });

    // Master Barang: semua boleh melihat dan menambah barang baru; ubah parameter dan aktif / nonaktif hanya Supervisor (dicek ProductRequest dan grup di bawah).
    Route::get('products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('products', [ProductController::class, 'store'])->name('products.store');
    Route::middleware('supervisor')->group(function () {
        Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::post('products/{product}/toggle', [ProductController::class, 'toggle'])->name('products.toggle');

        Route::get('units', [UnitController::class, 'index'])->name('units.index');
        Route::get('units/create', [UnitController::class, 'create'])->name('units.create');
        Route::post('units', [UnitController::class, 'store'])->name('units.store');
        Route::get('units/{unit}', [UnitController::class, 'show'])->name('units.show');
        Route::put('units/{unit}', [UnitController::class, 'update'])->name('units.update');
        Route::post('units/{unit}/toggle', [UnitController::class, 'toggle'])->name('units.toggle');

        foreach (['categories' => [CategoryController::class, 'category'], 'locations' => [LocationController::class, 'location']] as $path => [$controller, $param]) {
            Route::get($path, [$controller, 'index'])->name("{$path}.index");
            Route::get("{$path}/create", [$controller, 'create'])->name("{$path}.create");
            Route::post($path, [$controller, 'store'])->name("{$path}.store");
            Route::get("{$path}/{{$param}}", [$controller, 'show'])->name("{$path}.show");
            Route::put("{$path}/{{$param}}", [$controller, 'update'])->name("{$path}.update");
            Route::post("{$path}/{{$param}}/toggle", [$controller, 'toggle'])->name("{$path}.toggle");
        }
    });
    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');

    Route::get('suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
    Route::post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::get('suppliers/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');
    Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    Route::post('suppliers/{supplier}/toggle', [SupplierController::class, 'toggle'])->name('suppliers.toggle');

    // Master Departemen dan Penerima: Data Entry dan Supervisor sama-sama boleh mengelola.
    foreach (['departments' => [DepartmentController::class, 'department'], 'recipients' => [RecipientController::class, 'recipient']] as $path => [$controller, $param]) {
        Route::get($path, [$controller, 'index'])->name("{$path}.index");
        Route::get("{$path}/create", [$controller, 'create'])->name("{$path}.create");
        Route::post($path, [$controller, 'store'])->name("{$path}.store");
        Route::get("{$path}/{{$param}}", [$controller, 'show'])->name("{$path}.show");
        Route::put("{$path}/{{$param}}", [$controller, 'update'])->name("{$path}.update");
        Route::post("{$path}/{{$param}}/toggle", [$controller, 'toggle'])->name("{$path}.toggle");
    }

    // Dokumen stok: {type} = opening | receiving | issuance | adjustment (hak per jenis dicek di controller / request).
    Route::prefix('docs/{type}')->whereIn('type', ['opening', 'receiving', 'issuance', 'adjustment'])->controller(StockDocumentController::class)->group(function () {
        Route::get('/', 'index')->name('docs.index');
        Route::get('create', 'create')->name('docs.create');
        Route::post('/', 'store')->name('docs.store');
        Route::post('conversion', 'conversion')->name('docs.conversion');
        Route::get('{document}/print', 'print')->name('docs.print');
        Route::get('{document}', 'show')->name('docs.show');
        Route::put('{document}', 'update')->name('docs.update');
    });

    Route::prefix('reports')->controller(ReportController::class)->group(function () {
        Route::get('stock-card', 'stockCard')->name('reports.stock-card');
        Route::get('summary', 'summary')->name('reports.summary');
        Route::get('expiry', 'expiry')->name('reports.expiry');
        Route::get('receiving', 'receiving')->name('reports.receiving');
        Route::get('issuance', 'issuance')->name('reports.issuance');
        Route::get('adjustment', 'adjustment')->middleware('supervisor')->name('reports.adjustment');
    });
});
