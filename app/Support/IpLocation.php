<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/* REMARK
 * Tujuan       : Perkiraan lokasi + operator dari alamat IP untuk layar Log Aktivitas (kota, provinsi, negara · ISP). Hanya perkiraan kasar: IP seluler sering menunjuk kota pusat jaringan operator, bukan posisi orangnya.
 *                IP lokal / privat (laptop, jaringan kantor) tidak dikirim ke mana pun. IP publik dikirim ke layanan pihak ketiga (ipwho.is, HTTPS, tanpa kunci) SEKALI per IP lalu disimpan di cache.
 *                Per tampilan layar paling banyak MAX_LOOKUPS IP baru dicari (batas waktu 3 detik) supaya layar tidak lambat; sisanya tampil "Mencari..." dan terisi pada pemuatan berikutnya.
 * Sumber hulu  : config sstock.ip_lookup (SSTOCK_IP_LOOKUP=false mematikan pencarian keluar), cache aplikasi.
 * Output       : label(ips) => [ip => teks].
 * Dipakai oleh : SuperAdminController@log (hanya super admin).
 * Status       : Aktif.
 */
final class IpLocation
{
    private const MAX_LOOKUPS = 8;

    private const OK_TTL_DAYS = 30;

    private const FAIL_TTL_MINUTES = 10;

    /**
     * @param  string[]  $ips
     * @return array<string, string>
     */
    public static function label(array $ips): array
    {
        $out = [];
        $budget = self::MAX_LOOKUPS;
        foreach (array_unique($ips) as $ip) {
            $out[$ip] = self::one($ip, $budget);
        }

        return $out;
    }

    private static function one(string $ip, int &$budget): string
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return '';
        }
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return 'Jaringan lokal';
        }
        $key = 'iploc:'.$ip;
        if (($hit = Cache::get($key)) !== null) {
            return $hit;
        }
        if (! config('sstock.ip_lookup') || $budget <= 0) {
            return 'Mencari...';
        }
        $budget--;

        return self::fetch($ip, $key, $budget);
    }

    private static function fetch(string $ip, string $key, int &$budget): string
    {
        try {
            $r = Http::timeout(3)->get('https://ipwho.is/'.$ip, ['fields' => 'success,city,region,country,connection']);
            $j = $r->json();
        } catch (\Throwable) {
            $j = null;
            $budget = 0;   // layanan mati / timeout: jangan menunggu 3 detik lagi untuk IP berikutnya pada tampilan ini
        }
        if (! is_array($j) || ($j['success'] ?? false) !== true) {
            Cache::put($key, 'Tidak diketahui', now()->addMinutes(self::FAIL_TTL_MINUTES));

            return 'Tidak diketahui';
        }
        $place = implode(', ', array_filter([$j['city'] ?? '', $j['region'] ?? '', $j['country'] ?? '']));
        $isp = $j['connection']['isp'] ?? '';
        $text = $place !== '' ? $place.($isp !== '' ? ' · '.$isp : '') : ($isp !== '' ? $isp : 'Tidak diketahui');
        Cache::put($key, $text, now()->addDays(self::OK_TTL_DAYS));

        return $text;
    }
}
