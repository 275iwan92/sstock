<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/* REMARK
 * Tujuan       : Gambar apa pun yang ditaruh di public/media/bg/sstock otomatis jadi wallpaper login: PNG / WebP / BMP / GIF / AVIF diubah ke JPG, gambar terlalu besar (> 2560 px lebar atau > 1,5 MB) dikecilkan,
 *                rasio ganjil (mis. potret) dipotong tengah ke 16:9. File asli dipindah ke storage/app/wallpaper-source (tidak ikut git). Login memilih acak dari semua .jpg di folder itu (DevLib Frame).
 * Sumber hulu  : folder wallpaper, ekstensi GD (PNG, JPEG, WebP, BMP, GIF, AVIF).
 * Output       : file .jpg siap pakai di folder wallpaper; jumlah gambar yang diproses. Gagal pada satu file tidak pernah menggagalkan halaman login (dicatat ke log).
 * Dipakai oleh : AuthController@show dan PasswordResetController (lewat defer(): jalan SESUDAH halaman terkirim, jadi login tidak menunggu; gambar baru tampil di pembukaan login berikutnya), perintah sstock:wallpapers.
 * Status       : Aktif.
 */
final class Wallpapers
{
    private const EXT = ['png', 'webp', 'bmp', 'gif', 'avif', 'jpg', 'jpeg'];

    private const MAX_WIDTH = 2560;

    private const MAX_BYTES = 1_500_000;

    private const MAX_PIXELS = 40_000_000;  // shortcut: batas memori GD (256 MB), naikkan memory_limit bila perlu foto lebih besar

    public static function dir(): string
    {
        return public_path('media/bg/sstock');
    }

    public static function backupDir(): string
    {
        return storage_path('app/wallpaper-source');
    }

    /** @return int jumlah gambar yang diproses */
    public static function sync(?string $dir = null, ?string $backup = null): int
    {
        $dir ??= self::dir();
        $backup ??= self::backupDir();
        $done = 0;

        foreach (glob($dir.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (! is_file($file) || ! in_array($ext, self::EXT, true) || str_ends_with(strtolower($file), '-portrait.'.$ext) || time() - filemtime($file) < 3) {
                continue;  // bukan gambar, varian potret milik DevLib, atau file masih disalin
            }
            if (in_array($ext, ['jpg', 'jpeg'], true) && self::ready($file)) {
                continue;
            }
            try {
                self::convert($file, $ext, $dir, $backup);
                $done++;
            } catch (\Throwable $e) {
                Log::warning("Wallpaper {$file} dilewati: ".$e->getMessage());
            }
        }

        return $done;
    }

    private static function ready(string $file): bool
    {
        $size = @getimagesize($file);

        return $size && $size[0] <= self::MAX_WIDTH && filesize($file) <= self::MAX_BYTES;
    }

    private static function convert(string $file, string $ext, string $dir, string $backup): void
    {
        $info = @getimagesize($file);
        if (! $info || $info[0] * $info[1] > self::MAX_PIXELS) {
            throw new \RuntimeException('bukan gambar yang bisa dibaca, atau terlalu besar');
        }
        [$w, $h] = $info;
        $src = @imagecreatefromstring((string) file_get_contents($file));
        if (! $src) {
            throw new \RuntimeException('format tidak didukung');
        }

        // Rasio di luar 1,4 - 2,4 (mis. potret) dipotong tengah ke 16:9; selebihnya dipertahankan.
        [$sx, $sy, $sw, $sh] = [0, 0, $w, $h];
        $ratio = $w / $h;
        if ($ratio < 1.4 || $ratio > 2.4) {
            if ($ratio < 16 / 9) {
                $sh = (int) round($w * 9 / 16);
                $sy = (int) (($h - $sh) / 2);
            } else {
                $sw = (int) round($h * 16 / 9);
                $sx = (int) (($w - $sw) / 2);
            }
        }
        $tw = min($sw, self::MAX_WIDTH);
        $th = (int) round($sh * $tw / $sw);

        $dst = imagecreatetruecolor($tw, $th);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));  // PNG / GIF transparan jadi putih
        imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $tw, $th, $sw, $sh);
        imageinterlace($dst, true);

        $isJpg = in_array($ext, ['jpg', 'jpeg'], true);
        $name = $isJpg ? basename($file) : (Str::slug(pathinfo($file, PATHINFO_FILENAME)) ?: 'wallpaper').'.jpg';
        $target = $dir.DIRECTORY_SEPARATOR.$name;
        for ($i = 2; ! $isJpg && file_exists($target); $i++) {
            $target = $dir.DIRECTORY_SEPARATOR.pathinfo($name, PATHINFO_FILENAME)."-{$i}.jpg";
        }

        // Simpan dulu ke berkas sementara, baru asli dipindah ke cadangan, lalu hasil menggantikannya.
        $tmp = $dir.DIRECTORY_SEPARATOR.'.'.Str::random(8).'.tmp';
        if (! imagejpeg($dst, $tmp, 88)) {
            @unlink($tmp);
            throw new \RuntimeException('gagal menulis JPG');
        }
        @mkdir($backup, 0775, true);
        $keep = $backup.DIRECTORY_SEPARATOR.basename($file);
        if (file_exists($keep)) {
            $keep = $backup.DIRECTORY_SEPARATOR.date('YmdHis').'-'.basename($file);
        }
        rename($file, $keep);
        rename($tmp, $target);
    }
}
