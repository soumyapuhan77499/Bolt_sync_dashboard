<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session()->get('admin_logged_in')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Schema::hasTable('admin_users')) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'admin_users table not found. Please run migrations first.');
        }

        $admin = DB::table('admin_users')
            ->where('email', $validated['email'])
            ->first();

        if (! $admin) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Invalid email or password.');
        }

        if (isset($admin->status) && $admin->status !== 'active') {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Your account is inactive.');
        }

        if (! Hash::check($validated['password'], $admin->password)) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Invalid email or password.');
        }

        try {
            DB::table('admin_users')
                ->where('id', $admin->id)
                ->update([
                    'last_login_at' => now(),
                    'updated_at' => now(),
                ]);
        } catch (\Throwable $e) {
            // Ignore if columns do not exist yet
        }

        session([
            'admin_logged_in' => true,
            'admin_user_id' => $admin->id,
            'admin_name' => $admin->name ?? 'Admin',
            'admin_email' => $admin->email,
            'admin_role' => $admin->role ?? 'admin',
        ]);

        return redirect()
            ->route('admin.dashboard')
            ->with('success', 'Login successful.');
    }

    public function logout(Request $request)
    {
        $request->session()->forget([
            'admin_logged_in',
            'admin_user_id',
            'admin_name',
            'admin_email',
            'admin_role',
        ]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('admin.login')
            ->with('success', 'Logged out successfully.');
    }
}