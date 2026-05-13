<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $role = (string) $request->query('role', 'all');
        $status = (string) $request->query('status', 'all');

        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $needle = mb_strtolower($search);

                $query->where(function ($inner) use ($needle) {
                    $inner
                        ->whereRaw('LOWER(name) LIKE ?', ['%'.$needle.'%'])
                        ->orWhereRaw('LOWER(email) LIKE ?', ['%'.$needle.'%']);
                });
            })
            ->when($role !== 'all', fn ($query) => $query->where('role', $role))
            ->when($status !== 'all', fn ($query) => $query->where('is_active', $status === 'active'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search,
            'role' => $role,
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['super_admin', 'admin'])],
            'is_active' => ['nullable', 'boolean'],
            'discord_webhook_url' => ['nullable', 'url', 'max:2048', 'starts_with:https://discord.com/api/webhooks/'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'is_active' => $request->boolean('is_active', true),
            'discord_webhook_url' => $data['discord_webhook_url'] ?? null,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User admin berhasil dibuat.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'managedUser' => $user,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', Rule::in(['super_admin', 'admin'])],
            'is_active' => ['nullable', 'boolean'],
            'discord_webhook_url' => ['nullable', 'url', 'max:2048', 'starts_with:https://discord.com/api/webhooks/'],
        ]);

        $newRole = (string) $data['role'];
        $newIsActive = $request->boolean('is_active');
        $authUser = $request->user();

        if ((int) $authUser->id === (int) $user->id && ! $newIsActive) {
            return back()
                ->withInput($request->except('password'))
                ->with('error', 'Anda tidak bisa menonaktifkan akun sendiri.');
        }

        if ((int) $authUser->id === (int) $user->id && $newRole !== 'super_admin') {
            return back()
                ->withInput($request->except('password'))
                ->with('error', 'Anda tidak bisa menghapus role super admin dari akun sendiri.');
        }

        if ($this->wouldRemoveLastActiveSuperAdmin($user, $newRole, $newIsActive)) {
            return back()
                ->withInput($request->except('password'))
                ->with('error', 'Minimal harus ada satu super admin aktif.');
        }

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $newRole,
            'is_active' => $newIsActive,
            'discord_webhook_url' => $data['discord_webhook_url'] ?? null,
        ];

        if (filled($data['password'] ?? null)) {
            $payload['password'] = $data['password'];
        }

        $user->update($payload);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User admin berhasil diperbarui.');
    }

    public function destroy(User $user, Request $request): RedirectResponse
    {
        $authUser = $request->user();

        if ((int) $authUser->id === (int) $user->id) {
            return back()->with('error', 'Anda tidak bisa menghapus akun sendiri.');
        }

        if ($this->wouldRemoveLastActiveSuperAdmin($user, 'admin', false)) {
            return back()->with('error', 'Minimal harus ada satu super admin aktif.');
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User admin berhasil dihapus.');
    }

    private function wouldRemoveLastActiveSuperAdmin(User $user, string $newRole, bool $newIsActive): bool
    {
        $isCurrentlyActiveSuperAdmin = $user->role === 'super_admin' && (bool) $user->is_active;
        $remainsActiveSuperAdmin = $newRole === 'super_admin' && $newIsActive === true;

        if (! $isCurrentlyActiveSuperAdmin || $remainsActiveSuperAdmin) {
            return false;
        }

        return User::query()
            ->where('role', 'super_admin')
            ->where('is_active', true)
            ->whereKeyNot($user->id)
            ->count() === 0;
    }
}
