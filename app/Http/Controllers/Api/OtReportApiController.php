<?php

/**
 * OtReportApiController.php
 *
 * PURPOSE: Mobile/tablet API mirror of Hospital\Report\OtReportController (web) —
 *          Phase 8 (Reports & Dashboard), last phase per docs/ROUND3_OT_MOBILE_API_PRD_PLAN.md
 *          §16 ("build this phase last — it consumes data the other phases produce").
 *          See docs/OT_WORKFLOW_UPGRADE_PRD.md §8 and plan doc §12 (FR-OT-39/40).
 *
 * DESIGN: extends Hospital\Report\OtReportController and reuses its report-building
 * logic directly (`buildReport()`, `REPORTS` catalogue, `resolveDateRange()` — all
 * changed from `private` to `protected` on the parent, a pure visibility change, zero
 * behavior change) rather than duplicating ~400 lines of 14 report queries across two
 * controllers. `export()`/`exportPdf()` are inherited UNCHANGED (not overridden) —
 * their signature `(Request, string $slug, string $type)` already matches the API
 * route, and they already return the correct file-download response type. Only
 * `index()`/`show()` are overridden, to return JSON instead of a View.
 *
 * `authorizePermission()` (parent, calls RolePermissionService::can() against the
 * `hospital_user` guard) works correctly here too — verified in
 * app/Http/Middleware/CheckPermission.php that the Sanctum-authenticated user gets
 * bound onto the `hospital_user` guard before the controller runs, same bridging
 * every other API controller in this round relies on.
 *
 * NOTE: the new list/show methods below are named `apiIndex()`/`apiShow()`, not
 * `index()`/`show()` — PHP requires an overriding method's return type to be
 * covariant with the parent's. The parent declares `: View`; a JSON-returning
 * method can't override that (fatal "declaration must be compatible" error, hit
 * and fixed during this phase). Different method names sidestep the conflict
 * entirely while still inheriting `buildReport()`/`resolveDateRange()`/`REPORTS`.
 *
 * PERMISSIONS: reports.view (index/show/dashboard summary), reports.export (export/exportPdf).
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Hospital\Report\OtReportController;
use App\Models\Hospital\Dosage;
use App\Models\Hospital\OT\OtBooking;
use App\Models\Hospital\OT\OtLensDetail;
use App\Models\Hospital\OT\OtPayment;
use App\Models\Hospital\Patient;
use App\Models\Hospital\PrimaryExamination;
use App\Models\Hospital\SecondaryExamination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OtReportApiController extends OtReportController
{
    public function apiIndex(string $slug): JsonResponse
    {
        $reportsByGroup = collect(self::REPORTS)
            ->map(fn (array $meta, string $key) => [...$meta, 'key' => $key])
            ->groupBy('group');

        // Lens Report has its own dedicated endpoint/screen (summary +
        // lens-power breakdown) rather than the generic headings/rows
        // apiShow() used by self::REPORTS, so it's appended here instead of
        // into that list — matches web's index() exactly.
        $reportsByGroup = $reportsByGroup->map(fn ($reports, $group) => $group === 'Clinical Reports'
            ? $reports->push(['group' => 'Clinical Reports', 'label' => 'Lens Report', 'key' => 'lens-report'])
            : $reports);

        return response()->json(['success' => true, 'data' => $reportsByGroup]);
    }

    public function apiShow(Request $request, string $slug, string $type): JsonResponse
    {
        if (! array_key_exists($type, self::REPORTS)) {
            return response()->json(['success' => false, 'message' => 'Unknown report type.'], 404);
        }

        [$from, $to] = $this->resolveDateRange($request);
        [$headings, $rows] = $this->buildReport($type, $from, $to);

        return response()->json([
            'success' => true,
            'data' => [
                'type' => $type,
                'label' => self::REPORTS[$type]['label'],
                'headings' => $headings,
                'rows' => $rows,
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    /**
     * JSON mirror of the parent's lensReport() (which returns a View).
     * Deliberately self-contained (not reusing the parent's private
     * resolveLensReportDateRange()/buildLensReportData() — those stay
     * private and web-only) rather than widening the web controller's
     * surface for this API's sake. lensReportExport() is inherited
     * UNCHANGED (not overridden) — it already returns a file download, no
     * View involved, so the API route points straight at it.
     */
    public function apiLensReport(Request $request, string $slug): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $today = now()->toDateString();
        $fromDate = $validated['from'] ?? $today;
        $toDate = $validated['to'] ?? $today;
        if ($toDate < $fromDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }
        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->endOfDay();

        $tenantId = (int) app('tenant')->id;

        // Mirrors web's OtReportController::buildLensReportData() (web pull
        // 2026-10-17): one row per full lens configuration (Power + Category +
        // Company + Model + Type) with how many times it was implanted. Lens
        // Category lives on ot_counselling, pulled via the booking relation.
        $lensDetails = OtLensDetail::query()
            ->with([
                'booking.counselling:id,ot_booking_id,lens_category',
                'inventoryItem:id,lens_code,manufacturer',
            ])
            ->where('tenant_id', $tenantId)
            ->where('is_implanted', true)
            ->whereBetween('implanted_at', [$from, $to])
            ->get();

        $breakdown = $lensDetails
            ->map(fn (OtLensDetail $d) => [
                'power' => number_format((float) $d->lens_power, 2),
                'category' => $d->booking?->counselling?->lens_category ? ucfirst($d->booking->counselling->lens_category) : '-',
                'company' => $d->manufacturer ?: ($d->inventoryItem?->manufacturer ?? '-'),
                'model' => $d->lens_name ?: '-',
                'type' => $d->lens_type ?: '-',
            ])
            ->groupBy(fn (array $row) => implode('|', $row))
            ->map(fn ($group) => [...$group->first(), 'count' => $group->count()])
            ->sortBy(fn (array $row) => (float) $row['power'])
            ->values();

        $headings = ['Lens Power', 'Lens Category', 'Lens Company', 'Lens Model', 'Lens Type', 'Count'];
        $rows = $breakdown->map(fn (array $row) => [
            '+'.$row['power'].'D',
            $row['category'],
            $row['company'],
            $row['model'],
            $row['type'],
            $row['count'],
        ])->all();

        return response()->json([
            'success' => true,
            'data' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'headings' => $headings,
                'rows' => $rows,
            ],
        ]);
    }

    /**
     * Returns this patient's most relevant exam (secondary-preferred, same as
     * web) as JSON, for the app to render as a PDF natively (the `pdf`/
     * `printing` packages, same as the OPD prescription screen) instead of
     * downloading a DomPDF-rendered file. Switched away from
     * `Pdf::loadView('hospital.exam.print'/'secondary_print')->download()`
     * because that blade is written and tested only against real browsers
     * (web's own print action calls `window.print()`, never DomPDF) — DomPDF's
     * much weaker CSS support was producing visibly broken formatting. See
     * OT_BUGS_ROUND4_AUDIT.md Bug #6 / OT_BUGS_ROUND4_FIX_PLAN.md Phase 4.
     *
     * Same route, same `reports.view` permission, same URL as before —
     * deliberately NOT switched to reuse `PatientHistoryApiController`'s
     * `opd.exam.history`-gated endpoint, since that would change who can
     * print from here (a `reports.view`-only user currently can). JSON shape
     * mirrors `PatientHistoryApiController::buildHistory()`'s per-exam
     * serialization exactly, so the app's existing `ExamRecord`/
     * `PatientHistorySummary` models parse it unchanged.
     */
    public function apiPrescriptionPdf(Request $request, string $slug, int $patient): JsonResponse
    {
        // Permission enforced by the route's 'permission:reports.view' middleware
        // (authorizePermission() on the parent is private, not inheritable — same
        // reason apiIndex()/apiShow() above don't call it either).
        $patientModel = Patient::findOrFail($patient);
        $tenant = app('tenant');

        $secondaryExam = SecondaryExamination::where('patient_id', $patient)
            ->where('tenant_id', $tenant->id)
            ->with('doctor')
            ->first();

        $primaryExam = PrimaryExamination::where('patient_id', $patient)
            ->where('tenant_id', $tenant->id)
            ->with('prescriptions.medicine', 'prescriptions.dosage', 'doctor')
            ->first();

        abort_if(! $primaryExam && ! $secondaryExam, 404, 'No examination found for this patient.');

        $exam = $secondaryExam
            ? $this->secondaryExamToJson($secondaryExam, Dosage::all(['id', 'dosage'])->keyBy('id'))
            : $this->primaryExamToJson($primaryExam);

        return response()->json([
            'success' => true,
            'data' => [
                'patient' => [
                    'id' => $patientModel->id,
                    'name' => trim(implode(' ', array_filter([
                        $patientModel->first_name, $patientModel->middle_name, $patientModel->last_name,
                    ]))),
                    'patient_code' => $patientModel->patient_code,
                    'gender' => $patientModel->gender,
                    'age' => $patientModel->age,
                    'contact_no' => $patientModel->contact_no,
                    'location' => $patientModel->locationLabel,
                    'created_at' => $patientModel->created_at?->toISOString(),
                    'visit_days' => 1,
                ],
                'exam' => $exam,
            ],
        ]);
    }

    /**
     * Re-index list fields inside exam_data so they always JSON-encode as
     * arrays [], not objects {} — same guard as
     * PatientHistoryApiController::normalizeExamData(), duplicated here
     * rather than shared since this is currently the only other caller;
     * worth factoring out if a third caller appears.
     */
    private function normalizeExamDataForJson(mixed $raw): array|\stdClass
    {
        if (! is_array($raw) || count($raw) === 0) {
            return new \stdClass();
        }
        foreach (['co_rows', 'kco_rows', 'diagnoses', 'rx'] as $field) {
            if (isset($raw[$field]) && is_array($raw[$field])) {
                $raw[$field] = array_values($raw[$field]);
            }
        }

        return $raw;
    }

    private function primaryExamToJson(PrimaryExamination $exam): array
    {
        return [
            'id' => $exam->id,
            'type' => 'primary',
            'examined_at' => $exam->examined_at?->toISOString(),
            'doctor' => $exam->doctor?->name,
            'exam_data' => $this->normalizeExamDataForJson($exam->exam_data),
            'prescriptions' => $exam->prescriptions->map(fn ($rx) => [
                'medicine_name' => $rx->medicine?->brand_name ?: ($rx->medicine?->name ?? '-'),
                'dosage' => $rx->dosage?->dosage ?? '-',
                'duration' => $rx->duration ? $rx->duration.' D' : '-',
                'eye' => $rx->eye ?? '-',
            ])->values()->all(),
        ];
    }

    private function secondaryExamToJson(SecondaryExamination $exam, $dosageMasters): array
    {
        $prescriptions = collect($exam->exam_data['rx'] ?? [])
            ->map(fn ($rx) => [
                'medicine_name' => $rx['name'] ?? '-',
                'dosage' => isset($rx['dosage_id'])
                    ? ($dosageMasters->get((int) $rx['dosage_id'])?->dosage ?? '-')
                    : '-',
                'duration' => ! empty($rx['duration']) ? $rx['duration'].' D' : '-',
                'eye' => $rx['eye'] ?? '-',
            ])
            ->values()
            ->all();

        return [
            'id' => $exam->id,
            'type' => 'secondary',
            'examined_at' => $exam->examined_at?->toISOString(),
            'doctor' => $exam->doctor?->name,
            'exam_data' => $this->normalizeExamDataForJson($exam->exam_data),
            'prescriptions' => $prescriptions,
        ];
    }

    /**
     * FR-OT-40 — Management Dashboard Widgets.
     *
     * Only widgets with a real, derivable data source are included:
     * total_patients, surgeries_completed, avg_waiting_time_minutes (ward attend ->
     * operated), patient_turnaround_time_minutes (ward attend -> discharge),
     * revenue_trend (daily payment sums), lens_consumption (implants in range).
     *
     * `ot_utilization_pct` from the plan doc is deliberately OMITTED — it needs an OT
     * room/slot capacity figure (rooms x operating hours) that doesn't exist anywhere
     * in the current schema. Returning a fabricated number would be worse than not
     * returning one; flag to the user/backend team if this is actually wanted, it
     * needs a new capacity-config concept, not just a query.
     */
    public function dashboardSummary(Request $request): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;
        [$from, $to] = $this->resolveDateRange($request);
        $fromStart = Carbon::parse($from)->startOfDay();
        $toEnd = Carbon::parse($to)->endOfDay();

        $bookingsInRange = OtBooking::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('surgery_date', [$fromStart, $toEnd]);

        $totalPatients = (clone $bookingsInRange)->distinct('patient_id')->count('patient_id');

        $operatedInRange = OtBooking::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('ot_status', [OtBooking::STATUS_OPERATED, OtBooking::STATUS_DISCHARGED])
            ->whereBetween('operated_at', [$fromStart, $toEnd]);

        $surgeriesCompleted = (clone $operatedInRange)->count();

        $avgWaitingMinutes = (clone $operatedInRange)
            ->whereNotNull('attended_at')
            ->whereNotNull('operated_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, attended_at, operated_at)) as avg_minutes')
            ->value('avg_minutes');

        $turnaroundInRange = OtBooking::query()
            ->where('tenant_id', $tenantId)
            ->where('ot_status', OtBooking::STATUS_DISCHARGED)
            ->whereBetween('discharged_at', [$fromStart, $toEnd])
            ->whereNotNull('attended_at');

        $avgTurnaroundMinutes = (clone $turnaroundInRange)
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, attended_at, discharged_at)) as avg_minutes')
            ->value('avg_minutes');

        $revenueTrend = OtPayment::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('paid_at', [$fromStart, $toEnd])
            ->selectRaw('DATE(paid_at) as date, SUM(package_amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $lensConsumption = OtLensDetail::query()
            ->where('tenant_id', $tenantId)
            ->where('is_implanted', true)
            ->whereBetween('implanted_at', [$fromStart, $toEnd])
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'from' => $from,
                'to' => $to,
                'total_patients' => $totalPatients,
                'surgeries_completed' => $surgeriesCompleted,
                'avg_waiting_time_minutes' => $avgWaitingMinutes !== null ? round((float) $avgWaitingMinutes, 1) : null,
                'patient_turnaround_time_minutes' => $avgTurnaroundMinutes !== null ? round((float) $avgTurnaroundMinutes, 1) : null,
                'revenue_trend' => $revenueTrend->map(fn ($row) => ['date' => $row->date, 'total' => (float) $row->total])->values(),
                'lens_consumption' => $lensConsumption,
            ],
        ]);
    }
}
