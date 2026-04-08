{{--
    Alert Component
    Usage: <x-alert type="success" message="Saved!" />
           <x-alert type="danger">Something went wrong.</x-alert>
    Types: success, danger, warning, info
--}}
@props([
    'type'        => 'info',
    'message'     => '',
    'dismissible' => false,
])

@php
    $iconMap = [
        'success' => 'fa-solid fa-circle-check',
        'danger'  => 'fa-solid fa-circle-exclamation',
        'warning' => 'fa-solid fa-triangle-exclamation',
        'info'    => 'fa-solid fa-circle-info',
    ];
    $icon = $iconMap[$type] ?? $iconMap['info'];
@endphp

<div class="hms-alert hms-alert-{{ $type }}" @if($dismissible) data-dismissible @endif>
    <i class="{{ $icon }}"></i>
    <div style="flex:1">{{ $message ?: $slot }}</div>
    @if($dismissible)
        <button type="button" onclick="this.parentElement.remove()"
                style="background:none;border:none;cursor:pointer;font-size:1rem;color:inherit;opacity:.7;padding:0">
            <i class="fa-solid fa-xmark"></i>
        </button>
    @endif
</div>
