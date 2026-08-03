<?php

/**
 * OtWardController.php
 *
 * PURPOSE: OT Workflow Upgrade — Phase 3 (Ward Module).
 *          Wires up the previously-unused `ot_pre_op` and `ot_dilation_entries`
 *          tables — ward nurse records pre-op vitals (incl. SpO2) and logs each
 *          eye-drop dose (medicine, eye, dose number, administered by) before the
 *          patient is handed off to the OT Assistant via
 *          OtAccountantController::markReadyForOt.
 *          Doctor / OT Assistant assignment happens on Patient Status:
 *          Ready for OT → OT Assistant; Hold/Complicated/Preparing → Doctor.
 *          See docs/OT_WORKFLOW_UPGRADE_PRD.md §3.
 *
 * PERMISSIONS: ot.ward.entry (view), ot.preop.entry (vitals), ot.dilation.track (eye drops)
 */

namespace App\Http\Controllers\Hospital\OT;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\OT\OtBooking;
use App\Models\Hospital\OT\OtDilationEntry;
use App\Models\Hospital\OT\OtPreOp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OtWardController extends Controller
{
    /** Statuses allowed to open / edit ward vitals (PDF Step 6–7). */
    private const WARD_ALLOWED_STATUSES = [
        OtBooking::STATUS_PAYMENT_VERIFIED,
        OtBooking::STATUS_IN_WARD,
        OtBooking::STATUS_DILATED,
        OtBooking::STATUS_READY,
    ];

    public function show(string $slug, OtBooking $booking): View
    {
        $tenantId = (int) app('tenant')->id;
        abort_unless((int) $booking->tenant_id === $tenantId, 403, 'Unauthorized booking access.');

        if (! in_array($booking->ot_status, self::WARD_ALLOWED_STATUSES, true)) {
            return redirect()
                ->route('hospital.ot.ward.index', ['slug' => $slug])
                ->with('error', 'Ward entry is only available after counsellor payment verification.');
        }

        $booking->load([
            'patient:id,patient_code,first_name,middle_name,last_name,contact_no',
            'payments',
            'otDoctor:id,name',
            'otAssistant:id,name',
        ]);

        $preOp = OtPreOp::query()
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->first();

        $eyeDrops = OtDilationEntry::query()
            ->with('administeredBy:id,name')
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->orderBy('administered_at')
            ->orderBy('dose_number')
            ->get();

        $otDoctors = HospitalUser::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNotNull('doctor_type')
                    ->orWhereHas('role', function ($q) {
                        $q->where(function ($inner) {
                            $inner->whereIn('slug', ['doctor'])
                                ->orWhereIn('name', ['doctor']);
                        });
                    });
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $otAssistants = HospitalUser::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->whereHas('role', fn ($q) => $q->where('slug', 'ot_assistant'))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('hospital.ot.ward.show', [
            'slug' => $slug,
            'booking' => $booking,
            'preOp' => $preOp,
            'eyeDrops' => $eyeDrops,
            'otDoctors' => $otDoctors,
            'otAssistants' => $otAssistants,
        ]);
    }

    public function storeVitals(Request $request, string $slug, OtBooking $booking): RedirectResponse
    {
        $tenantId = (int) app('tenant')->id;
        abort_unless((int) $booking->tenant_id === $tenantId, 403, 'Unauthorized booking access.');

        if (! in_array($booking->ot_status, self::WARD_ALLOWED_STATUSES, true)) {
            return redirect()
                ->route('hospital.ot.ward.index', ['slug' => $slug])
                ->with('error', 'Ward entry is only available after counsellor payment verification.');
        }

        $validated = $request->validate([
            'bp' => ['nullable', 'string', 'max:20'],
            'pulse' => ['nullable', 'string', 'max:20'],
            'rbs' => ['nullable', 'numeric', 'between:0,999.9'],
            'temperature' => ['nullable', 'numeric', 'between:0,99.9'],
            'spo2' => ['nullable', 'numeric', 'between:0,100'],
            'hba1c' => ['nullable', 'numeric', 'between:0,99.9'],
            'pre_op_status' => ['required', Rule::in(OtPreOp::STATUSES)],
        ]);

        OtPreOp::query()->updateOrCreate(
            ['tenant_id' => $tenantId, 'ot_booking_id' => $booking->id],
            [
                ...$validated,
                'entered_by' => (int) auth('hospital_user')->id(),
            ]
        );

        $message = 'Vitals recorded.';

        // Patient Status form posts assign_staff=1 with doctor / assistant selection.
        if ($request->boolean('assign_staff')) {
            $isReady = $validated['pre_op_status'] === OtPreOp::STATUS_READY_FOR_SURGERY;

            $staff = $request->validate([
                'ot_doctor_id' => [
                    $isReady ? 'nullable' : 'required',
                    'integer',
                    Rule::exists('hospital_users', 'id')->where(function ($q) use ($tenantId) {
                        $q->where('tenant_id', $tenantId)
                            ->where('status', 'active')
                            ->where(function ($inner) {
                                $inner->whereNotNull('doctor_type')
                                    ->orWhereExists(function ($sub) {
                                        $sub->selectRaw('1')
                                            ->from('roles')
                                            ->whereColumn('roles.id', 'hospital_users.role_id')
                                            ->where(function ($roleQ) {
                                                $roleQ->where('roles.slug', 'doctor')
                                                    ->orWhere('roles.name', 'doctor');
                                            });
                                    });
                            });
                    }),
                ],
                'ot_assistant_id' => [
                    $isReady ? 'required' : 'nullable',
                    'integer',
                    Rule::exists('hospital_users', 'id')->where(function ($q) use ($tenantId) {
                        $q->where('tenant_id', $tenantId)
                            ->where('status', 'active')
                            ->whereExists(function ($sub) {
                                $sub->selectRaw('1')
                                    ->from('roles')
                                    ->whereColumn('roles.id', 'hospital_users.role_id')
                                    ->where('roles.slug', 'ot_assistant');
                            });
                    }),
                ],
            ], [
                'ot_doctor_id.required' => 'Select a doctor when the patient is not ready for OT (consultation).',
                'ot_assistant_id.required' => 'Select an OT Assistant when the patient is Ready for OT.',
            ]);

            $booking->update([
                'ot_doctor_id' => ! empty($staff['ot_doctor_id'])
                    ? (int) $staff['ot_doctor_id']
                    : $booking->ot_doctor_id,
                'ot_assistant_id' => ! empty($staff['ot_assistant_id'])
                    ? (int) $staff['ot_assistant_id']
                    : ($isReady ? null : $booking->ot_assistant_id),
            ]);

            $message = $isReady
                ? 'Status saved. Patient assigned to OT Assistant.'
                : 'Status saved. Patient assigned to doctor for consultation.';
        }

        // Move paid→verified patients into in_ward once vitals start (if still only verified).
        if ($booking->ot_status === OtBooking::STATUS_PAYMENT_VERIFIED) {
            $booking->update([
                'ot_status' => OtBooking::STATUS_IN_WARD,
                'attended_at' => $booking->attended_at ?? now(),
            ]);
        }

        return redirect()
            ->route('hospital.ot.ward.show', ['slug' => $slug, 'booking' => $booking->id])
            ->with('success', $message);
    }

    public function addEyeDrop(Request $request, string $slug, OtBooking $booking): RedirectResponse
    {
        $tenantId = (int) app('tenant')->id;
        abort_unless((int) $booking->tenant_id === $tenantId, 403, 'Unauthorized booking access.');

        if (! in_array($booking->ot_status, self::WARD_ALLOWED_STATUSES, true)) {
            return redirect()
                ->route('hospital.ot.ward.index', ['slug' => $slug])
                ->with('error', 'Ward entry is only available after counsellor payment verification.');
        }

        $validated = $request->validate([
            'medicine_name' => ['required', 'string', 'max:150'],
            'eye' => ['required', Rule::in(['RE', 'LE'])],
            'dose_number' => ['required', 'integer', 'min:1', 'max:20'],
            'administered_at' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        OtDilationEntry::query()->create([
            'tenant_id' => $tenantId,
            'ot_booking_id' => $booking->id,
            'administered_at' => $validated['administered_at'] ?? now(),
            'medicine_name' => $validated['medicine_name'],
            'eye' => $validated['eye'],
            'dose_number' => $validated['dose_number'],
            'is_instilled' => true,
            'administered_by' => (int) auth('hospital_user')->id(),
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return redirect()
            ->route('hospital.ot.ward.show', ['slug' => $slug, 'booking' => $booking->id])
            ->with('success', 'Eye drop dose logged.');
    }
}
