{{--
    Coming Soon Component (for placeholder pages)
    Usage: <x-coming-soon
               icon="fa-solid fa-pills"
               title="Medicine Management"
               description="Full medicine master list with prescriptions and groups."
               :features="['Medicine CRUD', 'Medicine Groups', 'Quick Prescription', 'Stock Tracking']"
               phase="Phase 5"
               :backUrl="route('hospital.dashboard', ['slug' => $slug])"
           />
--}}
@props([
    'icon'        => 'fa-solid fa-rocket',
    'title'       => 'Coming Soon',
    'description' => 'This feature is under development.',
    'features'    => [],
    'phase'       => '',
    'backUrl'     => '',
])

<div class="hms-card">
    <div class="hms-coming-soon">
        <div class="hms-coming-soon-icon">
            <i class="{{ $icon }}"></i>
        </div>

        <h3>{{ $title }}</h3>
        <p>{{ $description }}</p>

        @if(count($features))
            <div class="hms-coming-soon-features">
                @foreach($features as $feature)
                    <span><i class="fa-solid fa-circle-check"></i> {{ $feature }}</span>
                @endforeach
            </div>
        @endif

        @if($phase)
            <div class="hms-coming-soon-phase">
                <i class="fa-solid fa-calendar-days"></i> {{ $phase }}
            </div>
        @endif

        @if($backUrl)
            <div style="margin-top:1.25rem">
                <a href="{{ $backUrl }}" class="hms-btn hms-btn-outline">
                    <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        @endif
    </div>
</div>
