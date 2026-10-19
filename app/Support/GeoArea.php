<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/* REMARK
 * Tujuan       : Cari dan baca wilayah 5 tingkat (kelurahan > kecamatan > kota > provinsi > negara) dari tabel geo_*, meniru lookup geo_area tmerp:
 *                nilai area = "LEVEL:id" (VILLAGE | DISTRICT | CITY), label penuh "Pepelegi, Waru, Kab. Sidoarjo, Jawa Timur, Indonesia", kode pos dari tingkat terdalam yang punya.
 * Sumber hulu  : geo_country, geo_region, geo_city, geo_district, geo_village (salinan tm2mst, lihat sstock:import-geo).
 * Output       : baris [area, level, id, full_label, postal_code, village, district, city, region, country].
 * Dipakai oleh : GeoLookup (kotak cari di form), SupplierController (grid, form).
 * Status       : Aktif.
 */
final class GeoArea
{
    private const PATTERN = '/^(VILLAGE|DISTRICT|CITY):(\d+)$/';

    /** @return array<int, array<string, mixed>> kelurahan dulu, lalu kecamatan, lalu kota */
    public static function search(string $q, int $limit = 30): array
    {
        $like = "%{$q}%";
        $prefix = "{$q}%";

        $villages = self::villages()->where(fn ($w) => $w->where('v.village_name', 'like', $like)->orWhere('d.district_name', 'like', $like)->orWhere('v.post_code', 'like', $prefix))
            ->orderByRaw('(d.district_name = ?) desc', [$q])->orderByRaw('(v.village_name like ?) desc', [$prefix])->orderBy('v.village_name')->limit(15)->get();
        $districts = self::districts()->where(fn ($w) => $w->where('d.district_name', 'like', $like)->orWhere('c.city_name', 'like', $like)->orWhere('d.post_code', 'like', $prefix))
            ->orderByRaw('(d.district_name = ?) desc', [$q])->orderBy('d.district_name')->limit(10)->get();
        $cities = self::cities()->where(fn ($w) => $w->where('c.city_name', 'like', $like)->orWhere('r.region_name', 'like', $like)->orWhere('c.post_code', 'like', $prefix))
            ->orderByRaw('(c.city_name = ?) desc', [$q])->orderBy('c.city_name')->limit(10)->get();

        return array_slice([
            ...$villages->map(fn ($r) => self::row($r, 'VILLAGE'))->all(),
            ...$districts->map(fn ($r) => self::row($r, 'DISTRICT'))->all(),
            ...$cities->map(fn ($r) => self::row($r, 'CITY'))->all(),
        ], 0, $limit);
    }

    public static function find(?string $key): ?array
    {
        if (! $key || ! preg_match(self::PATTERN, $key, $m)) {
            return null;
        }
        $r = match ($m[1]) {
            'VILLAGE' => self::villages()->where('v.id_village', (int) $m[2]),
            'DISTRICT' => self::districts()->where('d.id_district', (int) $m[2]),
            'CITY' => self::cities()->where('c.id_city', (int) $m[2]),
        };
        $row = $r->first();

        return $row ? self::row($row, $m[1]) : null;
    }

    /**
     * @param  array<int, ?string>  $keys  "LEVEL:id"
     * @return array<string, array<string, mixed>> dikunci "LEVEL:id"
     */
    public static function findMany(array $keys): array
    {
        $ids = ['VILLAGE' => [], 'DISTRICT' => [], 'CITY' => []];
        foreach (array_unique(array_filter($keys)) as $key) {
            if (preg_match(self::PATTERN, $key, $m)) {
                $ids[$m[1]][] = (int) $m[2];
            }
        }
        $out = [];
        foreach ([['VILLAGE', self::villages(), 'v.id_village'], ['DISTRICT', self::districts(), 'd.id_district'], ['CITY', self::cities(), 'c.id_city']] as [$level, $query, $col]) {
            if ($ids[$level]) {
                foreach ($query->whereIn($col, $ids[$level])->get() as $r) {
                    $out[$level.':'.$r->id] = self::row($r, $level);
                }
            }
        }

        return $out;
    }

    private static function villages(): Builder
    {
        return DB::table('geo_village as v')
            ->join('geo_district as d', 'd.id_district', '=', 'v.district_id')
            ->join('geo_city as c', 'c.id_city', '=', 'd.city_id')
            ->join('geo_region as r', 'r.id_region', '=', 'c.region_id')
            ->join('geo_country as ct', 'ct.id_country', '=', 'r.country_id')
            ->selectRaw('v.id_village as id, v.village_name, d.district_name, c.city_type, c.city_name, r.region_name, ct.country_name, coalesce(v.post_code, d.post_code, c.post_code) as postal');
    }

    private static function districts(): Builder
    {
        return DB::table('geo_district as d')
            ->join('geo_city as c', 'c.id_city', '=', 'd.city_id')
            ->join('geo_region as r', 'r.id_region', '=', 'c.region_id')
            ->join('geo_country as ct', 'ct.id_country', '=', 'r.country_id')
            ->selectRaw('d.id_district as id, null as village_name, d.district_name, c.city_type, c.city_name, r.region_name, ct.country_name, coalesce(d.post_code, c.post_code) as postal');
    }

    private static function cities(): Builder
    {
        return DB::table('geo_city as c')
            ->join('geo_region as r', 'r.id_region', '=', 'c.region_id')
            ->join('geo_country as ct', 'ct.id_country', '=', 'r.country_id')
            ->selectRaw('c.id_city as id, null as village_name, null as district_name, c.city_type, c.city_name, r.region_name, ct.country_name, c.post_code as postal');
    }

    private static function row(object $r, string $level): array
    {
        $city = trim(($r->city_type ?? '').' '.$r->city_name);

        return [
            'area' => $level.':'.$r->id, 'level' => $level, 'id' => (int) $r->id,
            'full_label' => implode(', ', array_filter([$r->village_name, $r->district_name, $city, $r->region_name, $r->country_name])),
            'postal_code' => $r->postal, 'village' => $r->village_name, 'district' => $r->district_name, 'city' => $city, 'region' => $r->region_name, 'country' => $r->country_name,
        ];
    }
}
