<?php

namespace App\Http\Middleware;

use App\Support\EmailRules;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lowercases known email input fields before validation/controllers run.
 * Login fields named "email" that contain a phone number are unchanged
 * (digits have no case).
 */
class NormalizeEmailInput
{
    public function handle(Request $request, Closure $next): Response
    {
        $normalized = [];

        foreach (EmailRules::INPUT_KEYS as $key) {
            if (! $request->has($key)) {
                continue;
            }

            $value = $request->input($key);

            if (! is_string($value) || $value === '') {
                continue;
            }

            $normalized[$key] = EmailRules::normalize($value);
        }

        if ($normalized !== []) {
            $request->merge($normalized);
        }

        return $next($request);
    }
}
