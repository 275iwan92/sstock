<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga log aktivitas (CSV bulanan) dan layar super admin: yang dicatat (Buka, Input / Ubah / Hapus, Login, Login gagal, Logout) lengkap dengan user, IP, waktu; ping dan JSON baca tidak dicatat; kata sandi tidak pernah dicatat;
 *                layar Log Aktivitas dan Akun UAT hanya untuk super admin (selain dia 404, termasuk Administrator); super admin bisa mengubah kata sandi akun lain dan tidak tampil di daftar Pengguna.
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir().'/sstock-activity-'.uniqid();
        config(['sstock.activity_dir' => $this->dir, 'sstock.super_admin' => ['username' => 'johiwan', 'hash' => null]]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    private function super(): User
    {
        return User::factory()->administrator()->create(['username' => 'johiwan']);
    }

    public function test_opening_a_page_is_logged_with_user_ip_and_time(): void
    {
        $user = User::factory()->create(['username' => 'penguji']);

        $this->actingAs($user)->get('/')->assertOk();

        $row = ActivityLog::read()[0];
        $this->assertSame('penguji', $row['user']);
        $this->assertSame('127.0.0.1', $row['ip']);
        $this->assertSame('Buka', $row['action']);
        $this->assertStringContainsString('home', $row['detail']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $row['at']);
    }

    public function test_writes_are_logged_as_input_change_and_delete_without_the_body(): void
    {
        Route::middleware('web')->post('uji-baru', fn () => 'x')->name('things.store');
        Route::middleware('web')->put('uji-ubah', fn () => 'x')->name('things.update');
        Route::middleware('web')->delete('uji-hapus', fn () => 'x')->name('things.destroy');
        $this->actingAs(User::factory()->create(['username' => 'penguji']));

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $this->post('uji-baru', ['rahasia' => 'ISI-FORMULIR']);
        $this->put('uji-ubah');
        $this->delete('uji-hapus');

        $actions = array_column(ActivityLog::read(), 'action');
        $this->assertSame(['Hapus', 'Ubah', 'Input'], array_slice($actions, 0, 3));
        $this->assertStringNotContainsString('ISI-FORMULIR', (string) file_get_contents(ActivityLog::file()));
    }

    public function test_login_failed_login_and_logout_are_logged_and_the_password_never_is(): void
    {
        User::factory()->create(['username' => 'penguji', 'password' => 'rahasia-benar']);

        $this->post('/login', ['login' => 'penguji', 'password' => 'salah-sekali']);
        $this->post('/login', ['login' => 'penguji', 'password' => 'rahasia-benar']);
        $this->post('/logout');

        $rows = ActivityLog::read();
        $this->assertSame(['Logout', 'Login', 'Login gagal'], array_slice(array_column($rows, 'action'), 0, 3));
        $this->assertSame('penguji', $rows[2]['user']);
        $this->assertStringNotContainsString('salah-sekali', (string) file_get_contents(ActivityLog::file()));
        $this->assertStringNotContainsString('rahasia-benar', (string) file_get_contents(ActivityLog::file()));
    }

    public function test_session_ping_and_json_reads_are_not_logged_and_guests_are_not_logged(): void
    {
        $this->get('/login');
        $this->actingAs(User::factory()->create())->get(route('session.ping'));
        $this->getJson('/')->status();

        $this->assertSame([], ActivityLog::read());
    }

    public function test_a_spreadsheet_formula_in_a_value_is_defused_and_one_record_stays_on_one_line(): void
    {
        ActivityLog::write("=HYPERLINK(\"x\")\nbaris-baru", '1.2.3.4', 'Login gagal', 'login');

        $this->assertSame(1, count(file(ActivityLog::file())));
        $this->assertStringStartsWith("'=HYPERLINK", ActivityLog::read()[0]['user']);
    }

    public function test_the_log_reads_newest_first_and_only_the_tail_of_a_big_file(): void
    {
        foreach (range(1, 30) as $i) {
            ActivityLog::write('u', '1.1.1.1', 'Buka', "baris-$i");
        }

        $rows = ActivityLog::read(null, 5);

        $this->assertSame(['baris-30', 'baris-29', 'baris-28', 'baris-27', 'baris-26'], array_column($rows, 'detail'));
    }

    public function test_only_the_super_admin_can_open_the_screens_everyone_else_gets_404(): void
    {
        ActivityLog::write('x', '9.9.9.9', 'Buka', 'home /');
        $admin = User::factory()->administrator()->create();
        $super = $this->super();

        foreach (['super.log', 'super.accounts'] as $name) {
            $this->actingAs($admin)->get(route($name))->assertNotFound();
            $this->actingAs(User::factory()->supervisor()->create())->get(route($name))->assertNotFound();
            $this->actingAs($super)->get(route($name))->assertOk();
        }
        $this->actingAs($super)->get(route('super.log'))->assertSee('9.9.9.9')->assertDontSee('data-g-new', false);   // log hanya dibaca: tanpa tombol Data Baru
        $this->actingAs($super)->get(route('super.log.download', ['month' => now()->format('Y-m')]))->assertOk()->assertHeader('content-disposition');
        $this->actingAs($admin)->get(route('super.log.download', ['month' => now()->format('Y-m')]))->assertNotFound();
    }

    public function test_on_the_phone_the_super_screens_open_inside_the_shell_so_the_menu_button_stays(): void
    {
        $super = $this->super();
        $menu = $this->actingAs($super)->withCookie('ui', 'hp')->get('/')->assertOk()->getContent();
        $this->assertStringContainsString(route('hp.open', 'LOG'), $menu);
        $this->assertStringNotContainsString('href="'.route('super.log').'"', $menu, 'tidak lagi halaman mandiri tanpa kerangka');

        $this->actingAs($super)->withCookie('ui', 'hp')->get(route('hp.open', 'LOG'))->assertOk()->assertSee('data-dl-phone-menu', false)->assertSee('<iframe class="dl-phone__frame" src="'.route('super.log').'"', false);
        $this->actingAs($super)->withCookie('ui', 'hp')->get(route('hp.open', 'UAT'))->assertOk()->assertSee(route('super.accounts'), false);
        $this->actingAs(User::factory()->administrator()->create())->get(route('hp.open', 'LOG'))->assertNotFound();
    }

    public function test_the_menu_entries_exist_only_for_the_super_admin(): void
    {
        $this->actingAs(User::factory()->administrator()->create())->get('/')->assertDontSee('Log Aktivitas');
        $this->actingAs($this->super())->get('/')->assertSee('Log Aktivitas')->assertSee('Akun UAT');
    }

    public function test_accounts_screen_shows_the_seeded_defaults_and_hides_the_super_admin(): void
    {
        $this->seed(\Database\Seeders\UserSeeder::class);

        $this->actingAs($this->super())->get(route('super.accounts'))->assertOk()->assertSee('supervisor')->assertSee('1qa2ws3ed')->assertDontSee('johiwan');
    }

    public function test_super_admin_changes_a_password_and_the_old_sessions_of_that_account_end(): void
    {
        $super = $this->super();
        $entry = User::factory()->create(['username' => 'entry', 'password' => 'lama-123']);
        DB::table('sessions')->insert(['id' => 'abc', 'user_id' => $entry->id, 'ip_address' => '1.1.1.1', 'user_agent' => 'x', 'payload' => '', 'last_activity' => time()]);

        $this->actingAs($super)->post(route('super.password', $entry), ['password' => 'baru-456'])->assertRedirect(route('super.accounts'));

        $this->assertTrue(Hash::check('baru-456', $entry->fresh()->password));
        $this->assertSame(0, DB::table('sessions')->where('user_id', $entry->id)->count());
        $this->actingAs($super)->post(route('super.password', $entry), ['password' => 'abc'])->assertSessionHasErrors('password');
        $this->actingAs($super)->post(route('super.password', $super), ['password' => 'baru-456'])->assertNotFound();
        $this->actingAs(User::factory()->administrator()->create())->post(route('super.password', $entry), ['password' => 'baru-456'])->assertNotFound();
    }
}
