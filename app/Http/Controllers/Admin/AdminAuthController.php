<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::withTrashed()
            ->where('email', $credentials['email'])
            ->first();

        if ($user && ($user->trashed() || ! $user->is_active)) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Akun tidak aktif.');
        }

        if (! Auth::attempt($credentials)) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Login gagal.');
        }

        $request->session()->regenerate();

        $user = Auth::user();
        if (($user?->is_active ?? true) === false) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Akun tidak aktif.');
        }

        return redirect()->to('/admin/dashboard');
    }
}
