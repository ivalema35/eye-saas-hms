@extends('hospital.layouts.app')
@section('title', 'Collection — '.$reception->name)
@section('page-header', 'Collection — '.$reception->name)

@section('page-actions')
    <a href="{{ route('hospital.dashboard.collection', [
            'slug' => $slug,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]) }}" class="hms-btn hms-btn-outline">
        <i class="bi bi-arrow-left me-1"></i> All Reception
    </a>
@endsection

@section('content')
<div class="acoll-show-page">
    <div class="acoll-show-hint mb-3">
        @if($startDate === $endDate)
            {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}
        @else
            {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} – {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
        @endif
        · {{ $count }} patients
    </div>

    <div class="row g-3 mb-4">
        @foreach($buckets as $bucket)
            <div class="col-md-4">
                <div class="card acoll-bucket-card border-0 h-100">
                    <div class="card-body">
                        <div class="acoll-bucket-label">{{ $bucket['label'] }}</div>
                        <div class="acoll-bucket-value">{{ money($bucket['total'], 0) }}</div>
                        <div class="acoll-bucket-meta">{{ $bucket['count'] }} patients</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card acoll-total-line border-0">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <div class="acoll-bucket-label">Total Collection</div>
                <div class="acoll-bucket-meta">{{ $reception->name }} · case-wise sum</div>
            </div>
            <div class="acoll-bucket-value mb-0">{{ money($total, 0) }}</div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.acoll-show-page { --c:#1B4F72; --soft:#EBF5FB; --border:rgba(27,79,114,.12); padding-bottom:1.5rem; }
.acoll-show-hint { font-size:.85rem; color:rgba(27,79,114,.7); font-weight:600; }
.acoll-bucket-card, .acoll-total-line {
    border-radius:18px; box-shadow:0 8px 22px rgba(27,79,114,.06); border:1px solid var(--border)!important;
}
.acoll-bucket-label { font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:rgba(27,79,114,.68); }
.acoll-bucket-value { font-size:1.65rem; font-weight:900; color:var(--c); letter-spacing:-.5px; margin:.3rem 0; }
.acoll-bucket-meta { font-size:.78rem; color:rgba(27,79,114,.65); }
.acoll-total-line { background:linear-gradient(135deg,#EBF5FB,#fff); }
</style>
@endpush
