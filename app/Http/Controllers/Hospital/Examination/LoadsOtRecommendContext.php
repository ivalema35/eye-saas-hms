<?php

namespace App\Http\Controllers\Hospital\Examination;

use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\OT\OtAppointment;
use App\Models\Hospital\OT\OtBooking;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

trait LoadsOtRecommendContext
{
    /**
     * @return array{0: Collection, 1: Collection, 2: ?OtBooking, 3: ?int, 4: ?string, 5: ?string, 6: Collection, 7: Collection}
     */
    protected function otRecommendContext(int $tenantId, int $patientId): array
    {
        $otSlots = DB::table('tbl_ot_slots')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->orderBy('start_time')
            ->orderBy('slot_name')
            ->get(['id', 'slot_name', 'start_time', 'end_time']);

        $otSurgeryTypes = DB::table('ot_surgery_types')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->orderBy('surgery_name')
            ->get(['id', 'ot_type_id', 'surgery_name']);

        $existingOtRecommendation = OtBooking::query()
            ->with('counselling:id,ot_booking_id,diagnosis')
            ->where('tenant_id', $tenantId)
            ->where('patient_id', $patientId)
            ->whereIn('ot_status', [
                OtBooking::STATUS_BOOKED,
                OtBooking::STATUS_SURGERY_RECOMMENDED,
            ])
            ->orderByDesc('id')
            ->first();

        // Prefill from OT Appointment (slot/time + date chosen at booking).
        [$otDefaultSlotId, $otDefaultSurgeryDate] = $this->defaultsFromOtAppointment(
            $tenantId,
            $patientId,
            $otSlots
        );

        $otDefaultDiagnosisHint = trim((string) ($existingOtRecommendation?->counselling?->diagnosis ?? ''));
        if ($otDefaultDiagnosisHint === '') {
            $otDefaultDiagnosisHint = $this->patientExamDiagnosisText($tenantId, $patientId) ?? '';
        }

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

        return [
            $otSlots,
            $otSurgeryTypes,
            $existingOtRecommendation,
            $otDefaultSlotId,
            $otDefaultSurgeryDate,
            $otDefaultDiagnosisHint !== '' ? $otDefaultDiagnosisHint : null,
            $otAssistants,
            $otDoctors,
        ];
    }

    /**
     * @param  Collection<int, object>  $otSlots
     * @return array{0: ?int, 1: ?string}
     */
    protected function defaultsFromOtAppointment(int $tenantId, int $patientId, Collection $otSlots): array
    {
        $appointment = OtAppointment::query()
            ->where('tenant_id', $tenantId)
            ->where('converted_patient_id', $patientId)
            ->where('status', '!=', OtAppointment::STATUS_CANCELLED)
            ->whereNotNull('appointment_time')
            ->orderByDesc('appointment_date')
            ->orderByDesc('id')
            ->first(['id', 'appointment_date', 'appointment_time']);

        if (! $appointment) {
            return [null, null];
        }

        $apptTime = null;
        try {
            $apptTime = Carbon::parse($appointment->appointment_time)->format('H:i');
        } catch (\Throwable) {
            $apptTime = substr((string) $appointment->appointment_time, 0, 5) ?: null;
        }

        $slotId = null;
        if ($apptTime) {
            $matched = $otSlots->first(function ($slot) use ($apptTime) {
                $start = substr((string) ($slot->start_time ?? ''), 0, 5);

                return $start !== '' && $start === $apptTime;
            });
            $slotId = $matched ? (int) $matched->id : null;
        }

        $date = null;
        if ($appointment->appointment_date) {
            $dateStr = $appointment->appointment_date->format('Y-m-d');
            if ($dateStr >= now()->toDateString()) {
                $date = $dateStr;
            }
        }

        return [$slotId, $date];
    }

    protected function patientExamDiagnosisText(int $tenantId, int $patientId): ?string
    {
        $examData = DB::table('secondary_examinations')
            ->where('tenant_id', $tenantId)
            ->where('patient_id', $patientId)
            ->value('exam_data');

        if (! $examData) {
            $examData = DB::table('primary_examinations')
                ->where('tenant_id', $tenantId)
                ->where('patient_id', $patientId)
                ->value('exam_data');
        }

        if (is_string($examData)) {
            $examData = json_decode($examData, true);
        }

        $ids = collect($examData['diagnoses'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return null;
        }

        $names = DB::table('tbl_master_diagnosis')
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->pluck('value')
            ->filter()
            ->values();

        return $names->isNotEmpty() ? $names->implode(', ') : null;
    }
}
