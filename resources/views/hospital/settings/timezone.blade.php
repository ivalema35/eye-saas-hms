@extends('hospital.layouts.app')
@section('title', 'Timezone Settings')
@section('page-header', 'Timezone Settings')

@section('content')
<div style="max-width:700px; margin:0 auto">

    {{-- Status Card --}}
    <div class="hms-card mb-4">
        <div class="hms-card-header">
            <h3 class="hms-card-title">
                <i class="bi bi-clock-history" style="color:#1B4F72"></i>
                Current Timezone
            </h3>
            @if($isOverride)
                <span class="hms-badge" style="background:#FEF3C7;color:#92400E">
                    <i class="bi bi-pencil-fill me-1"></i> Manually Overridden
                </span>
            @else
                <span class="hms-badge hms-badge-info">
                    <i class="bi bi-globe2 me-1"></i> Country Default
                </span>
            @endif
        </div>
        <div class="hms-card-body">
            <div style="display:flex;align-items:center;gap:1rem;padding:.75rem 1rem;background:#EFF6FF;border-radius:10px;border:1px solid #BFDBFE">
                <i class="bi bi-geo-alt-fill" style="font-size:1.5rem;color:#1B4F72"></i>
                <div>
                    <div style="font-size:1.1rem;font-weight:700;color:#1B4F72">{{ $current }}</div>
                    <div style="font-size:.8rem;color:#64748B">
                        Current time:
                        <strong>{{ \Carbon\Carbon::now($current)->format('d M Y, h:i:s A') }}</strong>
                    </div>
                </div>
            </div>

            @if($isOverride && $tenant->country)
                <div class="mt-3">
                    <form method="POST" action="{{ route('hospital.settings.timezone.reset', ['slug' => request()->route('slug')]) }}"
                          onsubmit="return confirm('Reset to country default timezone?')">
                        @csrf
                        <button type="submit" class="hms-btn hms-btn-outline hms-btn-sm">
                            <i class="bi bi-arrow-counterclockwise"></i>
                            Reset to Country Default
                            @php
                                $countryTz = \App\Models\Platform\MasterCountry::whereRaw('LOWER(name) = ?', [strtolower($tenant->country)])->value('default_timezone');
                            @endphp
                            @if($countryTz)
                                <span class="ms-1 text-muted">({{ $countryTz }})</span>
                            @endif
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    {{-- Success / Error Alerts --}}
    @if(session('success'))
        <div class="hms-alert hms-alert-success mb-3">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="hms-alert hms-alert-danger mb-3">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ $errors->first() }}
        </div>
    @endif

    {{-- Update Form --}}
    <div class="hms-card">
        <div class="hms-card-header">
            <h3 class="hms-card-title">
                <i class="bi bi-pencil-square" style="color:#1B4F72"></i>
                Change Timezone
            </h3>
        </div>
        <div class="hms-card-body">
            <form method="POST" action="{{ route('hospital.settings.timezone.update', ['slug' => request()->route('slug')]) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="hms-form-label" for="timezone">
                        Select Timezone
                        <span class="text-danger">*</span>
                    </label>
                    <select name="timezone" id="timezone" class="hms-form-control" required>
                        @foreach($groupedTimezones as $region => $zones)
                            <optgroup label="{{ $region }}">
                                @foreach($zones as $tz)
                                    <option value="{{ $tz }}" @selected($tz === $current)>
                                        {{ $tz }}
                                        ({{ \Carbon\Carbon::now($tz)->format('h:i A') }})
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <div class="hms-form-hint">
                        <i class="bi bi-info-circle me-1"></i>
                        Changing this will override the country default. All dates/times will display in selected timezone.
                    </div>
                </div>

                {{-- Live preview --}}
                <div id="tz-preview" style="display:none;padding:.6rem 1rem;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:8px;margin-bottom:1rem;font-size:.875rem">
                    <i class="bi bi-clock me-1" style="color:#16A34A"></i>
                    Selected timezone current time: <strong id="tz-preview-time"></strong>
                </div>

                <div style="display:flex;gap:.75rem">
                    <button type="submit" class="hms-btn hms-btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Save Timezone
                    </button>
                    <a href="{{ route('hospital.settings.index', ['slug' => request()->route('slug')]) }}"
                       class="hms-btn hms-btn-outline">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    // Live timezone preview using Intl API
    const sel = document.getElementById('timezone');
    const preview = document.getElementById('tz-preview');
    const previewTime = document.getElementById('tz-preview-time');

    function updatePreview() {
        const tz = sel.value;
        try {
            const now = new Date();
            const formatted = now.toLocaleString('en-IN', {
                timeZone: tz,
                day: '2-digit', month: 'short', year: 'numeric',
                hour: '2-digit', minute: '2-digit', second: '2-digit',
                hour12: true
            });
            previewTime.textContent = formatted;
            preview.style.display = 'block';
        } catch (e) {
            preview.style.display = 'none';
        }
    }

    sel.addEventListener('change', updatePreview);
    updatePreview();
</script>
@endpush
