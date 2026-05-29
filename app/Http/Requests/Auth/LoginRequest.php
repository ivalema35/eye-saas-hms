<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * LoginRequest
 *
 * Validates login credentials for all guards (hospital + superadmin).
 * Includes rate limiting: 5 failed attempts per email+slug+IP per minute.
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Unique throttle key: email + slug (or 'superadmin') + IP.
     * Hospital login: scoped per tenant to prevent cross-tenant lockout.
     */
    public function throttleKey(): string
    {
        $slug = $this->route('slug') ?? 'superadmin';

        return Str::transliterate(
            Str::lower($this->string('email')).'|'.$slug.'|'.$this->ip()
        );
    }

    /**
     * Throw a ValidationException if the request is rate limited.
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Increment the rate limiter on a failed attempt.
     */
    public function hitRateLimiter(): void
    {
        RateLimiter::hit($this->throttleKey());
    }

    /**
     * Clear the rate limiter on a successful login.
     */
    public function clearRateLimiter(): void
    {
        RateLimiter::clear($this->throttleKey());
    }
}
