{{--
    Page Header Component
    Usage: <x-page-header title="Patients" subtitle="Manage patient records">
               <a href="..." class="hms-btn hms-btn-primary">Add New</a>
           </x-page-header>
--}}
@props([
    'title'    => '',
    'subtitle' => '',
])

<div class="hms-page-header">
    <div>
        <h1>{{ $title }}</h1>
        @if($subtitle)
            <p style="font-size:.8rem;color:var(--hms-text-muted);margin:.25rem 0 0;">{{ $subtitle }}</p>
        @endif
    </div>
    @if($slot->isNotEmpty())
        <div class="hms-page-actions">{{ $slot }}</div>
    @endif
</div>
