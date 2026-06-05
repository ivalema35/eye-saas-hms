@extends('hospital.layouts.app')
@section('title', 'Receptionist Dashboard')
@section('page-header', 'Receptionist Dashboard')

@section('content')

@php
    $pendingFocRequests = $pendingFocRequests ?? collect();
    $wGreen  = (int) hospital_setting('wait_green_max',  30);
    $wOrange = (int) hospital_setting('wait_orange_max', 60);
    $wRed    = (int) hospital_setting('wait_red_max',   120);
    $wDGreen  = (int) hospital_setting('wait_d_green_max',  40);
    $wDOrange = (int) hospital_setting('wait_d_orange_max', 90);
    $wDRed    = (int) hospital_setting('wait_d_red_max',   120);
    $wNdGreen  = (int) hospital_setting('wait_nd_green_max',  20);
    $wNdOrange = (int) hospital_setting('wait_nd_orange_max', 60);
    $wNdRed    = (int) hospital_setting('wait_nd_red_max',   120);
@endphp

{{-- ============================================================
     Today Collection Cards
============================================================ --}}
<div class="hms-stats-grid">
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-blue"><i class="fa-solid fa-indian-rupee-sign"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">Gross Collection</div>
            <div class="hms-stat-value">₹{{ number_format($todayGross, 2) }}</div>
            <div class="hms-stat-meta">{{ now()->format('d M Y') }}</div>
        </div>
    </div>
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-red"><i class="fa-solid fa-hand-holding-heart"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">FOC Deductions</div>
            <div class="hms-stat-value">₹{{ number_format($todayFocAmount, 2) }}</div>
        </div>
    </div>
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-green"><i class="fa-solid fa-wallet"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">Net Collection</div>
            <div class="hms-stat-value">₹{{ number_format($todayNet, 2) }}</div>
        </div>
    </div>
</div>

{{-- ============================================================
     Stats Row
============================================================ --}}
<div class="hms-stats-grid" style="margin-top:1rem">
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-blue"><i class="fa-solid fa-person-walking"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">Walk-ins Today</div>
            <div class="hms-stat-value">{{ $todayWalkinCount }}</div>
        </div>
    </div>
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-teal"><i class="fa-solid fa-phone"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">Phone Appointments</div>
            <div class="hms-stat-value">{{ $todayPhoneCount }}</div>
        </div>
    </div>
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-orange"><i class="fa-solid fa-clock"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">FOC Pending</div>
            <div class="hms-stat-value">{{ $focPendingCount }}</div>
        </div>
    </div>
</div>

{{-- ============================================================
     Doctors Overview Cards
============================================================ --}}
<div class="hms-card" style="margin-top:1.5rem">
    <div class="hms-card-header">
        <h3 class="hms-card-title"><i class="fa-solid fa-user-doctor"></i> Doctors — Today Overview</h3>
    </div>
    <div class="hms-card-body">
        <div class="hms-stats-grid">
            @forelse($doctors as $doc)
                <div class="hms-stat-card" style="border-left:3px solid #1B4F72">
                    <div class="hms-stat-body">
                        <div class="hms-stat-label">{{ $doc->name }}</div>
                        <div class="hms-stat-value">{{ $doc->today_total }}</div>
                        <div class="hms-stat-meta">
                            P: {{ $doc->today_primary }} &bull; S: {{ $doc->today_secondary }}
                        </div>
                    </div>
                </div>
            @empty
                <p style="color:#9CA3AF;margin:0">No doctors found</p>
            @endforelse
        </div>
    </div>
</div>

{{-- ============================================================
     Today's Patients Table
============================================================ --}}
<div class="hms-card" style="margin-top:1.5rem">
    <div class="hms-card-header">
        <h3 class="hms-card-title"><i class="fa-solid fa-users"></i> My Patients — Today</h3>
        <a href="{{ route('hospital.patients.create', ['slug' => $slug]) }}" class="hms-btn hms-btn-sm hms-btn-primary">
            <i class="fa-solid fa-user-plus"></i> New Patient
        </a>
    </div>
    <div class="hms-card-body" style="padding:0">
        <table class="hms-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>MRD</th>
                    <th>Name</th>
                    <th>Age / Gender</th>
                    <th>Contact</th>
                    <th>Doctor</th>
                    <th>Fee (₹)</th>
                    <th>Type</th>
                    <th>Time</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($todayPatients as $i => $p)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td><strong>{{ $p->patient_code }}</strong></td>
                        @php
                            $rWaitMins  = (int) $p->created_at->diffInMinutes(now());
                            $rWaitClass = $rWaitMins < $wGreen ? 'wait-green' : ($rWaitMins < $wOrange ? 'wait-orange' : ($rWaitMins < $wRed ? 'wait-red' : 'wait-fire'));
                            $rWaitFmt   = $rWaitMins < 60 ? $rWaitMins.'m' : floor($rWaitMins/60).'h'.($rWaitMins%60 > 0 ? ' '.($rWaitMins%60).'m' : '');
                            $rPExam     = $p->primaryExamination ?? null;
                            $rIsDilated = $rPExam && ($rPExam->exam_data['dilate'] ?? 'No') === 'Yes';
                            $rIsNd      = $rPExam && ($rPExam->exam_data['dilate'] ?? 'No') !== 'Yes';
                            $rPrimeMins = $rPExam ? (int) \Carbon\Carbon::parse($rPExam->examined_at ?? $p->primary_done_at)->diffInMinutes(now()) : 0;
                            $rPrimeFmt  = $rPrimeMins < 60 ? $rPrimeMins.'m' : floor($rPrimeMins/60).'h'.($rPrimeMins%60>0?' '.($rPrimeMins%60).'m':'');
                            $rDClass    = $rPrimeMins < $wDGreen  ? 'wait-green' : ($rPrimeMins < $wDOrange  ? 'wait-orange' : ($rPrimeMins < $wDRed  ? 'wait-red' : 'wait-fire'));
                            $rNdClass   = $rPrimeMins < $wNdGreen ? 'wait-green' : ($rPrimeMins < $wNdOrange ? 'wait-orange' : ($rPrimeMins < $wNdRed ? 'wait-red' : 'wait-fire'));
                        @endphp
                        <td>{{ $p->full_name }}</td>
                        <td>{{ $p->age }}y / {{ ucfirst($p->gender) }}</td>
                        <td>{{ $p->contact_no }}</td>
                        <td>{{ $p->doctor?->name ?? '—' }}</td>
                        <td>{{ number_format($p->case_fee, 2) }}</td>
                        <td>
                            <span class="hms-badge {{ $p->type === 'walkin' ? 'hms-badge-info' : 'hms-badge-warning' }}">
                                {{ ucfirst($p->type) }}
                            </span>
                        </td>
                        <td>
                            {{ $p->created_at->format('h:i A') }}<br>
                            @if($p->secondary_done_at)
                                <span style="color:#16a34a;font-size:.8rem;font-weight:700;margin-top:4px;display:inline-block;">
                                    <i class="bi bi-check2-circle"></i> Done
                                </span>
                            @else
                            <div class="d-flex flex-column align-items-start gap-1 mt-1">
                                <span class="wait-pill {{ $rWaitClass }}" data-wait-from="{{ $p->created_at->toIso8601String() }}" data-badge="R">
                                    <span class="wp-r">R</span>
                                    <span class="wp-time">{{ $rWaitFmt }}</span>
                                </span>
                                @if($rIsDilated && $rPExam)
                                    <span class="wait-pill {{ $rDClass }}" data-wait-from="{{ \Carbon\Carbon::parse($rPExam->examined_at ?? $p->primary_done_at)->toIso8601String() }}" data-badge="D" data-thresholds="{{ $wDGreen }},{{ $wDOrange }},{{ $wDRed }}">
                                        <span class="wp-r">D</span>
                                        <span class="wp-time">{{ $rPrimeFmt }}</span>
                                    </span>
                                @elseif($rIsNd && $rPExam)
                                    <span class="wait-pill {{ $rNdClass }}" data-wait-from="{{ \Carbon\Carbon::parse($rPExam->examined_at ?? $p->primary_done_at)->toIso8601String() }}" data-badge="ND" data-thresholds="{{ $wNdGreen }},{{ $wNdOrange }},{{ $wNdRed }}">
                                        <span class="wp-r" style="font-size:.58rem;">ND</span>
                                        <span class="wp-time">{{ $rPrimeFmt }}</span>
                                    </span>
                                @endif
                            </div>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('hospital.patients.show', ['slug' => $slug, 'patient' => $p->id]) }}"
                               class="hms-btn hms-btn-sm hms-btn-outline" title="View">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center" style="color:#9CA3AF;padding:2rem">No patients registered today</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="hms-card" style="margin-top:1.5rem">
    <div class="hms-card-header">
        <h3 class="hms-card-title"><i class="fa-solid fa-hand-holding-heart"></i> Pending FOC Requests</h3>
        <span class="hms-badge hms-badge-warning">{{ $pendingFocRequests->count() }} pending</span>
    </div>
    <div class="hms-card-body" style="padding:0">
        <table class="hms-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Doctor</th>
                    <th>Patient</th>
                    <th>MRD</th>
                    <th>Fee to Waive</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pendingFocRequests as $i => $foc)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $foc->doctor?->name ?? '—' }}</td>
                        <td>{{ $foc->patient?->full_name ?? '—' }}</td>
                        <td>{{ $foc->patient?->patient_code ?? '—' }}</td>
                        <td>₹{{ number_format((float) $foc->foc_fee, 2) }}</td>
                        <td>
                            <form method="POST" action="{{ route('hospital.foc.accept', ['slug' => $slug, 'id' => $foc->id]) }}" style="display:inline">
                                @csrf
                                <button type="submit" class="hms-btn hms-btn-sm hms-btn-success">Accept</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center" style="color:#9CA3AF;padding:2rem">No pending FOC requests</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('styles')
<style>
.wait-pill { display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:3px 10px 3px 3px;font-weight:700;white-space:nowrap;transition:background .4s,box-shadow .4s;vertical-align:middle; }
.wait-pill.wait-green  { background:rgba(22,163,74,.10); box-shadow:0 0 0 1px rgba(22,163,74,.25); }
.wait-pill.wait-orange { background:rgba(234,88,12,.10); box-shadow:0 0 0 1px rgba(234,88,12,.25); }
.wait-pill.wait-red    { background:rgba(220,38,38,.10); box-shadow:0 0 0 1px rgba(220,38,38,.25); }
.wait-pill.wait-fire   { background:rgba(220,38,38,.10); box-shadow:0 0 0 1px rgba(220,38,38,.35);animation:fire-glow 1s ease-in-out infinite alternate; }
@keyframes fire-glow { from{box-shadow:0 0 0 1px rgba(220,38,38,.35),0 0 6px rgba(234,88,12,.4);} to{box-shadow:0 0 0 2px rgba(220,38,38,.55),0 0 12px rgba(234,88,12,.6);} }
.wp-r { display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;border-radius:50%;font-size:.68rem;font-weight:900;color:#fff;flex-shrink:0; }
.wait-green  .wp-r { background:#16a34a; }
.wait-orange .wp-r { background:#ea580c; }
.wait-red    .wp-r { background:#dc2626; }
.wait-fire   .wp-r { background:linear-gradient(135deg,#dc2626,#ea580c);animation:fire-glow 1s ease-in-out infinite alternate; }
.wp-time { font-size:.75rem;font-weight:700; }
.wait-green  .wp-time { color:#15803d; }
.wait-orange .wp-time { color:#c2410c; }
.wait-red    .wp-time { color:#b91c1c; }
.wait-fire   .wp-time { color:#dc2626; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const W = { green: {{ $wGreen }}, orange: {{ $wOrange }}, red: {{ $wRed }} };
    function getWCustm(m,g,o,r){return m<g?'wait-green':m<o?'wait-orange':m<r?'wait-red':'wait-fire';}
    function getWaitClass(m){return getWCustm(m,W.green,W.orange,W.red);}
    function fmtTime(m) { return m < 60 ? m+'m' : Math.floor(m/60)+'h'+(m%60>0?' '+m%60+'m':''); }
    function updateWaitPills() {
        const now = Date.now();
        document.querySelectorAll('.wait-pill[data-wait-from]').forEach(function (pill) {
            const mins = Math.floor((now - new Date(pill.dataset.waitFrom).getTime()) / 60000);
            const thr  = pill.dataset.thresholds ? pill.dataset.thresholds.split(',').map(Number) : null;
            pill.className = 'wait-pill ' + (thr ? getWCustm(mins,thr[0],thr[1],thr[2]) : getWaitClass(mins));
            const t = pill.querySelector('.wp-time');
            if (t) t.textContent = fmtTime(mins);
        });
    }
    document.addEventListener('DOMContentLoaded', function () {
        updateWaitPills();
        setInterval(updateWaitPills, 30000);
    });
})();
</script>
@endpush

@endsection
