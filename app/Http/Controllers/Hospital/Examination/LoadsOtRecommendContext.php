<?php

namespace App\Http\Controllers\Hospital\Examination;

use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\OT\OtBooking;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

trait LoadsOtRecommendContext
{
    /**
     * @return array{0: Collection, 1: ?OtBooking, 2: ?string, 3: Collection, 4: Collection}
     */
    protected function otRecommendContext(int $tenantId, int $patientId): array
    {
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
            $otSurgeryTypes,
            $existingOtRecommendation,
            $otDefaultDiagnosisHint !== '' ? $otDefaultDiagnosisHint : null,
            $otAssistants,
            $otDoctors,
        ];
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
