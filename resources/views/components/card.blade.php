{{--
    Card Component
    Usage: <x-card title="Recent Activity" subtitle="Last 7 days">
               ... content ...
           </x-card>
    Or:    <x-card>... content ...</x-card>
--}}
@props([
    'title'    => '',
    'subtitle' => '',
    'noPad'    => false,
])

<div {{ $attributes->merge(['class' => 'hms-card']) }} @if($noPad) style="padding:0" @endif>
    @if($title)
        <div class="hms-card-header">
            <div>
                <h3 class="hms-card-title">{{ $title }}</h3>
                @if($subtitle)
                    <p class="hms-card-subtitle">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="hms-page-actions">{{ $actions }}</div>
            @endisset
        </div>
    @endif
    {{ $slot }}
</div>
