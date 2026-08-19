@extends('superadmin.layouts.app')

@section('title', 'Diagnosis Master')
{{-- Layout page-header intentionally unused — matches Medicine Master's design
(heading, breadcrumb, list all sit inside one bordered card). --}}

@push('styles')
    <style>
        .dm-page {
            --dm-primary: #1B4F72;
        }

        .dm-premium-card {
            background: #ffffff;
            border: 1px solid rgba(15, 79, 134, 0.12);
            border-radius: 16px;
            box-shadow: 0 12px 32px rgba(15, 79, 134, 0.08);
            overflow: hidden;
        }

        .dm-header-block {
            padding: 1.25rem 1.5rem 1rem;
        }

        .dm-header-title {
            font-weight: 800;
            font-size: 1.3rem;
            color: var(--dm-primary);
            letter-spacing: -.015em;
            display: flex;
            align-items: center;
            gap: .55rem;
        }

        .dm-header-title i {
            color: var(--dm-primary);
            font-size: 1.2rem;
        }

        .dm-breadcrumb {
            margin-top: .4rem;
            display: flex;
            align-items: center;
            gap: .4rem;
            font-size: .85rem;
            color: #8891A0;
        }

        .dm-breadcrumb a {
            color: #8891A0;
            text-decoration: none;
        }

        .dm-breadcrumb a:hover {
            color: var(--dm-primary);
        }

        .dm-breadcrumb-sep {
            color: #C3C9D3;
        }

        .dm-breadcrumb-current {
            color: #4A5568;
            font-weight: 600;
        }

        .dm-actions-row {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: .75rem;
            padding: 0 1.5rem 1rem;
        }

        .dm-panel-body {
            padding: 0 1.5rem 1.5rem;
        }

        .dm-empty {
            padding: 3rem;
            text-align: center;
            color: #94A3B8;
        }

        .dm-empty i {
            font-size: 2.5rem;
            display: block;
            margin-bottom: .75rem;
        }

        .dm-empty p {
            margin: 0;
            font-weight: 600;
        }

        .dm-hint {
            margin: .25rem 0 0;
            font-size: .85rem;
        }
    </style>
@endpush

@section('content')
    <div class="dm-page">
        <div class="dm-premium-card">

            <div class="dm-header-block">
                <div class="dm-header-title"><i class="bi bi-info-circle-fill"></i> Diagnosis Master</div>
                <nav class="dm-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ route('superadmin.dashboard') }}">Home</a>
                    <span class="dm-breadcrumb-sep">/</span>
                    <span>Masters</span>
                    <span class="dm-breadcrumb-sep">/</span>
                    <span class="dm-breadcrumb-current">Diagnosis Master</span>
                </nav>
            </div>

            <div class="dm-actions-row">
                <button type="button" class="hms-btn hms-btn-primary hms-btn-sm" data-bs-toggle="modal"
                    data-bs-target="#addDiagnosisModal" style="color: #1b4f72;">
                    <i class="bi bi-plus-lg"></i> Add Diagnosis
                </button>
            </div>

            <div class="dm-panel-body">
                <div class="hms-card" style="padding:0">
                    <div class="hms-card-header">
                        <h3 class="hms-card-title"><i class="bi bi-info-circle-fill" style="color:#ffffff"></i> Diagnosis
                            List</h3>
                        <span class="hms-badge hms-badge-info">{{ $diagnoses->count() }} total</span>
                    </div>
                    @if($diagnoses->isEmpty())
                        <div class="dm-empty"><i class="bi bi-info-circle-fill"></i>
                            <p>No diagnoses yet</p>
                            <p class="dm-hint">Click "Add Diagnosis" to get started.</p>
                        </div>
                    @else
                        <div class="hms-table-wrap" style="border:none">
                            <table class="hms-table js-datatable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Diagnosis</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($diagnoses as $i => $d)
                                        <tr>
                                            <td style="color:#94A3B8;font-size:.8rem">{{ $i + 1 }}</td>
                                            <td style="font-weight:600;color:#1B4F72">{{ $d->value }}</td>
                                            <td>
                                                <button
                                                    class="hms-badge toggle-btn {{ $d->is_active ? 'hms-badge-success' : 'hms-badge-secondary' }}"
                                                    data-url="{{ route('superadmin.diagnosis-master.toggle', $d->id) }}"
                                                    style="border:none;cursor:pointer;padding:.25rem .6rem;font-size:.75rem">
                                                    {{ $d->is_active ? 'Active' : 'Inactive' }}
                                                </button>
                                            </td>
                                            <td class="text-end">
                                                <div style="display:flex;gap:.4rem;justify-content:flex-end">
                                                    <button class="hms-btn hms-btn-outline hms-btn-xs edit-diagnosis-btn"
                                                        data-id="{{ $d->id }}" data-value="{{ $d->value }}">
                                                        <i class="bi bi-pencil-fill"></i>
                                                    </button>
                                                    <form method="POST"
                                                        action="{{ route('superadmin.diagnosis-master.destroy', $d->id) }}"
                                                        class="del-form">
                                                        @csrf @method('DELETE')
                                                        <button class="hms-btn hms-btn-xs"
                                                            style="background:#FEE2E2;color:#B91C1C;border:none"><i
                                                                class="bi bi-trash-fill"></i></button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>{{-- /.dm-premium-card --}}
    </div>{{-- /.dm-page --}}

    {{-- ══════════════════════════════════════════════════════
    MODALS
    ══════════════════════════════════════════════════════ --}}

    {{-- Add Diagnosis --}}
    <div class="modal fade" id="addDiagnosisModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content" style="border-radius:12px;border:none;box-shadow:0 20px 60px rgba(0,0,0,.15)">
                <div class="modal-header" style="background:#1B4F72;border-radius:12px 12px 0 0;padding:.875rem 1.25rem">
                    <h5 class="modal-title" style="color:#fff;font-weight:700;font-size:.95rem"><i
                            class="bi bi-info-circle-fill me-2"></i>Add Diagnosis</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('superadmin.diagnosis-master.store') }}">
                    @csrf
                    <div class="modal-body" style="padding:1.25rem">
                        <label class="hms-label">Diagnosis <span style="color:#E53E3E">*</span></label>
                        <input type="text" name="value" class="hms-input" placeholder="e.g. Chronic Simple Glaucoma"
                            required autofocus>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #F1F5F9;padding:.75rem 1.25rem">
                        <button type="button" class="hms-btn hms-btn-outline hms-btn-sm"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="hms-btn hms-btn-primary hms-btn-sm" style="color: #1b4f72;"><i
                                class="bi bi-plus-lg"></i>
                            Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit Diagnosis --}}
    <div class="modal fade" id="editDiagnosisModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content" style="border-radius:12px;border:none;box-shadow:0 20px 60px rgba(0,0,0,.15)">
                <div class="modal-header" style="background:#1B4F72;border-radius:12px 12px 0 0;padding:.875rem 1.25rem">
                    <h5 class="modal-title" style="color:#fff;font-weight:700;font-size:.95rem"><i
                            class="bi bi-pencil-fill me-2"></i>Edit Diagnosis</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editDiagnosisForm" action="">
                    @csrf @method('PUT')
                    <div class="modal-body" style="padding:1.25rem">
                        <label class="hms-label">Diagnosis <span style="color:#E53E3E">*</span></label>
                        <input type="text" name="value" id="editDiagnosisValue" class="hms-input" required>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #F1F5F9;padding:.75rem 1.25rem">
                        <button type="button" class="hms-btn hms-btn-outline hms-btn-sm"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="hms-btn hms-btn-primary hms-btn-sm" style="color: #1B4F72;"><i
                                class="bi bi-check-lg"></i>
                            Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        (function () {
            /* ── Edit Diagnosis ── */
            document.querySelectorAll('.edit-diagnosis-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    document.getElementById('editDiagnosisForm').action =
                        '{{ route("superadmin.diagnosis-master.update", ":id") }}'.replace(':id', this.dataset.id);
                    document.getElementById('editDiagnosisValue').value = this.dataset.value;
                    new bootstrap.Modal(document.getElementById('editDiagnosisModal')).show();
                });
            });

            /* ── Toggle Active ── */
            document.querySelectorAll('.toggle-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    fetch(this.dataset.url, {
                        method: 'PATCH',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' }
                    }).then(r => r.json()).then(data => {
                        if (data.is_active) {
                            this.textContent = 'Active';
                            this.classList.replace('hms-badge-secondary', 'hms-badge-success');
                        } else {
                            this.textContent = 'Inactive';
                            this.classList.replace('hms-badge-success', 'hms-badge-secondary');
                        }
                    });
                });
            });

            /* ── Delete confirm ── */
            document.querySelectorAll('.del-form').forEach(form => {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Delete this entry?',
                        text: 'This action cannot be undone.',
                        icon: 'warning', showCancelButton: true,
                        confirmButtonColor: '#B91C1C', confirmButtonText: 'Yes, delete',
                    }).then(r => { if (r.isConfirmed) form.submit(); });
                });
            });
        })();
    </script>
@endpush