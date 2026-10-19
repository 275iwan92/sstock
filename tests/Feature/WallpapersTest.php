<?php

namespace Tests\Feature;

use App\Support\Wallpapers;
use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga sinkronisasi wallpaper otomatis: PNG / WebP jadi JPG, gambar besar dikecilkan, potret dipotong 16:9, JPG kecil tidak disentuh, file rusak / bukan gambar diabaikan, file asli dicadangkan.
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class WallpapersTest extends TestCase
{
    private string $dir;

    private string $backup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'wp-'.uniqid();
        $this->backup = $this->dir.'-backup';
        mkdir($this->dir, 0775, true);
    }

    protected function tearDown(): void
    {
        foreach ([$this->dir, $this->backup] as $d) {
            foreach (glob($d.'/{,.}*', GLOB_BRACE) ?: [] as $f) {
                is_file($f) && @unlink($f);
            }
            @rmdir($d);
        }
        parent::tearDown();
    }

    private function image(string $name, int $w, int $h, string $type = 'png'): string
    {
        $im = imagecreatetruecolor($w, $h);
        imagefill($im, 0, 0, imagecolorallocate($im, 30, 90, 160));
        $path = $this->dir.DIRECTORY_SEPARATOR.$name;
        $type === 'png' ? imagepng($im, $path) : ($type === 'webp' ? imagewebp($im, $path) : imagejpeg($im, $path, 85));
        touch($path, time() - 60);   // bukan file yang sedang disalin

        return $path;
    }

    public function test_new_images_become_ready_to_use_jpgs_and_originals_are_backed_up(): void
    {
        $this->image('Foto Besar.png', 3200, 1800);
        $this->image('Kecil Webp.webp', 1600, 900, 'webp');
        $this->image('Potret.png', 900, 1600);
        $ok = $this->image('sudah-siap.jpg', 1920, 1080, 'jpg');
        $mtime = filemtime($ok);
        file_put_contents($this->dir.'/catatan.txt', 'bukan gambar');
        file_put_contents($this->dir.'/rusak.png', 'bukan png sungguhan');
        touch($this->dir.'/rusak.png', time() - 60);

        $this->assertSame(3, Wallpapers::sync($this->dir, $this->backup));

        $this->assertSame([2560, 1440], array_slice(getimagesize($this->dir.'/foto-besar.jpg'), 0, 2));
        $this->assertSame('image/jpeg', getimagesize($this->dir.'/foto-besar.jpg')['mime']);
        $this->assertSame([1600, 900], array_slice(getimagesize($this->dir.'/kecil-webp.jpg'), 0, 2));
        $this->assertSame([900, 506], array_slice(getimagesize($this->dir.'/potret.jpg'), 0, 2));   // dipotong tengah ke 16:9
        $this->assertSame($mtime, filemtime($ok));                                                  // JPG kecil tidak disentuh
        $this->assertFileExists($this->dir.'/catatan.txt');
        $this->assertFileExists($this->dir.'/rusak.png');                                          // gagal dibaca: dibiarkan, tidak melempar
        foreach (['Foto Besar.png', 'Kecil Webp.webp', 'Potret.png'] as $f) {
            $this->assertFileExists($this->backup.'/'.$f);
            $this->assertFileDoesNotExist($this->dir.'/'.$f);
        }
        $this->assertSame(0, Wallpapers::sync($this->dir, $this->backup));                         // kedua kali: tidak ada yang berubah
    }

    public function test_oversized_jpg_is_shrunk_and_name_collisions_get_a_number(): void
    {
        $this->image('lebar.jpg', 3000, 1688, 'jpg');
        $this->image('a b.png', 800, 450);
        $this->image('a-b.jpg', 800, 450, 'jpg');   // nama hasil 'a-b.jpg' sudah dipakai

        Wallpapers::sync($this->dir, $this->backup);

        $this->assertSame(2560, getimagesize($this->dir.'/lebar.jpg')[0]);
        $this->assertFileExists($this->dir.'/a-b-2.jpg');
    }

    public function test_a_file_still_being_copied_is_left_alone(): void
    {
        $im = imagecreatetruecolor(800, 450);
        imagepng($im, $this->dir.'/baru.png');   // mtime = sekarang

        $this->assertSame(0, Wallpapers::sync($this->dir, $this->backup));
        $this->assertFileExists($this->dir.'/baru.png');
    }
}
