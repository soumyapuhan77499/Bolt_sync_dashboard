<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session()->has('admin_logged_in')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ], [
            'login.required' => 'Email or User ID is required',
            'password.required' => 'Password is required',
        ]);

        $loginValue = trim($request->login);

        $admin = AdminUser::where('status', 'active')
            ->where(function ($query) use ($loginValue) {
                $query->where('email', $loginValue)
                      ->orWhere('user_id', $loginValue);
            })
            ->first();

        if (!$admin) {
            return back()
                ->with('error', 'Admin not found or inactive')
                ->withInput();
        }

        if (!Hash::check($request->password, $admin->password)) {
            return back()
                ->with('error', 'Invalid password')
                ->withInput();
        }

        $request->session()->regenerate();

        session([
            'admin_logged_in'    => true,
            'admin_id'           => $admin->id,
            'admin_name'         => $admin->name,
            'admin_user_id'      => $admin->user_id,
            'admin_email'        => $admin->email,
            'admin_is_super_admin' => (bool) $admin->is_super_admin,
        ]);

        return redirect()
            ->route('admin.dashboard')
            ->with('success', 'Login successful');
    }

    public function logout(Request $request)
    {
        $request->session()->forget([
            'admin_logged_in',
            'admin_id',
            'admin_name',
            'admin_user_id',
            'admin_email',
            'admin_is_super_admin',
        ]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('admin.login')
            ->with('success', 'Logged out successfully.');
    }
}