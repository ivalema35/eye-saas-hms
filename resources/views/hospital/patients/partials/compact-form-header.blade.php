@php
    $activeMode = $activeMode ?? 'walkin';
    $permSvc = app(\App\Services\Auth\RolePermissionService::class);
    $canPhone = $permSvc->can('opd.patient.register_phone');
    $walkinUrl = route('hospital.patients.create', ['slug' => $slug]);
    $phoneUrl = route('hospital.patients.create-phone', ['slug' => $slug]);
@endphp

<div class="rpc-mode-bar">
    <a href="{{ $walkinUrl }}"
        class="rpc-mode-btn rpc-mode-btn--walkin {{ $activeMode === 'walkin' ? 'is-active' : '' }}">
        <i class="bi bi-house-door-fill"></i> Walk In
    </a>
    @if($canPhone)
        <a href="{{ $phoneUrl }}"
            class="rpc-mode-btn rpc-mode-btn--phone {{ $activeMode === 'phone' ? 'is-active' : '' }}">
            <i class="bi bi-telephone-fill"></i> Phone
        </a>
    @endif
</div>
