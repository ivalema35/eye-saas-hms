@extends('superadmin.layouts.app')
@section('title', 'Audit Logs')
@section('page-header', 'Audit Logs')

@section('content')

{{-- Filter Card --}}
<div class="hms-card" style="padding:1.25rem;margin-bottom:1.25rem">
    <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:1rem">
        <i class="bi bi-funnel-fill" style="color:#1B4F72;font-size:.875rem"></i>
        <span style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748B">Filters</span>
    </div>
    <form method="GET" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end">
        <div class="hms-form-group" style="margin-bottom:0;flex:1;min-width:180px">
            <label class="hms-label">Action</label>
            <input type="text" name="action" class="hms-input"
                   value="{{ request('action') }}" placeholder="e.g. hospital.suspended">
        </div>
        <div class="hms-form-group" style="margin-bottom:0;min-width:180px">
            <label class="hms-label">Hospital</label>
            <select name="tenant_id" class="hms-select">
                <option value="">All Hospitals</option>
                @foreach($tenants as $t)
                    <option value="{{ $t->id }}" {{ request('tenant_id')==$t->id ? 'selected':'' }}>{{ $t->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="hms-form-group" style="margin-bottom:0;min-width:220px">
            <label class="hms-label">Date range</label>
            <input type="text" class="hms-input"
                data-hms-date-range
                data-start-name="from"
                data-end-name="to"
                data-start-value="{{ request('from') }}"
                data-end-value="{{ request('to') }}"
                data-auto-submit="1"
                placeholder="Select start → end date"
                autocomplete="off"
                readonly
                style="width:220px">
        </div>
        <div style="display:flex;gap:.5rem">
            <button type="submit" class="hms-btn hms-btn-primary hms-btn-sm">
                <i class="bi bi-funnel-fill"></i> Filter
            </button>
            <a href="{{ route('superadmin.audit-logs.index') }}" class="hms-btn hms-btn-outline hms-btn-sm">Clear</a>
        </div>
    </form>
</div>

{{-- Audit Log Table --}}
<div class="hms-card" style="padding:0">
    <div class="hms-card-header">
        <h3 class="hms-card-title">
            <i class="bi bi-shield-fill" style="color:#1B4F72"></i>
            Audit Log
        </h3>
        <span class="hms-badge hms-badge-info">{{ $logs->total() }} entries</span>
    </div>

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
                    <th>Date / Time</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                @php
                    $actionColor = match(true) {
                        str_contains($log->action, 'created')   => 'background:#D5F5E3;color:#1A6F5B',
                        str_contains($log->action, 'activated') => 'background:#D6EAF8;color:#154360',
                        str_contains($log->action, 'suspended') => 'background:#FADBD8;color:#641E16',
                        str_contains($log->action, 'archived')  => 'background:#FDEBD0;color:#784212',
                        str_contains($log->action, 'payment')   => 'background:#D5F5E3;color:#1A6F5B',
                        default                                  => 'background:#F4F6F7;color:#2C3E50',
                    };
                @endphp
                <tr>
                    <td style="color:#94A3B8;font-size:.75rem">{{ $log->id }}</td>
                    <td>
                        <span class="hms-badge" style="{{ $actionColor }};font-size:.7rem">{{ $log->action }}</span>
                    </td>
                    <td style="font-size:.85rem;max-width:220px;color:#334155">{{ $log->description }}</td>
                    <td style="font-size:.85rem">
                        @if($log->tenant)
                            <a href="{{ route('superadmin.hospitals.show', $log->tenant) }}"
                               style="font-weight:600;color:#1A202C">{{ $log->tenant->name }}</a>
                            <div style="font-size:.72rem;color:#64748B">{{ $log->tenant->slug }}</div>
                        @else
                            <span style="color:#94A3B8">Platform</span>
                        @endif
                    </td>
                    <td style="font-size:.85rem;color:#475569">{{ $log->admin?->name ?? '—' }}</td>
                    <td style="font-family:monospace;font-size:.75rem;color:#94A3B8">{{ $log->ip_address ?? '—' }}</td>
                    <td style="white-space:nowrap;font-size:.82rem;color:#475569">
                        {{ $log->created_at->format('d M Y') }}
                        <div style="font-size:.72rem;color:#94A3B8">{{ $log->created_at->format('h:i A') }}</div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-5">
                        <x-empty-state
                            icon="bi bi-clock-history"
                            title="No Audit Logs Found"
                            description="No system activity has been recorded matching your criteria." />
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
    <div style="padding:1rem;border-top:1px solid rgba(27,79,114,.1)">
        {{ $logs->withQueryString()->links() }}
    </div>
    @endif
</div>

@endsection
