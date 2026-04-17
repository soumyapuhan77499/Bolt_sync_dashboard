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
    $request->validate([
        'email' => 'required|email',
        'password' => 'required|string',
    ], [
        'email.required' => 'Email is required',
        'email.email' => 'Enter a valid email address',
        'password.required' => 'Password is required',
    ]);

    $admin = AdminUser::where('email', $request->email)
        ->where('status', 'active')
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
        'admin_id' => $admin->id,
        'admin_name' => $admin->name,
        'admin_user_id' => $admin->user_id,
        'admin_email' => $admin->email,
    ]);

    return redirect()
        ->route('admin.dashboard')
        ->with('success', 'Login successful');
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