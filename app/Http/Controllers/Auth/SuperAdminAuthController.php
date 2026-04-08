<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * SuperAdmin Authentication Controller
 *
 * Handles login/logout for Platform Admins.
 * Guard: superadmin
 * Route: /superadmin/login
 */
class SuperAdminAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('superadmin.auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        if (Auth::guard('superadmin')->attempt(
            $request->only('email', 'password'),
            $request->boolean('remember')
        )) {
            $request->clearRateLimiter();
            $request->session()->regenerate();

            return redirect()->route('superadmin.dashboard');
        }

        $request->hitRateLimiter();

        return back()
            ->withErrors(['email' => __('auth.failed')])
            ->withInput($request->only('email'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('superadmin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('superadmin.login');
    }
}
