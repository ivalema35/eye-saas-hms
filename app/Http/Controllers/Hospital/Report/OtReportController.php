<?php

/**
 * OtReportController.php
 *
 * PURPOSE: OT Workflow Upgrade — Phase 8 (Reports Module & Management
 *          Dashboards). One generic controller serves all 14 OT report types
 *          (Operational Registers, Clinical Reports, Financial Reports) —
 *          mirrors the codebase's established generic-controller pattern
 *          (BasicMasterController/DetailMasterController, see PRD_MASTER.md §8.9)
 *          rather than one controller+export class per report.
 *          "Registration Register" is OT-linked OPD check-ins + OT appointment arrivals
 *          (Phase B2 — docs/OT_1.0_REMAINING_PRD.md).
 *          See docs/OT_WORKFLOW_UPGRADE_PRD.md §8.
 *
 * PERMISSIONS: reports.view (index/show), reports.export (export)
 */

namespace App\Http\Controllers\Hospital\Report;

use App\Exports\GenericArrayExport;
use App\Http\Controllers\Controller;
use App\Models\Hospital\Dosage;
use App\Models\Hospital\ExamPrescription;
use App\Models\Hospital\MedicineRoute;
use App\Models\Hospital\OT\LensInventory;
use App\Models\Hospital\OT\OtAppointment;
use App\Models\Hospital\OT\OtBooking;
use App\Models\Hospital\OT\OtCounselling;
use App\Models\Hospital\OT\OtLensDetail;
use App\Models\Hospital\OT\OtPayment;
use App\Models\Hospital\OT\OtSurgery;
use App\Models\Hospital\Patient;
use App\Models\Hospital\PrimaryExamination;
use App\Models\Hospital\SecondaryExamination;
use App\Services\Auth\RolePermissionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OtReportController extends Controller
{
    public function __construct(private readonly RolePermissionService $permissionService)
    {
    }

    /** @var array<string, array{group: string, label: string}> */
    protected const REPORTS = [
        // Operational Registers
        'appointments' => ['group' => 'Operational Registers', 'label' => 'Appointment Register'],
        'registration' => ['group' => 'Operational Registers', 'label' => 'Registration Register'],
        'doctor-consultation' => ['group' => 'Operational Registers', 'label' => 'Doctor Consultation Register'],
        'counselling' => ['group' => 'Operational Registers', 'label' => 'Counselling Register'],
        'billing' => ['group' => 'Operational Registers', 'label' => 'Billing Register'],
        'ot' => ['group' => 'Operational Registers', 'label' => 'OT Register'],
        'discharge' => ['group' => 'Operational Registers', 'label' => 'Discharge Register'],
        // Clinical Reports
        'hospital-report' => ['group' => 'Clinical Reports', 'label' => 'Hospital Report'],
        'surgery-wise' => ['group' => 'Clinical Reports', 'label' => 'Surgery-wise Report'],
        'doctor-wise' => ['group' => 'Clinical Reports', 'label' => 'Doctor-wise Report'],
        'lens-usage' => ['group' => 'Clinical Reports', 'label' => 'Lens Usage Report'],
        'complications' => ['group' => 'Clinical Reports', 'label' => 'Complication Report'],
        // Financial Reports
        'daily-collection' => ['group' => 'Financial Reports', 'label' => 'Daily Collection'],
        'monthly-revenue' => ['group' => 'Financial Reports', 'label' => 'Monthly Revenue'],
        'package-wise' => ['group' => 'Financial Reports', 'label' => 'Package-wise Revenue'],
        'pending-payments' => ['group' => 'Financial Reports', 'label' => 'Pending Payments'],
    ];

    public function index(string $slug): View
    {
        $this->authorizePermission('reports.view');

        $reportsByGroup = collect(self::REPORTS)
            ->map(fn (array $meta, string $key) => [...$meta, 'key' => $key])
            ->groupBy('group');

        return view('hospital.reports.ot.index', [
            'slug' => $slug,
            'reportsByGroup' => $reportsByGroup,
        ]);
    }

    public function show(Request $request, string $slug, string $type): View
    {
        $this->authorizePermission('reports.view');

        abort_unless(array_key_exists($type, self::REPORTS), 404);

        [$from, $to] = $this->resolveDateRange($request);
        [$headings, $rows] = $this->buildReport($type, $from, $to);

        return view('hospital.reports.ot.show', [
            'slug' => $slug,
            'type' => $type,
            'label' => self::REPORTS[$type]['label'],
            'headings' => $headings,
            'rows' => $rows,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function export(Request $request, string $slug, string $type): BinaryFileResponse
    {
        $this->authorizePermission('reports.export');

        abort_unless(array_key_exists($type, self::REPORTS), 404);

        [$from, $to] = $this->resolveDateRange($request);
        [$headings, $rows] = $this->buildReport($type, $from, $to);

        $filename = str_replace('-', '_', $type).'_'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(new GenericArrayExport($rows, $headings), $filename);
    }

    public function exportPdf(Request $request, string $slug, string $type)
    {
        $this->authorizePermission('reports.export');

        abort_unless(array_key_exists($type, self::REPORTS), 404);

        [$from, $to] = $this->resolveDateRange($request);
        [$headings, $rows] = $this->buildReport($type, $from, $to);

        $filename = str_replace('-', '_', $type).'_'.now()->format('Y-m-d').'.pdf';

        $pdf = Pdf::loadView('hospital.reports.ot.pdf', [
            'label' => self::REPORTS[$type]['label'],
            'headings' => $headings,
            'rows' => $rows,
            'from' => $from,
            'to' => $to,
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }

    /**
     * Opens the patient's full exam prescription in the same browser print layout
     * used by Primary/Secondary exam (matches on-screen prescription UI).
     * Prefer secondary when it exists (carries P + S doctor names + full chart).
     */
    public function patientPrescriptionPdf(Request $request, string $slug, int $patient): View
    {
        $this->authorizePermission('reports.view');

        $patientModel = Patient::findOrFail($patient);
        $tenant = app('tenant');

        $secondaryExam = SecondaryExamination::where('patient_id', $patient)
            ->where('tenant_id', $tenant->id)
            ->with('doctor')
            ->first();

        $primaryExam = PrimaryExamination::where('patient_id', $patient)
            ->where('tenant_id', $tenant->id)
            ->with('prescriptions.medicine.medicineType', 'prescriptions.dosage', 'doctor')
            ->first();

        abort_if(! $primaryExam && ! $secondaryExam, 404, 'No examination found for this patient.');

        $primaryDoctorName = $primaryExam?->doctor?->name;
        $secondaryDoctorName = $secondaryExam?->doctor?->name;

        $diagnosisMasters = collect(DB::table('tbl_master_diagnosis')
            ->where('tenant_id', $tenant->id)
            ->orderBy('id')
            ->get(['id', DB::raw('value as diagnosis')]));

        $complaintMasters = collect(DB::table('chief_complaints')
            ->where('tenant_id', $tenant->id)
            ->orderBy('id')
            ->get(['id', DB::raw('value as complaint')]));

        $kcoMasters = collect(DB::table('kcos')
            ->where('tenant_id', $tenant->id)
            ->orderBy('id')
            ->get(['id', DB::raw('value as kco')]));

        $backUrl = route('hospital.reports.ot.show', [
            'slug' => $slug,
            'type' => 'hospital-report',
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ]);

        if ($secondaryExam) {
            $dosages = Dosage::orderBy('dosage')->get(['id', 'dosage']);
            $routes = MedicineRoute::where('tenant_id', $tenant->id)->orderBy('name')->get(['id', 'name']);

            return view('hospital.exam.secondary_print', [
                'patient' => $patientModel,
                'exam' => $secondaryExam,
                'tenant' => $tenant,
                'slug' => $slug,
                'diagnosisMasters' => $diagnosisMasters,
                'complaintMasters' => $complaintMasters,
                'kcoMasters' => $kcoMasters,
                'dosages' => $dosages,
                'routes' => $routes,
                'primaryDoctorName' => $primaryDoctorName,
                'secondaryDoctorName' => $secondaryDoctorName,
                'backUrl' => $backUrl,
            ]);
        }

        $adviceMasters = collect(DB::table('tbl_master_advice')
            ->where('tenant_id', $tenant->id)
            ->orderBy('id')
            ->get(['id', DB::raw('value as advice')]));

        return view('hospital.exam.print', [
            'patient' => $patientModel,
            'exam' => $primaryExam,
            'tenant' => $tenant,
            'slug' => $slug,
            'diagnosisMasters' => $diagnosisMasters,
            'adviceMasters' => $adviceMasters,
            'complaintMasters' => $complaintMasters,
            'kcoMasters' => $kcoMasters,
            'primaryDoctorName' => $primaryDoctorName,
            'secondaryDoctorName' => $secondaryDoctorName,
            'backUrl' => $backUrl,
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function resolveDateRange(Request $request): array
    {
        $from = $request->filled('from') ? $request->string('from')->toString() : now()->startOfMonth()->toDateString();
        $to = $request->filled('to') ? $request->string('to')->toString() : now()->toDateString();

        return [$from, $to];
    }

    /**
     * @return array{0: list<string>, 1: list<array<int, mixed>>}
     */
    protected function buildReport(string $type, string $from, string $to): array
    {
        $tenantId = (int) app('tenant')->id;
        $toEnd = Carbon::parse($to)->endOfDay();
        $fromStart = Carbon::parse($from)->startOfDay();

        return match ($type) {
            'hospital-report' => $this->hospitalReport($tenantId, $fromStart, $toEnd),
            'appointments' => $this->appointmentsReport($tenantId, $fromStart, $toEnd),
            'registration' => $this->registrationReport($tenantId, $fromStart, $toEnd),
            'doctor-consultation' => $this->doctorConsultationReport($tenantId, $fromStart, $toEnd),
            'counselling' => $this->counsellingReport($tenantId, $fromStart, $toEnd),
            'billing' => $this->billingReport($tenantId, $fromStart, $toEnd),
            'ot' => $this->otRegisterReport($tenantId, $fromStart, $toEnd),
            'discharge' => $this->dischargeReport($tenantId, $fromStart, $toEnd),
            'surgery-wise' => $this->surgeryWiseReport($tenantId, $fromStart, $toEnd),
            'doctor-wise' => $this->doctorWiseReport($tenantId, $fromStart, $toEnd),
            'lens-usage' => $this->lensUsageReport($tenantId, $fromStart, $toEnd),
            'complications' => $this->complicationsReport($tenantId, $fromStart, $toEnd),
            'daily-collection' => $this->dailyCollectionReport($tenantId, $fromStart, $toEnd),
            'monthly-revenue' => $this->monthlyRevenueReport($tenantId, $fromStart, $toEnd),
            'package-wise' => $this->packageWiseReport($tenantId, $fromStart, $toEnd),
            'pending-payments' => $this->pendingPaymentsReport($tenantId, $fromStart, $toEnd),
            default => [[], []],
        };
    }

    /**
     * Hospital Report (Clinical Reports) — full patient journey: registration
     * (receptionist) time, demographics, primary/secondary exam start times,
     * and medicines prescribed across both exams.
     */
    private function hospitalReport(int $tenantId, Carbon $from, Carbon $to): array
    {
        $rows = Patient::query()
            ->with([
                'doctor:id,name',
                'masterCity.district',
                'masterCity.state',
                'location',
                'primaryExamination.prescriptions.medicine',
                'secondaryExamination',
            ])
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$from, $to])
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Patient $p) {
                $registerType = in_array((string) $p->type, ['phone', '1'], true) ? 'Phone' : 'Walk-in';

                $primaryMeds = collect($p->primaryExamination?->prescriptions ?? [])
                    ->map(fn (ExamPrescription $rx) => $rx->medicine?->brand_name ?: $rx->medicine?->name)
                    ->filter()
                    ->values();

                $secondaryMeds = collect($p->secondaryExamination?->exam_data['rx'] ?? [])
                    ->map(fn (array $rx) => $rx['name'] ?? null)
                    ->filter()
                    ->values();

                $medicines = $primaryMeds->concat($secondaryMeds)->unique()->implode(', ');

                return [
                    optional($p->created_at)->format('d M Y h:i A') ?? '-',
                    $p->full_name,
                    $p->age ?: '-',
                    $p->cityName ?: '-',
                    $p->doctor?->name ?? '-',
                    $registerType,
                    optional($p->primaryExamination?->examined_at)->format('d M Y h:i A') ?? '-',
                    optional($p->secondaryExamination?->examined_at)->format('d M Y h:i A') ?? '-',
                    $medicines !== '' ? $medicines : '-',
                    $p->id,
                ];
            })->all();

        return [
            ['Start Time', 'Patient Name', 'Age', 'City', 'Doctor', 'Register Type', 'Primary Exam Time', 'Secondary Exam Time', 'Medicines', 'Patient ID'],
            $rows,
        ];
    }

    /**
     * OT-linked OPD registration / check-in register (PDF Operational Reports).
     * Patients checked in during the range who have (or get) an OT booking,
     * plus OT appointment arrivals completed/confirmed in the same window.
     */
    private function registrationReport(int $tenantId, Carbon $from, Carbon $to): array
    {
        $checkInRows = Patient::query()
            ->with([
                'doctor:id,name',
                'otBookings' => fn ($q) => $q->orderByDesc('id')->limit(1),
            ])
            ->where('tenant_id', $tenantId)
            ->whereBetween('checked_in_at', [$from, $to])
            ->whereHas('otBookings')
            ->orderByDesc('checked_in_at')
            ->get()
            ->map(function (Patient $p) {
                $booking = $p->otBookings->first();

                return [
                    $p->patient_code,
                    $p->full_name,
                    $p->contact_no ?? '-',
                    optional($p->checked_in_at)->format('d M Y h:i A') ?? '-',
                    $p->doctor?->name ?? '-',
                    'OPD Check-in',
                    $booking?->ot_type ?? '-',
                    $booking ? str_replace('_', ' ', (string) $booking->ot_status) : '-',
                ];
            });

        $appointmentRows = OtAppointment::query()
            ->with(['doctor:id,name'])
            ->where('tenant_id', $tenantId)
            ->whereBetween('appointment_date', [$from->toDateString(), $to->toDateString()])
            ->whereIn('status', [
                OtAppointment::STATUS_CONFIRMED,
                OtAppointment::STATUS_COMPLETED,
                OtAppointment::STATUS_BOOKED,
            ])
            ->orderByDesc('appointment_date')
            ->get()
            ->map(fn (OtAppointment $a) => [
                $a->appointment_number,
                $a->patient_name,
                $a->mobile_no ?? '-',
                optional($a->appointment_date)->format('d M Y').($a->appointment_time ? ' '.substr((string) $a->appointment_time, 0, 5) : ''),
                $a->doctor?->name ?? '-',
                'OT Appointment ('.str_replace('_', ' ', $a->appointment_type).')',
                '-',
                ucfirst($a->status),
            ]);

        $rows = $checkInRows->concat($appointmentRows)->values()->all();

        return [
            ['UHID / Appt #', 'Patient', 'Mobile', 'Registered / Check-in', 'Doctor', 'Source', 'Surgery Type', 'Status'],
            $rows,
        ];
    }

    private function appointmentsReport(int $tenantId, Carbon $from, Carbon $to): array
    {
        $rows = OtAppointment::query()
            ->with(['doctor:id,name'])
            ->where('tenant_id', $tenantId)
            ->whereBetween('appointment_date', [$from->toDateString(), $to->toDateString()])
            ->orderByDesc('appointment_date')
            ->get()
            ->map(fn (OtAppointment $a) => [
                $a->appointment_number,
                $a->patient_name,
                $a->mobile_no,
                str_replace('_', ' ', $a->appointment_type),
                optional($a->appointment_date)->format('d M Y'),
                $a->doctor?->name ?? '-',
                ucfirst($a->status),
            ])->all();

        return [['Appt #', 'Patient', 'Mobile', 'Type', 'Date', 'Doctor', 'Status'], $rows];
    }

    private function doctorConsultationReport(int $tenantId, Carbon $from, Carbon $to): array
    {
        $rows = Patient::query()
            ->with(['doctor:id,name'])
            ->where('tenant_id', $tenantId)
            ->whereBetween('appointment_date', [$from->toDateString(), $to->toDateString()])
            ->whereNotNull('primary_done_at')
            ->orderByDesc('appointment_date')
            ->get()
            ->map(fn (Patient $p) => [
                $p->patient_code,
                $p->full_name,
                $p->doctor?->name ?? '-',
                optional($p->primary_done_at)->format('d M Y h:i A') ?? '-',
                optional($p->secondary_done_at)->format('d M Y h:i A') ?? '-',
            ])->all();

        return [['Patient Code', 'Patient', 'Doctor', 'Primary Done', 'Secondary Done'], $rows];
    }

    private function counsellingReport(int $tenantId, Carbon $from, Carbon $to): array
    {
        $rows = OtCounselling::query()
            ->with(['booking.patient:id,first_name,middle_name,last_name,patient_code', 'counsellor:id,name'])
            ->where('tenant_id', $tenantId)
            ->whereBetween('counselled_at', [$from, $to])
            ->orderByDesc('counselled_at')
            ->get()
            ->map(fn (OtCounselling $c) => [
                $c->booking?->patient?->full_name ?? '-',
                $c->diagnosis ?? '-',
                $c->lens_option ?? '-',
                number_format((float) ($c->package_amount ?? 0), 2),
                $c->counsellor?->name ?? '-',
                optional($c->counselled_at)->format('d M Y h:i A') ?? '-',
            ])->all();

        return [['Patient', 'Diagnosis', 'Lens Option', 'Package Amount', 'Counsellor', 'Counselled At'], $rows];
    }

    private function billingReport(int $tenantId, Carbon $from, Carbon $to): array
    {
        $rows = OtPayment::query()
            ->with(['booking.patient:id,first_name,middle_name,last_name,patient_code', 'recordedBy:id,name'])
            ->where('tenant_id', $tenantId)
            ->whereBetween('paid_at', [$from, $to])
            ->orderByDesc('paid_at')
            ->get()
            ->map(fn (OtPayment $p) => [
                $p->booking?->patient?->full_name ?? '-',
                $p->receipt_number ?? '-',
                number_format((float) $p->package_amount, 2),
                strtoupper((string) $p->payment_mode),
                $p->recordedBy?->name ?? '-',
                optional($p->paid_at)->format('d M Y h:i A') ?? '-',
            ])->all();

        return [['Patient', 'Receipt #', 'Amount Paid', 'Mode', 'Recorded By', 'Paid At'], $rows];
    }

    private function otRegisterReport(int $tenantId, Carbon $from, Carbon $to): array
    {
        $rows = OtSurgery::query()
            ->with(['booking.patient:id,first_name,middle_name,last_name,patient_code', 'operatedBy:id,name', 'assistant:id,name'])
            ->where('tenant_id', $tenantId)
            ->whereBetween('surgery_at', [$from, $to])
            ->orderByDesc('surgery_at')
            ->get()
            ->map(fn (OtSurgery $s) => [
                $s->booking?->patient?->full_name ?? '-',
                $s->surgery_name,
                $s->eye_operated,
                $s->operatedBy?->name ?? '-',
                $s->assistant?->name ?? '-',
                optional($s->surgery_at)->format('d M Y h:i A') ?? '-',
                ucfirst((string) $s->surgery_status),
            ])->all();

        return [['Patient', 'Surgery', 'Eye', 'Surgeon', 'Assistant', 'Date/Time', 'Status'], $rows];
    }

    private function dischargeReport(int $tenantId, Carbon $from, Carbon $to): array
    {
        $rows = DB::table('ot_invoices')
            ->join('ot_bookings', 'ot_bookings.id', '=', 'ot_invoices.ot_booking_id')
            ->join('patients', 'patients.id', '=', 'ot_bookings.patient_id')
            ->where('ot_invoices.tenant_id', $tenantId)
            ->whereNull('ot_bookings.deleted_at')
            ->whereBetween('ot_invoices.created_at', [$from, $to])
            ->orderByDesc('ot_invoices.created_at')
            ->select('patients.first_name', 'patients.last_name', 'ot_invoices.invoice_number', 'ot_invoices.net_amount', 'ot_invoices.follow_up_date', 'ot_bookings.discharged_at')
            ->get()
            ->map(fn ($row) => [
                trim($row->first_name.' '.$row->last_name),
                $row->invoice_number,
                number_format((float) $row->net_amount, 2),
                $row->follow_up_date ? Carbon::parse($row->follow_up_date)->format('d M Y') : '-',
                $row->discharged_at ? Carbon::parse($row->discharged_at)->format('d M Y h:i A') : '-',
            ])->all();

        return [['Patient', 'Invoice #', 'Net Amount', 'Follow-up Date', 'Discharged At'], $rows];
    }

    private function surgeryWiseReport(int $tenantId, Carbon $from, Carbon $to): array
    {
        $rows = OtSurgery::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('surgery_at', [$from, $to])
            ->selectRaw('surgery_name, COUNT(*) as total, SUM(CASE WHEN complication_status != "none" THEN 1 ELSE 0 END) as complications')
            ->groupBy('surgery_name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [$row->surgery_name, $row->total, $row->complications])
            ->all();

        return [['Surgery', 'Total Performed', 'With Complications'], $rows];
    }

    private function doctorWiseReport(int $tenantId, Carbon $from, Carbon $to): array
    {
        $rows = OtSurgery::query()
            ->with('operatedBy:id,name')
            ->where('tenant_id', $tenantId)
            ->whereBetween('surgery_at', [$from, $to])
            ->get()
            ->groupBy('operated_by')
            ->map(function ($group) {
                $first = $group->first();

                return [
                    $first->operatedBy?->name ?? '-',
                    $group->count(),
                    $group->where('complication_status', '!=', 'none')->count(),
                ];
            })
            ->values()
            ->sortByDesc(fn ($row) => $row[1])
            ->values()
            ->all();

        return [['Doctor', 'Surgeries Performed', 'With Complications'], $rows];
    }

    private function lensUsageReport(int $tenantId, Carbon $from, Carbon $to): array
    {
        $rows = OtLensDetail::query()
            ->with(['booking.patient:id,first_name,middle_name,last_name', 'inventoryItem:id,lens_code,manufacturer'])
            ->where('tenant_id', $tenantId)
            ->where('is_implanted', true)
            ->whereBetween('implanted_at', [$from, $to])
            ->orderByDesc('implanted_at')
            ->get()
            ->map(fn (OtLensDetail $l) => [
                $l->booking?->patient?->full_name ?? '-',
                $l->lens_name,
                $l->manufacturer ?? $l->inventoryItem?->manufacturer ?? '-',
                $l->lens_type,
                '+'.number_format((float) $l->lens_power, 2).'D',
                $l->inventoryItem?->lens_code ?? '-',
                optional($l->implanted_at)->format('d M Y') ?? '-',
            ])->all();

        return [['Patient', 'Lens', 'Manufacturer', 'Type', 'Power', 'Inventory Code', 'Implanted On'], $rows];
    }

    private function complicationsReport(int $tenantId, Carbon $from, Carbon $to): array
    {
        $rows = OtSurgery::query()
            ->with(['booking.patient:id,first_name,middle_name,last_name', 'operatedBy:id,name'])
            ->where('tenant_id', $tenantId)
            ->where('complication_status', '!=', 'none')
            ->whereBetween('surgery_at', [$from, $to])
            ->orderByDesc('surgery_at')
            ->get()
            ->map(fn (OtSurgery $s) => [
                $s->booking?->patient?->full_name ?? '-',
                $s->surgery_name,
                $s->operatedBy?->name ?? '-',
                ucfirst((string) $s->complication_status),
                $s->complication_notes ?? $s->complication ?? '-',
                optional($s->surgery_at)->format('d M Y') ?? '-',
            ])->all();

        return [['Patient', 'Surgery', 'Doctor', 'Severity', 'Notes', 'Date'], $rows];
    }

    private function dailyCollectionReport(int $tenantId, Carbon $from, Carbon $to): array
    {
        $rows = OtPayment::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('paid_at', [$from, $to])
            ->selectRaw('DATE(paid_at) as day, SUM(package_amount) as total, COUNT(*) as txns')
            ->groupBy('day')
            ->orderByDesc('day')
            ->get()
            ->map(fn ($row) => [Carbon::parse($row->day)->format('d M Y'), number_format((float) $row->total, 2), $row->txns])
            ->all();

        return [['Date', 'Total Collected', 'Transactions'], $rows];
    }

    private function monthlyRevenueReport(int $tenantId, Carbon $from, Carbon $to): array
    {
        $rows = OtPayment::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('paid_at', [$from, $to])
            ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as ym, SUM(package_amount) as total, COUNT(*) as txns")
            ->groupBy('ym')
            ->orderByDesc('ym')
            ->get()
            ->map(fn ($row) => [Carbon::createFromFormat('Y-m', $row->ym)->format('M Y'), number_format((float) $row->total, 2), $row->txns])
            ->all();

        return [['Month', 'Total Revenue', 'Transactions'], $rows];
    }

    private function packageWiseReport(int $tenantId, Carbon $from, Carbon $to): array
    {
        $rows = OtCounselling::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('counselled_at', [$from, $to])
            ->whereNotNull('package_name')
            ->selectRaw('package_name, COUNT(*) as total_bookings, SUM(package_amount) as total_revenue')
            ->groupBy('package_name')
            ->orderByDesc('total_revenue')
            ->get()
            ->map(fn ($row) => [$row->package_name, $row->total_bookings, number_format((float) $row->total_revenue, 2)])
            ->all();

        return [['Package', 'Bookings', 'Total Revenue'], $rows];
    }

    private function pendingPaymentsReport(int $tenantId, Carbon $from, Carbon $to): array
    {
        $rows = OtBooking::query()
            ->with(['patient:id,first_name,middle_name,last_name,contact_no', 'payments'])
            ->where('tenant_id', $tenantId)
            ->whereBetween('surgery_date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->filter(fn (OtBooking $b) => $b->payment_status !== 'paid')
            ->map(fn (OtBooking $b) => [
                $b->patient?->full_name ?? '-',
                $b->patient?->contact_no ?? '-',
                number_format((float) ($b->package_amount ?? 0), 2),
                number_format((float) $b->remaining_balance, 2),
                ucfirst(str_replace('_', ' ', $b->payment_status)),
            ])
            ->values()
            ->all();

        return [['Patient', 'Mobile', 'Package Amount', 'Remaining Balance', 'Status'], $rows];
    }

    private function authorizePermission(string $permissionKey): void
    {
        abort_unless($this->permissionService->can($permissionKey), 403, 'Access denied.');
    }
}
