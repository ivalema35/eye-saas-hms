@extends('superadmin.layouts.app')

@section('title', 'Hospitals')
@section('page-header', 'Hospitals')

@section('page-actions')
    <button type="button" class="hms-btn hms-btn-primary hms-btn-sm" style="color:#1b4f72;"
        data-bs-toggle="modal" data-bs-target="#addHospitalModal">
        <i class="bi bi-plus-lg"></i> Add Hospital
    </button>
@endsection

@section('content')

    @if(($pendingCount ?? 0) > 0 && request('status') !== 'pending')
        <div class="hms-card" style="margin-bottom:1.25rem;padding:1rem 1.25rem;border-color:rgba(217,119,6,.28);display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">
            <div>
                <strong style="color:#0f4f86">{{ $pendingCount }} registration request{{ $pendingCount === 1 ? '' : 's' }} waiting</strong>
                <div style="font-size:.8rem;color:#64748B">Accept a request before that hospital admin can login.</div>
            </div>
            <a href="{{ route('superadmin.hospitals.index', ['status' => 'pending']) }}" class="hms-btn hms-btn-primary hms-btn-sm" style="color:#1b4f72">
                Review requests
            </a>
        </div>
    @endif

    {{-- Filter Card --}}
    <div class="hms-card" style="margin-bottom:1.25rem;padding:1.25rem">
        <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:1rem">
            <i class="bi bi-funnel-fill" style="color:#1B4F72;font-size:.875rem"></i>
            <span
                style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748B">Filters</span>
        </div>
        <form method="GET" action="{{ route('superadmin.hospitals.index') }}"
            style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end">
            <div class="hms-form-group" style="margin-bottom:0;flex:1;min-width:200px">
                <label class="hms-label">Search</label>
                <input type="text" name="search" class="hms-input" placeholder="Hospital name, slug, email, city..."
                    value="{{ request('search') }}">
            </div>
            <div class="hms-form-group" style="margin-bottom:0;min-width:160px">
                <label class="hms-label">Status</label>
                <select name="status" class="hms-select">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="trial" {{ request('status') === 'trial' ? 'selected' : '' }}>Trial</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="grace" {{ request('status') === 'grace' ? 'selected' : '' }}>Grace</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                </select>
            </div>
            <div style="display:flex;gap:.5rem">
                <button type="submit" class="hms-btn hms-btn-primary hms-btn-sm" style="color: #1b4f72;">
                    <i class="bi bi-search"></i> Filter
                </button>
                <a href="{{ route('superadmin.hospitals.index') }}" class="hms-btn hms-btn-outline hms-btn-sm">
                    Clear
                </a>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="hms-card" style="padding:0">
        <div class="hms-card-header">
            <h3 class="hms-card-title">
                <i class="bi bi-hospital-fill" style="color:#1B4F72"></i>
                Hospital List
            </h3>
            <span class="hms-badge hms-badge-info">{{ $tenants->total() }} total</span>
        </div>

        @if($tenants->count() === 0)
            <x-empty-state icon="bi bi-hospital-fill" title="No hospitals found"
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
                                    <span style="font-weight:600;color:#1A202C">{{ $tenant->name }}</span>
                                    <div style="font-size:.75rem;color:#64748B">{{ $tenant->slug }}</div>
                                </td>
                                <td>
                                    {{ $tenant->admin_name ?? '—' }}
                                    @if($tenant->admin_email)
                                        <div style="font-size:.75rem;color:#64748B">{{ $tenant->admin_email }}</div>
                                    @endif
                                </td>
                                <td>{{ $tenant->city ?? '—' }}</td>
                                <td>
                                    <span class="hms-badge hms-badge-{{ $tenant->displayStatus() }}">
                                        {{ ucfirst($tenant->displayStatus()) }}
                                    </span>
                                </td>
                                <td style="font-size:.85rem;color:#64748B">
                                    {{ optional($tenant->trial_ends_at)->format('d M Y') ?? '—' }}
                                </td>
                                <td style="font-size:.85rem;color:#64748B">
                                    {{ $tenant->created_at->format('d M Y') }}
                                </td>
                                <td style="text-align:right">
                                    <div style="display:flex;gap:.25rem;justify-content:flex-end;align-items:center">
                                        <a href="{{ route('superadmin.hospitals.show', $tenant) }}" class="hms-btn-icon"
                                            data-tooltip="View Details">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>
                                        @if($tenant->status === 'pending')
                                            <form method="POST" action="{{ route('superadmin.hospitals.approve', $tenant) }}"
                                                style="margin:0">
                                                @csrf
                                                <button type="submit" class="hms-btn-icon hms-btn-icon-success"
                                                    data-tooltip="Accept registration">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('superadmin.hospitals.reject', $tenant) }}"
                                                style="margin:0" onsubmit="return confirm('Reject this registration?')">
                                                @csrf
                                                <button type="submit" class="hms-btn-icon hms-btn-icon-danger"
                                                    data-tooltip="Reject registration">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </form>
                                        @else
                                        <button type="button" class="hms-btn-icon"
                                            style="background:transparent;border:0;padding:0;cursor:pointer"
                                            data-tooltip="Edit Hospital" data-bs-toggle="modal"
                                            data-bs-target="#editHospitalModal-{{ $tenant->id }}">
                                            <i class="bi bi-pencil-fill"></i>
                                        </button>
                                        @if(in_array($tenant->status, ['suspended', 'inactive', 'grace'], true))
                                            <form method="POST" action="{{ route('superadmin.hospitals.activate', $tenant) }}"
                                                style="margin:0">
                                                @csrf
                                                <button type="submit" class="hms-btn-icon hms-btn-icon-success"
                                                    data-tooltip="Activate Hospital">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @if(in_array($tenant->status, ['active', 'trial', 'grace'], true))
                                            <form method="POST" action="{{ route('superadmin.hospitals.suspend', $tenant) }}"
                                                style="margin:0" onsubmit="return confirm('Suspend this hospital?')">
                                                @csrf
                                                <button type="submit" class="hms-btn-icon hms-btn-icon-danger"
                                                    data-tooltip="Suspend Hospital">
                                                    <i class="bi bi-ban"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($tenants->hasPages())
                <div style="padding:1rem;border-top:1px solid rgba(27,79,114,.1)">
                    {{ $tenants->links() }}
                </div>
            @endif
        @endif
    </div>

@endsection

@push('modals')
    @include('superadmin.tenants._create_modal')

    @foreach($tenants as $tenant)
        <div class="modal fade" id="editHospitalModal-{{ $tenant->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content" style="border-radius:14px;border:none;box-shadow:0 25px 80px rgba(0,0,0,.2)">
                    <div class="modal-header" style="border-bottom:1px solid rgba(27,79,114,.12);padding:1.25rem 1.5rem">
                        <h5 class="modal-title"
                            style="font-weight:700;font-size:1rem;color:#1B4F72;display:flex;align-items:center;gap:.5rem">
                            <i class="bi bi-pencil-square"></i>
                            Edit Hospital: {{ $tenant->name }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" style="padding:1.5rem">
                        @include('superadmin.tenants._form', [
                            'tenant' => $tenant,
                            'action' => route('superadmin.hospitals.update', $tenant),
                            'editTenantId' => $tenant->id,
                            'submitLabel' => 'Save Changes',
                        ])
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endpush

@push('scripts')
    @if(old('edit_tenant_id'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modal = document.getElementById('editHospitalModal-{{ old('edit_tenant_id') }}');
                if (modal && window.bootstrap) {
                    bootstrap.Modal.getOrCreateInstance(modal).show();
                }
            });
        </script>
    @endif
@endpush