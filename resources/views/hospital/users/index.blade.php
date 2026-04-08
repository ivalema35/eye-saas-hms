@extends('hospital.layouts.app')

@section('title', 'Users')
@section('page-header', 'Users')

@section('page-actions')
    <a href="{{ route('hospital.users.create', ['slug' => $slug]) }}" class="hms-btn hms-btn-primary">
        <i class="fa-solid fa-user-plus"></i> Add User
    </a>
@endsection

@section('content')
<div class="hms-card">
    <div class="hms-card-header">
        <h3 class="hms-card-title">
            <i class="fa-solid fa-user-gear"></i> Staff Users
        </h3>
        <span class="hms-badge hms-badge-info">{{ $users->total() }} total</span>
    </div>
    <div class="hms-card-body">
        <table class="hms-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>
                            <strong>{{ $user->name }}</strong>
                        </td>
                        <td style="color:var(--hms-text-muted);font-size:.875rem">{{ $user->email }}</td>
                        <td>
                            @if($user->role)
                                <span style="display:inline-flex;align-items:center;gap:.375rem">
                                    <span style="width:8px;height:8px;border-radius:50%;background:{{ $user->role->color ?? '#1B4F72' }};display:inline-block"></span>
                                    {{ $user->role->name }}
                                </span>
                            @else
                                <span style="color:var(--hms-text-muted)">—</span>
                            @endif
                        </td>
                        <td>{{ $user->contact ?: '—' }}</td>
                        <td>
                            <span class="hms-badge {{ $user->status === 'active' ? 'hms-badge-success' : 'hms-badge-dark' }}">
                                {{ ucfirst($user->status) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-2">
                                @if(Route::has('hospital.users.edit'))
                                    <a href="{{ route('hospital.users.edit', ['slug' => $slug, 'id' => $user->id]) }}" class="btn btn-sm btn-outline-primary">
                                        Edit
                                    </a>
                                @endif
                                @if(Route::has('hospital.users.destroy'))
                                    <form method="POST" action="{{ route('hospital.users.destroy', ['slug' => $slug, 'id' => $user->id]) }}" onsubmit="return confirm('Delete this user?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endif
                                @if(! Route::has('hospital.users.edit') && ! Route::has('hospital.users.destroy'))
                                    <span style="color: var(--hms-text-muted); font-size: .875rem;">No actions configured</span>
                                @endif
                            </div>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="padding:3rem 1rem;text-align:center">
                            <x-empty-state
                                icon="fa-solid fa-user-gear"
                                title="No users yet"
                                description="Add your first user to get started."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-3">
            {{ $users->links() }}
        </div>
    </div>
</div>
@endsection
