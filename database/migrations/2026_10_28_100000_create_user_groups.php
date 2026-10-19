<?php

use App\Support\Modules;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/* REMARK
 * Tujuan       : Grup pengguna dan akses Grup terhadap Modul (menu). Tiga grup bawaan (Data Entry, Supervisor, Administrator) mengikuti peran, lalu pengguna yang sudah ada dimasukkan ke grup sesuai perannya.
 * Output       : user_groups, user_group_modules, users.group_id.
 * Dipakai oleh : GroupController, UserController, User::canAccess, Menu.
 * Status       : Aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_groups', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->timestamps();
        });
        Schema::create('user_group_modules', function (Blueprint $table) {
            $table->foreignId('group_id')->constrained('user_groups')->cascadeOnDelete();
            $table->string('module', 20);
            $table->primary(['group_id', 'module']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('group_id')->nullable()->after('role')->constrained('user_groups')->nullOnDelete();
        });

        foreach (['data_entry' => ['DATAENTRY', 'Data Entry', 'entry'], 'supervisor' => ['SUPERVISOR', 'Supervisor', 'supervisor'], 'administrator' => ['ADMIN', 'Administrator', 'administrator']] as $role => [$code, $name, $level]) {
            $id = DB::table('user_groups')->insertGetId(['code' => $code, 'name' => $name, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('user_group_modules')->insert(array_map(fn ($m) => ['group_id' => $id, 'module' => $m], $level === 'administrator' ? array_keys(Modules::all()) : Modules::defaults($level)));
            DB::table('users')->where('role', $role)->update(['group_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_id');
        });
        Schema::dropIfExists('user_group_modules');
        Schema::dropIfExists('user_groups');
    }
};
