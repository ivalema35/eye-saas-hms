<?php

/**
 * IdentifyTenant.php
 *
 * PURPOSE: Multi-tenant routing middleware.
 *          URL path ke pehle segment se hospital slug padhta hai
 *          aur usse database me dhundh kar tenant set karta hai.
 *
 * ROUTING STRATEGY: PATH-BASED (subdomain nahi)
 *   Web URL:  hmssaas.com/{slug}/patients
 *   API URL:  hmssaas.com/api/v1/{slug}/patients
 *   Slug extraction: $request->route('slug') — works for both prefixes
 *
 * IMPORTANT: Ye middleware har hospital route pe apply hoga.
 *            Agar tenant nahi mila to 404 return karo.
 *
 * ACCESSIBLE BY: All hospital routes automatically
 * APPLIED IN: routes/hospital.php (Route::prefix('{slug}'))
 */

namespace App\Http\Middleware;

use App\Models\Platform\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    /**
     * Ye slugs kisi bhi hospital ko nahi mil sakti.
     * In par koi tenant nahi hoga — ye platform ke apne routes hain.
     */
    private array $reservedSlugs = [
        'superadmin', 'admin', 'register', 'login',
        'pricing', 'api', 'health', 'webhook',
        'static', 'assets', 'storage',
    ];

    /**
     * handle() — URL se slug nikalkar tenant ko identify karta hai.
     *
     * Flow:
     * 1. URL ka pehla segment lo: request()->segment(1) → 'aakasheye'
     * 2. Reserved slugs check karo
     * 3. Database me tenant dhundo
     * 4. App container me bind karo: app()->instance('tenant', $tenant)
     * 5. Config me tenant_id set karo for global scope
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Route parameter se slug lo — ye web aur API dono ke liye kaam karta hai.
        // Web:  /{slug}/patients          → route param 'slug'
        // API:  /api/v1/{slug}/patients   → route param 'slug'
        // Fallback: segment(1) sirf tab use hoga jab route bind nahi hua ho.
        $slug = $request->route('slug') ?? $request->segment(1);

        // Agar slug khaali hai ya reserved hai to 404
        if (! $slug || in_array(strtolower($slug), $this->reservedSlugs)) {
            abort(404, 'Hospital not found.');
        }

        // Database me tenant dhundo — status check nahi, sirf existence
        $tenant = Tenant::where('slug', $slug)->first();

        // Tenant nahi mila to 404
        if (! $tenant) {
            abort(404, 'Hospital portal not found. Please check the URL.');
        }

        // Tenant ko app container me bind karo
        // Baad me controllers me: $tenant = app('tenant')
        app()->instance('tenant', $tenant);

        // Global config me tenant_id set karo
        // BelongsToTenant trait is config ko use karega
        Config::set('app.tenant_id', $tenant->id);

        // Request me bhi set karo (convenience ke liye)
        $request->merge(['_tenant' => $tenant]);

        return $next($request);
    }
}
