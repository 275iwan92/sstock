<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
        $rows = User::query()->orderBy('name')->get()->map(fn (User $u) => [
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
        return view('users.form', ['user' => $user, 'groups' => $this->groups()]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $user = User::create($request->validated() + ['is_active' => true]);

        return redirect()->route('users.show', $user)->with('status', 'Pengguna disimpan.');
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $action = $request->input('_action');
        if (in_array($action, ['activate', 'deactivate'], true)) {
            if ($action === 'deactivate' && $user->is($request->user())) {
                return redirect()->route('users.show', $user)->withErrors(['user' => 'Anda tidak bisa menonaktifkan akun sendiri.']);
            }
            $user->update(['is_active' => $action === 'activate']);

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

    private function groups(): array
    {
        return UserGroup::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    public function toggle(Request $request, User $user): JsonResponse
    {
        abort_if($user->is($request->user()), 422, 'Anda tidak bisa menonaktifkan akun sendiri.');
        $user->update(['is_active' => ! $user->is_active]);

        return response()->json(['active' => $user->is_active]);
    }
}
