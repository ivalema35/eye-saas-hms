@extends('hospital.layouts.app')

@section('title', 'Users')
@section('page-header', 'Users')

@section('page-actions')
    <button type="button"
            class="hms-btn hms-btn-primary users-add-btn"
            data-bs-toggle="modal"
            data-bs-target="#userFormModal"
            onclick="resetUserForm()">
        <i class="bi bi-person-plus-fill"></i> Add User
    </button>
@endsection

@section('content')
<div class="users-premium-page">
   

    <div class="hms-card users-card">
        <div class="hms-card-header users-card-header">
            <div class="users-title-wrap">
                <span class="users-title-icon">
                    <i class="bi bi-person-gear"></i>
                </span>
                <div>
                    <h3 class="hms-card-title users-title">
                        Staff Users
                    </h3>
                    <div class="users-subtitle">Review team accounts and keep access information current.</div>
                </div>
            </div>
            <span class="hms-badge hms-badge-info users-count-pill">{{ $users->total() }} total</span>
        </div>

        <div class="hms-card-body users-card-body">
            <div class="users-table-wrap">
                <table class="hms-table users-table">
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
                                <td class="users-email-cell" style="color:var(--hms-text-muted);font-size:.875rem">{{ $user->email }}</td>
                                <td>
                                    @if($user->role)
                                        <span class="users-role-pill" style="display:inline-flex;align-items:center;gap:.375rem">
                                            <span class="users-role-marker" style="width:8px;height:8px;border-radius:50%;background:{{ $user->role->color ?? '#1B4F72' }};display:inline-block"></span>
                                            {{ $user->role->name }}
                                        </span>
                                    @else
                                        <span style="color:var(--hms-text-muted)">-</span>
                                    @endif
                                </td>
                                <td class="users-contact-cell">{{ $user->contact ?: '-' }}</td>
                                <td>
                                    <span class="hms-badge users-status-badge {{ $user->status === 'active' ? 'hms-badge-success' : 'hms-badge-dark' }}">
                                        {{ ucfirst($user->status) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2 users-action-group">
                                        @if(Route::has('hospital.users.edit'))
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-primary users-action-btn users-edit-btn"
                                                    onclick="openUserEditModal(@js($user))"
                                                    title="Edit">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                        @endif
                                        @if(Route::has('hospital.users.destroy'))
                                            <form method="POST" action="{{ route('hospital.users.destroy', ['slug' => $slug, 'id' => $user->id]) }}" onsubmit="return confirm('Delete this user?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger users-action-btn users-delete-btn" title="Delete">
                                                    <i class="bi bi-trash3-fill"></i>
                                                </button>
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
                                <td colspan="6" class="users-empty-cell" style="padding:3rem 1rem;text-align:center">
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
            </div>

            <div class="mt-3 users-pagination">
                {{ $users->links() }}
            </div>
        </div>
    </div>
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

    .users-add-btn {
        background: var(--users-primary, #1B4F72) !important;
        border-color: var(--users-primary, #1B4F72) !important;
        color: #fff !important;
        border-radius: 12px;
        font-weight: 900;
        box-shadow: 0 12px 26px rgba(27, 79, 114, .16);
    }

    .users-add-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 16px 34px rgba(27, 79, 114, .22);
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
        border-radius: 22px;
        background: rgba(255, 255, 255, .88);
        box-shadow: 0 18px 48px rgba(27, 79, 114, .10) !important;
        overflow: hidden;
        padding: 0;
        animation: users-card-rise 520ms cubic-bezier(.2,.9,.2,1) both;
    }

    .users-card-header {
        background: linear-gradient(135deg, rgba(235, 245, 251, .96), rgba(255, 255, 255, .94));
        border-bottom: 1px solid var(--users-border) !important;
        padding: 1.1rem 1.25rem;
        margin-bottom: 0;
    }

    .users-title-wrap {
        display: flex;
        align-items: center;
        gap: .85rem;
    }

    .users-title-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: rgba(235, 245, 251, .95);
        color: var(--users-primary);
        border: 1px solid var(--users-border-strong);
    }

    .users-title {
        color: var(--users-primary);
        font-weight: 900;
        letter-spacing: -.2px;
    }

    .users-subtitle {
        color: var(--users-muted);
        font-size: .84rem;
        font-weight: 650;
        margin-top: .12rem;
    }

    .users-count-pill {
        background: rgba(255, 255, 255, .78) !important;
        color: var(--users-primary) !important;
        border: 1px solid var(--users-border) !important;
        border-radius: 999px;
        padding: .52rem .85rem;
        font-weight: 900;
        box-shadow: 0 10px 22px rgba(27, 79, 114, .08);
    }

    .users-card-body {
        padding: .9rem;
        background: var(--users-soft);
    }

    .users-table-wrap {
        overflow-x: auto;
    }

    .users-table {
        border-collapse: separate;
        border-spacing: 0 8px;
        min-width: 900px;
    }

    .users-table thead tr,
    .users-table thead th {
        background: var(--users-primary) !important;
    }

    .users-table thead th {
        color: #fff !important;
        border: 0 !important;
        padding: .9rem 1rem;
        font-size: .72rem;
        letter-spacing: .08em;
        font-weight: 900;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .users-table thead th:first-child {
        border-top-left-radius: 14px;
        border-bottom-left-radius: 14px;
    }

    .users-table thead th:last-child {
        border-top-right-radius: 14px;
        border-bottom-right-radius: 14px;
    }

    .users-table tbody tr {
        animation: users-row-in 460ms ease both;
        transition: transform 170ms ease, box-shadow 170ms ease;
    }

    .users-table tbody tr:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 34px rgba(27, 79, 114, .10);
    }

    .users-table tbody td {
        background: rgba(255, 255, 255, .94);
        border-top: 1px solid rgba(27, 79, 114, .08);
        border-bottom: 1px solid rgba(27, 79, 114, .08);
        color: var(--users-primary);
        padding: .9rem 1rem;
        vertical-align: middle;
        font-weight: 650;
    }

    .users-table tbody td:first-child {
        border-left: 1px solid rgba(27, 79, 114, .08);
        border-top-left-radius: 14px;
        border-bottom-left-radius: 14px;
    }

    .users-table tbody td:last-child {
        border-right: 1px solid rgba(27, 79, 114, .08);
        border-top-right-radius: 14px;
        border-bottom-right-radius: 14px;
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
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes users-card-rise {
        from { opacity: 0; transform: translateY(12px) scale(.99); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    @keyframes users-row-in {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
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
