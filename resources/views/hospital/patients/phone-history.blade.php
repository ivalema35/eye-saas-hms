@extends('hospital.layouts.app')
@section('title', 'Phone Appointment History')
@section('page-header', 'Phone Appointment History')

@push('styles')
<style>
.phone-history-page {
    padding-bottom: 2rem;
}

.phone-history-card {
    background: #ffffff;
    border: 1px solid rgba(27, 79, 114, 0.12);
    border-radius: 20px;
    box-shadow: 0 10px 28px rgba(27, 79, 114, 0.08);
    overflow: hidden;
}

.phone-history-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid rgba(27, 79, 114, 0.12);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}

.phone-history-title {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
    color: #1B4F72;
}

.phone-history-filter {
    display: flex;
    align-items: end;
    gap: .65rem;
    flex-wrap: wrap;
}

.phone-history-filter .form-label {
    font-size: .78rem;
    font-weight: 700;
    color: rgba(27, 79, 114, 0.8);
    margin-bottom: .25rem;
}

.phone-history-filter .form-control {
    min-width: 170px;
    border-radius: 10px;
    border: 1px solid rgba(27, 79, 114, 0.16);
}

.phone-history-filter .btn {
    border-radius: 10px;
    font-weight: 600;
    padding: .45rem .9rem;
}

.phone-history-body {
    padding: 1rem 1.25rem 1.25rem;
}

.phone-history-date {
    margin: .9rem 0 .55rem;
    font-size: .94rem;
    font-weight: 700;
    color: #1B4F72;
}

.phone-history-table {
    margin: 0;
}

.phone-history-table thead th {
    background: rgba(27, 79, 114, 0.07);
    color: rgba(27, 79, 114, 0.82);
    font-size: .76rem;
    text-transform: uppercase;
    letter-spacing: .04em;
    border-bottom: 1px solid rgba(27, 79, 114, 0.14);
}

.phone-history-table td {
    vertical-align: middle;
    color: #1F3345;
    font-size: .9rem;
}

.phone-history-empty {
    text-align: center;
    color: rgba(27, 79, 114, 0.7);
    padding: 2.2rem 1rem;
}

.phone-history-footer {
    margin-top: 1rem;
    display: flex;
    justify-content: flex-end;
}

@media (max-width: 768px) {
    .phone-history-header {
        padding: 1rem;
    }

    .phone-history-body {
        padding: .9rem;
    }

    .phone-history-filter .form-control {
        min-width: 140px;
    }
}
</style>
@endpush

@section('content')
<div class="phone-history-page">
    <div class="phone-history-card">
        <div class="phone-history-header">
            <h3 class="phone-history-title">Date-wise Phone Appointment Patients</h3>

            <form method="GET" class="phone-history-filter">
                <div>
                    <label class="form-label" for="from_date">From Date</label>
                    <input type="date" id="from_date" name="from_date" value="{{ $fromDate }}" class="form-control">
                </div>
                <div>
                    <label class="form-label" for="to_date">To Date</label>
                    <input type="date" id="to_date" name="to_date" value="{{ $toDate }}" class="form-control">
                </div>
                <div>
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('hospital.patients.phone-history', ['slug' => $slug]) }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>

        <div class="phone-history-body">
            @forelse($groupedPatients as $date => $rows)
                <h4 class="phone-history-date">{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</h4>
                <div class="table-responsive">
                    <table class="table phone-history-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>MRD</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Reception</th>
                                <th>Contact</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $index => $patient)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $patient->patient_code }}</td>
                                    <td>{{ $patient->full_name }}</td>
                                    <td>{{ $patient->doctor?->name ?? '—' }}</td>
                                    <td>{{ $patient->reception?->name ?? '—' }}</td>
                                    <td>{{ $patient->contact_no ?: '—' }}</td>
                                    <td>{{ $patient->created_at?->format('h:i A') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @empty
                <div class="phone-history-empty">No phone appointment history found for selected dates.</div>
            @endforelse

            <div class="phone-history-footer">
                {{ $patients->links('vendor.pagination.hms') }}
            </div>
        </div>
    </div>
</div>
@endsection
