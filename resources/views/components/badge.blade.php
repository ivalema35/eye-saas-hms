{{--
    Badge Component
    Usage: <x-badge type="success" text="Active" />
           <x-badge type="warning">Pending</x-badge>
    Types: success, warning, danger, info, trial, grace, suspended, inactive, purple, dark
--}}
@props([
    'type' => 'info',
    'text' => '',
])

<span class="hms-badge hms-badge-{{ $type }}">{{ $text ?: $slot }}</span>
