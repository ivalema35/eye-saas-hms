<?php

namespace App\Http\Controllers\Hospital\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\OT\OtBooking;
use App\Models\Hospital\OT\OtPreOp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Doctor dashboard OT drill-down — bookings by operating doctor + date range.
 * Phase 2: Assign OT Assistant (agree OT) / Send to Account (surgery_refused).
 */
class DoctorOtListController extends Controller
{
    public function index(Request $request, string $slug): View
    {
        [$startDate, $endDate] = $this->resolvedDates($request);

        $doctorId = $request->filled('doctor_id') ? (int) $request->input('doctor_id') : null;

        $query = OtBooking::query()
            ->with([
                'patient:id,first_name,middle_name,last_name,contact_no,age',
                'otDoctor:id,name',
                'otAssistant:id,name',
                'preOp',
            ])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween(DB::raw('DATE(surgery_date)'), [$startDate, $endDate])
                    ->orWhere(function ($nullDate) use ($startDate, $endDate) {
                        $today = now()->toDateString();
                        if ($startDate <= $today && $endDate >= $today) {
                            $nullDate->whereNull('surgery_date')
                                ->whereNotIn('ot_status', [
                                    OtBooking::STATUS_OPERATED,
                                    OtBooking::STATUS_DISCHARGED,
                                    OtBooking::STATUS_SURGERY_REFUSED,
                                ]);
                        }
                    });
            });

        if ($doctorId) {
            $query->where(function ($q) use ($doctorId) {
                $q->where('ot_doctor_id', $doctorId)
                    ->orWhere(function ($inner) use ($doctorId) {
                        $inner->whereNull('ot_doctor_id')->where('booked_by', $doctorId);
                    });
            });
        }

        $bookings = $query
            ->orderByDesc('surgery_date')
            ->orderByDesc('id')
            ->paginate((int) config('app.pagination_limit', 25))
            ->withQueryString();

        $doctor = $doctorId
            ? HospitalUser::query()->where('id', $doctorId)->first(['id', 'name'])
            : null;

        $doctors = HospitalUser::query()
            ->whereHas('role', fn ($q) => $q->whereIn('slug', ['doctor', 'ot_doctor']))
            ->orderBy('name')
            ->get(['id', 'name']);

        $tenantId = (int) app('tenant')->id;
        $otAssistants = HospitalUser::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->whereHas('role', fn ($q) => $q->where('slug', 'ot_assistant'))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('hospital.dashboard.doctor_ot_list', [
            'slug' => $slug,
            'bookings' => $bookings,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'doctor' => $doctor,
            'doctorId' => $doctorId,
            'doctors' => $doctors,
            'otAssistants' => $otAssistants,
        ]);
    }

    /**
     * Doctor agrees OT after consult → assign OT Assistant and mark Ready.
     */
    public function assignAssistant(Request $request, string $slug, int $bookingId): RedirectResponse
    {
        $tenantId = (int) app('tenant')->id;
        $booking = OtBooking::query()
            ->with('preOp')
            ->whereKey($bookingId)
            ->firstOrFail();

        abort_unless((int) $booking->tenant_id === $tenantId, 403);
        $this->assertCanManage($booking);

        if (! $booking->isDoctorConsultationPending()) {
            return redirect()->back()->with('error', 'This patient is not awaiting doctor consultation actions.');
        }

        $validated = $request->validate([
            'ot_assistant_id' => [
                'required',
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
            'ot_assistant_id.required' => 'Select an OT Assistant to proceed with surgery.',
        ]);

        DB::transaction(function () use ($booking, $validated, $tenantId): void {
            OtPreOp::query()->updateOrCreate(
                ['tenant_id' => $tenantId, 'ot_booking_id' => $booking->id],
                [
                    'pre_op_status' => OtPreOp::STATUS_READY_FOR_SURGERY,
                    'entered_by' => (int) auth('hospital_user')->id(),
                ]
            );

            $booking->update([
                'ot_assistant_id' => (int) $validated['ot_assistant_id'],
                'ot_status' => OtBooking::STATUS_READY,
                'attended_at' => $booking->attended_at ?? now(),
            ]);
        });

        return redirect()
            ->back()
            ->with('success', 'OT Assistant assigned. Patient is Ready for OT.');
    }

    /**
     * Patient refuses surgery → Accounts queue for full refund (Phase 3 records refund).
     */
    public function refuseSurgery(string $slug, int $bookingId): RedirectResponse
    {
        $tenantId = (int) app('tenant')->id;
        $booking = OtBooking::query()
            ->with('preOp')
            ->whereKey($bookingId)
            ->firstOrFail();

        abort_unless((int) $booking->tenant_id === $tenantId, 403);
        $this->assertCanManage($booking);

        if (! $booking->isDoctorConsultationPending()) {
            return redirect()->back()->with('error', 'This patient is not awaiting doctor consultation actions.');
        }

        $booking->update([
            'ot_status' => OtBooking::STATUS_SURGERY_REFUSED,
            'ot_assistant_id' => null,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Patient refused OT. Sent to Accounts for refund (status: surgery_refused).');
    }

    private function assertCanManage(OtBooking $booking): void
    {
        $user = auth('hospital_user')->user();
        if (! $user) {
            abort(403);
        }

        if (method_exists($user, 'isSuperUser') && $user->isSuperUser()) {
            return;
        }

        $slug = $user->role?->slug;
        if (in_array($slug, ['hospital_admin', 'admin'], true)) {
            return;
        }

        if ((int) $booking->ot_doctor_id === (int) $user->id) {
            return;
        }

        abort(403, 'You can only act on patients assigned to you.');
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
