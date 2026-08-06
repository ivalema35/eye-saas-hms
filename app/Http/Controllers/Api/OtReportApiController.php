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
use App\Models\Hospital\OT\OtBooking;
use App\Models\Hospital\OT\OtLensDetail;
use App\Models\Hospital\OT\OtPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class OtReportApiController extends OtReportController
{
    public function apiIndex(string $slug): JsonResponse
    {
        $reportsByGroup = collect(self::REPORTS)
            ->map(fn (array $meta, string $key) => [...$meta, 'key' => $key])
            ->groupBy('group');

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
