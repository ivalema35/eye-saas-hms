{{--
  Shared read-only OT booking details modal.
  Required: $booking
  Optional: $modalIdPrefix (default otDeskView), $modalTitle
--}}
@php
    $modalIdPrefix = $modalIdPrefix ?? 'otDeskView';
    $modalTitle = $modalTitle ?? 'OT Booking Details';
    $statusLabel = str((string) $booking->ot_status)->replace('_', ' ')->title();
@endphp
<div class="modal fade ot-desk-view-modal" id="{{ $modalIdPrefix }}{{ $booking->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mb-0">
                    <i class="bi bi-clipboard2-pulse me-2"></i>{{ $modalTitle }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="ot-desk-modal-grid">
                    <div class="ot-desk-modal-field">
                        <div class="ot-desk-modal-label">Patient</div>
                        <div class="ot-desk-modal-value">{{ $booking->patient?->full_name ?? '-' }}</div>
                    </div>
                    <div class="ot-desk-modal-field">
                        <div class="ot-desk-modal-label">Patient Code</div>
                        <div class="ot-desk-modal-value">{{ $booking->patient?->patient_code ?? '-' }}</div>
                    </div>
                    <div class="ot-desk-modal-field">
                        <div class="ot-desk-modal-label">Phone</div>
                        <div class="ot-desk-modal-value">{{ $booking->patient?->contact_no ?? '-' }}</div>
                    </div>
                    <div class="ot-desk-modal-field">
                        <div class="ot-desk-modal-label">Status</div>
                        <div class="ot-desk-modal-value">{{ $statusLabel }}</div>
                    </div>
                    <div class="ot-desk-modal-field">
                        <div class="ot-desk-modal-label">OT Doctor</div>
                        <div class="ot-desk-modal-value">{{ $booking->otDoctor?->name ? 'Dr. '.$booking->otDoctor->name : '-' }}</div>
                    </div>
                    <div class="ot-desk-modal-field">
                        <div class="ot-desk-modal-label">Surgery Date</div>
                        <div class="ot-desk-modal-value">{{ optional($booking->surgery_date)->format('d M Y') ?? '-' }}</div>
                    </div>
                    <div class="ot-desk-modal-field">
                        <div class="ot-desk-modal-label">Eye</div>
                        <div class="ot-desk-modal-value">{{ $booking->eye ?? '-' }}</div>
                    </div>
                    <div class="ot-desk-modal-field">
                        <div class="ot-desk-modal-label">Surgery Type</div>
                        <div class="ot-desk-modal-value">{{ $booking->ot_type ?? '-' }}</div>
                    </div>
                    <div class="ot-desk-modal-field">
                        <div class="ot-desk-modal-label">Package Amount</div>
                        <div class="ot-desk-modal-value">{{ money_code((float) ($booking->package_amount ?? 0), 2) }}</div>
                    </div>
                    <div class="ot-desk-modal-field">
                        <div class="ot-desk-modal-label">Payment Mode</div>
                        <div class="ot-desk-modal-value">{{ $booking->payment_mode ? str($booking->payment_mode)->replace('_', ' ')->title() : '-' }}</div>
                    </div>
                    @if($booking->relationLoaded('payments') && $booking->payments->isNotEmpty())
                        <div class="ot-desk-modal-field ot-desk-modal-wide">
                            <div class="ot-desk-modal-label">Payments</div>
                            <div class="ot-desk-modal-value">
                                {{ money_code((float) $booking->payments->sum('package_amount'), 2) }}
                                ({{ $booking->payments->count() }} receipt{{ $booking->payments->count() === 1 ? '' : 's' }})
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
