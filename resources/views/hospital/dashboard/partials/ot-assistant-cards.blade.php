{{--
  Other OT assistants and how many Ready patients are assigned to each.
  Required: $slug, $otAssistantCards, $activeAssistantId
  Optional: $cardRoute (defaults to the home dashboard)
--}}
@php
    $cardRoute = $cardRoute ?? 'hospital.dashboard';
    $selfId = (int) auth('hospital_user')->id();
    $others = ($otAssistantCards ?? collect())->reject(fn ($assistant) => (int) $assistant->id === $selfId)->values();
@endphp

@once
@push('styles')
<style>
    .ota-peer-label {
        font-size: .78rem;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: rgba(27, 79, 114, .72);
        margin-bottom: .55rem;
    }
    .ota-peer-grid {
        display: flex;
        flex-wrap: wrap;
        gap: .75rem;
    }
    .ota-peer-card {
        display: flex;
        align-items: center;
        gap: .7rem;
        min-width: 196px;
        background: #fff;
        border: 1px solid rgba(27, 79, 114, .14);
        border-radius: 14px;
        padding: .75rem .9rem;
        text-decoration: none;
        color: #1B4F72;
        box-shadow: 0 8px 20px rgba(27, 79, 114, .06);
    }
    .ota-peer-card:hover,
    .ota-peer-card.is-active {
        border-color: #1B4F72;
        background: #EBF5FB;
        color: #1B4F72;
    }
    .ota-peer-avatar {
        width: 36px;
        height: 36px;
        border-radius: 12px;
        background: #EBF5FB;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.05rem;
        flex: 0 0 auto;
    }
    .ota-peer-card.is-active .ota-peer-avatar { background: #fff; }
    .ota-peer-body { display: flex; flex-direction: column; min-width: 0; }
    .ota-peer-name { font-weight: 800; font-size: .92rem; line-height: 1.2; }
    .ota-peer-count { font-size: .78rem; font-weight: 700; color: rgba(27, 79, 114, .72); }
    .bento-dashboard > .ota-peer-beside {
        grid-column: span 8;
        margin: 0;
        align-self: stretch;
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 10px 28px rgba(27, 79, 114, .08);
        padding: 1rem 1.1rem;
        min-height: 118px;
    }
    @media (max-width: 900px) {
        .bento-dashboard > .ota-peer-beside { grid-column: span 12; }
    }
</style>
@endpush
@endonce

@if($others->isNotEmpty())
    <div class="ota-peer-wrap {{ !empty($besideCompleted) ? 'ota-peer-beside' : 'mb-3' }}">
        <div class="ota-peer-label">Other OT Assistants</div>
        <div class="ota-peer-grid">
            @foreach($others as $assistant)
                @php
                    $isActive = (int) ($activeAssistantId ?? 0) === (int) $assistant->id;
                    $url = route($cardRoute, ['slug' => $slug, 'view_assistant' => $assistant->id]);
                @endphp
                <a href="{{ $url }}" class="ota-peer-card {{ $isActive ? 'is-active' : '' }}">
                    <span class="ota-peer-avatar"><i class="bi bi-person-badge"></i></span>
                    <span class="ota-peer-body">
                        <span class="ota-peer-name">{{ $assistant->name }}</span>
                        <span class="ota-peer-count">Assigned: {{ (int) ($assistant->assigned_count ?? 0) }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    </div>
@endif
