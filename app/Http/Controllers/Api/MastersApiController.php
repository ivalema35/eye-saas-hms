<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\Referrer;
use App\Models\Hospital\Slot;
use App\Models\Platform\MasterCity;
use App\Models\Platform\MasterCountry;
use App\Models\Platform\MasterDistrict;
use App\Models\Platform\MasterState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MastersApiController extends Controller
{
    public function cases(): JsonResponse
    {
        $cases = DB::table('tbl_cases')
            ->where('tenant_id', app('tenant')->id)
            ->whereNull('deleted_at')
            ->select('id', 'case_type as name', 'case_fee as fee')
            ->orderBy('case_type')
            ->get();

        return response()->json(['success' => true, 'data' => $cases]);
    }

    public function doctors(): JsonResponse
    {
        $tenantId = app('tenant')->id;

        $doctors = HospitalUser::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNotNull('doctor_type')
                    ->orWhereHas('role', function ($r) {
                        $r->where(function ($inner) {
                            $inner->whereIn('slug', ['doctor', 'ot_assistant'])
                                ->orWhereIn('name', ['doctor', 'ot_assistant']);
                        });
                    });
            })
            ->select('id', 'name', 'doctor_type')
            ->orderBy('name')
            ->get();

        return response()->json(['success' => true, 'data' => $doctors]);
    }

    public function locations(): JsonResponse
    {
        $hospitalCountry = app('tenant')->country;

        $locations = MasterCity::with(['district', 'state'])
            ->whereHas('state.country', fn ($q) => $q->where('name', $hospitalCountry))
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'id'       => $c->id,
                'city'     => $c->name,
                'district' => $c->district?->name ?? '',
                'state'    => $c->state?->name ?? '',
            ]);

        return response()->json(['success' => true, 'data' => $locations]);
    }

    public function storeLocation(Request $request): JsonResponse
    {
        $request->validate([
            'city'     => ['required', 'string', 'max:150'],
            'state'    => ['required', 'string', 'max:150'],
            'district' => ['nullable', 'string', 'max:150'],
        ]);

        $tenant  = app('tenant');
        $country = MasterCountry::whereRaw('LOWER(name) = ?', [strtolower($tenant->country)])->first();

        if (! $country) {
            return response()->json([
                'success' => false,
                'message' => 'Hospital country is not configured in the global master.',
            ], 422);
        }

        $stateName = MasterState::normalize($request->state);
        $state = MasterState::where('country_id', $country->id)
            ->whereRaw('LOWER(name) = ?', [strtolower($stateName)])
            ->first()
            ?? MasterState::create(['country_id' => $country->id, 'name' => $stateName, 'is_active' => true]);

        $district = null;
        if ($request->filled('district')) {
            $districtName = MasterDistrict::normalize($request->district);
            $district = MasterDistrict::where('state_id', $state->id)
                ->whereRaw('LOWER(name) = ?', [strtolower($districtName)])
                ->first()
                ?? MasterDistrict::create(['state_id' => $state->id, 'name' => $districtName, 'is_active' => true]);
        }

        $cityName = MasterCity::normalize($request->city);
        $city = MasterCity::where('state_id', $state->id)
            ->where('district_id', $district?->id)
            ->whereRaw('LOWER(name) = ?', [strtolower($cityName)])
            ->first();

        if (! $city) {
            $city = MasterCity::create([
                'state_id'    => $state->id,
                'district_id' => $district?->id,
                'name'        => $cityName,
                'is_active'   => true,
            ]);
        }

        return response()->json([
            'success' => true,
            'id'      => $city->id,
            'data'    => [
                'id'       => $city->id,
                'city'     => $city->name,
                'district' => $district?->name ?? '',
                'state'    => $state->name,
            ],
            'message' => 'City added successfully.',
        ], 201);
    }

    /**
     * Mirrors Hospital\Master\BasicMasterController::updateLocation()
     * exactly — same find-or-create state/district, same duplicate-city
     * check, same fields updated. Web already lets a Hospital Admin (or
     * anyone with `master.locations`) edit locations; the app previously
     * had no route for this at all. See OT_BUGS_ROUND4_FIX_PLAN.md
     * "Locations master read-only" follow-up.
     */
    public function updateLocation(string $slug, Request $request, int $id): JsonResponse
    {
        $request->validate([
            'city'     => ['required', 'string', 'max:150'],
            'state'    => ['required', 'string', 'max:150'],
            'district' => ['nullable', 'string', 'max:150'],
        ]);

        $masterCity = MasterCity::with('state.country')->findOrFail($id);
        $country = $masterCity->state->country;

        $stateName = MasterState::normalize($request->state);
        $state = MasterState::where('country_id', $country->id)
            ->whereRaw('LOWER(name) = ?', [strtolower($stateName)])
            ->first()
            ?? MasterState::create(['country_id' => $country->id, 'name' => $stateName, 'is_active' => true]);

        $district = null;
        if ($request->filled('district')) {
            $districtName = MasterDistrict::normalize($request->district);
            $district = MasterDistrict::where('state_id', $state->id)
                ->whereRaw('LOWER(name) = ?', [strtolower($districtName)])
                ->first()
                ?? MasterDistrict::create(['state_id' => $state->id, 'name' => $districtName, 'is_active' => true]);
        }

        $cityName = MasterCity::normalize($request->city);
        $dup = MasterCity::where('state_id', $state->id)
            ->where('district_id', $district?->id)
            ->whereRaw('LOWER(name) = ?', [strtolower($cityName)])
            ->where('id', '!=', $id)
            ->exists();

        if ($dup) {
            return response()->json([
                'success' => false,
                'message' => "City \"{$cityName}\" already exists in this location.",
            ], 422);
        }

        $masterCity->update([
            'state_id'    => $state->id,
            'district_id' => $district?->id,
            'name'        => $cityName,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully.',
            'data' => [
                'id'       => $masterCity->id,
                'city'     => $masterCity->name,
                'district' => $district?->name ?? '',
                'state'    => $state->name,
            ],
        ]);
    }

    /**
     * Mirrors Hospital\Master\BasicMasterController::destroy() for the
     * 'locations' type exactly — a bare delete with no reference check.
     * Same behavior/risk as web (MasterCity is a shared platform table,
     * not tenant-scoped) — not introduced by this endpoint, not fixed
     * here either, since that would be a unilateral behavior change
     * beyond matching web.
     */
    public function destroyLocation(string $slug, int $id): JsonResponse
    {
        $city = MasterCity::findOrFail($id);
        $city->delete();

        return response()->json(['success' => true, 'message' => 'Location deleted successfully.']);
    }

    public function slots(): JsonResponse
    {
        $tenantId = app('tenant')->id;

        $slots = Slot::where('tenant_id', $tenantId)
            ->select('id', 'slot_name as name', 'start_time', 'end_time')
            ->orderBy('slot_name')
            ->get();

        return response()->json(['success' => true, 'data' => $slots]);
    }

    public function referrers(): JsonResponse
    {
        $tenantId = app('tenant')->id;

        $referrers = Referrer::where('tenant_id', $tenantId)
            ->select('id', 'name', 'contact')
            ->orderBy('name')
            ->get();

        return response()->json(['success' => true, 'data' => $referrers]);
    }
}
