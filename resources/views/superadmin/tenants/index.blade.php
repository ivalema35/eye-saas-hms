@extends('superadmin.layouts.app')

@section('title', 'Hospitals')
@section('page-header', 'Hospitals')

@section('page-actions')
    <div style="display:flex;gap:.75rem;align-items:center">
        <span class="text-muted" style="font-size:.85rem">{{ $tenants->total() }} hospitals</span>
        <a href="{{ route('superadmin.hospitals.create') }}" class="hms-btn hms-btn-primary hms-btn-sm">
            <i class="fa-solid fa-plus"></i> Add Hospital
        </a>
    </div>
@endsection

@section('content')

    {{-- Filters --}}
    <div class="hms-card" style="margin-bottom:1rem;padding:1rem">
        <form method="GET" action="{{ route('superadmin.hospitals.index') }}" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end">
            <div class="hms-form-group" style="margin-bottom:0;flex:1;min-width:200px">
                <label style="font-size:.75rem">Search</label>
                <input type="text" name="search" class="hms-input" placeholder="Hospital name, slug, email, city..."
                       value="{{ request('search') }}">
            </div>
            <div class="hms-form-group" style="margin-bottom:0;min-width:150px">
                <label style="font-size:.75rem">Status</label>
                <select name="status" class="hms-select">
                    <option value="">All Statuses</option>
                    <option value="trial" {{ request('status') === 'trial' ? 'selected' : '' }}>Trial</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="grace" {{ request('status') === 'grace' ? 'selected' : '' }}>Grace</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                </select>
            </div>
            <div style="display:flex;gap:.5rem">
                <button type="submit" class="hms-btn hms-btn-primary hms-btn-sm"><i class="fa-solid fa-search"></i> Filter</button>
                <a href="{{ route('superadmin.hospitals.index') }}" class="hms-btn hms-btn-secondary hms-btn-sm">Clear</a>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="hms-card" style="padding:0">
        <div class="hms-card-header">
            <h3 class="hms-card-title"><i class="fa-solid fa-hospital-user"></i> Hospital List</h3>
            <span class="hms-badge hms-badge-info">{{ $tenants->total() }} total</span>
        </div>
        @if($tenants->count() === 0)
            <x-empty-state
                icon="fa-solid fa-hospital"
                title="No hospitals found"
                description="No hospitals match your search or filter criteria. Try adjusting the filters above." />
        @else
            <div class="hms-table-wrap" style="border:none">
                <table class="hms-table">
                    <thead>
                        <tr>
                            <th>Hospital</th>
                            <th>Admin</th>
                            <th>City</th>
                            <th>Status</th>
                            <th>Trial Ends</th>
                            <th>Registered</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tenants as $tenant)
                            <tr>
                                <td>
                                    <strong>{{ $tenant->name }}</strong>
                                    <div style="font-size:.75rem;color:var(--hms-text-muted)">{{ $tenant->slug }}</div>
                                </td>
                                <td>
                                    {{ $tenant->admin_name ?? '-' }}
                                    <div style="font-size:.75rem;color:var(--hms-text-muted)">{{ $tenant->admin_email ?? '' }}</div>
                                </td>
                                <td>{{ $tenant->city ?? '-' }}</td>
                                <td>
                                    <span class="hms-badge hms-badge-{{ $tenant->status ?? 'inactive' }}">
                                        {{ ucfirst($tenant->status ?? 'inactive') }}
                                    </span>
                                </td>
                                <td>{{ optional($tenant->trial_ends_at)->format('d M Y') ?? '-' }}</td>
                                <td>{{ $tenant->created_at->format('d M Y') }}</td>
                                <td style="text-align:right">
                                    <div style="display:flex;gap:.25rem;justify-content:flex-end;align-items:center">
                                        <a href="{{ route('superadmin.hospitals.show', $tenant) }}"
                                           class="hms-btn-icon" data-tooltip="View Details">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <a href="{{ route('superadmin.hospitals.edit', $tenant) }}"
                                           class="hms-btn-icon" data-tooltip="Edit Hospital">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        @if(in_array($tenant->status, ['suspended', 'inactive', 'grace'], true))
                                            <form method="POST" action="{{ route('superadmin.hospitals.activate', $tenant) }}" style="margin:0">
                                                @csrf
                                                <button type="submit" class="hms-btn-icon hms-btn-icon-success" data-tooltip="Activate Hospital">
                                                    <i class="fa-solid fa-check"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @if(in_array($tenant->status, ['active', 'trial', 'grace'], true))
                                            <form method="POST" action="{{ route('superadmin.hospitals.suspend', $tenant) }}" style="margin:0"
                                                  onsubmit="return confirm('Suspend this hospital?')">
                                                @csrf
                                                <button type="submit" class="hms-btn-icon hms-btn-icon-danger" data-tooltip="Suspend Hospital">
                                                    <i class="fa-solid fa-ban"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($tenants->hasPages())
                <div style="padding:1rem;border-top:1px solid var(--hms-border)">
                    {{ $tenants->links() }}
                </div>
            @endif
        @endif
    </div>

@endsection
