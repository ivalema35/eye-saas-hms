{{--
    HMS Table Component (responsive data table wrapper)
    Usage: <x-hms-table :headers="['Name', 'Email', 'Status', 'Actions']" empty="No users found.">
               @foreach($users as $user)
                   <tr>
                       <td>{{ $user->name }}</td>
                       <td>{{ $user->email }}</td>
                       <td><x-badge type="success" text="Active" /></td>
                       <td>...</td>
                   </tr>
               @endforeach
           </x-hms-table>
--}}
@props([
    'headers'   => [],
    'empty'     => 'No records found.',
    'emptyIcon' => 'fa-solid fa-inbox',
    'hasData'   => true,
])

@if($hasData)
    <div class="hms-table-wrap">
        <table class="hms-table">
            @if(count($headers))
                <thead>
                    <tr>
                        @foreach($headers as $header)
                            <th>{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
            @endif
            <tbody>
                {{ $slot }}
            </tbody>
        </table>
    </div>
@else
    <x-empty-state :icon="$emptyIcon" :title="$empty" />
@endif
