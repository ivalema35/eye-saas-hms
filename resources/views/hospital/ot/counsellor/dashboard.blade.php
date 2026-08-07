@extends('hospital.layouts.app')
@section('title', 'OT Counsellor Dashboard')
@section('page-header', 'OT Counsellor Dashboard')

@section('content')
    <div class="ot-counsellor-page">
        <div class="card ot-premium-card border-0">
            <div class="ot-card-header">
                <div class="ot-title-wrap">
                    <span class="ot-title-icon" aria-hidden="true">
                        <i class="bi bi-chat-left-heart" style="font-size: 1.2rem;"></i>
                    </span>
                    <div class="flex-grow-1">
                        <h5 class="ot-title">Awaiting Counselling</h5>
                        <div class="ot-subtitle">Bookings that still need diagnosis, lens/package selection and consent.</div>
                    </div>
                </div>
                <span class="badge ot-total-pill">{{ $bookings->total() }} total</span>
            </div>

            <div class="card-body p-0">
                @if(session('success'))
                    <div class="alert alert-success mx-4 mt-3 ot-alert">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger mx-4 mt-3 ot-alert">{{ session('error') }}</div>
                @endif

                <div class="ot-table-wrap">
                    <div class="table-responsive">
                        <table class="table ot-premium-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th><i class="bi bi-person-badge me-1"></i>Patient</th>
                                    <th><i class="bi bi-telephone me-1"></i>Phone</th>
                                    <th><i class="bi bi-person-vcard me-1"></i>OT Doctor</th>
                                    <th><i class="bi bi-calendar-event me-1"></i>OT Date</th>
                                    <th><i class="bi bi-eye me-1"></i>Eye</th>
                                    <th><i class="bi bi-activity me-1"></i>Surgery Type</th>
                                    <th><i class="bi bi-flag me-1"></i>Status</th>
                                    <th class="ot-actions-col"><i class="bi bi-three-dots me-1"></i>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($bookings as $booking)
                                    <tr>
                                        <td><i class="bi bi-person-fill me-1 text-muted"></i>{{ $booking->patient?->full_name ?? '-' }}</td>
                                        <td>{{ $booking->patient?->contact_no ?? '-' }}</td>
                                        <td>{{ $booking->otDoctor?->name ? 'Dr. ' . $booking->otDoctor->name : '-' }}</td>
                                        <td>{{ optional($booking->surgery_date)->format('d M Y') }}</td>
                                        <td><span class="badge ot-type-badge">{{ $booking->eye }}</span></td>
                                        <td>{{ $booking->ot_type }}</td>
                                        <td>
                                            @if($booking->ot_status === \App\Models\Hospital\OT\OtBooking::STATUS_SURGERY_RECOMMENDED)
                                                <span class="badge ot-status-badge ot-status-recommended">Surgery Recommended</span>
                                            @else
                                                <span class="badge ot-status-badge ot-status-booked">Booked</span>
                                            @endif
                                        </td>
                                        <td class="ot-actions-cell">
                                            <a href="{{ route('hospital.ot.counsellor.form', ['slug' => $slug, 'bookingId' => $booking->id]) }}"
                                                class="btn btn-sm ot-view-btn">
                                                <i class="bi bi-chat-left-text me-1"></i> Counsel
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center ot-empty">
                                            <i class="bi bi-inbox me-1"></i> No bookings awaiting counselling.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        @if($bookings->hasPages())
            <div class="mt-3 d-flex justify-content-center">
                {{ $bookings->links() }}
            </div>
        @endif

        {{-- OT Workflow Upgrade — Phase 5: Payment Verification queue (PDF Step 6) --}}
        <div class="card ot-premium-card border-0 mt-4">
            <div class="ot-card-header">
                <div class="ot-title-wrap">
                    <span class="ot-title-icon" aria-hidden="true">
                        <i class="bi bi-shield-check" style="font-size: 1.2rem;"></i>
                    </span>
                    <div class="flex-grow-1">
                        <h5 class="ot-title">Payment Status</h5>
                        <div class="ot-subtitle">Bookings billing has taken payment on — status updates automatically as they move forward.</div>
                    </div>
                </div>
                <span class="badge ot-total-pill">{{ $paymentVerificationQueue->total() }} total</span>
            </div>

            <div class="card-body p-0">
                <div class="ot-table-wrap">
                    <div class="table-responsive">
                        <table class="table ot-premium-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th><i class="bi bi-person-badge me-1"></i>Patient</th>
                                    <th><i class="bi bi-telephone me-1"></i>Phone</th>
                                    <th><i class="bi bi-calendar-event me-1"></i>OT Date</th>
                                    <th><i class="bi bi-cash-coin me-1"></i>Package Amount</th>
                                    <th><i class="bi bi-flag me-1"></i>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($paymentVerificationQueue as $booking)
                                    <tr>
                                        <td><i class="bi bi-person-fill me-1 text-muted"></i>{{ $booking->patient?->full_name ?? '-' }}</td>
                                        <td>{{ $booking->patient?->contact_no ?? '-' }}</td>
                                        <td>{{ optional($booking->surgery_date)->format('d M Y') }}</td>
                                        <td>{{ money_code((float) ($booking->package_amount ?? 0), 2) }}</td>
                                        <td>
                                            @if($booking->ot_status === \App\Models\Hospital\OT\OtBooking::STATUS_PAID)
                                                <span class="badge bg-warning-subtle text-warning-emphasis">Paid</span>
                                            @else
                                                <span class="badge bg-success-subtle text-success-emphasis">Paid</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center ot-empty">
                                            <i class="bi bi-inbox me-1"></i> No bookings paid yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        @if($paymentVerificationQueue->hasPages())
            <div class="mt-3 d-flex justify-content-center">
                {{ $paymentVerificationQueue->links() }}
            </div>
        @endif
    </div>
@endsection

@push('styles')
    <style>
        /*
          OT Counsellor Dashboard (Design refresh)
          Keep Blade/dynamic logic untouched; CSS-only + layout wrappers.
          Palette follows hospital shell theme (#1B4F72).
        */

        .ot-counsellor-page {
            --ot-secondary: #1B4F72;
            --ot-s2-06: rgba(27, 79, 114, 0.06);
            --ot-s2-08: rgba(27, 79, 114, 0.08);
            --ot-s2-12: rgba(27, 79, 114, 0.12);
            --ot-s2-18: rgba(27, 79, 114, 0.18);
            --ot-s2-24: rgba(27, 79, 114, 0.24);

            position: relative;
            padding: .25rem 0 1.25rem;
            color: var(--ot-secondary);
            animation: ot-page-in 420ms ease both;
        }

        @keyframes ot-page-in {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .ot-counsellor-page .btn {
            border-radius: 12px;
            font-weight: 800;
            transition: transform 170ms ease, box-shadow 170ms ease, background 170ms ease, border-color 170ms ease, color 170ms ease;
        }

        .ot-counsellor-page .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 26px rgba(27, 79, 114, 0.14);
        }

        .ot-premium-card {
            background: rgba(255, 255, 255, 0.84);
            border: 1px solid var(--ot-s2-12) !important;
            border-radius: 22px;
            box-shadow: 0 18px 48px rgba(27, 79, 114, 0.10);
            overflow: hidden;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            animation: ot-card-rise 520ms cubic-bezier(.2,.9,.2,1) both;
        }

        @keyframes ot-card-rise {
            from { opacity: 0; transform: translateY(10px) scale(0.99); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        .ot-card-header {
            background:
                linear-gradient(135deg, rgba(235, 245, 251, 0.92), rgba(255, 255, 255, 0.94)),
                #ffffff;
            border-bottom: 1px solid var(--ot-s2-12);
            padding: 1.15rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .ot-title-wrap {
            display: flex;
            align-items: center;
            gap: .85rem;
            min-width: 0;
        }

        .ot-title-icon {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            background: var(--ot-secondary);
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 14px 30px rgba(27, 79, 114, 0.22);
            flex: 0 0 auto;
        }

        .ot-title {
            font-weight: 900;
            letter-spacing: -0.2px;
            margin: 0;
            color: var(--ot-secondary);
        }

        .ot-subtitle {
            margin: .15rem 0 0;
            font-weight: 650;
            color: rgba(27, 79, 114, 0.72);
            font-size: .85rem;
        }

        .ot-total-pill {
            background: rgba(255,255,255,.78);
            color: var(--ot-secondary);
            border: 1px solid var(--ot-s2-12);
            border-radius: 999px;
            padding: .55rem .85rem;
            font-weight: 900;
            white-space: nowrap;
            box-shadow: 0 10px 22px rgba(27,79,114,.08);
        }

        .ot-alert {
            border-radius: 14px;
            font-weight: 650;
        }

        .ot-table-wrap {
            padding: .9rem !important;
            overflow-x: auto;
        }

        .ot-premium-table {
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0 8px;
            min-width: 900px;
        }

        .ot-premium-table thead,
        .ot-premium-table thead th {
            background: var(--ot-secondary) !important;
        }

        .ot-premium-table thead th {
            color: #ffffff !important;
            border-bottom: none !important;
            font-size: .72rem;
            letter-spacing: .08em;
            font-weight: 900;
            text-transform: uppercase;
            padding-top: .9rem;
            padding-bottom: .9rem;
            white-space: nowrap;
        }

        .ot-premium-table thead th:first-child {
            border-top-left-radius: 14px;
            border-bottom-left-radius: 14px;
        }

        .ot-premium-table thead th:last-child {
            border-top-right-radius: 14px;
            border-bottom-right-radius: 14px;
        }

        .ot-premium-table thead th.ot-actions-col,
        .ot-premium-table tbody td.ot-actions-cell {
            text-align: end;
        }

        .ot-premium-table tbody td {
            background: rgba(255, 255, 255, 0.88);
            border-top: 1px solid var(--ot-s2-12);
            border-bottom: 1px solid var(--ot-s2-12);
            padding: .95rem .95rem;
            font-weight: 750;
            color: rgba(27, 79, 114, 0.90);
            vertical-align: middle;
            white-space: nowrap;
        }

        .ot-premium-table tbody td:first-child {
            border-left: 1px solid var(--ot-s2-12);
            border-top-left-radius: 14px;
            border-bottom-left-radius: 14px;
            color: rgba(27, 79, 114, 0.82);
            font-weight: 900;
        }

        .ot-premium-table tbody td:last-child {
            border-right: 1px solid var(--ot-s2-12);
            border-top-right-radius: 14px;
            border-bottom-right-radius: 14px;
        }

        .ot-premium-table tbody tr {
            animation: ot-row-in 460ms cubic-bezier(.2,.9,.2,1) both;
            transform-origin: center;
        }

        @keyframes ot-row-in {
            from { opacity: 0; transform: translateY(6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .ot-premium-table tbody tr:hover td {
            background: rgba(235, 245, 251, 0.78);
            border-color: var(--ot-s2-18);
        }

        .ot-premium-table tbody tr:hover {
            transform: translateY(-1px);
        }

        .ot-type-badge {
            background: rgba(27, 79, 114, 0.08);
            border: 1px solid var(--ot-s2-18);
            color: var(--ot-secondary);
            border-radius: 999px;
            padding: .4rem .7rem;
            font-weight: 800;
        }

        .ot-status-badge {
            border-radius: 999px;
            padding: .45rem .70rem;
            font-weight: 900;
            letter-spacing: .02em;
            box-shadow: 0 10px 22px rgba(27, 79, 114, 0.10);
            color: #fff;
        }

        .ot-status-recommended { background: #E0A800; }
        .ot-status-booked { background: var(--ot-secondary); }

        .ot-view-btn {
            border-radius: 12px;
            border: 1px solid var(--ot-s2-18);
            background: var(--ot-secondary);
            color: #fff;
            font-weight: 900;
            padding: .45rem .8rem;
        }

        .ot-view-btn:hover {
            background: #15405d;
            border-color: #15405d;
            color: #fff;
        }

        .ot-verify-btn {
            border-radius: 12px;
            border: 1px solid rgba(30, 142, 90, 0.3);
            background: #1E8E5A;
            color: #fff;
            font-weight: 900;
            padding: .45rem .8rem;
        }

        .ot-verify-btn:hover {
            background: #17714a;
            border-color: #17714a;
            color: #fff;
        }

        .ot-empty {
            padding: 2.25rem 1rem !important;
            color: rgba(27, 79, 114, 0.72) !important;
            font-weight: 800;
        }

        @media (prefers-reduced-motion: reduce) {
            .ot-counsellor-page,
            .ot-premium-card,
            .ot-premium-table tbody tr,
            .ot-counsellor-page .btn {
                animation: none !important;
                transition: none !important;
            }
        }
    </style>
@endpush