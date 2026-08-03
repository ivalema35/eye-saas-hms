<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Platform\MasterCity;
use App\Models\Platform\MasterCountry;
use App\Models\Platform\MasterDistrict;
use App\Models\Platform\MasterState;
use App\Models\Platform\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlatformLocationApiController extends Controller
{
    // ── Dropdown data for filter selects ─────────────────────────────────────

    public function dropdownData(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'countries' => MasterCountry::orderBy('name')->get(['id', 'name', 'country_code', 'default_timezone', 'currency_code', 'currency_symbol', 'currency_name', 'fx_inr_per_unit']),
                'states'    => MasterState::with('country:id,name')->orderBy('name')->get(['id', 'country_id', 'name']),
                'districts' => MasterDistrict::with('state:id,name')->orderBy('name')->get(['id', 'state_id', 'name']),
            ],
        ]);
    }

    // ── Countries ─────────────────────────────────────────────────────────────

    public function countries(Request $request): JsonResponse
    {
        $q = MasterCountry::withCount('states');
        if ($s = $request->search) $q->where('name', 'like', "%{$s}%");
        $page = $q->orderBy('name')->paginate(25);

        return response()->json(['success' => true, 'data' => [
            'items'     => $page->items(),
            'total'     => $page->total(),
            'last_page' => $page->lastPage(),
        ]]);
    }

    public function storeCountry(Request $request): JsonResponse
    {
        $request->validate([
            'name'             => ['required', 'string', 'max:100'],
            'country_code'     => ['required', 'string', 'size:2'],
            'default_timezone' => ['required', 'string', 'timezone'],
            'currency_code'    => ['required', 'string', 'size:3'],
            'currency_symbol'  => ['required', 'string', 'max:10'],
            'currency_name'    => ['nullable', 'string', 'max:50'],
            'fx_inr_per_unit'  => ['required', 'numeric', 'min:0.0001', 'max:999999'],
        ]);
        $name = MasterCountry::normalize($request->name);
        $countryCode = strtoupper($request->country_code);

        if (MasterCountry::whereRaw('LOWER(name) = ?', [strtolower($name)])->exists()) {
            return response()->json(['success' => false, 'message' => "Country \"{$name}\" already exists."], 422);
        }

        if (MasterCountry::whereRaw('UPPER(country_code) = ?', [$countryCode])->exists()) {
            return response()->json(['success' => false, 'message' => "Country code \"{$countryCode}\" already exists."], 422);
        }

        $code = strtoupper($request->currency_code);
        $preset = \App\Services\Platform\CurrencyService::commonCurrencies()[$code] ?? null;

        $item = MasterCountry::create([
            'name' => $name,
            'country_code' => $countryCode,
            'default_timezone' => $request->default_timezone,
            'currency_code' => $code,
            'currency_symbol' => $request->currency_symbol ?: ($preset['symbol'] ?? '₹'),
            'currency_name' => $request->currency_name ?: ($preset['name'] ?? null),
            'fx_inr_per_unit' => (float) $request->fx_inr_per_unit,
            'is_active' => true,
        ]);
        return response()->json(['success' => true, 'message' => "Country \"{$name}\" added.", 'data' => $item]);
    }

    public function updateCountry(Request $request, int $id): JsonResponse
    {
        $country = MasterCountry::findOrFail($id);
        $request->validate([
            'name'             => ['required', 'string', 'max:100'],
            'country_code'     => ['required', 'string', 'size:2'],
            'default_timezone' => ['required', 'string', 'timezone'],
            'currency_code'    => ['required', 'string', 'size:3'],
            'currency_symbol'  => ['required', 'string', 'max:10'],
            'currency_name'    => ['nullable', 'string', 'max:50'],
            'fx_inr_per_unit'  => ['required', 'numeric', 'min:0.0001', 'max:999999'],
        ]);
        $name = MasterCountry::normalize($request->name);
        $countryCode = strtoupper($request->country_code);

        if (MasterCountry::whereRaw('LOWER(name) = ?', [strtolower($name)])->where('id', '!=', $id)->exists()) {
            return response()->json(['success' => false, 'message' => "Country \"{$name}\" already exists."], 422);
        }

        if (MasterCountry::whereRaw('UPPER(country_code) = ?', [$countryCode])->where('id', '!=', $id)->exists()) {
            return response()->json(['success' => false, 'message' => "Country code \"{$countryCode}\" already exists."], 422);
        }

        $code = strtoupper($request->currency_code);
        $preset = \App\Services\Platform\CurrencyService::commonCurrencies()[$code] ?? null;
        $symbol = $request->currency_symbol ?: ($preset['symbol'] ?? $country->currency_symbol);

        $country->update([
            'name' => $name,
            'country_code' => $countryCode,
            'default_timezone' => $request->default_timezone,
            'currency_code' => $code,
            'currency_symbol' => $symbol,
            'currency_name' => $request->currency_name ?: ($preset['name'] ?? $country->currency_name),
            'fx_inr_per_unit' => (float) $request->fx_inr_per_unit,
        ]);

        // Cascade timezone to non-overridden tenants
        Tenant::where('country', $name)->where('is_timezone_override', false)
            ->update(['timezone' => $request->default_timezone]);

        Tenant::where('country', $name)->where('is_currency_override', false)
            ->update([
                'currency_code' => $code,
                'currency_symbol' => $symbol,
            ]);

        return response()->json(['success' => true, 'message' => 'Country updated.', 'data' => $country->fresh()]);
    }

    public function destroyCountry(int $id): JsonResponse
    {
        MasterCountry::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Country deleted.']);
    }

    public function toggleCountry(int $id): JsonResponse
    {
        $item = MasterCountry::findOrFail($id);
        $item->update(['is_active' => !$item->is_active]);
        return response()->json(['success' => true, 'is_active' => $item->is_active]);
    }

    // ── States ────────────────────────────────────────────────────────────────

    public function states(Request $request): JsonResponse
    {
        $q = MasterState::with('country:id,name');
        if ($c = $request->country_id) $q->where('country_id', $c);
        if ($s = $request->search)     $q->where('name', 'like', "%{$s}%");
        $page = $q->orderBy('name')->paginate(25);

        return response()->json(['success' => true, 'data' => [
            'items'     => $page->map(fn ($r) => [
                'id' => $r->id, 'name' => $r->name, 'country_id' => $r->country_id,
                'country_name' => $r->country?->name, 'is_active' => $r->is_active,
            ]),
            'total'     => $page->total(),
            'last_page' => $page->lastPage(),
        ]]);
    }

    public function storeState(Request $request): JsonResponse
    {
        $request->validate(['country_id' => 'required|exists:tbl_master_countries,id', 'name' => 'required|string|max:150']);
        $name = MasterState::normalize($request->name);

        if (MasterState::where('country_id', $request->country_id)->whereRaw('LOWER(name) = ?', [strtolower($name)])->exists()) {
            return response()->json(['success' => false, 'message' => "State \"{$name}\" already exists."], 422);
        }

        $item = MasterState::create(['country_id' => $request->country_id, 'name' => $name, 'is_active' => true]);
        return response()->json(['success' => true, 'message' => "State \"{$name}\" added.", 'data' => $item]);
    }

    public function updateState(Request $request, int $id): JsonResponse
    {
        $state = MasterState::findOrFail($id);
        $request->validate(['country_id' => 'required|exists:tbl_master_countries,id', 'name' => 'required|string|max:150']);
        $name = MasterState::normalize($request->name);

        if (MasterState::where('country_id', $request->country_id)->whereRaw('LOWER(name) = ?', [strtolower($name)])->where('id', '!=', $id)->exists()) {
            return response()->json(['success' => false, 'message' => "State \"{$name}\" already exists."], 422);
        }

        $state->update(['country_id' => $request->country_id, 'name' => $name]);
        return response()->json(['success' => true, 'message' => 'State updated.', 'data' => $state->fresh()]);
    }

    public function destroyState(int $id): JsonResponse
    {
        MasterState::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'State deleted.']);
    }

    public function toggleState(int $id): JsonResponse
    {
        $item = MasterState::findOrFail($id);
        $item->update(['is_active' => !$item->is_active]);
        return response()->json(['success' => true, 'is_active' => $item->is_active]);
    }

    // ── Districts ─────────────────────────────────────────────────────────────

    public function districts(Request $request): JsonResponse
    {
        $q = MasterDistrict::with('state:id,name');
        if ($s = $request->state_id) $q->where('state_id', $s);
        if ($sr = $request->search)  $q->where('name', 'like', "%{$sr}%");
        $page = $q->orderBy('name')->paginate(25);

        return response()->json(['success' => true, 'data' => [
            'items'     => $page->map(fn ($r) => [
                'id' => $r->id, 'name' => $r->name, 'state_id' => $r->state_id,
                'state_name' => $r->state?->name, 'is_active' => $r->is_active,
            ]),
            'total'     => $page->total(),
            'last_page' => $page->lastPage(),
        ]]);
    }

    public function storeDistrict(Request $request): JsonResponse
    {
        $request->validate(['state_id' => 'required|exists:tbl_master_states,id', 'name' => 'required|string|max:150']);
        $name = MasterDistrict::normalize($request->name);

        if (MasterDistrict::where('state_id', $request->state_id)->whereRaw('LOWER(name) = ?', [strtolower($name)])->exists()) {
            return response()->json(['success' => false, 'message' => "District \"{$name}\" already exists."], 422);
        }

        $item = MasterDistrict::create(['state_id' => $request->state_id, 'name' => $name, 'is_active' => true]);
        return response()->json(['success' => true, 'message' => "District \"{$name}\" added.", 'data' => $item]);
    }

    public function updateDistrict(Request $request, int $id): JsonResponse
    {
        $district = MasterDistrict::findOrFail($id);
        $request->validate(['state_id' => 'required|exists:tbl_master_states,id', 'name' => 'required|string|max:150']);
        $name = MasterDistrict::normalize($request->name);

        if (MasterDistrict::where('state_id', $request->state_id)->whereRaw('LOWER(name) = ?', [strtolower($name)])->where('id', '!=', $id)->exists()) {
            return response()->json(['success' => false, 'message' => "District \"{$name}\" already exists."], 422);
        }

        $district->update(['state_id' => $request->state_id, 'name' => $name]);
        return response()->json(['success' => true, 'message' => 'District updated.', 'data' => $district->fresh()]);
    }

    public function destroyDistrict(int $id): JsonResponse
    {
        MasterDistrict::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'District deleted.']);
    }

    public function toggleDistrict(int $id): JsonResponse
    {
        $item = MasterDistrict::findOrFail($id);
        $item->update(['is_active' => !$item->is_active]);
        return response()->json(['success' => true, 'is_active' => $item->is_active]);
    }

    // ── Cities ────────────────────────────────────────────────────────────────

    public function cities(Request $request): JsonResponse
    {
        $q = MasterCity::with('state:id,name', 'district:id,name');
        if ($s  = $request->state_id)    $q->where('state_id', $s);
        if ($d  = $request->district_id) $q->where('district_id', $d);
        if ($sr = $request->search)      $q->where('name', 'like', "%{$sr}%");
        $page = $q->orderBy('name')->paginate(25);

        return response()->json(['success' => true, 'data' => [
            'items'     => $page->map(fn ($r) => [
                'id' => $r->id, 'name' => $r->name, 'state_id' => $r->state_id,
                'district_id' => $r->district_id,
                'state_name'    => $r->state?->name,
                'district_name' => $r->district?->name,
                'is_active'     => $r->is_active,
            ]),
            'total'     => $page->total(),
            'last_page' => $page->lastPage(),
        ]]);
    }

    public function storeCity(Request $request): JsonResponse
    {
        $request->validate([
            'state_id'    => 'required|exists:tbl_master_states,id',
            'district_id' => 'nullable|exists:tbl_master_districts,id',
            'name'        => 'required|string|max:150',
        ]);
        $name       = MasterCity::normalize($request->name);
        $districtId = $request->district_id ?: null;

        if (MasterCity::where('state_id', $request->state_id)->where('district_id', $districtId)->whereRaw('LOWER(name) = ?', [strtolower($name)])->exists()) {
            return response()->json(['success' => false, 'message' => "City \"{$name}\" already exists."], 422);
        }

        $item = MasterCity::create(['state_id' => $request->state_id, 'district_id' => $districtId, 'name' => $name, 'is_active' => true]);
        return response()->json(['success' => true, 'message' => "City \"{$name}\" added.", 'data' => $item]);
    }

    public function updateCity(Request $request, int $id): JsonResponse
    {
        $city = MasterCity::findOrFail($id);
        $request->validate([
            'state_id'    => 'required|exists:tbl_master_states,id',
            'district_id' => 'nullable|exists:tbl_master_districts,id',
            'name'        => 'required|string|max:150',
        ]);
        $name       = MasterCity::normalize($request->name);
        $districtId = $request->district_id ?: null;

        if (MasterCity::where('state_id', $request->state_id)->where('district_id', $districtId)->whereRaw('LOWER(name) = ?', [strtolower($name)])->where('id', '!=', $id)->exists()) {
            return response()->json(['success' => false, 'message' => "City \"{$name}\" already exists."], 422);
        }

        $city->update(['state_id' => $request->state_id, 'district_id' => $districtId, 'name' => $name]);
        return response()->json(['success' => true, 'message' => 'City updated.', 'data' => $city->fresh()]);
    }

    public function destroyCity(int $id): JsonResponse
    {
        MasterCity::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'City deleted.']);
    }

    public function toggleCity(int $id): JsonResponse
    {
        $item = MasterCity::findOrFail($id);
        $item->update(['is_active' => !$item->is_active]);
        return response()->json(['success' => true, 'is_active' => $item->is_active]);
    }
}
