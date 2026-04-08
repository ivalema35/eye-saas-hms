{{--
    Stat Card Component
    Usage: <x-stat-card icon="fa-solid fa-users" label="Total Patients" value="123" meta="Today" color="blue" />
    Colors: blue, green, orange, teal, red, purple
--}}
@props([
    'icon'  => 'fa-solid fa-chart-bar',
    'label' => 'Label',
    'value' => '0',
    'meta'  => '',
    'color' => 'blue',
])

@php
    $colorMap = [
        'blue'   => 'hsi-blue',
        'green'  => 'hsi-green',
        'orange' => 'hsi-orange',
        'teal'   => 'hsi-teal',
        'red'    => 'hsi-red',
        'purple' => 'hsi-purple',
    ];
    $iconClass = $colorMap[$color] ?? 'hsi-blue';
@endphp

<div class="hms-stat-card">
    <div class="hms-stat-icon {{ $iconClass }}">
        <i class="{{ $icon }}"></i>
    </div>
    <div class="hms-stat-body">
        <div class="hms-stat-label">{{ $label }}</div>
        <div class="hms-stat-value">{{ $value }}</div>
        @if($meta)
            <div class="hms-stat-meta">{{ $meta }}</div>
        @endif
    </div>
</div>
