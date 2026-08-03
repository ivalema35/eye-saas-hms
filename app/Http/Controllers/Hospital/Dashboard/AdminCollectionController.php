<?php

namespace App\Http\Controllers\Hospital\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\Patient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Hospital admin dashboard "Total Collection" drill-down.
 * Reception-wise totals + single reception old / new / other case breakdown.
 */
class AdminCollectionController extends Controller
{
    public function index(Request $request, string $slug): View
    {
        [$startDate, $endDate] = $this->resolvedDates($request);

        $rows = $this->receptionCollectionRows($startDate, $endDate);

        return view('hospital.dashboard.admin_collection', [
            'slug' => $slug,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'rows' => $rows,
            'grandTotal' => $rows->sum('total'),
        ]);
    }

    public function show(Request $request, string $slug, int $receptionId): View
    {
        [$startDate, $endDate] = $this->resolvedDates($request);

        $reception = HospitalUser::query()
            ->whereHas('role', fn (Builder $q) => $q->whereIn('slug', ['receptionist', 'receptionist_opd']))
            ->findOrFail($receptionId);

        $patients = Patient::query()
            ->with(['caseType:id,case_type'])
            ->where('reception_id', $receptionId)
            ->whereDate('appointment_date', '>=', $startDate)
            ->whereDate('appointment_date', '<=', $endDate)
            ->get(['id', 'case_id', 'case_fee']);

        $buckets = [
            'new' => ['label' => 'New Case', 'count' => 0, 'total' => 0.0],
            'old' => ['label' => 'Old Case', 'count' => 0, 'total' => 0.0],
            'other' => ['label' => 'Other', 'count' => 0, 'total' => 0.0],
        ];

        foreach ($patients as $patient) {
            $key = $this->caseBucketKey($patient->caseType?->case_type);
            $buckets[$key]['count']++;
            $buckets[$key]['total'] += (float) $patient->case_fee;
        }

        $total = array_sum(array_column($buckets, 'total'));
        $count = array_sum(array_column($buckets, 'count'));

        return view('hospital.dashboard.admin_collection_show', [
            'slug' => $slug,
            'reception' => $reception,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'buckets' => $buckets,
            'total' => $total,
            'count' => $count,
        ]);
    }

    /**
     * @return Collection<int, object{id:int,name:string,count:int,total:float}>
     */
    private function receptionCollectionRows(string $startDate, string $endDate): Collection
    {
        $receptions = HospitalUser::query()
            ->whereHas('role', fn (Builder $q) => $q->whereIn('slug', ['receptionist', 'receptionist_opd']))
            ->orderBy('name')
            ->get(['id', 'name']);

        $stats = Patient::query()
            ->selectRaw('reception_id, COUNT(*) as patient_count, COALESCE(SUM(case_fee), 0) as fee_total')
            ->whereNotNull('reception_id')
            ->whereDate('appointment_date', '>=', $startDate)
            ->whereDate('appointment_date', '<=', $endDate)
            ->groupBy('reception_id')
            ->get()
            ->keyBy('reception_id');

        return $receptions->map(function (HospitalUser $rec) use ($stats) {
            $row = $stats->get($rec->id);

            return (object) [
                'id' => $rec->id,
                'name' => $rec->name,
                'count' => (int) ($row->patient_count ?? 0),
                'total' => (float) ($row->fee_total ?? 0),
            ];
        })->filter(fn ($row) => $row->count > 0 || $row->total > 0)->values();
    }

    private function caseBucketKey(?string $caseType): string
    {
        $value = strtolower(trim((string) $caseType));

        if (str_contains($value, 'old')) {
            return 'old';
        }
        if (str_contains($value, 'new')) {
            return 'new';
        }

        return 'other';
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolvedDates(Request $request): array
    {
        $today = now()->toDateString();
        $start = $request->input('start_date') ?: $today;
        $end = $request->input('end_date') ?: $start;

        if ($end < $start) {
            [$start, $end] = [$end, $start];
        }

        return [$start, $end];
    }
}
