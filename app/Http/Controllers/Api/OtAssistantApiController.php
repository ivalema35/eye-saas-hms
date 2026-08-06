<?php

/**
 * OtAssistantApiController.php
 *
 * PURPOSE: Mobile/tablet API mirror of Hospital\OT\OtAssistantController (web).
 *          OT Workflow Upgrade — Phase 4/5 (OT Assistant module: surgery recording +
 *          pre-surgery verification checklist + lens details). See
 *          docs/OT_WORKFLOW_UPGRADE_PRD.md §4 and docs/ROUND3_OT_MOBILE_API_PRD_PLAN.md §8
 *          (FR-OT-29..32).
 *
 * PERMISSIONS: ot.lens.record|ot.lens.implant|ot.surgery.ready|ot.surgery.record|ot.patient.list
 *              (OR-group for the bookings queue, matches web), ot.surgery.record (surgery
 *              form/store), ot.lens.record + ot.lens.implant (lens form/store).
 */

namespace App\Http\Controllers\Api;

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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OtAssistantApiController extends Controller
{
    /** Fixed lens-type taxonomy — PDF §10, mirrors OtAssistantController::LENS_TYPES exactly. */
    public const LENS_TYPES = [
        'Accommodating', 'Aspheric', 'EDOF', 'Monofocal', 'Multifocal', 'Spherical', 'Toric', 'Trifocal',
    ];

    public function __construct(private LensInventoryService $lensInventoryService)
    {
    }

    /**
     * NOTE (deviation from the plan doc): the current web dashboard() only returns
     * the ready-for-surgery queue — the "ready-for-lens" queue mentioned in the plan
     * doc (based on the shipped MOBILE_API_PRD's older wording) was removed on the
     * web side ("Lens workflow UI hidden — counselling already captures planned
     * lens" per OtAssistantController's own comment). Mirroring actual current
     * behavior, not the stale PRD wording.
     */
    public function bookings(Request $request): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;
        $user = auth('sanctum')->user();
        $assistantId = (int) $user->id;
        $seeAll = $user->isSuperUser() || ($user->role?->slug === 'hospital_admin');

        $query = OtBooking::query()
            ->where('tenant_id', $tenantId)
            ->with([
                'patient:id,first_name,middle_name,last_name,contact_no',
                'otDoctor:id,name',
                'otAssistant:id,name',
                'payments',
            ])
            ->where('ot_status', OtBooking::STATUS_READY);

        if (! $seeAll) {
            $query->where('ot_assistant_id', $assistantId);
        }

        $bookings = $query
            ->orderBy('surgery_date')
            ->orderByDesc('id')
            ->paginate((int) $request->integer('per_page', 25));

        return response()->json(['success' => true, 'data' => $bookings]);
    }

    /**
     * Form data for the surgery-recording screen — not in the original PRD skeleton,
     * added because the mobile app needs surgery-type/medicine dropdown options
     * (mirrors what web's createSurgery() passes to the blade view).
     */
    public function surgeryFormData(string $slug, int $bookingId): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;
        $booking = $this->findAssistantBooking($bookingId);

        if (! $booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

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

        $medicines = Medicine::query()->orderBy('name')->get(['name']);

        $medicineGroups = MedicineGroup::with('items.medicine')
            ->where('tenant_id', $tenantId)
            ->whereIn('usage_scope', ['ot', 'both'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'booking' => $booking,
                'counselling' => $counselling,
                'verification' => $verification,
                'surgery_types' => $surgeryTypes,
                'medicines' => $medicines,
                'medicine_groups' => $medicineGroups,
            ],
        ]);
    }

    /**
     * NOTE (deviation from the plan doc): there is no separate backend "verify"
     * action — the pre-surgery checklist (identity/consent/payment/correct-eye)
     * is validated and written inside this same storeSurgery() call, atomically
     * with the surgery record, exactly like the web form. A standalone
     * `POST .../verify` endpoint (plan doc FR-OT-29) would have no real backend
     * action behind it, so it was not built — the checklist fields are part of
     * this request body instead, matching actual web behavior.
     */
    public function storeSurgery(string $slug, Request $request, int $bookingId): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;
        $user = auth('sanctum')->user();
        $assistantId = (int) $user->id;

        $booking = $this->findAssistantBooking($bookingId);
        if (! $booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        if ($booking->ot_status !== OtBooking::STATUS_READY) {
            return response()->json(['success' => false, 'message' => 'Surgery can only be recorded when the patient status is Ready for OT.'], 422);
        }

        $operatedBy = $booking->ot_doctor_id ? (int) $booking->ot_doctor_id : $assistantId;

        $validated = $request->validate([
            'identity_verified' => ['accepted'],
            'consent_verified' => ['accepted'],
            'payment_verified' => ['accepted'],
            'correct_eye_verified' => ['accepted'],

            'surgery_name' => ['required', 'string', 'max:255'],
            'ot_room' => ['nullable', 'string', 'max:100'],
            'eye_operated' => ['required', Rule::in(['RE', 'LE', 'Both'])],
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
                Rule::exists('medicines', 'name')->where(fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at')),
            ],
            'ot_medicines.*.dose' => ['nullable', 'string', 'max:255'],
        ], [
            'identity_verified.accepted' => 'Identity must be verified before surgery can be recorded.',
            'consent_verified.accepted' => 'Consent must be verified before surgery can be recorded.',
            'payment_verified.accepted' => 'Payment must be verified before surgery can be recorded.',
            'correct_eye_verified.accepted' => 'Correct eye must be verified before surgery can be recorded.',
        ]);

        $surgeryAssistantId = (int) ($booking->ot_assistant_id ?: $assistantId);

        $bookedEye = (string) $booking->eye;
        $eyeOperated = (string) $validated['eye_operated'];
        if ($bookedEye !== 'Both' && $eyeOperated !== 'Both' && $bookedEye !== $eyeOperated) {
            return response()->json([
                'success' => false,
                'message' => "Operated eye ({$eyeOperated}) does not match booked eye ({$bookedEye}).",
            ], 422);
        }

        $otMedicines = collect($validated['ot_medicines'] ?? [])
            ->filter(fn (array $item): bool => ! empty($item['medicine']) || ! empty($item['dose']))
            ->values()
            ->all();

        if ($otMedicines === [] && ! empty($validated['medicine_group_id'])) {
            $group = MedicineGroup::with('items.medicine')->find($validated['medicine_group_id']);
            $otMedicines = $group
                ? $group->items->map(fn ($item) => [
                    'medicine' => $item->medicine?->name,
                    'dose' => trim(($item->frequency ?? '') . ' ' . ($item->duration ?? '')),
                ])->filter(fn ($item) => ! empty($item['medicine']))->values()->all()
                : [];
        }

        $surgery = DB::transaction(function () use ($validated, $otMedicines, $operatedBy, $assistantId, $surgeryAssistantId, $tenantId, $booking) {
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
                    ->where(fn ($q) => $q->where('name', $name)->orWhere('brand_name', $name))
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

            $booking->update(['ot_status' => OtBooking::STATUS_OPERATED, 'operated_at' => now()]);

            return $surgery;
        });

        return response()->json([
            'success' => true,
            'message' => 'Surgery recorded successfully.',
            'data' => $surgery->fresh(),
        ], 201);
    }

    public function lens(string $slug, int $bookingId): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;

        $booking = OtBooking::query()
            ->where('tenant_id', $tenantId)
            ->with(['patient:id,patient_code,first_name,middle_name,last_name,contact_no'])
            ->find($bookingId);

        if (! $booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

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

        return response()->json([
            'success' => true,
            'data' => [
                'booking' => $booking,
                'lens_detail' => $lensDetail,
                'lens_types' => self::LENS_TYPES,
                'lens_powers' => $lensPowers,
                'lens_inventory' => $lensInventory,
            ],
        ]);
    }

    public function storeLens(string $slug, Request $request, int $bookingId): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;

        $booking = OtBooking::query()->where('tenant_id', $tenantId)->find($bookingId);
        if (! $booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        $allowedStatuses = [
            OtBooking::STATUS_READY,
            OtBooking::STATUS_OPERATED,
            OtBooking::STATUS_IN_WARD,
            OtBooking::STATUS_DILATED,
            OtBooking::STATUS_PAYMENT_VERIFIED,
        ];

        if ($booking->ot_status === OtBooking::STATUS_DISCHARGED) {
            return response()->json(['success' => false, 'message' => 'Cannot update lens details after discharge.'], 422);
        }

        if (! in_array($booking->ot_status, $allowedStatuses, true)) {
            return response()->json(['success' => false, 'message' => 'Lens details can only be saved for patients in ward / ready / operated stages.'], 422);
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

        $lensDetail = OtLensDetail::query()->updateOrCreate(
            ['tenant_id' => $tenantId, 'ot_booking_id' => $booking->id],
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
                'entered_by' => (int) auth('sanctum')->id(),
            ]
        );

        // Auto-decrements lens_inventory.available_stock exactly once, only on the
        // false -> true implant transition — reuses the same service the web uses,
        // no reimplementation of the decrement logic here.
        $this->lensInventoryService->handleImplantTransition($lensInventoryId, $wasImplanted, $isImplanted);

        if ($isImplanted) {
            $booking->update([
                'ot_status' => OtBooking::STATUS_OPERATED,
                'operated_at' => $booking->operated_at ?? now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lens details saved successfully.',
            'data' => $lensDetail,
        ]);
    }

    private function findAssistantBooking(int $bookingId): ?OtBooking
    {
        $tenantId = (int) app('tenant')->id;
        $user = auth('sanctum')->user();
        $seeAll = $user->isSuperUser() || ($user->role?->slug === 'hospital_admin');

        $query = OtBooking::query()
            ->where('tenant_id', $tenantId)
            ->with(['patient:id,first_name,middle_name,last_name,contact_no']);

        if (! $seeAll) {
            $query->where('ot_assistant_id', (int) $user->id);
        }

        return $query->find($bookingId);
    }
}
