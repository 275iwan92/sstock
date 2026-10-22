<?php

namespace Tests;

use App\Models\Item;
use App\Models\Product;
use App\Support\Constants;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Arr;

abstract class TestCase extends BaseTestCase
{
    // Tes tidak boleh bergantung pada `npm run build` (public/build tidak ikut Git): Vite dimatikan agar penguji baru tidak melihat puluhan tes merah.
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Constants::forget();   // cache pengaturan statis tidak boleh bocor antar tes
    }

    // Barang uji: produk baru (kode tetap) beserta batch tunggalnya; mengembalikan batch (Item) yang dipakai dokumen.
    protected function makeItem(array $attrs): Item
    {
        return Product::register(Arr::except($attrs, ['code', 'expiry_date']), $attrs['code'] ?? null)->batchFor($attrs['expiry_date'] ?? null);
    }
}
