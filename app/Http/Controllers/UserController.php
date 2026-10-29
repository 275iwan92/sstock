<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/* REMARK
 * Tujuan       : Menu Keamanan > Pengguna: daftar, tambah, ubah (nama, e-mail, peran, password baru), aktif / nonaktif. Hanya Administrator.
 *                Pengaman: Administrator tidak bisa menonaktifkan atau mengubah peran dirinya sendiri (supaya sistem tidak kehilangan Administrator).
 * Sumber hulu  : users.
 * Output       : grid Pengguna, form Pengguna.
 * Dipakai oleh : routes/web.php (users.*, middleware administrator).
 * Status       : Aktif.
 */
class UserController extends Controller
{
    public function index()
    {
        $rows = User::visible()->with('group:id,name')->orderBy('name')->get()->map(fn (User $u) => [
            'id' => $u->id, 'name' => $u->name, 'username' => $u->username, 'email' => $u->email, 'role' => $u->roleLabel(), 'group' => $u->group?->name ?? '—', 'active' => $u->is_active,
        ])->all();

        return view('users.index', ['rows' => $rows]);
    }

    public function create()
    {
        return view('users.form', ['user' => new User(['role' => User::ROLE_DATA_ENTRY]), 'groups' => $this->groups()]);
    }

    public function show(User $user)
    {
        abort_if($user->isSuperAdmin(), 404);
        return view('users.form', ['user' => $user, 'groups' => $this->groups($user->group_id)]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $user = User::create($request->validated() + ['is_active' => true]);

        return redirect()->route('users.show', $user)->with('status', 'Pengguna disimpan.');
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        abort_if($user->isSuperAdmin(), 404);
        $action = $request->input('_action');
        if (in_array($action, ['activate', 'deactivate'], true)) {
            if ($action === 'deactivate') {
                if ($error = $this->deactivate($request, $user)) {
                    return redirect()->route('users.show', $user)->withErrors(['user' => $error]);
                }
            } else {
                $user->update(['is_active' => true]);
            }

            return redirect()->route('users.show', $user)->with('status', $action === 'activate' ? 'Pengguna diaktifkan.' : 'Pengguna dinonaktifkan.');
        }

        $data = $request->validated();
        if ($user->is($request->user()) && $data['role'] !== $user->role) {
            return redirect()->route('users.show', $user)->withInput()->withErrors(['role' => 'Anda tidak bisa mengubah peran akun sendiri.']);
        }
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $user->update($data);

        return redirect()->route('users.show', $user)->with('status', 'Pengguna disimpan.');
    }

    // Grup nonaktif tidak ditawarkan, kecuali grup yang kini dipakai pengguna itu (supaya tidak terhapus diam-diam saat disimpan).
    private function groups(?int $current = null): array
    {
        return UserGroup::query()->where(fn ($q) => $q->where('is_active', true)->orWhere('id', $current))->orderBy('name')->pluck('name', 'id')->all();
    }

    public function toggle(Request $request, User $user): JsonResponse
    {
        abort_if($user->isSuperAdmin(), 404);
        abort_if($user->is($request->user()), 422, 'Anda tidak bisa menonaktifkan akun sendiri.');
        if ($user->is_active) {
            abort_if($error = $this->deactivate($request, $user), 422, (string) $error);
        } else {
            $user->update(['is_active' => true]);
        }

        return response()->json(['active' => $user->is_active]);
    }

    // Menonaktifkan = sesi dan "ingat saya" ikut dicabut supaya akun langsung keluar. Mengembalikan pesan galat, atau null bila berhasil.
    private function deactivate(Request $request, User $user): ?string
    {
        if ($user->is($request->user())) {
            return 'Anda tidak bisa menonaktifkan akun sendiri.';
        }
        $otherAdmins = User::visible()->where('role', User::ROLE_ADMINISTRATOR)->where('is_active', true)->whereKeyNot($user->id);
        if ($user->isAdministrator() && ! $otherAdmins->exists()) {
            return 'Administrator aktif terakhir tidak bisa dinonaktifkan.';
        }
        $user->forceFill(['is_active' => false, 'remember_token' => null])->save();
        DB::table('sessions')->where('user_id', $user->id)->delete();

        return null;
    }
}
