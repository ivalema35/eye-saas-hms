@extends('hospital.layouts.app')
@section('title', 'Record OT Payment')
@section('page-header', 'Record OT Payment')

@section('page-actions')
    <a href="{{ route('hospital.ot.accountant.dashboard', ['slug' => $slug]) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
    </a>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0 fw-bold" style="color: var(--color-primary);">
                    <i class="bi bi-receipt me-2"></i> Payment Form
                </h5>
            </div>

            <div class="card-body p-4">
                @if($errors->any())
                    <div class="alert alert-danger mb-4">
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label text-muted">Patient Name</label>
                        <input type="text" class="form-control" value="{{ $booking->patient?->full_name ?? '-' }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">Phone</label>
                        <input type="text" class="form-control" value="{{ $booking->patient?->contact_no ?? '-' }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">OT Date</label>
                        <input type="text" class="form-control" value="{{ optional($booking->surgery_date)->format('d M Y') }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">Mediclaim Status</label>
                        <input type="text" class="form-control" value="{{ ($counselling?->mediclaim ?? $booking->has_mediclaim) ? 'YES' : 'NO' }}" readonly>
                    </div>
                </div>

                <form method="POST" action="{{ route('hospital.ot.payments.store', ['slug' => $slug, 'bookingId' => $booking->id]) }}">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Package Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">INR</span>
                                <input type="number" step="0.01" min="0" name="package_amount"
                                       value="{{ old('package_amount', $defaultPackageAmount) }}"
                                       class="form-control" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Payment Mode <span class="text-danger">*</span></label>
                            <select name="payment_mode" class="form-select" required>
                                <option value="">Select payment mode...</option>
                                <option value="cash" {{ old('payment_mode') === 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="online" {{ old('payment_mode') === 'online' ? 'selected' : '' }}>Online</option>
                                <option value="mediclaim" {{ old('payment_mode') === 'mediclaim' ? 'selected' : '' }}>Mediclaim</option>
                            </select>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Receipt Number</label>
                            <input type="text" name="receipt_number" id="receipt_number"
                                   value="{{ old('receipt_number') }}"
                                   class="form-control" placeholder="RCP-YYYYMM-XXXX">
                        </div>
                        <div class="col-md-4 d-grid align-items-end">
                            <button type="button" id="autoReceiptBtn" class="btn btn-outline-primary mt-md-4">
                                <i class="bi bi-magic me-1"></i> Auto-generate Receipt
                            </button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3 mt-4">
                        <a href="{{ route('hospital.ot.accountant.dashboard', ['slug' => $slug]) }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check2-circle me-1"></i> Save Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const receiptInput = document.getElementById('receipt_number');
    const autoBtn = document.getElementById('autoReceiptBtn');

    function pad(n) {
        return String(n).padStart(2, '0');
    }

    autoBtn.addEventListener('click', function () {
        const now = new Date();
        const yyyy = now.getFullYear();
        const mm = pad(now.getMonth() + 1);
        const rand = String(Math.floor(Math.random() * 9000) + 1000);
        receiptInput.value = `RCP-${yyyy}${mm}-${rand}`;
    });
});
</script>
@endpush
