<?php

namespace App\Http\Controllers\Hospital\Setting;

use App\Http\Controllers\Controller;
use App\Models\Platform\Tenant;
use App\Services\Platform\TimezoneService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimezoneController extends Controller
{
    public function __construct(private readonly TimezoneService $timezoneService) {}

    public function index(): View
    {
        $tenant = app('tenant');

        return view('hospital.settings.timezone', [
            'tenant'          => $tenant,
            'groupedTimezones' => TimezoneService::groupedTimezones(),
            'current'         => $tenant->timezone ?? 'UTC',
            'isOverride'      => (bool) $tenant->is_timezone_override,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'timezone' => ['required', 'string', 'timezone'],
        ]);

        $tenant = Tenant::findOrFail((int) config('app.tenant_id'));

        $this->timezoneService->setOverride($tenant, $request->timezone);

        return redirect()
            ->route('hospital.settings.timezone', ['slug' => $request->route('slug')])
            ->with('success', 'Timezone updated successfully.');
    }

    public function reset(Request $request): RedirectResponse
    {
        $tenant = Tenant::findOrFail((int) config('app.tenant_id'));

        $this->timezoneService->resetToCountryDefault($tenant);

        return redirect()
            ->route('hospital.settings.timezone', ['slug' => $request->route('slug')])
            ->with('success', 'Timezone reset to country default.');
    }
}
