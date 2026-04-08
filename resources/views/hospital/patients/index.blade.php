@extends('hospital.layouts.app')
@section('title', 'Patients')
@section('page-header', $showAll ? 'All Patients' : "Today's Patients")

@section('page-actions')
    <a href="{{ route('hospital.patients.create', ['slug' => $slug]) }}" class="hms-btn hms-btn-primary">
        <i class="bi bi-person-plus"></i> Walk-in
    </a>
    <a href="{{ route('hospital.patients.create-phone', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">
        <i class="bi bi-telephone"></i> Phone Appt
    </a>
@endsection

@section('content')
<div class="card premium-card border-0 mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0 fw-bold" style="color: var(--color-primary);">
            <i class="bi bi-people-fill me-2"></i>
            {{ $showAll ? 'All Patients' : "Today's Patients" }}
        </h5>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <form method="GET" action="{{ route('hospital.patients.index', ['slug' => $slug]) }}" class="d-flex align-items-center gap-2">
                @if($showAll)
                    <input type="hidden" name="all" value="1">
                @endif
                <div class="input-group" style="min-width:260px">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control border-start-0 ps-0"
                           placeholder="Search name, MRD, contact...">
                </div>
                <button type="submit" class="btn btn-primary text-nowrap">
                    <i class="bi bi-search"></i> Search
                </button>
            </form>

            <div class="btn-group" role="group" aria-label="Patient range toggle">
                <a href="{{ route('hospital.patients.index', ['slug' => $slug]) }}"
                   class="btn {{ !$showAll ? 'btn-primary' : 'btn-outline-secondary' }}">
                    <i class="bi bi-calendar-day"></i> Today
                </a>
                <a href="{{ route('hospital.patients.index', ['slug' => $slug, 'all' => 1]) }}"
                   class="btn {{ $showAll ? 'btn-primary' : 'btn-outline-secondary' }}">
                    <i class="bi bi-list-ul"></i> All
                </a>
            </div>

            <span class="badge text-bg-light border">{{ $patients->total() }} total</span>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table premium-table mb-0 table-hover align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>MRD</th>
                        <th>Patient Name</th>
                        <th>Age / Gender</th>
                        <th>Contact</th>
                        <th>Doctor</th>
                        <th>Fee (₹)</th>
                        <th>Case Type</th>
                        <th>Status</th>
                        <th>Time</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($patients as $i => $p)
                        @php
                            $primary = $p->primaryExamination;
                            $secondary = $p->secondaryExamination;

                            $isPrimaryDone = $primary !== null;
                            $isSecondaryDone = $secondary !== null;

                            $unlockTimeMs = null;
                            $isDilating = false;

                            if ($primary && ($primary->exam_data['dilate'] ?? 'No') === 'Yes' && $primary->dilation_time && ! $isSecondaryDone) {
                                $expectedUnlock = $primary->updated_at->copy()->addMinutes($primary->dilation_time);

                                if (now()->lessThan($expectedUnlock)) {
                                    $isDilating = true;
                                    $unlockTimeMs = $expectedUnlock->timestamp * 1000;
                                }
                            }
                        @endphp
                        <tr>
                            <td>{{ $patients->firstItem() + $i }}</td>
                            <td><span class="fw-semibold" style="color: var(--color-secondary);">{{ $p->patient_code }}</span></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center"
                                         style="width:35px;height:35px;color:var(--color-primary)">
                                        <i class="bi bi-person-fill fs-5"></i>
                                    </div>
                                    <div>
                                        <span class="d-block fw-medium text-dark">{{ $p->full_name }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $p->age }}y / {{ ucfirst($p->gender) }}</td>
                            <td>{{ $p->contact_no }}</td>
                            <td>{{ $p->doctor?->name ?? '—' }}</td>
                            <td>{{ number_format($p->case_fee, 2) }}</td>
                            <td><span class="text-muted">{{ ucfirst($p->type) }}</span></td>
                            <td>
                                @if($p->secondary_done_at)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">
                                        <i class="bi bi-check-circle-fill me-1"></i> Secondary Done
                                    </span>
                                @elseif($p->primary_done_at)
                                    <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1 rounded-pill">
                                        <i class="bi bi-check-circle-fill me-1"></i> Primary Done
                                    </span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 rounded-pill">
                                        <i class="bi bi-hourglass-split me-1"></i> Waiting
                                    </span>
                                @endif
                            </td>
                            <td>{{ $p->created_at->format('h:i A') }}</td>
                            <td>
                                <div class="d-flex flex-wrap gap-2 justify-content-start align-items-center">
                                    <a href="{{ route('hospital.patients.history', ['slug' => $slug, 'search' => $p->patient_code]) }}" class="btn btn-sm btn-outline-info" title="View History">
                                        <i class="fas fa-history"></i> History
                                    </a>

                                    @if($isPrimaryDone)
                                        <button class="btn btn-sm btn-light border text-muted" disabled title="Primary Completed">
                                            <i class="fas fa-check-circle text-success"></i> Primary Done
                                        </button>
                                    @else
                                        <a href="{{ route('hospital.exam.primary.show', ['slug' => $slug, 'id' => $p->id]) }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> Primary Exam
                                        </a>
                                    @endif

                                    @if(! $isPrimaryDone)
                                        <button class="btn btn-sm btn-light border text-muted" disabled title="Complete Primary Exam First">
                                            <i class="fas fa-lock"></i> Secondary Locked
                                        </button>
                                    @elseif($isSecondaryDone)
                                        <button class="btn btn-sm btn-light border text-muted" disabled title="Secondary Completed">
                                            <i class="fas fa-check-circle text-success"></i> Sec. Done
                                        </button>
                                    @elseif($isDilating)
                                        <a href="{{ route('hospital.exam.secondary.show', ['slug' => $slug, 'id' => $p->id]) }}"
                                           class="btn btn-sm btn-warning disabled dilation-timer-btn"
                                           data-unlock-time="{{ $unlockTimeMs }}"
                                           id="sec-btn-{{ $p->id }}"
                                           onclick="return false;"
                                           style="pointer-events: none; opacity: 0.8;">
                                            <i class="fas fa-clock"></i> <span class="timer-text">Wait: --:--</span>
                                        </a>
                                    @else
                                        <a href="{{ route('hospital.exam.secondary.show', ['slug' => $slug, 'id' => $p->id]) }}" class="btn btn-sm btn-success">
                                            <i class="fas fa-stethoscope"></i> Secondary Exam
                                        </a>
                                    @endif

                                    <a href="{{ route('hospital.patients.edit', ['slug' => $slug, 'patient' => $p->id]) }}" class="btn btn-sm btn-outline-secondary" title="Edit Patient">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>

                                                                        <form method="POST" action="{{ route('hospital.patients.destroy', ['slug' => $slug, 'patient' => $p->id]) }}"
                                                                                    class="d-inline delete-form">
                                        @csrf
                                        @method('DELETE')
                                                                                <button type="button" class="btn btn-sm btn-outline-danger delete-btn" title="Delete Patient">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" style="padding:3rem 1rem;text-align:center">
                                <x-empty-state
                                    icon="bi bi-people"
                                    title="No patients found"
                                    description="{{ request('search') ? 'No results match your search.' : ($showAll ? 'No patients registered yet.' : 'No patients registered today.') }}"
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($patients->hasPages())
    <div style="margin-top:1rem;display:flex;justify-content:center">
        {{ $patients->links() }}
    </div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    function updateDilationButtons() {
        const now = new Date().getTime();

        document.querySelectorAll('.dilation-timer-btn').forEach(function (btn) {
            const unlockTime = parseInt(btn.getAttribute('data-unlock-time') || '0', 10);
            const distance = unlockTime - now;
            const label = btn.querySelector('.timer-text');

            if (distance <= 0) {
                btn.classList.remove('btn-warning', 'disabled', 'dilation-timer-btn');
                btn.classList.add('btn-sm', 'btn-success');
                btn.removeAttribute('onclick');
                btn.removeAttribute('style');

                const icon = btn.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-stethoscope';
                }

                if (label) {
                    label.textContent = 'Secondary Exam';
                }

                return;
            }

            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            const secStr = seconds < 10 ? '0' + seconds : String(seconds);

            if (label) {
                label.textContent = 'Wait: ' + minutes + ':' + secStr;
            }
        });
    }

    document.querySelectorAll('.delete-btn').forEach(function (button) {
        button.addEventListener('click', function (e) {
            e.preventDefault();

            const form = this.closest('form');

            if (!form || typeof Swal === 'undefined') {
                return;
            }

            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then(function (result) {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });

    updateDilationButtons();
    setInterval(updateDilationButtons, 1000);
});
</script>
@endsection
