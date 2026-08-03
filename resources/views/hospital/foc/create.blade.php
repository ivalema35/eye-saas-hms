@extends('hospital.layouts.app')
@section('title', 'New FOC Request')
@section('page-header', 'New FOC Request')

@push('styles')
<style>
.foc-create-card {
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    border: 1px solid #E2E8F0;
    overflow: hidden;
    font-family: 'Inter', sans-serif;
}
.foc-create-head {
    background: #1B4F72 !important;
    color: #fff;
}
.foc-primary-btn {
    background: #1B4F72;
    border-color: #1B4F72;
    border-radius: 8px;
    color: #fff;
}
.foc-primary-btn:hover {
    background: #16405d;
    border-color: #16405d;
    color: #fff;
}
</style>
@endpush

@section('content')

<div class="card foc-create-card" style="max-width:740px">
    <div class="card-header foc-create-head border-bottom-0">
        <h5 class="mb-0 fw-semibold"><i class="fa-solid fa-hand-holding-heart me-1"></i> Create FOC Request</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('hospital.foc.request', ['slug' => $slug]) }}">
            @csrf

            <input type="hidden" name="doctor_id" value="{{ auth('hospital_user')->id() }}">

            <div class="mb-3">
                <label class="form-label">Patient <span class="text-danger">*</span></label>
                <select name="patient_id" class="form-select @error('patient_id') is-invalid @enderror" required>
                    <option value="">Select Patient</option>
                    @foreach($patients as $patient)
                        <option value="{{ $patient->id }}" {{ old('patient_id') == $patient->id ? 'selected' : '' }}>
                            {{ $patient->full_name }} ({{ $patient->patient_code }})
                        </option>
                    @endforeach
                </select>
                @error('patient_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Select Receptionist <span class="text-danger">*</span></label>
                <select name="reception_id" class="form-select @error('reception_id') is-invalid @enderror" required>
                    <option value="">Select Receptionist</option>
                    @foreach($receptionists as $receptionist)
                        <option value="{{ $receptionist->id }}" {{ old('reception_id') == $receptionist->id ? 'selected' : '' }}>
                            {{ $receptionist->name }}
                        </option>
                    @endforeach
                </select>
                @error('reception_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Fee to Waive ({{ currency_symbol() }}) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" name="foc_fee" class="form-control @error('foc_fee') is-invalid @enderror" value="{{ old('foc_fee') }}" placeholder="Enter amount" required>
                @error('foc_fee')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Reason <span class="text-danger">*</span></label>
                <textarea name="reason" class="form-control @error('reason') is-invalid @enderror" rows="3" placeholder="Reason for FOC request" required>{{ old('reason') }}</textarea>
                @error('reason')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn foc-primary-btn">
                    <i class="fa-solid fa-paper-plane me-1"></i> Submit Request
                </button>
                <a href="{{ route('hospital.foc.index', ['slug' => $slug]) }}" class="btn btn-outline-secondary" style="border-radius:8px">Cancel</a>
            </div>
        </form>
    </div>
</div>

@endsection
