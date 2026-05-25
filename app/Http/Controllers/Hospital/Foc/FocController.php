<?php

namespace App\Http\Controllers\Hospital\Foc;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hospital\Foc\StoreFocRequest;
use App\Models\Hospital\Foc;
use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Free of Charge (FOC) Controller
 *
 * Workflow:
 *   1. Doctor creates FOC request → status = pending
 *   2. Reception approves → status = accepted, accepted = true
 *   3. Reception rejects  → status = rejected, accepted = false
 */
class FocController extends Controller
{
    public function index(): View
    {
        $slug = request()->route('slug');
        $tenantId = app('tenant')->id;
        $focs = Foc::with([
            'patient' => fn ($q) => $q->withTrashed(),
            'doctor',
            'acceptedByUser',
        ])
            ->where('tenant_id', $tenantId)
            ->latest()
            ->get();

        return view('hospital.foc.index', compact('focs', 'slug'));
    }

    public function create(): View
    {
        $slug = request()->route('slug');
        $tenantId = app('tenant')->id;

        $patients = Patient::where('tenant_id', $tenantId)
            ->latest()
            ->get();

        $receptionists = HospitalUser::query()
            ->where('tenant_id', $tenantId)
            ->active()
            ->whereHas('role', fn ($q) => $q->whereIn('slug', ['receptionist', 'receptionist_opd', 'hospital_admin']))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('hospital.foc.create', compact('patients', 'receptionists', 'slug'));
    }

    public function store(StoreFocRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $tenantId = app('tenant')->id;
        $currentUserId = Auth::guard('hospital_user')->id();

        $doctorId = $data['doctor_id'] ?? $currentUserId;
        $receptionId = $data['reception_id'] ?? null;

        $alreadyRequested = Foc::query()
            ->where('tenant_id', $tenantId)
            ->where('patient_id', $data['patient_id'])
            ->whereDate('created_at', today())
            ->whereIn('status', ['pending', 'accepted'])
            ->exists();

        if ($alreadyRequested) {
            return back()->with('error', 'FOC request already exists (pending/accepted) for this patient today.');
        }

        Foc::create([
            'tenant_id' => $tenantId,
            'patient_id' => $data['patient_id'],
            'doctor_id' => $doctorId,
            'reception_id' => $receptionId,
            'foc_fee' => $data['foc_fee'],
            'reason' => $data['reason'],
            'status' => 'pending',
            'accepted' => false,
        ]);

        return back()->with('success', 'FOC request submitted successfully.');
    }

    public function accept(Request $request, string $slug, int $id): RedirectResponse
    {
        $foc = Foc::query()
            ->where('tenant_id', app('tenant')->id)
            ->findOrFail($id);

        if (! $foc->isPending()) {
            return back()->with('error', 'Only pending FOC requests can be accepted.');
        }

        $foc->update([
            'status' => 'accepted',
            'accepted' => true,
            'accepted_by' => Auth::guard('hospital_user')->id(),
            'accepted_at' => now(),
        ]);

        return back()->with('success', 'FOC accepted successfully.');
    }

    public function show(string $slug, Foc $foc): View
    {
        $foc->load('patient', 'doctor', 'reception', 'acceptedByUser');

        return view('hospital.foc.show', compact('foc', 'slug'));
    }

    /**
     * Approve a pending FOC request (reception action).
     */
    public function approve(Request $request, string $slug, Foc $foc): RedirectResponse
    {
        if (! $foc->isPending()) {
            return back()->with('error', 'Only pending FOC requests can be approved.');
        }

        $foc->update([
            'status' => 'accepted',
            'accepted' => true,
            'accepted_by' => Auth::guard('hospital_user')->id(),
            'accepted_at' => now(),
        ]);

        return redirect()
            ->route('hospital.foc.show', ['slug' => $slug, 'foc' => $foc->id])
            ->with('success', 'FOC request approved. Fee has been waived.');
    }

    /**
     * Reject a pending FOC request (reception action).
     */
    public function reject(Request $request, string $slug, Foc $foc): RedirectResponse
    {
        if (! $foc->isPending()) {
            return back()->with('error', 'Only pending FOC requests can be rejected.');
        }

        $validated = $request->validate([
            'rejected_reason' => ['required', 'string', 'max:500'],
        ]);

        $foc->update([
            'status' => 'rejected',
            'accepted' => false,
            'rejected_reason' => $validated['rejected_reason'],
        ]);

        return redirect()
            ->route('hospital.foc.show', ['slug' => $slug, 'foc' => $foc->id])
            ->with('success', 'FOC request rejected.');
    }
}
