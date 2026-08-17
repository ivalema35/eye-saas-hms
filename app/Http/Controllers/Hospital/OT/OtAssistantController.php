<?php

namespace App\Http\Controllers\Hospital\OT;

use App\Http\Controllers\Controller;
use App\Models\Hospital\Medicine;
use App\Models\Hospital\MedicineGroup;
use App\Models\Hospital\OT\LensInventory;
use App\Models\Hospital\OT\OtBooking;
use App\Models\Hospital\OT\OtLensDetail;
use App\Models\Hospital\OT\OtLensPower;
use App\Models\Hospital\OT\OtSurgery;
use App\Models\Hospital\OT\OtSurgeryMedicine;
use App\Models\Hospital\OT\OtVerification;
use App\Services\Hospital\LensInventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OtAssistantController extends Controller
{
    /**
     * Fixed lens-type taxonomy — PDF §10. Kept as a hardcoded list (not an
     * admin-configurable master) since it's a closed clinical vocabulary, not
     * hospital-specific data. See docs/OT_WORKFLOW_UPGRADE_PRD.md §4.
     */
    public const LENS_TYPES = [
        'Accommodating', 'Aspheric', 'EDOF', 'Monofocal', 'Multifocal', 'Spherical', 'Toric', 'Trifocal',
    ];

    public function __construct(private LensInventoryService $lensInventoryService)
    {
    }

    public function dashboard(string $slug): View
    {
        $user = auth('hospital_user')->user();
        $assistantId = (int) $user->id;
        $seeAll = $user->isSuperUser() || ($user->role?->slug === 'hospital_admin');

        // Ready-for-surgery queue — absorbed from the old ot_doctor role (docs/tulsi.md §5).
        $readyQuery = OtBooking::query()
            ->with([
                'patient:id,first_name,middle_name,last_name,contact_no',
                'otDoctor:id,name',
                'otAssistant:id,name',
                'payments',
            ])
            ->where('ot_status', OtBooking::STATUS_READY);

        // Assigned OT Assistant sees only their queue; Hospital Admin sees all ready patients.
        if (! $seeAll) {
            $readyQuery->where('ot_assistant_id', $assistantId);
        }

        $readyBookings = $readyQuery
            ->orderBy('surgery_date')
            ->orderByDesc('id')
            ->get();

        // Lens workflow UI hidden — counselling already captures planned lens;
        // routes/controllers kept for optional direct access / future use.
        return view('hospital.ot.assistant.dashboard', [
            'slug' => $slug,
            'readyBookings' => $readyBookings,
            'seeAll' => $seeAll,
        ]);
    }

    public function createSurgery(string $slug, int $bookingId): View
    {
        $tenantId = (int) app('tenant')->id;
        $user = auth('hospital_user')->user();
        $assistantId = (int) $user->id;
        $seeAll = $user->isSuperUser() || ($user->role?->slug === 'hospital_admin');

        $bookingQuery = OtBooking::query()
            ->with(['patient:id,first_name,middle_name,last_name,contact_no']);

        if (! $seeAll) {
            $bookingQuery->where('ot_assistant_id', $assistantId);
        }

        $booking = $bookingQuery->findOrFail($bookingId);

        $counselling = DB::table('ot_counselling')
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->first();

        $verification = OtVerification::query()
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->first();

        $surgeryTypes = DB::table('ot_surgery_types')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->orderBy('surgery_name')
            ->get(['id', 'surgery_name']);

        $medicines = Medicine::query()
            ->orderBy('name')
            ->get(['name']);

        // OT Workflow Upgrade — Phase 4: OT-scoped medicine groups quick-fill
        // ward medicines (docs/OT_WORKFLOW_UPGRADE_PRD.md §4).
        $medicineGroups = MedicineGroup::with('items.medicine')
            ->where('tenant_id', $tenantId)
            ->whereIn('usage_scope', ['ot', 'both'])
            ->orderBy('name')
            ->get();

        return view('hospital.ot.assistant.surgery', [
            'slug' => $slug,
            'booking' => $booking,
            'counselling' => $counselling,
            'verification' => $verification,
            'surgeryTypes' => $surgeryTypes,
            'medicines' => $medicines,
            'medicineGroups' => $medicineGroups,
            'lensTypes' => self::LENS_TYPES,
        ]);
    }

    public function storeSurgery(Request $request, string $slug, int $bookingId): RedirectResponse
    {
        $tenantId = (int) app('tenant')->id;
        $user = auth('hospital_user')->user();
        $assistantId = (int) $user->id;
        $seeAll = $user->isSuperUser() || ($user->role?->slug === 'hospital_admin');

        $bookingQuery = OtBooking::query();
        if (! $seeAll) {
            $bookingQuery->where('ot_assistant_id', $assistantId);
        }
        $booking = $bookingQuery->findOrFail($bookingId);

        abort_unless((int) $booking->tenant_id === $tenantId, 403, 'Unauthorized booking access.');

        if ($booking->ot_status !== OtBooking::STATUS_READY) {
            return redirect()
                ->route('hospital.ot.assistant.dashboard', ['slug' => $slug])
                ->with('error', 'Surgery can only be recorded when the patient status is Ready for OT.');
        }

        // operated_by = assigned surgeon when present; else the recording user.
        $operatedBy = $booking->ot_doctor_id
            ? (int) $booking->ot_doctor_id
            : $assistantId;

        // Eye locked to Recommend Surgery selection (booking.eye).
        $lockedEye = in_array((string) $booking->eye, ['RE', 'LE', 'Both'], true)
            ? (string) $booking->eye
            : null;

        $validated = $request->validate([
            'surgery_date' => ['required', 'date'],
            'surgery_name' => ['required', 'string', 'max:255'],
            'ot_room' => ['nullable', 'string', 'max:100'],
            'eye_operated' => array_values(array_filter([
                'required',
                $lockedEye
                    ? Rule::in([$lockedEye])
                    : Rule::in(['RE', 'LE', 'Both']),
            ])),
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date', 'after:start_time'],
            'complication_status' => ['required', Rule::in(['none', 'minor', 'major'])],
            'complication_notes' => ['nullable', 'string', 'max:4000'],
            'blood_loss' => ['nullable', 'string', 'max:100'],
            'medicine_group_id' => [
                'nullable', 'integer',
                Rule::exists('medicine_groups', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)->whereIn('usage_scope', ['ot', 'both'])),
            ],
            'ot_medicines' => ['nullable', 'array'],
            'ot_medicines.*.medicine' => [
                'nullable',
                Rule::exists('medicines', 'name')->where(fn ($query) => $query
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at')),
            ],
            'ot_medicines.*.dose' => ['nullable', 'string', 'max:255'],
            // Lens info — autofilled from counselling; assistant may confirm/edit.
            'lens_category' => ['nullable', Rule::in(['standard', 'premium'])],
            'lens_company' => ['nullable', 'string', 'max:150'],
            'lens_model' => ['nullable', 'string', 'max:150'],
            'lens_type' => ['nullable', Rule::in(self::LENS_TYPES)],
            'estimated_power' => ['nullable', 'numeric', 'between:-99.99,999.99'],
            'lens_cost' => ['nullable', 'numeric', 'min:0'],
        ], [
            'eye_operated.in' => 'Eye operated must match the eye selected at Recommend Surgery'
                .($lockedEye ? " ({$lockedEye})." : '.'),
        ]);

        // Assistant already assigned at Recommend Surgery (or current user for admin fill).
        $surgeryAssistantId = (int) ($booking->ot_assistant_id ?: $assistantId);

        $otMedicines = collect($validated['ot_medicines'] ?? [])
            ->filter(fn (array $item): bool => ! empty($item['medicine']) || ! empty($item['dose']))
            ->values()
            ->all();

        // Fallback: if the surgeon picked a medicine group but didn't fill any rows
        // manually (e.g. JS quick-fill didn't run), populate from the group directly.
        if ($otMedicines === [] && ! empty($validated['medicine_group_id'])) {
            $group = MedicineGroup::with('items.medicine')->find($validated['medicine_group_id']);
            $otMedicines = $group
                ? $group->items->map(fn ($item) => [
                    'medicine' => $item->medicine?->name,
                    'dose' => trim(($item->frequency ?? '').' '.($item->duration ?? '')),
                ])->filter(fn ($item) => ! empty($item['medicine']))->values()->all()
                : [];
        }

        DB::transaction(function () use ($validated, $otMedicines, $operatedBy, $assistantId, $surgeryAssistantId, $tenantId, $booking): void {
            // Keep counselling lens plan in sync (autofill source for billing/prints).
            $existingCounselling = DB::table('ot_counselling')
                ->where('tenant_id', $tenantId)
                ->where('ot_booking_id', $booking->id)
                ->first();

            $lensPayload = [
                'lens_category' => $validated['lens_category'] ?? null,
                'lens_company' => $validated['lens_company'] ?? null,
                'lens_model' => $validated['lens_model'] ?? null,
                'lens_type' => $validated['lens_type'] ?? null,
                'estimated_power' => $validated['estimated_power'] ?? null,
                'lens_cost' => $validated['lens_cost'] ?? null,
                'updated_at' => now(),
            ];

            if ($existingCounselling) {
                $ot = (float) ($existingCounselling->ot_charges ?? 0);
                $sur = (float) ($existingCounselling->surgeon_charges ?? 0);
                $nurs = (float) ($existingCounselling->nursing_charges ?? 0);
                $cons = (float) ($existingCounselling->consumables_charges ?? 0);
                $lens = (float) ($validated['lens_cost'] ?? $existingCounselling->lens_cost ?? 0);
                $total = round($ot + $sur + $nurs + $cons + $lens, 2);
                if ($total > 0) {
                    $lensPayload['total_estimate'] = $total;
                    $lensPayload['package_amount'] = $total;
                }
                DB::table('ot_counselling')
                    ->where('id', $existingCounselling->id)
                    ->update($lensPayload);
            } else {
                DB::table('ot_counselling')->insert(array_merge($lensPayload, [
                    'tenant_id' => $tenantId,
                    'ot_booking_id' => $booking->id,
                    'created_at' => now(),
                ]));
            }

            // Auto-record verification checklist (UI checklist removed).
            OtVerification::query()->updateOrCreate(
                ['tenant_id' => $tenantId, 'ot_booking_id' => $booking->id],
                [
                    'identity_verified' => true,
                    'consent_verified' => true,
                    'payment_verified' => true,
                    'correct_eye_verified' => true,
                    'verified_by' => $assistantId,
                    'verified_at' => now(),
                ]
            );

            $surgery = OtSurgery::query()->create([
                'tenant_id' => $tenantId,
                'ot_booking_id' => $booking->id,
                'operated_by' => $operatedBy,
                'assistant_id' => $surgeryAssistantId,
                'surgery_name' => $validated['surgery_name'],
                'ot_room' => $validated['ot_room'] ?? null,
                'eye_operated' => $validated['eye_operated'],
                'complication_status' => $validated['complication_status'],
                'complication_notes' => $validated['complication_notes'] ?? null,
                'medicine_group_id' => $validated['medicine_group_id'] ?? null,
                'surgery_status' => 'operated',
                'complication' => $validated['complication_notes'] ?? null,
                'blood_loss' => $validated['blood_loss'] ?? null,
                'surgery_at' => now(),
                'start_time' => $validated['start_time'] ?? null,
                'end_time' => $validated['end_time'] ?? null,
            ]);

            foreach ($otMedicines as $item) {
                $name = trim((string) ($item['medicine'] ?? ''));
                if ($name === '') {
                    continue;
                }

                $medicineId = Medicine::query()
                    ->where('tenant_id', $tenantId)
                    ->where(function ($q) use ($name) {
                        $q->where('name', $name)->orWhere('brand_name', $name);
                    })
                    ->value('id');

                OtSurgeryMedicine::query()->create([
                    'tenant_id' => $tenantId,
                    'ot_surgery_id' => $surgery->id,
                    'medicine_id' => $medicineId,
                    'medicine_name' => $name,
                    'quantity' => 1,
                    'dose' => isset($item['dose']) ? trim((string) $item['dose']) : null,
                ]);
            }

            $booking->update([
                'ot_status' => OtBooking::STATUS_OPERATED,
                'surgery_date' => $validated['surgery_date'],
                'operated_at' => now(),
            ]);
        });

        return redirect()
            ->route('hospital.ot.assistant.dashboard', ['slug' => $slug])
            ->with('success', 'Surgery recorded successfully.');
    }

    public function editLens(string $slug, int $bookingId): View
    {
        $tenantId = (int) app('tenant')->id;

        $booking = OtBooking::query()
            ->with(['patient:id,patient_code,first_name,middle_name,last_name,contact_no'])
            ->findOrFail($bookingId);

        $lensDetail = OtLensDetail::query()
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->latest('id')
            ->first();

        $lensPowers = OtLensPower::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderByDesc('is_favourite')
            ->orderBy('power')
            ->get();

        // OT Workflow Upgrade — Phase 7: pick a specific physical stock unit.
        // Keep the already-selected item visible even if stock later hit 0,
        // so re-opening this form for an already-implanted lens doesn't break.
        $lensInventory = LensInventory::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where(function ($query) use ($lensDetail) {
                $query->where('available_stock', '>', 0);
                if ($lensDetail?->lens_inventory_id) {
                    $query->orWhere('id', $lensDetail->lens_inventory_id);
                }
            })
            ->orderBy('lens_name')
            ->get();

        return view('hospital.ot.assistant.lens_form', [
            'slug' => $slug,
            'booking' => $booking,
            'lensDetail' => $lensDetail,
            'lensTypes' => self::LENS_TYPES,
            'lensPowers' => $lensPowers,
            'lensInventory' => $lensInventory,
        ]);
    }

    public function storeLens(Request $request, string $slug, int $bookingId): RedirectResponse
    {
        $tenantId = (int) app('tenant')->id;

        $booking = OtBooking::query()
            ->where('tenant_id', $tenantId)
            ->findOrFail($bookingId);

        abort_unless((int) $booking->tenant_id === $tenantId, 403, 'Unauthorized booking access.');

        // Do not overwrite discharged bookings; only allow lens entry in active OT stages.
        $allowedStatuses = [
            OtBooking::STATUS_READY,
            OtBooking::STATUS_OPERATED,
            OtBooking::STATUS_IN_WARD,
            OtBooking::STATUS_DILATED,
            OtBooking::STATUS_PAYMENT_VERIFIED,
        ];

        if ($booking->ot_status === OtBooking::STATUS_DISCHARGED) {
            return redirect()
                ->route('hospital.ot.assistant.dashboard', ['slug' => $slug])
                ->with('error', 'Cannot update lens details after discharge.');
        }

        if (! in_array($booking->ot_status, $allowedStatuses, true)) {
            return redirect()
                ->route('hospital.ot.assistant.dashboard', ['slug' => $slug])
                ->with('error', 'Lens details can only be saved for patients in ward / ready / operated stages.');
        }

        $validated = $request->validate([
            'lens_inventory_id' => [
                'nullable', 'integer',
                Rule::exists('lens_inventory', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'lens_name' => ['required', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:150'],
            'lens_type' => ['required', Rule::in(self::LENS_TYPES)],
            'lens_power' => ['required', 'numeric', 'between:-99.99,99.99'],
            'axis' => ['nullable', 'numeric', 'between:0,180'],
            'lens_mrp' => ['required', 'numeric', 'min:0'],
            'batch_number' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'expiry_date' => ['nullable', 'date'],
            'implanted' => ['nullable', 'boolean'],
        ]);

        $isImplanted = (bool) ($validated['implanted'] ?? false);
        $existing = OtLensDetail::query()
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->first();

        $wasImplanted = (bool) ($existing?->is_implanted ?? false);
        $lensInventoryId = $validated['lens_inventory_id'] ?? $existing?->lens_inventory_id;

        OtLensDetail::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'ot_booking_id' => $booking->id,
            ],
            [
                'lens_inventory_id' => $lensInventoryId,
                'lens_name' => $validated['lens_name'],
                'manufacturer' => $validated['manufacturer'] ?? null,
                'lens_type' => $validated['lens_type'],
                'lens_power' => $validated['lens_power'],
                'axis' => $validated['axis'] ?? null,
                'lens_mrp' => $validated['lens_mrp'],
                'batch_number' => $validated['batch_number'] ?? null,
                'serial_number' => $validated['serial_number'] ?? null,
                'expiry_date' => $validated['expiry_date'] ?? null,
                'is_implanted' => $isImplanted,
                'implanted_at' => $isImplanted ? ($existing?->implanted_at ?? now()) : null,
                'entered_by' => (int) auth('hospital_user')->id(),
            ]
        );

        // OT Workflow Upgrade — Phase 7: decrement stock exactly once, only on
        // the false -> true implant transition. See docs/OT_WORKFLOW_UPGRADE_PRD.md §7.
        $this->lensInventoryService->handleImplantTransition($lensInventoryId, $wasImplanted, $isImplanted);

        if ($isImplanted) {
            $booking->update([
                'ot_status' => OtBooking::STATUS_OPERATED,
                'operated_at' => $booking->operated_at ?? now(),
            ]);
        }

        return redirect()
            ->route('hospital.ot.assistant.dashboard', ['slug' => $slug])
            ->with('success', 'Lens details saved successfully.');
    }
}
