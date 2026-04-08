{{--
    Empty State Component (for tables/lists with no data)
    Usage: <x-empty-state icon="fa-solid fa-users" title="No Users Found" description="Add a user to get started.">
               <a href="/users/create" class="hms-btn hms-btn-primary">Add User</a>
           </x-empty-state>
--}}
@props([
    'icon'        => 'fa-solid fa-inbox',
    'title'       => 'No Data Found',
    'description' => '',
])

<div class="hms-empty-state">
    <div class="hms-empty-state-icon">
        <i class="{{ $icon }}"></i>
    </div>
    <h4>{{ $title }}</h4>
    @if($description)
        <p>{{ $description }}</p>
    @endif
    @if($slot->isNotEmpty())
        <div>{{ $slot }}</div>
    @endif
</div>
