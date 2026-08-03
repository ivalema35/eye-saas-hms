<?php

/**
 * OtCounsellorController.php
 *
 * PURPOSE: OT Workflow Upgrade — Phase 1 (Counsellor Module).
 *          Sits between "Doctor recommends surgery" and "Billing". Counsellor confirms
 *          diagnosis, picks lens + package, builds a cost estimate, captures informed
 *          consent (signatures), verifies blood reports, then sends the booking to Billing.
 *          See docs/OT_WORKFLOW_UPGRADE_PRD.md §1.
 *
 * PERMISSIONS: ot.counselling.fill (dashboard/form/counselling save), ot.consent.capture (consent save)
 */

namespace App\Http\Controllers\Hospital\OT;

use App\Http\Controllers\Controller;
use App\Models\Hospital\OT\OtBooking;
use App\Models\Hospital\OT\OtConsent;
use App\Models\Hospital\OT\OtCounselling;
use App\Models\Hospital\OT\OtLensOption;
use App\Models\Hospital\OT\OtPackageMaster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OtCounsellorController extends Controller
{
    public function dashboard(string $slug): View
    {
        $bookings = OtBooking::query()
            ->with(['patient:id,patient_code,first_name,middle_name,last_name,contact_no', 'otDoctor:id,name'])
            ->whereIn('ot_status', [OtBooking::STATUS_BOOKED, OtBooking::STATUS_SURGERY_RECOMMENDED])
            // Prefer doctor-recommended cases first (Phase A1), then receptionist bookings.
            ->orderByRaw("CASE WHEN ot_status = ? THEN 0 ELSE 1 END", [OtBooking::STATUS_SURGERY_RECOMMENDED])
            ->orderBy('surgery_date')
            ->orderByDesc('id')
            ->paginate((int) config('app.pagination_limit', 25), ['*'], 'page');

        // Payment status queue — shows bookings once billing has taken payment.
        // Stays visible after payment is verified (docs/tulsi.md §2/§3) so Reception can
        // see at a glance which of their counselled patients Accounts has finished with.
        $paymentVerificationQueue = OtBooking::query()
            ->with(['patient:id,patient_code,first_name,middle_name,last_name,contact_no', 'payments'])
            ->whereIn('ot_status', [
                OtBooking::STATUS_PAID,
                OtBooking::STATUS_PAYMENT_VERIFIED,
                OtBooking::STATUS_IN_WARD,
                OtBooking::STATUS_DILATED,
                OtBooking::STATUS_READY,
                OtBooking::STATUS_OPERATED,
                OtBooking::STATUS_DISCHARGED,
            ])
            ->orderBy('surgery_date')
            ->orderByDesc('id')
            ->paginate((int) config('app.pagination_limit', 25), ['*'], 'payment_page');

        return view('hospital.ot.counsellor.dashboard', [
            'slug' => $slug,
            'bookings' => $bookings,
            'paymentVerificationQueue' => $paymentVerificationQueue,
        ]);
    }

    public function show(string $slug, int $bookingId): View
    {
        $tenantId = (int) app('tenant')->id;

        $booking = OtBooking::query()
            ->with(['patient:id,patient_code,first_name,middle_name,last_name,contact_no', 'otDoctor:id,name'])
            ->findOrFail($bookingId);

        $counselling = OtCounselling::query()
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->first();

        $consent = OtConsent::query()
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->first();

        $lensOptions = OtLensOption::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        // Package master rows: each lens_cost is paired with a room + charges for autofill
        $packageCostOptions = OtPackageMaster::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereNotNull('lens_cost')
            ->orderBy('lens_cost')
            ->orderBy('room_category')
            ->get([
                'id',
                'package_name',
                'lens_cost',
                'room_category',
                'ot_charges',
                'surgeon_charges',
                'nursing_charges',
                'consumables_charges',
            ]);

        return view('hospital.ot.counsellor.form', [
            'slug' => $slug,
            'booking' => $booking,
            'counselling' => $counselling,
            'consent' => $consent,
            'lensOptions' => $lensOptions,
            'packageCostOptions' => $packageCostOptions,
        ]);
    }

    /**
     * Autofill lookup: lens_cost + room_category → package / charges.
     */
    public function lookupPackage(Request $request, string $slug): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;

        $validated = $request->validate([
            'lens_cost' => ['required', 'numeric', 'min:0'],
            'room_category' => ['required', Rule::in([OtPackageMaster::ROOM_GENERAL, OtPackageMaster::ROOM_PRIVATE])],
        ]);

        $package = OtPackageMaster::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('room_category', $validated['room_category'])
            ->where('lens_cost', (float) $validated['lens_cost'])
            ->first([
                'package_name',
                'lens_cost',
                'room_category',
                'ot_charges',
                'surgeon_charges',
                'nursing_charges',
                'consumables_charges',
            ]);

        if (! $package) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'package' => [
                'package_name' => $package->package_name,
                'ot_charges' => (float) $package->ot_charges,
                'surgeon_charges' => (float) $package->surgeon_charges,
                'nursing_charges' => (float) $package->nursing_charges,
                'consumables_charges' => (float) $package->consumables_charges,
            ],
        ]);
    }

    public function storeCounselling(Request $request, string $slug, int $bookingId): RedirectResponse
    {
        $tenantId = (int) app('tenant')->id;
        $booking = OtBooking::query()->findOrFail($bookingId);

        $validated = $request->validate([
            'diagnosis' => ['nullable', 'string', 'max:255'],
            'eye' => ['required', Rule::in(['RE', 'LE', 'Both'])],
            'surgery_type_confirmed' => ['nullable', 'boolean'],
            'mediclaim' => ['required', 'boolean'],
            'lens_option' => [
                'nullable',
                Rule::exists('ot_lens_options', 'name')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)->where('is_active', true)->whereNull('deleted_at')),
            ],
            'lens_category' => ['nullable', Rule::in(['standard', 'premium'])],
            'lens_company' => ['nullable', 'string', 'max:150'],
            'lens_model' => ['nullable', 'string', 'max:150'],
            'lens_type' => ['nullable', 'string', 'max:100'],
            'estimated_power' => ['nullable', 'numeric', 'between:-99.99,999.99'],
            'lens_cost' => ['nullable', 'numeric', 'min:0'],
            'package_name' => ['nullable', 'string', 'max:150'],
            'room_category' => ['nullable', Rule::in(['general', 'private'])],
            'ot_charges' => ['nullable', 'numeric', 'min:0'],
            'surgeon_charges' => ['nullable', 'numeric', 'min:0'],
            'nursing_charges' => ['nullable', 'numeric', 'min:0'],
            'consumables_charges' => ['nullable', 'numeric', 'min:0'],
            'payment_mode' => [
                'nullable',
                Rule::in(
                    filter_var($request->input('mediclaim'), FILTER_VALIDATE_BOOLEAN)
                        ? ['mediclaim']
                        : ['cash', 'online']
                ),
            ],
            'report_ok' => ['nullable', 'boolean'],
            'blood_reports_verified' => ['nullable', 'boolean'],
            'blood_reports_normal' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $totalEstimate = round((float) ($validated['lens_cost'] ?? 0), 2);

        $counsellorId = (int) auth('hospital_user')->id();

        DB::transaction(function () use ($tenantId, $booking, $validated, $totalEstimate, $counsellorId): void {
            OtCounselling::query()->updateOrCreate(
                ['tenant_id' => $tenantId, 'ot_booking_id' => $booking->id],
                [
                    'diagnosis' => $validated['diagnosis'] ?? null,
                    'surgery_type_confirmed' => (bool) ($validated['surgery_type_confirmed'] ?? false),
                    'mediclaim' => (bool) $validated['mediclaim'],
                    'lens_option' => $validated['lens_option'] ?? null,
                    'lens_category' => $validated['lens_category'] ?? null,
                    'lens_company' => $validated['lens_company'] ?? null,
                    'lens_model' => $validated['lens_model'] ?? null,
                    'lens_type' => $validated['lens_type'] ?? null,
                    'estimated_power' => $validated['estimated_power'] ?? null,
                    'lens_cost' => $validated['lens_cost'] ?? null,
                    'package_amount' => $totalEstimate,
                    'package_name' => $validated['package_name'] ?? null,
                    'room_category' => $validated['room_category'] ?? null,
                    'ot_charges' => $validated['ot_charges'] ?? null,
                    'surgeon_charges' => $validated['surgeon_charges'] ?? null,
                    'nursing_charges' => $validated['nursing_charges'] ?? null,
                    'consumables_charges' => $validated['consumables_charges'] ?? null,
                    'total_estimate' => $totalEstimate,
                    'payment_mode' => $validated['payment_mode'] ?? null,
                    'report_ok' => (bool) ($validated['report_ok'] ?? false),
                    'blood_reports_verified' => (bool) ($validated['blood_reports_verified'] ?? false),
                    'blood_reports_normal' => (bool) ($validated['blood_reports_normal'] ?? false),
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => $counsellorId,
                    'counselled_by' => $counsellorId,
                    'counselled_at' => now(),
                ]
            );

            // Keep the denormalized booking columns in sync — Accountant/Doctor screens
            // (OtAccountantController, ot/accountant/*.blade.php) already read these directly.
            $booking->update([
                'eye' => $validated['eye'],
                'has_mediclaim' => (bool) $validated['mediclaim'],
                'lens_option' => $validated['lens_option'] ?? null,
                'package_amount' => $totalEstimate,
                'payment_mode' => $validated['payment_mode'] ?? null,
                'reports_ok' => (bool) ($validated['report_ok'] ?? false),
            ]);
        });

        return redirect()
            ->route('hospital.ot.counsellor.form', ['slug' => $slug, 'bookingId' => $booking->id])
            ->with('success', 'Counselling details saved. Total estimate: ' . money_code($totalEstimate, 2));
    }

    public function storeConsent(Request $request, string $slug, int $bookingId): RedirectResponse
    {
        $tenantId = (int) app('tenant')->id;
        $booking = OtBooking::query()->findOrFail($bookingId);

        $validated = $request->validate([
            'consent_given' => ['required', 'boolean'],
            'patient_signature' => ['nullable', 'string'],
            'guardian_signature' => ['nullable', 'string'],
            'witness_name' => ['nullable', 'string', 'max:150'],
        ]);

        $existing = OtConsent::query()
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->first();

        $patientSignaturePath = $existing?->patient_signature_path;
        $guardianSignaturePath = $existing?->guardian_signature_path;

        if (! empty($validated['patient_signature']) && str_starts_with($validated['patient_signature'], 'data:image')) {
            $patientSignaturePath = $this->storeSignature($tenantId, $booking->id, 'patient', $validated['patient_signature']);
        }

        if (! empty($validated['guardian_signature']) && str_starts_with($validated['guardian_signature'], 'data:image')) {
            $guardianSignaturePath = $this->storeSignature($tenantId, $booking->id, 'guardian', $validated['guardian_signature']);
        }

        OtConsent::query()->updateOrCreate(
            ['tenant_id' => $tenantId, 'ot_booking_id' => $booking->id],
            [
                'consent_given' => (bool) $validated['consent_given'],
                'patient_signature_path' => $patientSignaturePath,
                'guardian_signature_path' => $guardianSignaturePath,
                'witness_name' => $validated['witness_name'] ?? null,
                'consent_date' => (bool) $validated['consent_given'] ? now() : null,
                'created_by' => (int) auth('hospital_user')->id(),
            ]
        );

        return redirect()
            ->route('hospital.ot.counsellor.form', ['slug' => $slug, 'bookingId' => $booking->id])
            ->with('success', 'Consent recorded successfully.');
    }

    public function sendToBilling(string $slug, int $bookingId): RedirectResponse
    {
        $tenantId = (int) app('tenant')->id;
        $booking = OtBooking::query()->findOrFail($bookingId);

        $hasCounselling = OtCounselling::query()
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->exists();

        $consentGiven = OtConsent::query()
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->where('consent_given', true)
            ->exists();

        if (! $hasCounselling) {
            return redirect()->back()->with('error', 'Save counselling details before sending to billing.');
        }

        if (! $consentGiven) {
            return redirect()->back()->with('error', 'Patient consent must be captured before sending to billing.');
        }

        $packageAmount = (float) ($booking->package_amount ?? 0);
        if ($packageAmount <= 0) {
            return redirect()->back()->with('error', 'Package / cost estimate must be greater than zero before sending to billing.');
        }

        $booking->update(['ot_status' => OtBooking::STATUS_COUNSELLED]);

        return redirect()
            ->route('hospital.ot.counsellor.dashboard', ['slug' => $slug])
            ->with('success', 'Patient sent to Billing.');
    }

    private function storeSignature(int $tenantId, int $bookingId, string $type, string $base64): string
    {
        $base64Data = preg_replace('#^data:image/\w+;base64,#i', '', $base64);
        $imageData = base64_decode($base64Data);

        $path = "tenants/{$tenantId}/ot-consents/{$bookingId}/{$type}_signature_".time().'.png';
        Storage::disk('public')->put($path, $imageData);

        return $path;
    }
}
