<?php

namespace App\Http\Controllers\Hospital\Dashboard;

use App\Exports\GenericArrayExport;
use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\Patient;
use App\Services\Hospital\HospitalCollectionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Hospital admin dashboard "Total Collection" drill-down.
 * Grand total = OPD case_fee + OT payments − OT refunds (OT refund never cuts OPD).
 * Reception table remains OPD case_fee only (per receptionist).
 */
class AdminCollectionController extends Controller
{
    public function __construct(private readonly HospitalCollectionService $collectionService) {}

    public function index(Request $request, string $slug): View
    {
        [$startDate, $endDate] = $this->resolvedDates($request);

        $rows = $this->receptionCollectionRows($startDate, $endDate);
        $breakdown = $this->collectionService->summaryForDateRange($startDate, $endDate);

        return view('hospital.dashboard.admin_collection', [
            'slug' => $slug,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'rows' => $rows,
            'breakdown' => $breakdown,
            'grandTotal' => (float) $breakdown['total'],
        ]);
    }

    public function show(Request $request, string $slug, int $receptionId): View
    {
        [$startDate, $endDate] = $this->resolvedDates($request);

        $reception = $this->findReception($receptionId);
        $patients = $this->receptionPatients($receptionId, $startDate, $endDate);

        return view('hospital.dashboard.admin_collection_show', [
            'slug' => $slug,
            'reception' => $reception,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'patients' => $patients,
            'total' => (float) $patients->sum(fn (Patient $p) => (float) $p->case_fee),
            'count' => $patients->count(),
        ]);
    }

    public function export(Request $request, string $slug, int $receptionId): BinaryFileResponse
    {
        [$startDate, $endDate] = $this->resolvedDates($request);

        $reception = $this->findReception($receptionId);
        $patients = $this->receptionPatients($receptionId, $startDate, $endDate);

        $rows = $patients->values()->map(function (Patient $patient, int $i): array {
            $stage = $patient->workflowStage();

            return [
                $i + 1,
                $patient->patient_code ?: '-',
                $patient->full_name,
                $patient->age !== null && $patient->age !== '' ? $patient->age : '-',
                $patient->caseType?->case_type ?: '-',
                (float) $patient->case_fee,
                $patient->doctor?->name ? 'Dr. '.$patient->doctor->name : '-',
                $stage['label'].($stage['sub'] ? ' ('.$stage['sub'].')' : ''),
                $patient->appointment_date?->format('d M Y') ?? '-',
            ];
        })->all();

        $rows[] = ['', '', 'Total', '', '', (float) $patients->sum(fn (Patient $p) => (float) $p->case_fee), '', '', ''];

        $filename = 'Collection_'.Str::slug($reception->name).'_'.$startDate.'_to_'.$endDate.'.xlsx';

        return Excel::download(new GenericArrayExport($rows, [
            '#',
            'Patient Code',
            'Patient Name',
            'Age',
            'Case Type',
            'Case Fee',
            'Doctor',
            'Status',
            'Date',
        ]), $filename);
    }

    private function findReception(int $receptionId): HospitalUser
    {
        return HospitalUser::query()
            ->whereHas('role', fn (Builder $q) => $q->whereIn('slug', ['receptionist', 'receptionist_opd']))
            ->findOrFail($receptionId);
    }

    /**
     * @return Collection<int, Patient>
     */
    private function receptionPatients(int $receptionId, string $startDate, string $endDate): Collection
    {
        return Patient::query()
            ->with([
                'caseType:id,case_type',
                'doctor:id,name',
                'latestOtBooking',
            ])
            ->where('reception_id', $receptionId)
            ->whereDate('appointment_date', '>=', $startDate)
            ->whereDate('appointment_date', '<=', $endDate)
            ->orderBy('appointment_date')
            ->orderBy('id')
            ->get();
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

    /**
     * @return array{0:string,1:string}
     */
    private function resolvedDates(Request $request): array
    {
        $today = now()->toDateString();
        $startDate = (string) ($request->input('start_date') ?: $today);
        $endDate = (string) ($request->input('end_date') ?: $startDate);

        if ($endDate < $startDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [$startDate, $endDate];
    }
}
