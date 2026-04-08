<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Hospital\HospitalUser;
use App\Models\Platform\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * UnifiedLoginController
 *
 * Single /login page for ALL hospital users.
 * User enters email + password — system finds their hospital automatically.
 *
 * Flow:
 *   1. Search email across hospital_admins, doctors, receptions, ot_staff
 *   2. Verify password
 *   3. Check tenant status (must be active/trial/grace)
 *   4. Log in via the correct guard
 *   5. Redirect to /{slug}/dashboard
 */
class UnifiedLoginController extends Controller
{
    private string $guard = 'hospital_user';

    public function show(): View
    {
        return view('landing.auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        $email    = $request->input('email');
        $password = $request->input('password');
        $remember = $request->boolean('remember');

        $user = HospitalUser::withoutTenantScope()
            ->where('email', $email)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->first();

        if ($user && Hash::check($password, $user->password)) {
            $tenant = Tenant::find($user->tenant_id);

            if (! $tenant || ! in_array($tenant->status, ['active', 'trial', 'grace'])) {
                $request->hitRateLimiter();

                return back()
                    ->withErrors(['email' => 'Your hospital subscription is inactive. Please contact support.'])
                    ->withInput(['email' => $email]);
            }

            Auth::guard($this->guard)->login($user, $remember);
            $request->clearRateLimiter();
            $request->session()->regenerate();

            return redirect()->route('hospital.dashboard', ['slug' => $tenant->slug]);
        }

        $request->hitRateLimiter();

        return back()
            ->withErrors(['email' => __('auth.failed')])
            ->withInput(['email' => $email]);
    }
}