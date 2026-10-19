<?php

namespace App\Support;

use Devlib\Lookup;

/* REMARK
 * Tujuan       : Lookup DevLib "geo_area" (kotak cari wilayah, ketik min. 2 huruf: kelurahan / kecamatan / kota / kode pos); memilih mengisi kode pos dan kotak baca-saja kecamatan, kota, provinsi, negara.
 * Sumber hulu  : GeoArea (tabel geo_*). Pola sama dengan lookup geo_area di tmerp.
 * Output       : Lookup::define('geo_area').
 * Dipakai oleh : AppServiceProvider::boot, view suppliers.form.
 * Status       : Aktif.
 */
final class GeoLookup
{
    public static function register(): void
    {
        Lookup::define('geo_area', [
            'key' => 'area', 'label' => 'full_label', 'source' => 'geo_country > geo_region > geo_city > geo_district > geo_village',
            'columns' => ['full_label' => 'Region', 'postal_code' => 'Postcode'],
            'fill' => ['postal_code' => 'postal_code', 'district_name' => 'district', 'city_name' => 'city', 'region_name' => 'region', 'country_name' => 'country'],
            'search' => fn (string $q) => GeoArea::search($q),
            'find' => fn ($v) => GeoArea::find((string) $v),
        ]);
    }
}
