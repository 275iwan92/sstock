<?php

namespace App\Console\Commands;

use App\Support\Wallpapers;
use Illuminate\Console\Command;

/* REMARK
 * Tujuan       : Menjalankan sinkronisasi wallpaper login secara manual (PNG / WebP / gambar besar di folder wallpaper diubah ke JPG siap pakai). Halaman login juga menjalankannya otomatis.
 * Output       : jumlah gambar yang diproses.
 * Dipakai oleh : php artisan sstock:wallpapers.
 * Status       : Aktif.
 */
class WallpapersCommand extends Command
{
    protected $signature = 'sstock:wallpapers';

    protected $description = 'Ubah gambar baru di folder wallpaper login menjadi JPG siap pakai';

    public function handle(): int
    {
        $this->info(Wallpapers::sync().' gambar diproses di '.Wallpapers::dir());

        return self::SUCCESS;
    }
}
