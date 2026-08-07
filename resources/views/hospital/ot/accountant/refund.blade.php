@extends('hospital.layouts.app')
@section('title', 'OT Full Refund')
@section('page-header', 'OT Full Refund')

@section('page-actions')
    <a href="{{ route('hospital.ot.accountant.dashboard', ['slug' => $slug, 'filter' => 'refunds']) }}" class="hms-btn hms-btn-outline">
        <i class="bi bi-arrow-left me-1"></i> Back to Refunds
    </a>
@endsection

@section('content')
@php
    $patient = $booking->patient;
@endphp
<div class="row justify-content-center">
    <div class="col-lg-8">
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3" style="color:#1B4F72;">Full Refund — Surgery Refused</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Patient</label>
                        <input type="text" class="form-control" readonly value="{{ $patient?->full_name ?? '-' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">UHID</label>
                        <input type="text" class="form-control" readonly value="{{ $patient?->patient_code ?? '-' }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted small">Total Paid</label>
                        <input type="text" class="form-control" readonly value="{{ money_code($booking->total_paid, 2) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted small">Already Refunded</label>
                        <input type="text" class="form-control" readonly value="{{ money_code($booking->total_refunded, 2) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted small">Refund Amount (full)</label>
                        <input type="text" class="form-control fw-bold" readonly value="{{ money_code($refundAmount, 2) }}">
                    </div>
                </div>

                <form method="POST" action="{{ route('hospital.ot.refunds.store', ['slug' => $slug, 'bookingId' => $booking->id]) }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Refund Mode <span class="text-danger">*</span></label>
                            <select name="payment_mode" class="form-select" required>
                                <option value="cash" @selected(old('payment_mode', 'cash') === 'cash')>Cash</option>
                                <option value="online" @selected(old('payment_mode') === 'online')>Online</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Receipt Number <span class="text-muted">(Auto)</span></label>
                            <input type="text" name="receipt_number" class="form-control"
                                value="{{ old('receipt_number', $autoReceiptNumber) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Reason</label>
                            <input type="text" name="reason" class="form-control" maxlength="500"
                                value="{{ old('reason', 'Patient refused OT — full refund') }}">
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('hospital.ot.accountant.dashboard', ['slug' => $slug, 'filter' => 'refunds']) }}"
                           class="hms-btn hms-btn-outline">Cancel</a>
                        <button type="submit" class="hms-btn hms-btn-primary"
                            onclick="return confirm('Confirm full refund of {{ number_format((float) $refundAmount, 2) }}?');">
                            <i class="bi bi-check2-circle me-1"></i> Record Full Refund
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
