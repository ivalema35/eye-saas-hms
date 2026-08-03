@extends('hospital.layouts.app')
@section('title', 'Discharge Counter Dashboard')
@section('page-header', 'Discharge Counter Dashboard')

@section('content')
<div class="dc-home-page">
    @if($subscriptionDaysLeft !== null && $subscriptionDaysLeft <= 14)
        <div class="alert {{ $subscriptionDaysLeft <= 3 ? 'alert-danger' : 'alert-warning' }} d-flex align-items-center gap-2 rounded-3 mb-4 shadow-sm">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>
                Subscription expires in <strong>{{ $subscriptionDaysLeft }} days</strong>. Please renew soon.
            </span>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-md-6 col-lg-4">
            <a href="{{ route('hospital.ot.billing.index', ['slug' => $slug]) }}"
               class="dc-home-card text-decoration-none d-block">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div>
                        <p class="metric-label mb-1">Discharge &amp; Invoices</p>
                        <div class="metric-value">{{ $dischargeDeskTotal ?? 0 }}</div>
                        <p class="metric-meta mb-0">Billing desk — invoices &amp; discharge docs</p>
                    </div>
                    <div class="metric-icon">
                        <i class="bi bi-receipt-cutoff"></i>
                    </div>
                </div>
                <div class="mt-3 open-hint">
                    Open Discharge &amp; Invoices <i class="bi bi-arrow-right-short"></i>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.dc-home-page {
    --dc-primary: #1B4F72;
    --dc-soft: #EBF5FB;
    padding-bottom: 1.5rem;
}
.dc-home-card {
    background: #fff;
    border: 1px solid rgba(27, 79, 114, 0.12);
    border-radius: 18px;
    padding: 1.25rem 1.35rem;
    box-shadow: 0 10px 28px rgba(27, 79, 114, 0.08);
    color: var(--dc-primary);
    transition: transform 160ms ease, box-shadow 160ms ease, border-color 160ms ease;
}
.dc-home-card:hover {
    transform: translateY(-2px);
    border-color: rgba(27, 79, 114, 0.28);
    box-shadow: 0 14px 32px rgba(27, 79, 114, 0.14);
    color: var(--dc-primary);
}
.dc-home-card .metric-label {
    font-size: .78rem;
    font-weight: 800;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: rgba(27, 79, 114, 0.72);
}
.dc-home-card .metric-value {
    font-size: 2rem;
    font-weight: 900;
    line-height: 1.1;
    color: var(--dc-primary);
}
.dc-home-card .metric-meta {
    font-size: .85rem;
    font-weight: 650;
    color: rgba(27, 79, 114, 0.65);
}
.dc-home-card .metric-icon {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    background: var(--dc-soft);
    color: var(--dc-primary);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}
.dc-home-card .open-hint {
    font-size: .82rem;
    font-weight: 800;
    color: rgba(27, 79, 114, 0.78);
}
</style>
@endpush
