@php
    $activeMode = $activeMode ?? 'walkin';
@endphp

<div class="rpc-mode-bar">
    @haspermission('patient_register')
    <a href="{{ route('hospital.patients.create', ['slug' => $slug]) }}"
        class="rpc-mode-btn rpc-mode-btn--walkin {{ $activeMode === 'walkin' ? 'is-active' : '' }}">
        <i class="bi bi-house-door-fill"></i> Walk In
    </a>
    @endhaspermission
    @haspermission('patient_register_phone')
        <a href="{{ route('hospital.patients.create-phone', ['slug' => $slug]) }}"
            class="rpc-mode-btn rpc-mode-btn--phone {{ $activeMode === 'phone' ? 'is-active' : '' }}">
            <i class="bi bi-telephone-fill"></i> Phone
        </a>
    @endhaspermission
</div>
