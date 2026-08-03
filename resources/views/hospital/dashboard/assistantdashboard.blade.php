@extends('hospital.layouts.app')
@section('title', 'OT Assistant Dashboard')
@section('page-header', 'OT Assistant Dashboard')

@section('content')
<div class="assistant-home-page">
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
            <a href="{{ route('hospital.dashboard.assistant-ot', ['slug' => $slug]) }}"
               class="assistant-ot-card text-decoration-none d-block">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div>
                        <p class="metric-label mb-1">My OT Patients</p>
                        <div class="metric-value">{{ $assistantOtTotal ?? 0 }}</div>
                        <p class="metric-meta mb-0">
                            Pending: {{ $assistantOtPending ?? 0 }}
                            &bull;
                            Complete: {{ $assistantOtComplete ?? 0 }}
                        </p>
                    </div>
                    <div class="metric-icon">
                        <i class="bi bi-eyeglasses"></i>
                    </div>
                </div>
                <div class="mt-3 open-hint">
                    Open assigned patients <i class="bi bi-arrow-right-short"></i>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-4">
            <a href="{{ route('hospital.ot.assistant.dashboard', ['slug' => $slug]) }}"
               class="assistant-ot-card text-decoration-none d-block">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div>
                        <p class="metric-label mb-1">Surgery Queue</p>
                        <div class="metric-value">{{ $assistantSurgeryReady ?? 0 }}</div>
                        <p class="metric-meta mb-0">Ready for OT — record surgery</p>
                    </div>
                    <div class="metric-icon">
                        <i class="bi bi-activity"></i>
                    </div>
                </div>
                <div class="mt-3 open-hint">
                    Open Surgery Queue <i class="bi bi-arrow-right-short"></i>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.assistant-home-page {
    --ah-primary: #1B4F72;
    --ah-soft: #EBF5FB;
    padding-bottom: 1.5rem;
}
.assistant-ot-card {
    background: #fff;
    border: 1px solid rgba(27, 79, 114, 0.12);
    border-radius: 18px;
    padding: 1.25rem 1.35rem;
    box-shadow: 0 10px 28px rgba(27, 79, 114, 0.08);
    color: var(--ah-primary);
    transition: transform 160ms ease, box-shadow 160ms ease, border-color 160ms ease;
}
.assistant-ot-card:hover {
    transform: translateY(-2px);
    border-color: rgba(27, 79, 114, 0.28);
    box-shadow: 0 14px 32px rgba(27, 79, 114, 0.14);
    color: var(--ah-primary);
}
.assistant-ot-card .metric-label {
    font-size: .78rem;
    font-weight: 800;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: rgba(27, 79, 114, 0.72);
}
.assistant-ot-card .metric-value {
    font-size: 2rem;
    font-weight: 900;
    line-height: 1.1;
    color: var(--ah-primary);
}
.assistant-ot-card .metric-meta {
    font-size: .85rem;
    font-weight: 650;
    color: rgba(27, 79, 114, 0.65);
}
.assistant-ot-card .metric-icon {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    background: var(--ah-soft);
    color: var(--ah-primary);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}
.assistant-ot-card .open-hint {
    font-size: .82rem;
    font-weight: 800;
    color: rgba(27, 79, 114, 0.78);
}
</style>
@endpush
