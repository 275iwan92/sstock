<?php

namespace Tests;

use App\Models\Item;
use App\Models\Product;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Arr;

abstract class TestCase extends BaseTestCase
{
    // Barang uji: produk baru (kode tetap) beserta batch tunggalnya; mengembalikan batch (Item) yang dipakai dokumen.
    protected function makeItem(array $attrs): Item
    {
        return Product::register(Arr::except($attrs, ['code', 'expiry_date']), $attrs['code'] ?? null)->batchFor($attrs['expiry_date'] ?? null);
    }
}
