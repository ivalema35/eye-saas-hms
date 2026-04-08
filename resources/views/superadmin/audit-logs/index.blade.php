@extends('superadmin.layouts.app')
@section('title', 'Audit Logs')
@section('page-header', 'Audit Logs')

@section('content')

<div class="hms-card" style="padding:1rem;margin-bottom:1rem">
    <form method="GET" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end">
        <div class="hms-form-group" style="margin-bottom:0;flex:1;min-width:180px">
            <label style="font-size:.75rem">Action</label>
            <input type="text" name="action" class="hms-input"
                   value="{{ request('action') }}" placeholder="e.g. hospital.suspended">
        </div>
        <div class="hms-form-group" style="margin-bottom:0;min-width:180px">
            <label style="font-size:.75rem">Hospital</label>
            <select name="tenant_id" class="hms-select">
                <option value="">All Hospitals</option>
                @foreach($tenants as $t)
                    <option value="{{ $t->id }}" {{ request('tenant_id')==$t->id ? 'selected':'' }}>{{ $t->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="hms-form-group" style="margin-bottom:0">
            <label style="font-size:.75rem">From</label>
            <input type="date" name="from" class="hms-input" value="{{ request('from') }}" style="width:140px">
        </div>
        <div class="hms-form-group" style="margin-bottom:0">
            <label style="font-size:.75rem">To</label>
            <input type="date" name="to" class="hms-input" value="{{ request('to') }}" style="width:140px">
        </div>
        <div style="display:flex;gap:.5rem">
            <button type="submit" class="hms-btn hms-btn-primary hms-btn-sm">
                <i class="fa-solid fa-filter"></i> Filter
            </button>
            <a href="{{ route('superadmin.audit-logs.index') }}" class="hms-btn hms-btn-secondary hms-btn-sm">Clear</a>
        </div>
    </form>
</div>

<div class="hms-card" style="padding:0">
    <div class="hms-table-wrap" style="border:none">
        <table class="hms-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>Hospital</th>
                    <th>Admin</th>
                    <th>IP</th>
                    <th>Date/Time</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                @php
                    $actionColor = match(true) {
                        str_contains($log->action, 'created')     => 'background:#D5F5E3;color:#1A6F5B',
                        str_contains($log->action, 'activated')   => 'background:#D6EAF8;color:#154360',
                        str_contains($log->action, 'suspended')   => 'background:#FADBD8;color:#641E16',
                        str_contains($log->action, 'archived')    => 'background:#FDEBD0;color:#784212',
                        str_contains($log->action, 'payment')     => 'background:#D5F5E3;color:#1A6F5B',
                        default                                    => 'background:#F4F6F7;color:#2C3E50',
                    };
                @endphp
                <tr>
                    <td style="color:var(--hms-text-muted);font-size:.75rem">{{ $log->id }}</td>
                    <td>
                        <span class="hms-badge" style="{{ $actionColor }};font-size:.7rem">{{ $log->action }}</span>
                    </td>
                    <td style="font-size:.85rem;max-width:220px">{{ $log->description }}</td>
                    <td style="font-size:.85rem">
                        @if($log->tenant)
                            <a href="{{ route('superadmin.hospitals.show', $log->tenant) }}">{{ $log->tenant->name }}</a>
                            <div style="font-size:.72rem;color:var(--hms-text-muted)">{{ $log->tenant->slug }}</div>
                        @else
                            <span style="color:var(--hms-text-muted)">Platform</span>
                        @endif
                    </td>
                    <td style="font-size:.85rem">{{ $log->admin?->name ?? '—' }}</td>
                    <td style="font-family:var(--hms-font-mono);font-size:.75rem;color:var(--hms-text-muted)">{{ $log->ip_address ?? '—' }}</td>
                    <td style="white-space:nowrap;font-size:.82rem">
                        {{ $log->created_at->format('d M Y') }}
                        <div style="font-size:.72rem;color:var(--hms-text-muted)">{{ $log->created_at->format('h:i A') }}</div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:2rem;color:var(--hms-text-muted)">No audit logs found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
    <div style="padding:1rem;border-top:1px solid var(--hms-border)">
        {{ $logs->withQueryString()->links() }}
    </div>
    @endif
</div>

@endsection
