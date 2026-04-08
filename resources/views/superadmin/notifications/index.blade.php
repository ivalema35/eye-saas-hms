@extends('superadmin.layouts.app')
@section('title', 'Notification Center')
@section('page-header', 'Notification Center')

@section('content')

<div style="display:grid;grid-template-columns:380px 1fr;gap:1.5rem;align-items:start">

    {{-- ===== COMPOSE FORM ===== --}}
    <div class="hms-card" style="padding:0">
        <div class="hms-card-header">
            <h3 class="hms-card-title">
                <i class="fa-solid fa-paper-plane" style="color:var(--hms-info)"></i> Compose Notification
            </h3>
        </div>
        <div style="padding:1.5rem">

        <form method="POST" action="{{ route('superadmin.notifications.send') }}" id="notifyForm">
            @csrf

            <div class="hms-form-group">
                <label>Subject <span style="color:var(--hms-danger)">*</span></label>
                <input type="text"
                       name="subject"
                       class="hms-input @error('subject') is-invalid @enderror"
                       placeholder="e.g. Important: Platform Update"
                       value="{{ old('subject') }}"
                       required>
                @error('subject')
                    <div class="hms-field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="hms-form-group">
                <label>Message <span style="color:var(--hms-danger)">*</span></label>
                <textarea name="message"
                          class="hms-input @error('message') is-invalid @enderror"
                          rows="6"
                          placeholder="Write your message here..."
                          required
                          style="resize:vertical">{{ old('message') }}</textarea>
                @error('message')
                    <div class="hms-field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="hms-form-group">
                <label>Recipients <span style="color:var(--hms-danger)">*</span></label>
                <select name="recipient" class="hms-select" id="recipientSelect" required>
                    <option value="all" {{ old('recipient') === 'all' ? 'selected' : '' }}>All Hospitals ({{ $tenants->count() }} hospitals)</option>
                    <option value="specific" {{ old('recipient') === 'specific' ? 'selected' : '' }}>Specific Hospitals</option>
                </select>
            </div>

            <div class="hms-form-group" id="specificTenantsGroup" style="display:none">
                <label>Select Hospitals <span style="color:var(--hms-danger)">*</span></label>
                <select name="tenant_ids[]"
                        class="hms-select @error('tenant_ids') is-invalid @enderror"
                        id="tenantSelect"
                        multiple
                        style="height:auto">
                    @foreach($tenants as $tenant)
                        <option value="{{ $tenant->id }}"
                                {{ is_array(old('tenant_ids')) && in_array($tenant->id, old('tenant_ids')) ? 'selected' : '' }}>
                            {{ $tenant->name }} ({{ $tenant->slug }}) — {{ ucfirst($tenant->status) }}
                        </option>
                    @endforeach
                </select>
                @error('tenant_ids')
                    <div class="hms-field-error">{{ $message }}</div>
                @enderror
            </div>

            {{-- Preview of recipient count --}}
            <div id="recipientPreview" style="font-size:.8rem;color:var(--hms-text-muted);margin-bottom:1rem;padding:.6rem .75rem;background:var(--hms-bg-light);border-radius:var(--hms-radius);border:1px solid var(--hms-border)">
                <i class="fa-solid fa-users" style="color:var(--hms-info)"></i>
                Sending to: <strong>All {{ $tenants->count() }} hospitals</strong>
            </div>

            <button type="submit" class="hms-btn hms-btn-primary" style="width:100%" id="sendBtn"
                    onclick="return confirm('Send this notification to the selected hospitals?')">
                <i class="fa-solid fa-paper-plane"></i> Send Notification
            </button>
        </form>
        </div>{{-- /padding wrapper --}}
    </div>

    {{-- ===== HISTORY TABLE ===== --}}
    <div>
        <div class="hms-card" style="padding:0">
            <div class="hms-card-header">
                <h3 class="hms-card-title">
                    <i class="fa-solid fa-clock-rotate-left"></i> Sent Notifications
                </h3>
                <span class="hms-badge hms-badge-info">last 50</span>
            </div>

            <div class="hms-table-wrap" style="border:none">
                <table class="hms-table">
                    <thead>
                        <tr>
                            <th>Hospital</th>
                            <th>Subject</th>
                            <th>Recipient Email</th>
                            <th>Status</th>
                            <th>Sent At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentNotifications as $notif)
                        <tr>
                            <td>
                                @if($notif->tenant)
                                    <a href="{{ route('superadmin.hospitals.show', $notif->tenant) }}" style="font-weight:600;font-size:.85rem">
                                        {{ $notif->tenant->name }}
                                    </a>
                                    <div style="font-size:.72rem;color:var(--hms-text-muted)">{{ $notif->tenant->slug }}</div>
                                @else
                                    <span style="color:var(--hms-text-muted);font-size:.82rem">Deleted Hospital</span>
                                @endif
                            </td>
                            <td style="font-size:.85rem;max-width:200px">{{ \Illuminate\Support\Str::limit($notif->subject, 50) }}</td>
                            <td style="font-size:.8rem;color:var(--hms-text-muted)">{{ $notif->recipient_email }}</td>
                            <td>
                                @if($notif->status === 'sent')
                                    <span class="hms-badge hms-badge-active">Sent</span>
                                @elseif($notif->status === 'failed')
                                    <span class="hms-badge hms-badge-suspended" title="{{ $notif->error_message }}">Failed</span>
                                @else
                                    <span class="hms-badge hms-badge-inactive">Pending</span>
                                @endif
                            </td>
                            <td style="font-size:.8rem;white-space:nowrap">
                                {{ $notif->sent_at?->format('d M Y, h:i A') ?? $notif->created_at->format('d M Y, h:i A') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" style="text-align:center;padding:2.5rem;color:var(--hms-text-muted)">
                                <i class="fa-solid fa-bell-slash" style="font-size:2rem;opacity:.3;display:block;margin-bottom:.5rem"></i>
                                No notifications sent yet.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
const recipientSelect = document.getElementById('recipientSelect');
const specificGroup   = document.getElementById('specificTenantsGroup');
const preview         = document.getElementById('recipientPreview');
const totalCount      = {{ $tenants->count() }};

function updateUI() {
    if (recipientSelect.value === 'specific') {
        specificGroup.style.display = 'block';
        const selected = document.getElementById('tenantSelect').selectedOptions.length;
        preview.innerHTML = `<i class="fa-solid fa-user-check" style="color:var(--hms-info)"></i> Sending to: <strong>${selected} selected hospital(s)</strong>`;
    } else {
        specificGroup.style.display = 'none';
        preview.innerHTML = `<i class="fa-solid fa-users" style="color:var(--hms-info)"></i> Sending to: <strong>All ${totalCount} hospitals</strong>`;
    }
}

recipientSelect.addEventListener('change', updateUI);

document.getElementById('tenantSelect')?.addEventListener('change', function () {
    const selected = this.selectedOptions.length;
    preview.innerHTML = `<i class="fa-solid fa-user-check" style="color:var(--hms-info)"></i> Sending to: <strong>${selected} selected hospital(s)</strong>`;
});

// Init Select2 for multi-select
$(document).ready(function () {
    $('#tenantSelect').select2({
        placeholder: 'Search and select hospitals...',
        allowClear: true,
        width: '100%',
    });
});
</script>
@endpush

@endsection
