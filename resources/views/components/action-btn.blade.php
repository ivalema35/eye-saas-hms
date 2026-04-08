{{--
    Action Button Component (dashboard quick-action grid)
    Usage: <x-action-btn icon="fa-solid fa-user-plus" label="Add Patient" url="/patients/create" color="blue" />
    Colors: blue, teal, orange, green, purple, gray
--}}
@props([
    'icon'  => 'fa-solid fa-arrow-right',
    'label' => 'Action',
    'url'   => '#',
    'color' => 'blue',
])

@php
    $colorMap = [
        'blue'   => 'qa-blue',
        'teal'   => 'qa-teal',
        'orange' => 'qa-orange',
        'green'  => 'qa-green',
        'purple' => 'qa-purple',
        'gray'   => 'qa-gray',
    ];
    $iconClass = $colorMap[$color] ?? 'qa-blue';
@endphp

<a href="{{ $url }}" class="hms-qa-btn">
    <span class="hms-qa-icon {{ $iconClass }}">
        <i class="{{ $icon }}"></i>
    </span>
    {{ $label }}
</a>
