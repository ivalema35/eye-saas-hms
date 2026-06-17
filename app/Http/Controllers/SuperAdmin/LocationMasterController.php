<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Imports\LocationMasterImport;
use App\Models\Platform\MasterCity;
use App\Models\Platform\MasterCountry;
use App\Models\Platform\MasterDistrict;
use App\Models\Platform\MasterState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class LocationMasterController extends Controller
{
    // ═══════════════════════════════════════════════════
    // INDEX — 4 tabs on one page
    // ═══════════════════════════════════════════════════

    public function index(): View
    {
        $tab = request('tab', 'countries');

        // $countries = MasterCountry::orderBy('name')->get();
        $countries = MasterCountry::withCount('states')
            ->orderBy('name')
            ->paginate(10)
            ->appends(request()->query());
        $states = collect();
        $districts = collect();
        $cities = collect();

        $filterCountryId = request()->integer('country_id') ?: null;
        $filterStateId = request()->integer('state_id') ?: null;
        $filterDistrictId = request()->integer('district_id') ?: null;
        $search = request('search', '');

        if ($tab === 'states') {
            $q = MasterState::with('country');
            if ($filterCountryId)
                $q->where('country_id', $filterCountryId);
            if ($search)
                $q->where('name', 'like', "%{$search}%");
            $states = $q->orderBy('name')->paginate(10)->appends(request()->query());
        }

        if ($tab === 'districts') {
            $q = MasterDistrict::with('state.country');
            if ($filterStateId)
                $q->where('state_id', $filterStateId);
            if ($search)
                $q->where('name', 'like', "%{$search}%");
            $districts = $q->orderBy('name')->paginate(10)->appends(request()->query());
        }

        if ($tab === 'cities') {
            $q = MasterCity::with('state.country', 'district');
            if ($filterStateId)
                $q->where('state_id', $filterStateId);
            if ($filterDistrictId)
                $q->where('district_id', $filterDistrictId);
            if ($search)
                $q->where('name', 'like', "%{$search}%");
            $cities = $q->orderBy('name')->paginate(10)->appends(request()->query());
        }

        // For state/district/city tabs — pre-load dependent selects
        $statesForFilter = $filterCountryId
            ? MasterState::where('country_id', $filterCountryId)->orderBy('name')->get()
            : collect();
        $districtsForFilter = $filterStateId
            ? MasterDistrict::where('state_id', $filterStateId)->orderBy('name')->get()
            : collect();

        return view('superadmin.locations.index', compact(
            'tab',
            'countries',
            'states',
            'districts',
            'cities',
            'filterCountryId',
            'filterStateId',
            'filterDistrictId',
            'search',
            'statesForFilter',
            'districtsForFilter'
        ));
    }

    // ═══════════════════════════════════════════════════
    // COUNTRY CRUD
    // ═══════════════════════════════════════════════════

    public function storeCountry(Request $request): RedirectResponse
    {
        $request->validate(['name' => 'required|string|max:100']);
        $name = MasterCountry::normalize($request->name);

        if (MasterCountry::whereRaw('LOWER(name) = ?', [strtolower($name)])->exists()) {
            return back()->with('error', "Country \"{$name}\" already exists.")->withInput();
        }

        MasterCountry::create(['name' => $name, 'is_active' => true]);

        return back()->with('success', "Country \"{$name}\" added.")->with('open_tab', 'countries');
    }

    public function updateCountry(Request $request, MasterCountry $country): RedirectResponse
    {
        $request->validate(['name' => 'required|string|max:100']);
        $name = MasterCountry::normalize($request->name);

        $dup = MasterCountry::whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->where('id', '!=', $country->id)->exists();

        if ($dup) {
            return back()->with('error', "Country \"{$name}\" already exists.")->withInput();
        }

        $country->update(['name' => $name]);

        return back()->with('success', 'Country updated.')->with('open_tab', 'countries');
    }

    public function destroyCountry(MasterCountry $country): RedirectResponse
    {
        $country->delete();
        return back()->with('success', 'Country deleted.')->with('open_tab', 'countries');
    }

    public function toggleCountry(MasterCountry $country): JsonResponse
    {
        $country->update(['is_active' => !$country->is_active]);
        return response()->json(['is_active' => $country->is_active]);
    }

    // ═══════════════════════════════════════════════════
    // STATE CRUD
    // ═══════════════════════════════════════════════════

    public function storeState(Request $request): RedirectResponse
    {
        $request->validate([
            'country_id' => 'required|integer|exists:tbl_master_countries,id',
            'name' => 'required|string|max:150',
        ]);
        $name = MasterState::normalize($request->name);

        $dup = MasterState::where('country_id', $request->country_id)
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])->exists();

        if ($dup) {
            return back()->with('error', "State \"{$name}\" already exists for this country.")->withInput();
        }

        MasterState::create(['country_id' => $request->country_id, 'name' => $name, 'is_active' => true]);

        return back()->with('success', "State \"{$name}\" added.")->with('open_tab', 'states');
    }

    public function updateState(Request $request, MasterState $state): RedirectResponse
    {
        $request->validate([
            'country_id' => 'required|integer|exists:tbl_master_countries,id',
            'name' => 'required|string|max:150',
        ]);
        $name = MasterState::normalize($request->name);

        $dup = MasterState::where('country_id', $request->country_id)
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->where('id', '!=', $state->id)->exists();

        if ($dup) {
            return back()->with('error', "State \"{$name}\" already exists.")->withInput();
        }

        $state->update(['country_id' => $request->country_id, 'name' => $name]);

        return back()->with('success', 'State updated.')->with('open_tab', 'states');
    }

    public function destroyState(MasterState $state): RedirectResponse
    {
        $state->delete();
        return back()->with('success', 'State deleted.')->with('open_tab', 'states');
    }

    public function toggleState(MasterState $state): JsonResponse
    {
        $state->update(['is_active' => !$state->is_active]);
        return response()->json(['is_active' => $state->is_active]);
    }

    // ═══════════════════════════════════════════════════
    // DISTRICT CRUD
    // ═══════════════════════════════════════════════════

    public function storeDistrict(Request $request): RedirectResponse
    {
        $request->validate([
            'state_id' => 'required|integer|exists:tbl_master_states,id',
            'name' => 'required|string|max:150',
        ]);
        $name = MasterDistrict::normalize($request->name);

        $dup = MasterDistrict::where('state_id', $request->state_id)
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])->exists();

        if ($dup) {
            return back()->with('error', "District \"{$name}\" already exists for this state.")->withInput();
        }

        MasterDistrict::create(['state_id' => $request->state_id, 'name' => $name, 'is_active' => true]);

        return back()->with('success', "District \"{$name}\" added.")->with('open_tab', 'districts');
    }

    public function updateDistrict(Request $request, MasterDistrict $district): RedirectResponse
    {
        $request->validate([
            'state_id' => 'required|integer|exists:tbl_master_states,id',
            'name' => 'required|string|max:150',
        ]);
        $name = MasterDistrict::normalize($request->name);

        $dup = MasterDistrict::where('state_id', $request->state_id)
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->where('id', '!=', $district->id)->exists();

        if ($dup) {
            return back()->with('error', "District \"{$name}\" already exists.")->withInput();
        }

        $district->update(['state_id' => $request->state_id, 'name' => $name]);

        return back()->with('success', 'District updated.')->with('open_tab', 'districts');
    }

    public function destroyDistrict(MasterDistrict $district): RedirectResponse
    {
        $district->delete();
        return back()->with('success', 'District deleted.')->with('open_tab', 'districts');
    }

    public function toggleDistrict(MasterDistrict $district): JsonResponse
    {
        $district->update(['is_active' => !$district->is_active]);
        return response()->json(['is_active' => $district->is_active]);
    }

    // ═══════════════════════════════════════════════════
    // CITY CRUD
    // ═══════════════════════════════════════════════════

    public function storeCity(Request $request): RedirectResponse
    {
        $request->validate([
            'state_id' => 'required|integer|exists:tbl_master_states,id',
            'district_id' => 'nullable|integer|exists:tbl_master_districts,id',
            'name' => 'required|string|max:150',
        ]);
        $name = MasterCity::normalize($request->name);
        $districtId = $request->district_id ?: null;

        $dup = MasterCity::where('state_id', $request->state_id)
            ->where('district_id', $districtId)
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])->exists();

        if ($dup) {
            return back()->with('error', "City \"{$name}\" already exists.")->withInput();
        }

        MasterCity::create([
            'state_id' => $request->state_id,
            'district_id' => $districtId,
            'name' => $name,
            'is_active' => true,
        ]);

        return back()->with('success', "City \"{$name}\" added.")->with('open_tab', 'cities');
    }

    public function updateCity(Request $request, MasterCity $city): RedirectResponse
    {
        $request->validate([
            'state_id' => 'required|integer|exists:tbl_master_states,id',
            'district_id' => 'nullable|integer|exists:tbl_master_districts,id',
            'name' => 'required|string|max:150',
        ]);
        $name = MasterCity::normalize($request->name);
        $districtId = $request->district_id ?: null;

        $dup = MasterCity::where('state_id', $request->state_id)
            ->where('district_id', $districtId)
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->where('id', '!=', $city->id)->exists();

        if ($dup) {
            return back()->with('error', "City \"{$name}\" already exists.")->withInput();
        }

        $city->update(['state_id' => $request->state_id, 'district_id' => $districtId, 'name' => $name]);

        return back()->with('success', 'City updated.')->with('open_tab', 'cities');
    }

    public function destroyCity(MasterCity $city): RedirectResponse
    {
        $city->delete();
        return back()->with('success', 'City deleted.')->with('open_tab', 'cities');
    }

    public function toggleCity(MasterCity $city): JsonResponse
    {
        $city->update(['is_active' => !$city->is_active]);
        return response()->json(['is_active' => $city->is_active]);
    }

    // ═══════════════════════════════════════════════════
    // EXCEL IMPORT
    // ═══════════════════════════════════════════════════

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $import = new LocationMasterImport();
        Excel::import($import, $request->file('file'));

        return back()->with('success', "Import done — {$import->imported} added, {$import->skipped} skipped.");
    }

    // ═══════════════════════════════════════════════════
    // AJAX — cascade dropdowns
    // ═══════════════════════════════════════════════════

    public function ajaxStates(Request $request): JsonResponse
    {
        $states = MasterState::where('country_id', $request->country_id)
            ->active()->orderBy('name')->get(['id', 'name']);
        return response()->json($states);
    }

    public function ajaxDistricts(Request $request): JsonResponse
    {
        $districts = MasterDistrict::where('state_id', $request->state_id)
            ->active()->orderBy('name')->get(['id', 'name']);
        return response()->json($districts);
    }
}
