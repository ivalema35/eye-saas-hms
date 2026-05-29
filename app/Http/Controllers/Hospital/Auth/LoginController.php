<?php

namespace App\Http\Controllers\Hospital\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Hospital\HospitalUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Hospital Login Controller
 *
 * Breeze-powered multi-guard login:
 *   hospital_admin -> doctor -> reception -> ot_staff
 *
 * URL: hmssaas.com/{slug}/login
 */
class LoginController extends Controller
{
    /** Unified hospital auth guard */
    private string $guard = 'hospital_user';

    public function show(): View
    {
        $slug = request()->route('slug');
        $tenant = app('tenant');

        return view('hospital.auth.login', [
            'slug' => $slug,
            'hospitalName' => $tenant?->name ?? 'Hospital',
            'hospitalLogo' => $tenant?->logo_path ?? null,
        ]);
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        $slug = $request->route('slug');
        $tenant = app('tenant');
        $login = trim((string) $request->input('email'));
        $normalizedContact = preg_replace('/\D+/', '', $login) ?? '';
        $password = $request->input('password');
        $remember = $request->boolean('remember');

        $user = HospitalUser::query()
            ->where('tenant_id', $tenant->id)
            ->where(function ($query) use ($login, $normalizedContact) {
                $query->where('email', $login)
                    ->orWhere('contact', $login);

                if ($normalizedContact !== '' && $normalizedContact !== $login) {
                    $query->orWhere('contact', $normalizedContact);
                }
            })
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->first();

        if ($user && Hash::check($password, $user->password)) {
            Auth::guard($this->guard)->login($user, $remember);
            $request->clearRateLimiter();
            $request->session()->regenerate();

            return redirect()->route('hospital.dashboard', ['slug' => $slug]);
        }

        $request->hitRateLimiter();

        return back()
            ->withErrors(['email' => __('auth.failed')])
            ->withInput(['email' => $login]);
    }

    public function logout(Request $request): RedirectResponse
    {
        $slug = $request->route('slug');

        if (Auth::guard($this->guard)->check()) {
            Auth::guard($this->guard)->logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('hospital.login', ['slug' => $slug])
            ->with('success', 'You have been logged out successfully.');
    }
}
