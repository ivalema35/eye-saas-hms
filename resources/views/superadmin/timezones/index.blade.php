@extends('superadmin.layouts.app')

@section('title', 'Timezone Master')
@section('page-header', 'Timezone Master')

@section('content')

<div class="hms-card" style="padding:0">
    <div class="hms-card-header">
        <h3 class="hms-card-title">
            <i class="bi bi-clock-fill" style="color:#1B4F72"></i> All Timezones
        </h3>
        <span class="hms-badge hms-badge-info">{{ $totalCount }} timezones</span>
    </div>

    {{-- Search bar --}}
    <div style="padding:.75rem 1.25rem;border-bottom:1px solid #F1F5F9;background:#F8FAFC">
        <div style="display:flex;gap:.75rem;align-items:center">
            <div style="position:relative;flex:1;max-width:400px">
                <i class="bi bi-search" style="position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:#94A3B8;pointer-events:none"></i>
                <input type="text" id="tzSearchInput"
                       placeholder="Search... (e.g. Kolkata, New_York, London)"
                       style="width:100%;padding:.5rem .75rem .5rem 2.25rem;border:1px solid #E2E8F0;border-radius:8px;font-size:.875rem;outline:none;background:#fff">
            </div>
            <span id="tzSearchCount" style="font-size:.82rem;color:#64748B;min-width:80px"></span>
        </div>
    </div>

    {{-- Timezone list grouped by region --}}
    <div style="padding:1.25rem" id="tzRegionsWrap">
        @foreach($groupedTimezones as $region => $zones)
        <div class="tz-region-block" style="margin-bottom:1.75rem">

            {{-- Region heading --}}
            <div style="font-size:.75rem;font-weight:700;color:#1B4F72;text-transform:uppercase;
                        letter-spacing:.07em;border-bottom:2px solid #EFF6FF;
                        padding-bottom:.4rem;margin-bottom:.65rem;display:flex;align-items:center;gap:.5rem">
                <i class="bi bi-globe2"></i>
                {{ $region }}
                <span style="font-weight:500;color:#94A3B8;font-size:.7rem">({{ count($zones) }})</span>
            </div>

            {{-- Timezone chips --}}
            <div style="display:flex;flex-wrap:wrap;gap:.4rem">
                @foreach($zones as $tz)
                <span class="tz-item"
                      data-tz="{{ strtolower($tz) }}"
                      title="{{ \Carbon\Carbon::now($tz)->format('d M Y, h:i A') }}"
                      style="display:inline-flex;align-items:center;gap:.35rem;
                             padding:.3rem .7rem;border-radius:6px;
                             background:#F8FAFC;border:1px solid #E2E8F0;
                             font-size:.8rem;color:#334155;white-space:nowrap;
                             cursor:default">
                    <i class="bi bi-clock" style="color:#1B4F72;font-size:.7rem"></i>
                    {{ $tz }}
                </span>
                @endforeach
            </div>

        </div>
        @endforeach

        {{-- No result state --}}
        <div id="tzNoResult" style="display:none;padding:3.5rem;text-align:center;color:#94A3B8">
            <i class="bi bi-search" style="font-size:2.5rem;display:block;margin-bottom:.6rem"></i>
            <p style="margin:0;font-weight:600;font-size:.95rem">No timezone found</p>
            <p style="margin:.3rem 0 0;font-size:.82rem">Try a different keyword</p>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const tzInput  = document.getElementById('tzSearchInput');
    const tzItems  = document.querySelectorAll('.tz-item');
    const tzBlocks = document.querySelectorAll('.tz-region-block');
    const tzCount  = document.getElementById('tzSearchCount');
    const tzNoResult = document.getElementById('tzNoResult');

    tzInput.addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        let visible = 0;

        tzItems.forEach(item => {
            const match = !q || item.dataset.tz.includes(q);
            item.style.display = match ? '' : 'none';
            if (match) visible++;
        });

        tzBlocks.forEach(block => {
            const hasVisible = [...block.querySelectorAll('.tz-item')]
                .some(i => i.style.display !== 'none');
            block.style.display = hasVisible ? '' : 'none';
        });

        tzCount.textContent = q ? visible + ' results' : '';
        tzNoResult.style.display = (q && visible === 0) ? 'block' : 'none';
    });
</script>
@endpush
