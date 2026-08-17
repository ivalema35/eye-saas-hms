@extends('hospital.layouts.app')

@section('title', $roleFilterLabel ? $roleFilterLabel : 'Users')
{{-- Layout page-header intentionally unused — the heading, breadcrumb and
list all sit inside one bordered card, matching the Patients / OT panel
design. --}}

@section('content')
    <div class="users-premium-page">

        <div class="users-outer-card">
            <div class="users-header-block">
                <div class="users-header-title">
                    <i class="bi bi-person-gear"></i> {{ $roleFilterLabel ? $roleFilterLabel : 'Users' }}
                </div>
                <nav class="users-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}">Home</a>
                    <span class="users-breadcrumb-sep">/</span>
                    <span class="users-breadcrumb-current">{{ $roleFilterLabel ? $roleFilterLabel : 'Users' }}</span>
                </nav>
            </div>

            <div class="hms-card users-card">
                <div class="hms-card-header users-card-header">
                    <div class="users-title-wrap">
                        <span class="users-title-icon">
                            <i class="bi bi-person-gear"></i>
                        </span>
                        <div>
                            <h3 class="hms-card-title users-title">
                                {{ $roleFilterLabel ? $roleFilterLabel : 'Staff Users' }}
                            </h3>
                            <div class="users-subtitle">
                                @if(!empty($roleFilterLabel))
                                    Manage {{ strtolower($roleFilterLabel) }} — add, edit, password, activate/deactivate,
                                    delete.
                                @else
                                    Review team accounts and keep access information current.
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="users-header-actions">
                        <span class="hms-badge hms-badge-info users-count-pill">{{ $users->count() }} total</span>
                        <button type="button" class="hms-btn hms-btn-primary users-add-btn" data-bs-toggle="modal"
                            data-bs-target="#userFormModal" onclick="resetUserForm()">
                            <i class="bi bi-person-plus-fill"></i> Add User
                        </button>
                    </div>
                </div>

                <div class="hms-card-body users-card-body">
                    <div class="users-table-wrap">
                        <table class="hms-table users-table js-datatable">
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
                                            <div class="users-name-cell">
                                                <span class="users-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                                <strong>{{ $user->name }}</strong>
                                            </div>
                                        </td>
                                        <td class="users-email-cell" style="color:var(--hms-text-muted);font-size:.875rem">
                                            {{ $user->email }}</td>
                                        <td>
                                            @if($user->role)
                                                <span class="users-role-pill"
                                                    style="display:inline-flex;align-items:center;gap:.375rem">
                                                    <span class="users-role-marker"
                                                        style="width:8px;height:8px;border-radius:50%;background:{{ $user->role->color ?? '#1B4F72' }};display:inline-block"></span>
                                                    {{ $user->role->name }}
                                                </span>
                                            @else
                                                <span style="color:var(--hms-text-muted)">-</span>
                                            @endif
                                        </td>
                                        <td class="users-contact-cell">{{ $user->contact ?: '-' }}</td>
                                        <td>
                                            <span
                                                class="hms-badge users-status-badge {{ $user->status === 'active' ? 'hms-badge-success' : 'hms-badge-dark' }}">
                                                {{ ucfirst($user->status) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-2 users-action-group">
                                                @if(Route::has('hospital.users.edit'))
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-primary users-action-btn users-edit-btn"
                                                        onclick="openUserEditModal(@js($user))" title="Edit">
                                                        <i class="bi bi-pencil-fill"></i>
                                                    </button>
                                                @endif
                                                @if(Route::has('hospital.users.destroy'))
                                                    <form method="POST"
                                                        action="{{ route('hospital.users.destroy', array_filter(['slug' => $slug, 'id' => $user->id, 'role' => $roleFilterKey ?? null])) }}"
                                                        onsubmit="return confirm('Delete this user?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        @if(!empty($roleFilterKey))
                                                            <input type="hidden" name="role" value="{{ $roleFilterKey }}">
                                                        @endif
                                                        <button type="submit"
                                                            class="btn btn-sm btn-outline-danger users-action-btn users-delete-btn"
                                                            title="Delete">
                                                            <i class="bi bi-trash3-fill"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                @if(!Route::has('hospital.users.edit') && !Route::has('hospital.users.destroy'))
                                                    <span style="color: var(--hms-text-muted); font-size: .875rem;">No actions
                                                        configured</span>
                                                @endif
                                            </div>
                                        </td>

                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="users-empty-cell" style="padding:3rem 1rem;text-align:center">
                                            <x-empty-state icon="fa-solid fa-user-gear" title="No users yet"
                                                description="Add your first user to get started." />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>{{-- /.users-outer-card --}}
    </div>

    @include('hospital.users._user_modal')
@endsection

@push('styles')
    <style>
        .users-premium-page {
            --users-primary: #1B4F72;
            --users-secondary: #2980B9;
            --users-success: #27AE60;
            --users-soft: #ebf5fbeb;
            --users-border: rgba(27, 79, 114, .12);
            --users-border-strong: rgba(27, 79, 114, .22);
            --users-muted: rgba(27, 79, 114, .68);
            color: var(--users-primary);
            animation: users-page-in 420ms ease both;
        }

        .users-outer-card {
            background: #ffffff;
            border: 1px solid rgba(15, 79, 134, 0.12) !important;
            border-radius: 16px;
            box-shadow: 0 12px 32px rgba(15, 79, 134, 0.08);
            overflow: hidden;
            padding: 1.25rem 1.5rem 1.5rem;
        }

        .users-header-block {
            padding: 0 0 1rem;
        }

        .users-header-title {
            font-weight: 800;
            font-size: 1.3rem;
            color: var(--users-primary);
            letter-spacing: -.015em;
            display: flex;
            align-items: center;
            gap: .55rem;
        }

        .users-header-title i {
            color: var(--users-primary);
            font-size: 1.2rem;
        }

        .users-breadcrumb {
            margin-top: .4rem;
            display: flex;
            align-items: center;
            gap: .4rem;
            font-size: .85rem;
            color: #8891a0;
        }

        .users-breadcrumb a {
            color: #8891a0;
            text-decoration: none;
        }

        .users-breadcrumb a:hover {
            color: var(--users-primary);
        }

        .users-breadcrumb-sep {
            color: #c3c9d3;
        }

        .users-breadcrumb-current {
            color: #4a5568;
            font-weight: 600;
        }

        .users-header-actions {
            display: flex;
            align-items: center;
            gap: .65rem;
            flex-wrap: wrap;
        }

        .users-add-btn {
            background: #ffffff !important;
            border-color: #ffffff !important;
            color: var(--users-primary) !important;
            border-radius: 10px;
            font-weight: 700;
            font-size: .875rem;
            box-shadow: none;
        }

        .users-add-btn:hover {
            transform: translateY(-1px);
            background: #eaf3fa !important;
            box-shadow: 0 8px 18px rgba(0, 0, 0, .10);
        }

        .users-hero {
            display: flex;
            align-items: center;
            gap: .9rem;
            padding: 1.2rem 1.25rem;
            margin-bottom: 1rem;
            border: 1px solid var(--users-border);
            border-radius: 22px;
            background: linear-gradient(135deg, rgba(235, 245, 251, .96), rgba(255, 255, 255, .94));
            box-shadow: 0 18px 48px rgba(27, 79, 114, .10);
            position: relative;
            overflow: hidden;
        }

        .users-hero::before {
            content: '';
            position: absolute;
            inset: 0 auto 0 0;
            width: 5px;
            background: var(--users-success);
        }

        .users-hero-icon {
            width: 52px;
            height: 52px;
            border-radius: 17px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: var(--users-primary);
            color: #fff;
            font-size: 1.25rem;
            box-shadow: 0 14px 30px rgba(27, 79, 114, .22);
        }

        .users-kicker {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            color: var(--users-secondary);
            font-size: .72rem;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
            margin-bottom: .15rem;
        }

        .users-hero h4 {
            margin: 0;
            color: var(--users-primary);
            font-weight: 900;
            letter-spacing: -.2px;
        }

        .users-hero p {
            margin: .15rem 0 0;
            color: var(--users-muted);
            font-size: .9rem;
            font-weight: 650;
        }

        .users-card {
            border: 1px solid var(--users-border) !important;
            border-radius: 10px;
            background: rgba(255, 255, 255, .88);
            box-shadow: 0 4px 16px rgba(27, 79, 114, .06) !important;
            overflow: hidden;
            padding: 0;
            animation: users-card-rise 520ms cubic-bezier(.2, .9, .2, 1) both;
        }

        .users-card-header {
            background: var(--users-primary);
            border-bottom: 0 !important;
            padding: 1.15rem 1.5rem;
            margin-bottom: 0;
        }

        .users-title-wrap {
            display: flex;
            align-items: center;
            gap: .85rem;
        }

        .users-title-icon {
            width: 40px;
            height: 40px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: rgba(255, 255, 255, .14);
            color: #ffffff;
            border: none;
        }

        .users-title {
            color: #ffffff !important;
            font-weight: 800;
            letter-spacing: -.2px;
        }

        .users-subtitle {
            color: rgba(255, 255, 255, .78);
            font-size: .85rem;
            font-weight: 500;
            margin-top: .15rem;
        }

        .users-count-pill {
            background: rgba(255, 255, 255, .12) !important;
            color: #ffffff !important;
            border: 1px solid rgba(255, 255, 255, .28) !important;
            border-radius: 999px;
            padding: .52rem .85rem;
            font-weight: 700;
            box-shadow: none;
        }

        .users-card-body {
            padding: .9rem;
        }

        .users-table-wrap {
            overflow-x: auto;
        }

        .users-table {
            border-collapse: collapse;
            width: 100%;
            min-width: 900px;
        }

        .users-table thead th {
            background: #F8FAFC !important;
            color: #4A5568 !important;
            border: 0 !important;
            border-bottom: 1px solid #E2E8F0 !important;
            padding: .7rem 1rem;
            font-size: .75rem;
            letter-spacing: .05em;
            font-weight: 700;
            text-transform: uppercase;
            white-space: nowrap;
            text-align: left;
        }

        .users-table tbody tr {
            transition: background 150ms ease;
        }

        .users-table tbody tr:hover {
            background: #F5F8FC;
        }

        .users-table tbody td {
            background: transparent;
            border: 0;
            border-bottom: 1px solid rgba(27, 79, 114, .08);
            color: var(--users-primary);
            padding: .75rem 1rem;
            vertical-align: middle;
            font-weight: 600;
        }

        .users-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .users-name-cell {
            display: inline-flex;
            align-items: center;
            gap: .6rem;
        }

        .users-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--users-primary);
            color: #fff;
            font-weight: 900;
            box-shadow: 0 10px 20px rgba(27, 79, 114, .16);
        }

        .users-email-cell,
        .users-contact-cell {
            color: rgba(27, 79, 114, .68) !important;
            font-weight: 650;
        }

        .users-role-pill {
            padding: .36rem .72rem;
            border-radius: 999px;
            background: var(--users-soft);
            border: 1px solid var(--users-border);
            color: var(--users-primary);
            font-weight: 850;
        }

        .users-role-marker {
            box-shadow: 0 0 0 3px rgba(27, 79, 114, .08);
        }

        .users-status-badge {
            border-radius: 999px;
            font-weight: 900;
        }

        .users-action-group {
            align-items: center;
        }

        .users-action-btn {
            width: 34px;
            height: 34px;
            padding: 0 !important;
            border-radius: 50% !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, .78) !important;
            transition: transform 170ms ease, box-shadow 170ms ease, background 170ms ease, color 170ms ease;
        }

        .users-edit-btn {
            border-color: var(--users-border-strong) !important;
            color: var(--users-primary) !important;
        }

        .users-edit-btn:hover {
            background: var(--users-primary) !important;
            color: #fff !important;
        }

        .users-delete-btn:hover {
            background: #dc2626 !important;
            color: #fff !important;
        }

        .users-action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 22px rgba(27, 79, 114, .12);
        }

        .users-empty-cell {
            background: rgba(255, 255, 255, .94) !important;
            border: 1px dashed var(--users-border-strong) !important;
            border-radius: 16px !important;
        }

        .users-pagination {
            padding: .85rem .25rem 0;
        }

        @keyframes users-page-in {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes users-card-rise {
            from {
                opacity: 0;
                transform: translateY(12px) scale(.99);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes users-row-in {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .users-premium-page,
            .users-card,
            .users-table tbody tr {
                animation: none;
            }

            .users-premium-page * {
                transition: none !important;
            }
        }

        @media (max-width: 576px) {
            .users-hero {
                align-items: flex-start;
            }

            .users-hero-icon,
            .users-title-icon {
                width: 42px;
                height: 42px;
                border-radius: 14px;
            }

            .users-card-header {
                align-items: flex-start;
            }
        }
    </style>
@endpush