@extends('superadmin.layouts.app')

@section('title', 'Medicine Master')
{{-- Layout page-header intentionally unused — the heading, breadcrumb, tabs
and list all sit inside one bordered card, matching the OT panel design. --}}

@push('styles')
    <style>
        .mm-page {
            --mm-primary: #1B4F72;
        }

        .mm-premium-card {
            background: #ffffff;
            border: 1px solid rgba(15, 79, 134, 0.12);
            border-radius: 16px;
            box-shadow: 0 12px 32px rgba(15, 79, 134, 0.08);
            overflow: hidden;
        }

        .mm-header-block {
            padding: 1.25rem 1.5rem 1rem;
        }

        .mm-header-title {
            font-weight: 800;
            font-size: 1.3rem;
            color: var(--mm-primary);
            letter-spacing: -.015em;
            display: flex;
            align-items: center;
            gap: .55rem;
        }

        .mm-header-title i {
            color: var(--mm-primary);
            font-size: 1.2rem;
        }

        .mm-breadcrumb {
            margin-top: .4rem;
            display: flex;
            align-items: center;
            gap: .4rem;
            font-size: .85rem;
            color: #8891A0;
        }

        .mm-breadcrumb a {
            color: #8891A0;
            text-decoration: none;
        }

        .mm-breadcrumb a:hover {
            color: var(--mm-primary);
        }

        .mm-breadcrumb-sep {
            color: #C3C9D3;
        }

        .mm-breadcrumb-current {
            color: #4A5568;
            font-weight: 600;
        }

        .mm-tabs-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .75rem;
            padding: 0 1.5rem;
            border-bottom: 2px solid #E2E8F0;
        }

        .mm-tab-nav {
            display: flex;
            gap: 0;
            flex-wrap: wrap;
        }

        .mm-tab-btn {
            padding: .9rem 1.1rem;
            font-size: .875rem;
            font-weight: 600;
            color: #64748B;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            transition: color .15s, border-color .15s;
            white-space: nowrap;
            margin-bottom: -2px;
            text-decoration: none;
        }

        .mm-tab-btn:hover {
            color: var(--mm-primary);
        }

        .mm-tab-btn.active {
            color: var(--mm-primary);
            border-bottom-color: var(--mm-primary);
        }

        .mm-tab-actions {
            display: flex;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap;
            padding: .6rem 0;
        }

        .mm-panel-body {
            padding: 1.25rem 1.5rem 1.5rem;
        }

        .mm-panel-body .hms-card:last-child {
            margin-bottom: 0;
        }

        .mm-empty {
            padding: 3rem;
            text-align: center;
            color: #94A3B8;
        }

        .mm-empty i {
            font-size: 2.5rem;
            display: block;
            margin-bottom: .75rem;
        }

        .mm-empty p {
            margin: 0;
            font-weight: 600;
        }

        .mm-hint {
            margin: .25rem 0 0;
            font-size: .85rem;
        }
    </style>
@endpush

@section('content')
    <div class="mm-page">
        <div class="mm-premium-card">

            <div class="mm-header-block">
                <div class="mm-header-title"><i class="bi bi-capsule"></i> Medicine Master</div>
                <nav class="mm-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ route('superadmin.dashboard') }}">Home</a>
                    <span class="mm-breadcrumb-sep">/</span>
                    <span>Masters</span>
                    <span class="mm-breadcrumb-sep">/</span>
                    <span class="mm-breadcrumb-current">Medicine Master</span>
                </nav>
            </div>

            {{-- ══════════════════════════════════════
            Tab Navigation + contextual Add button
            ══════════════════════════════════════ --}}
            <div class="mm-tabs-row">
                <div class="mm-tab-nav">
                    <a href="{{ route('superadmin.medicine-master.index', ['tab' => 'dosages']) }}"
                        class="mm-tab-btn {{ $tab === 'dosages' ? 'active' : '' }}">
                        <i class="bi bi-eyedropper"></i> Dosage
                    </a>
                    <a href="{{ route('superadmin.medicine-master.index', ['tab' => 'types']) }}"
                        class="mm-tab-btn {{ $tab === 'types' ? 'active' : '' }}">
                        <i class="bi bi-capsule"></i> Medicine Type
                    </a>
                    <a href="{{ route('superadmin.medicine-master.index', ['tab' => 'categories']) }}"
                        class="mm-tab-btn {{ $tab === 'categories' ? 'active' : '' }}">
                        <i class="bi bi-tags"></i> Medicine Category
                    </a>
                    <a href="{{ route('superadmin.medicine-master.index', ['tab' => 'routes']) }}"
                        class="mm-tab-btn {{ $tab === 'routes' ? 'active' : '' }}">
                        <i class="bi bi-signpost-split"></i> Mode
                    </a>
                    <a href="{{ route('superadmin.medicine-master.index', ['tab' => 'medicines']) }}"
                        class="mm-tab-btn {{ $tab === 'medicines' ? 'active' : '' }}">
                        <i class="bi bi-clipboard2-pulse"></i> Medicine List
                    </a>
                </div>
                <div class="mm-tab-actions">
                    @if($tab === 'dosages')
                        <button type="button" class="hms-btn hms-btn-primary hms-btn-sm" data-bs-toggle="modal"
                            data-bs-target="#addDosageModal" style="color: #1b4f72;">
                            <i class="bi bi-plus-lg"></i> Add Dosage
                        </button>
                    @elseif($tab === 'types')
                        <button type="button" class="hms-btn hms-btn-primary hms-btn-sm" data-bs-toggle="modal"
                            data-bs-target="#addTypeModal" style="color: #1b4f72;">
                            <i class="bi bi-plus-lg"></i> Add Medicine Type
                        </button>
                    @elseif($tab === 'categories')
                        <button type="button" class="hms-btn hms-btn-primary hms-btn-sm" data-bs-toggle="modal"
                            data-bs-target="#addCategoryModal" style="color: #1b4f72;">
                            <i class="bi bi-plus-lg"></i> Add Category
                        </button>
                    @elseif($tab === 'routes')
                        <button type="button" class="hms-btn hms-btn-primary hms-btn-sm" data-bs-toggle="modal"
                            data-bs-target="#addRouteModal" style="color: #1b4f72;">
                            <i class="bi bi-plus-lg"></i> Add Route
                        </button>
                    @elseif($tab === 'medicines')
                        <button type="button" class="hms-btn hms-btn-outline hms-btn-sm" data-bs-toggle="modal"
                            data-bs-target="#importMedicineModal">
                            <i class="bi bi-file-earmark-arrow-up"></i> Import Excel
                        </button>
                        <button type="button" class="hms-btn hms-btn-primary hms-btn-sm" data-bs-toggle="modal"
                            data-bs-target="#addMedicineModal" style="color: #1b4f72;">
                            <i class="bi bi-plus-lg"></i> Add Medicine
                        </button>
                    @endif
                </div>
            </div>

            <div class="mm-panel-body">

                {{-- ══════════════════════════════════════
                TAB: DOSAGES
                ══════════════════════════════════════ --}}
                @if($tab === 'dosages')
                    <div class="hms-card" style="padding:0">
                        <div class="hms-card-header">
                            <h3 class="hms-card-title"><i class="bi bi-eyedropper" style="color:#ffffff"></i> Dosage List</h3>
                            <span class="hms-badge hms-badge-info">{{ $dosages->count() }} total</span>
                        </div>
                        @if($dosages->isEmpty())
                            <div class="mm-empty"><i class="bi bi-eyedropper"></i>
                                <p>No dosages yet</p>
                                <p class="mm-hint">Click "Add Dosage" to get started.</p>
                            </div>
                        @else
                            <div class="hms-table-wrap" style="border:none">
                                <table class="hms-table js-datatable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Dosage</th>
                                            <th>Status</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($dosages as $i => $d)
                                            <tr>
                                                <td style="color:#94A3B8;font-size:.8rem">{{ $i + 1 }}</td>
                                                <td style="font-weight:600;color:#1B4F72">{{ $d->dosage }}</td>
                                                <td>
                                                    <button
                                                        class="hms-badge toggle-btn {{ $d->is_active ? 'hms-badge-success' : 'hms-badge-secondary' }}"
                                                        data-url="{{ route('superadmin.medicine-master.dosages.toggle', $d->id) }}"
                                                        style="border:none;cursor:pointer;padding:.25rem .6rem;font-size:.75rem">
                                                        {{ $d->is_active ? 'Active' : 'Inactive' }}
                                                    </button>
                                                </td>
                                                <td class="text-end">
                                                    <div style="display:flex;gap:.4rem;justify-content:flex-end">
                                                        <button class="hms-btn hms-btn-outline hms-btn-xs edit-dosage-btn"
                                                            data-id="{{ $d->id }}" data-dosage="{{ $d->dosage }}">
                                                            <i class="bi bi-pencil-fill"></i>
                                                        </button>
                                                        <form method="POST"
                                                            action="{{ route('superadmin.medicine-master.dosages.destroy', $d->id) }}"
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
                @endif

                {{-- ══════════════════════════════════════
                TAB: MEDICINE TYPES
                ══════════════════════════════════════ --}}
                @if($tab === 'types')
                    <div class="hms-card" style="padding:0">
                        <div class="hms-card-header">
                            <h3 class="hms-card-title"><i class="bi bi-capsule" style="color:#ffffff"></i> Medicine Type List
                            </h3>
                            <span class="hms-badge hms-badge-info">{{ $types->count() }} total</span>
                        </div>
                        @if($types->isEmpty())
                            <div class="mm-empty"><i class="bi bi-capsule"></i>
                                <p>No medicine types yet</p>
                                <p class="mm-hint">Click "Add Medicine Type" to get started.</p>
                            </div>
                        @else
                            <div class="hms-table-wrap" style="border:none">
                                <table class="hms-table js-datatable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Status</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($types as $i => $t)
                                            <tr>
                                                <td style="color:#94A3B8;font-size:.8rem">{{ $i + 1 }}</td>
                                                <td style="font-weight:600;color:#1B4F72">{{ $t->name }}</td>
                                                <td>
                                                    <button
                                                        class="hms-badge toggle-btn {{ $t->is_active ? 'hms-badge-success' : 'hms-badge-secondary' }}"
                                                        data-url="{{ route('superadmin.medicine-master.types.toggle', $t->id) }}"
                                                        style="border:none;cursor:pointer;padding:.25rem .6rem;font-size:.75rem">
                                                        {{ $t->is_active ? 'Active' : 'Inactive' }}
                                                    </button>
                                                </td>
                                                <td class="text-end">
                                                    <div style="display:flex;gap:.4rem;justify-content:flex-end">
                                                        <button class="hms-btn hms-btn-outline hms-btn-xs edit-type-btn"
                                                            data-id="{{ $t->id }}" data-name="{{ $t->name }}">
                                                            <i class="bi bi-pencil-fill"></i>
                                                        </button>
                                                        <form method="POST"
                                                            action="{{ route('superadmin.medicine-master.types.destroy', $t->id) }}"
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
                @endif

                {{-- ══════════════════════════════════════
                TAB: MEDICINE CATEGORIES
                ══════════════════════════════════════ --}}
                @if($tab === 'categories')
                    <div class="hms-card" style="padding:0">
                        <div class="hms-card-header">
                            <h3 class="hms-card-title"><i class="bi bi-tags" style="color:#ffffff"></i> Medicine Category List
                            </h3>
                            <span class="hms-badge hms-badge-info">{{ $categories->count() }} total</span>
                        </div>
                        @if($categories->isEmpty())
                            <div class="mm-empty"><i class="bi bi-tags"></i>
                                <p>No categories yet</p>
                                <p class="mm-hint">Click "Add Category" to get started.</p>
                            </div>
                        @else
                            <div class="hms-table-wrap" style="border:none">
                                <table class="hms-table js-datatable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Status</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($categories as $i => $c)
                                            <tr>
                                                <td style="color:#94A3B8;font-size:.8rem">{{ $i + 1 }}</td>
                                                <td style="font-weight:600;color:#1B4F72">{{ $c->name }}</td>
                                                <td>
                                                    <button
                                                        class="hms-badge toggle-btn {{ $c->is_active ? 'hms-badge-success' : 'hms-badge-secondary' }}"
                                                        data-url="{{ route('superadmin.medicine-master.categories.toggle', $c->id) }}"
                                                        style="border:none;cursor:pointer;padding:.25rem .6rem;font-size:.75rem">
                                                        {{ $c->is_active ? 'Active' : 'Inactive' }}
                                                    </button>
                                                </td>
                                                <td class="text-end">
                                                    <div style="display:flex;gap:.4rem;justify-content:flex-end">
                                                        <button class="hms-btn hms-btn-outline hms-btn-xs edit-category-btn"
                                                            data-id="{{ $c->id }}" data-name="{{ $c->name }}">
                                                            <i class="bi bi-pencil-fill"></i>
                                                        </button>
                                                        <form method="POST"
                                                            action="{{ route('superadmin.medicine-master.categories.destroy', $c->id) }}"
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
                @endif

                {{-- ══════════════════════════════════════
                TAB: ROUTES
                ══════════════════════════════════════ --}}
                @if($tab === 'routes')
                    <div class="hms-card" style="padding:0">
                        <div class="hms-card-header">
                            <h3 class="hms-card-title"><i class="bi bi-signpost-split" style="color:#ffffff"></i> Mode List</h3>
                            <span class="hms-badge hms-badge-info">{{ $routes->count() }} total</span>
                        </div>
                        @if($routes->isEmpty())
                            <div class="mm-empty"><i class="bi bi-signpost-split"></i>
                                <p>No routes yet</p>
                                <p class="mm-hint">Click "Add Route" to get started.</p>
                            </div>
                        @else
                            <div class="hms-table-wrap" style="border:none">
                                <table class="hms-table js-datatable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Status</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($routes as $i => $r)
                                            <tr>
                                                <td style="color:#94A3B8;font-size:.8rem">{{ $i + 1 }}</td>
                                                <td style="font-weight:600;color:#1B4F72">{{ $r->name }}</td>
                                                <td>
                                                    <button
                                                        class="hms-badge toggle-btn {{ $r->is_active ? 'hms-badge-success' : 'hms-badge-secondary' }}"
                                                        data-url="{{ route('superadmin.medicine-master.routes.toggle', $r->id) }}"
                                                        style="border:none;cursor:pointer;padding:.25rem .6rem;font-size:.75rem">
                                                        {{ $r->is_active ? 'Active' : 'Inactive' }}
                                                    </button>
                                                </td>
                                                <td class="text-end">
                                                    <div style="display:flex;gap:.4rem;justify-content:flex-end">
                                                        <button class="hms-btn hms-btn-outline hms-btn-xs edit-route-btn"
                                                            data-id="{{ $r->id }}" data-name="{{ $r->name }}">
                                                            <i class="bi bi-pencil-fill"></i>
                                                        </button>
                                                        <form method="POST"
                                                            action="{{ route('superadmin.medicine-master.routes.destroy', $r->id) }}"
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
                @endif

                {{-- ══════════════════════════════════════
                TAB: MEDICINES
                ══════════════════════════════════════ --}}
                @if($tab === 'medicines')
                    <div class="hms-card" style="padding:0">
                        <div class="hms-card-header">
                            <h3 class="hms-card-title"><i class="bi bi-clipboard2-pulse" style="color:#ffffff"></i> Medicine
                                List</h3>
                            <span class="hms-badge hms-badge-info">{{ $medicines->count() }} total</span>
                        </div>
                        @if($medicines->isEmpty())
                            <div class="mm-empty"><i class="bi bi-clipboard2-pulse"></i>
                                <p>No medicines yet</p>
                                <p class="mm-hint">Click "Add Medicine" to get started.</p>
                            </div>
                        @else
                            <div class="hms-table-wrap" style="border:none">
                                <table class="hms-table js-datatable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Type</th>
                                            <th>Dosage</th>
                                            <th>Company</th>
                                            <th>Price</th>
                                            <th>Status</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($medicines as $i => $m)
                                            <tr>
                                                <td style="color:#94A3B8;font-size:.8rem">{{ $i + 1 }}</td>
                                                <td style="font-weight:600;color:#1B4F72">{{ $m->name }}</td>
                                                <td style="color:#64748B;font-size:.875rem">{{ $m->medicineType->name ?? '—' }}</td>
                                                <td style="color:#64748B;font-size:.875rem">{{ $m->dosage->dosage ?? '—' }}</td>
                                                <td style="color:#64748B;font-size:.875rem">{{ $m->company ?? '—' }}</td>
                                                <td style="color:#64748B;font-size:.875rem">
                                                    {{ $m->price !== null ? number_format((float) $m->price, 2) : '—' }}
                                                </td>
                                                <td>
                                                    <button
                                                        class="hms-badge toggle-btn {{ $m->is_active ? 'hms-badge-success' : 'hms-badge-secondary' }}"
                                                        data-url="{{ route('superadmin.medicine-master.medicines.toggle', $m->id) }}"
                                                        style="border:none;cursor:pointer;padding:.25rem .6rem;font-size:.75rem">
                                                        {{ $m->is_active ? 'Active' : 'Inactive' }}
                                                    </button>
                                                </td>
                                                <td class="text-end">
                                                    <div style="display:flex;gap:.4rem;justify-content:flex-end">
                                                        <button class="hms-btn hms-btn-outline hms-btn-xs edit-medicine-btn"
                                                            data-id="{{ $m->id }}" data-name="{{ $m->name }}"
                                                            data-type-id="{{ $m->master_medicine_type_id }}"
                                                            data-dosage-id="{{ $m->master_dosage_id }}"
                                                            data-duration="{{ $m->duration }}" data-qty="{{ $m->qty }}"
                                                            data-composition="{{ $m->composition }}" data-company="{{ $m->company }}"
                                                            data-price="{{ $m->price }}">
                                                            <i class="bi bi-pencil-fill"></i>
                                                        </button>
                                                        <form method="POST"
                                                            action="{{ route('superadmin.medicine-master.medicines.destroy', $m->id) }}"
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
                @endif

            </div>{{-- /.mm-panel-body --}}
        </div>{{-- /.mm-premium-card --}}
    </div>{{-- /.mm-page --}}

    {{-- ══════════════════════════════════════════════════════
    MODALS
    ══════════════════════════════════════════════════════ --}}

    {{-- Add Dosage --}}
    <div class="modal fade" id="addDosageModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content" style="border-radius:12px;border:none;box-shadow:0 20px 60px rgba(0,0,0,.15)">
                <div class="modal-header" style="background:#1B4F72;border-radius:12px 12px 0 0;padding:.875rem 1.25rem">
                    <h5 class="modal-title" style="color:#fff;font-weight:700;font-size:.95rem"><i
                            class="bi bi-eyedropper me-2"></i>Add Dosage</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('superadmin.medicine-master.dosages.store') }}">
                    @csrf
                    <div class="modal-body" style="padding:1.25rem">
                        <label class="hms-label">Dosage <span style="color:#E53E3E">*</span></label>
                        <input type="text" name="dosage" class="hms-input" placeholder="e.g. 1-0-0" required autofocus>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #F1F5F9;padding:.75rem 1.25rem">
                        <button type="button" class="hms-btn hms-btn-outline hms-btn-sm"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="hms-btn hms-btn-primary hms-btn-sm"><i class="bi bi-plus-lg"></i>
                            Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit Dosage --}}
    <div class="modal fade" id="editDosageModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content" style="border-radius:12px;border:none;box-shadow:0 20px 60px rgba(0,0,0,.15)">
                <div class="modal-header" style="background:#1B4F72;border-radius:12px 12px 0 0;padding:.875rem 1.25rem">
                    <h5 class="modal-title" style="color:#fff;font-weight:700;font-size:.95rem"><i
                            class="bi bi-pencil-fill me-2"></i>Edit Dosage</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editDosageForm" action="">
                    @csrf @method('PUT')
                    <div class="modal-body" style="padding:1.25rem">
                        <label class="hms-label">Dosage <span style="color:#E53E3E">*</span></label>
                        <input type="text" name="dosage" id="editDosageValue" class="hms-input" required>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #F1F5F9;padding:.75rem 1.25rem">
                        <button type="button" class="hms-btn hms-btn-outline hms-btn-sm"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="hms-btn hms-btn-primary hms-btn-sm"><i class="bi bi-check-lg"></i>
                            Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Add Medicine Type --}}
    <div class="modal fade" id="addTypeModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content" style="border-radius:12px;border:none;box-shadow:0 20px 60px rgba(0,0,0,.15)">
                <div class="modal-header" style="background:#1B4F72;border-radius:12px 12px 0 0;padding:.875rem 1.25rem">
                    <h5 class="modal-title" style="color:#fff;font-weight:700;font-size:.95rem"><i
                            class="bi bi-capsule me-2"></i>Add Medicine Type</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('superadmin.medicine-master.types.store') }}">
                    @csrf
                    <div class="modal-body" style="padding:1.25rem">
                        <label class="hms-label">Name <span style="color:#E53E3E">*</span></label>
                        <input type="text" name="name" class="hms-input" placeholder="e.g. Eye Drop" required autofocus>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #F1F5F9;padding:.75rem 1.25rem">
                        <button type="button" class="hms-btn hms-btn-outline hms-btn-sm"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="hms-btn hms-btn-primary hms-btn-sm"><i class="bi bi-plus-lg"></i>
                            Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit Medicine Type --}}
    <div class="modal fade" id="editTypeModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content" style="border-radius:12px;border:none;box-shadow:0 20px 60px rgba(0,0,0,.15)">
                <div class="modal-header" style="background:#1B4F72;border-radius:12px 12px 0 0;padding:.875rem 1.25rem">
                    <h5 class="modal-title" style="color:#fff;font-weight:700;font-size:.95rem"><i
                            class="bi bi-pencil-fill me-2"></i>Edit Medicine Type</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editTypeForm" action="">
                    @csrf @method('PUT')
                    <div class="modal-body" style="padding:1.25rem">
                        <label class="hms-label">Name <span style="color:#E53E3E">*</span></label>
                        <input type="text" name="name" id="editTypeName" class="hms-input" required>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #F1F5F9;padding:.75rem 1.25rem">
                        <button type="button" class="hms-btn hms-btn-outline hms-btn-sm"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="hms-btn hms-btn-primary hms-btn-sm"><i class="bi bi-check-lg"></i>
                            Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Add Category --}}
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content" style="border-radius:12px;border:none;box-shadow:0 20px 60px rgba(0,0,0,.15)">
                <div class="modal-header" style="background:#1B4F72;border-radius:12px 12px 0 0;padding:.875rem 1.25rem">
                    <h5 class="modal-title" style="color:#fff;font-weight:700;font-size:.95rem"><i
                            class="bi bi-tags me-2"></i>Add Medicine Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('superadmin.medicine-master.categories.store') }}">
                    @csrf
                    <div class="modal-body" style="padding:1.25rem">
                        <label class="hms-label">Name <span style="color:#E53E3E">*</span></label>
                        <input type="text" name="name" class="hms-input" placeholder="e.g. Antibiotic" required autofocus>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #F1F5F9;padding:.75rem 1.25rem">
                        <button type="button" class="hms-btn hms-btn-outline hms-btn-sm"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="hms-btn hms-btn-primary hms-btn-sm"><i class="bi bi-plus-lg"></i>
                            Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit Category --}}
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content" style="border-radius:12px;border:none;box-shadow:0 20px 60px rgba(0,0,0,.15)">
                <div class="modal-header" style="background:#1B4F72;border-radius:12px 12px 0 0;padding:.875rem 1.25rem">
                    <h5 class="modal-title" style="color:#fff;font-weight:700;font-size:.95rem"><i
                            class="bi bi-pencil-fill me-2"></i>Edit Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editCategoryForm" action="">
                    @csrf @method('PUT')
                    <div class="modal-body" style="padding:1.25rem">
                        <label class="hms-label">Name <span style="color:#E53E3E">*</span></label>
                        <input type="text" name="name" id="editCategoryName" class="hms-input" required>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #F1F5F9;padding:.75rem 1.25rem">
                        <button type="button" class="hms-btn hms-btn-outline hms-btn-sm"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="hms-btn hms-btn-primary hms-btn-sm"><i class="bi bi-check-lg"></i>
                            Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Add Route --}}
    <div class="modal fade" id="addRouteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content" style="border-radius:12px;border:none;box-shadow:0 20px 60px rgba(0,0,0,.15)">
                <div class="modal-header" style="background:#1B4F72;border-radius:12px 12px 0 0;padding:.875rem 1.25rem">
                    <h5 class="modal-title" style="color:#fff;font-weight:700;font-size:.95rem"><i
                            class="bi bi-signpost-split me-2"></i>Add Route</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('superadmin.medicine-master.routes.store') }}">
                    @csrf
                    <div class="modal-body" style="padding:1.25rem">
                        <label class="hms-label">Name <span style="color:#E53E3E">*</span></label>
                        <input type="text" name="name" class="hms-input" placeholder="e.g. Topical" required autofocus>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #F1F5F9;padding:.75rem 1.25rem">
                        <button type="button" class="hms-btn hms-btn-outline hms-btn-sm"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="hms-btn hms-btn-primary hms-btn-sm"><i class="bi bi-plus-lg"></i>
                            Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit Route --}}
    <div class="modal fade" id="editRouteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content" style="border-radius:12px;border:none;box-shadow:0 20px 60px rgba(0,0,0,.15)">
                <div class="modal-header" style="background:#1B4F72;border-radius:12px 12px 0 0;padding:.875rem 1.25rem">
                    <h5 class="modal-title" style="color:#fff;font-weight:700;font-size:.95rem"><i
                            class="bi bi-pencil-fill me-2"></i>Edit Route</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editRouteForm" action="">
                    @csrf @method('PUT')
                    <div class="modal-body" style="padding:1.25rem">
                        <label class="hms-label">Name <span style="color:#E53E3E">*</span></label>
                        <input type="text" name="name" id="editRouteName" class="hms-input" required>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #F1F5F9;padding:.75rem 1.25rem">
                        <button type="button" class="hms-btn hms-btn-outline hms-btn-sm"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="hms-btn hms-btn-primary hms-btn-sm"><i class="bi bi-check-lg"></i>
                            Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Import Medicines --}}
    <div class="modal fade" id="importMedicineModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:12px;border:none;box-shadow:0 20px 60px rgba(0,0,0,.15)">
                <div class="modal-header" style="background:#1B4F72;border-radius:12px 12px 0 0;padding:.875rem 1.25rem">
                    <h5 class="modal-title" style="color:#fff;font-weight:700;font-size:.95rem"><i
                            class="bi bi-file-earmark-arrow-up me-2"></i>Import Medicines via Excel</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('superadmin.medicine-master.medicines.import') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body" style="padding:1.25rem">
                        <div
                            style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px;padding:1rem;margin-bottom:1.25rem">
                            <p style="margin:0 0 .5rem;font-weight:700;color:#1D4ED8;font-size:.875rem">
                                <i class="bi bi-info-circle-fill me-1"></i> Required Excel Format
                            </p>
                            <div style="margin-top:.5rem;display:flex;gap:.4rem;flex-wrap:wrap">
                                <code
                                    style="background:#DBEAFE;color:#1D4ED8;padding:.15rem .4rem;border-radius:4px;font-size:.78rem">Medicine Name</code>
                                <code
                                    style="background:#F3F4F6;color:#6B7280;padding:.15rem .4rem;border-radius:4px;font-size:.78rem">Medicine Type</code>
                                <code
                                    style="background:#F3F4F6;color:#6B7280;padding:.15rem .4rem;border-radius:4px;font-size:.78rem">Dosage</code>
                                <code
                                    style="background:#F3F4F6;color:#6B7280;padding:.15rem .4rem;border-radius:4px;font-size:.78rem">Company</code>
                                <code
                                    style="background:#F3F4F6;color:#6B7280;padding:.15rem .4rem;border-radius:4px;font-size:.78rem">Composition</code>
                                <code
                                    style="background:#F3F4F6;color:#6B7280;padding:.15rem .4rem;border-radius:4px;font-size:.78rem">Price</code>
                            </div>
                            <p style="margin:.5rem 0 0;font-size:.78rem;color:#6B7280">
                                Only <strong>Medicine Name</strong> is required — other columns can be left blank.
                                If Type or Dosage is filled, it must match existing master data (case-insensitive).
                                Duplicate names are skipped.
                            </p>
                            <p style="margin:1.5rem 0 0;font-size:1rem">
                                <a href="{{ route('superadmin.medicine-master.medicines.sample') }}"><i
                                        class="bi bi-download"></i> Download a sample file</a>
                            </p>
                        </div>
                        <div class="hms-form-group" style="margin-bottom:1.25rem">
                            <label class="hms-label">Select Hospitals <span style="color:#B91C1C">*</span></label>
                            <select name="tenant_ids[]" id="importTenantSelect"
                                class="hms-select @error('tenant_ids') is-invalid @enderror @error('tenant_ids.*') is-invalid @enderror"
                                multiple required>
                                <option value="__select_all__">— Select All Hospitals —</option>
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
                            @error('tenant_ids.*')
                                <div class="hms-field-error">{{ $message }}</div>
                            @enderror
                            <p style="margin:.5rem 0 0;font-size:.78rem;color:#6B7280">
                                Only active hospitals (login allowed) are listed. Imported medicines sync to selected hospitals only.
                            </p>
                        </div>
                        <label class="hms-label">Select File (.xlsx, .xls, .csv)</label>
                        <input type="file" name="file" class="hms-input" accept=".xlsx,.xls,.csv" required>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #F1F5F9;padding:.75rem 1.25rem">
                        <button type="button" class="hms-btn hms-btn-outline hms-btn-sm"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="hms-btn hms-btn-primary hms-btn-sm"><i class="bi bi-upload"></i> Import
                            Now</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Add Medicine --}}
    <div class="modal fade" id="addMedicineModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:12px;border:none;box-shadow:0 20px 60px rgba(0,0,0,.15)">
                <div class="modal-header" style="background:#1B4F72;border-radius:12px 12px 0 0;padding:.875rem 1.25rem">
                    <h5 class="modal-title" style="color:#fff;font-weight:700;font-size:.95rem"><i
                            class="bi bi-clipboard2-pulse me-2"></i>Add Medicine</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('superadmin.medicine-master.medicines.store') }}">
                    @csrf
                    <div class="modal-body" style="padding:1.25rem">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="hms-label">Medicine Type <span style="color:#E53E3E">*</span></label>
                                <select name="master_medicine_type_id" class="hms-select" required>
                                    <option value="">— Select Type —</option>
                                    @foreach($allTypes as $t)
                                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="hms-label">Medicine Name <span style="color:#E53E3E">*</span></label>
                                <input type="text" name="name" class="hms-input" placeholder="e.g. Moxifloxacin" required>
                            </div>
                            <div class="col-md-6">
                                <label class="hms-label">Medicine Dosage <span style="color:#E53E3E">*</span></label>
                                <select name="master_dosage_id" class="hms-select" required>
                                    <option value="">— Select Dosage —</option>
                                    @foreach($allDosages as $d)
                                        <option value="{{ $d->id }}">{{ $d->dosage }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="hms-label">Duration</label>
                                <input type="text" name="duration" class="hms-input" placeholder="e.g. 7 days">
                            </div>
                            <div class="col-md-6">
                                <label class="hms-label">Medicine Qty</label>
                                <input type="text" name="qty" class="hms-input" placeholder="e.g. 1 bottle">
                            </div>
                            <div class="col-md-6">
                                <label class="hms-label">Price</label>
                                <input type="number" step="0.01" min="0" name="price" class="hms-input"
                                    placeholder="e.g. 120.00">
                            </div>
                            <div class="col-md-6">
                                <label class="hms-label">Company</label>
                                <input type="text" name="company" class="hms-input" placeholder="e.g. Sun Pharma">
                            </div>
                            <div class="col-6">
                                <label class="hms-label">Composition</label>
                                <textarea name="composition" class="hms-input" rows="2"
                                    placeholder="e.g. Moxifloxacin 0.5% w/v"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #F1F5F9;padding:.75rem 1.25rem">
                        <button type="button" class="hms-btn hms-btn-outline hms-btn-sm"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="hms-btn hms-btn-primary hms-btn-sm"><i class="bi bi-plus-lg"></i> Add
                            Medicine</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit Medicine --}}
    <div class="modal fade" id="editMedicineModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:12px;border:none;box-shadow:0 20px 60px rgba(0,0,0,.15)">
                <div class="modal-header" style="background:#1B4F72;border-radius:12px 12px 0 0;padding:.875rem 1.25rem">
                    <h5 class="modal-title" style="color:#fff;font-weight:700;font-size:.95rem"><i
                            class="bi bi-pencil-fill me-2"></i>Edit Medicine</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editMedicineForm" action="">
                    @csrf @method('PUT')
                    <div class="modal-body" style="padding:1.25rem">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="hms-label">Medicine Type <span style="color:#E53E3E">*</span></label>
                                <select name="master_medicine_type_id" id="editMedicineType" class="hms-select" required>
                                    <option value="">— Select Type —</option>
                                    @foreach($allTypes as $t)
                                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="hms-label">Medicine Name <span style="color:#E53E3E">*</span></label>
                                <input type="text" name="name" id="editMedicineName" class="hms-input" required>
                            </div>
                            <div class="col-md-6">
                                <label class="hms-label">Medicine Dosage <span style="color:#E53E3E">*</span></label>
                                <select name="master_dosage_id" id="editMedicineDosage" class="hms-select" required>
                                    <option value="">— Select Dosage —</option>
                                    @foreach($allDosages as $d)
                                        <option value="{{ $d->id }}">{{ $d->dosage }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="hms-label">Duration</label>
                                <input type="text" name="duration" id="editMedicineDuration" class="hms-input">
                            </div>
                            <div class="col-md-6">
                                <label class="hms-label">Medicine Qty</label>
                                <input type="text" name="qty" id="editMedicineQty" class="hms-input">
                            </div>
                            <div class="col-md-6">
                                <label class="hms-label">Price</label>
                                <input type="number" step="0.01" min="0" name="price" id="editMedicinePrice"
                                    class="hms-input">
                            </div>
                            <div class="col-md-6">
                                <label class="hms-label">Company</label>
                                <input type="text" name="company" id="editMedicineCompany" class="hms-input">
                            </div>
                            <div class="col-12">
                                <label class="hms-label">Composition</label>
                                <textarea name="composition" id="editMedicineComposition" class="hms-input"
                                    rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #F1F5F9;padding:.75rem 1.25rem">
                        <button type="button" class="hms-btn hms-btn-outline hms-btn-sm"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="hms-btn hms-btn-primary hms-btn-sm"><i class="bi bi-check-lg"></i>
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
            /* ── Edit Dosage ── */
            document.querySelectorAll('.edit-dosage-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    document.getElementById('editDosageForm').action =
                        '{{ route("superadmin.medicine-master.dosages.update", ":id") }}'.replace(':id', this.dataset.id);
                    document.getElementById('editDosageValue').value = this.dataset.dosage;
                    new bootstrap.Modal(document.getElementById('editDosageModal')).show();
                });
            });

            /* ── Edit Medicine Type ── */
            document.querySelectorAll('.edit-type-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    document.getElementById('editTypeForm').action =
                        '{{ route("superadmin.medicine-master.types.update", ":id") }}'.replace(':id', this.dataset.id);
                    document.getElementById('editTypeName').value = this.dataset.name;
                    new bootstrap.Modal(document.getElementById('editTypeModal')).show();
                });
            });

            /* ── Edit Category ── */
            document.querySelectorAll('.edit-category-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    document.getElementById('editCategoryForm').action =
                        '{{ route("superadmin.medicine-master.categories.update", ":id") }}'.replace(':id', this.dataset.id);
                    document.getElementById('editCategoryName').value = this.dataset.name;
                    new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
                });
            });

            /* ── Edit Route ── */
            document.querySelectorAll('.edit-route-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    document.getElementById('editRouteForm').action =
                        '{{ route("superadmin.medicine-master.routes.update", ":id") }}'.replace(':id', this.dataset.id);
                    document.getElementById('editRouteName').value = this.dataset.name;
                    new bootstrap.Modal(document.getElementById('editRouteModal')).show();
                });
            });

            /* ── Edit Medicine ── */
            document.querySelectorAll('.edit-medicine-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    document.getElementById('editMedicineForm').action =
                        '{{ route("superadmin.medicine-master.medicines.update", ":id") }}'.replace(':id', this.dataset.id);
                    document.getElementById('editMedicineName').value = this.dataset.name || '';
                    document.getElementById('editMedicineType').value = this.dataset.typeId || '';
                    document.getElementById('editMedicineDosage').value = this.dataset.dosageId || '';
                    document.getElementById('editMedicineDuration').value = this.dataset.duration || '';
                    document.getElementById('editMedicineQty').value = this.dataset.qty || '';
                    document.getElementById('editMedicineCompany').value = this.dataset.company || '';
                    document.getElementById('editMedicinePrice').value = this.dataset.price || '';
                    document.getElementById('editMedicineComposition').value = this.dataset.composition || '';
                    new bootstrap.Modal(document.getElementById('editMedicineModal')).show();
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

            /* ── Import modal: hospital multi-select ── */
            const importTenantSelect = document.getElementById('importTenantSelect');
            if (importTenantSelect && typeof $ !== 'undefined' && $.fn.select2) {
                const $importTenantSelect = $('#importTenantSelect');

                $importTenantSelect.select2({
                    placeholder: 'Search and select hospitals...',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('#importMedicineModal'),
                });

                $importTenantSelect.on('select2:select', function (e) {
                    if (e.params.data.id === '__select_all__') {
                        const allIds = $importTenantSelect.find('option')
                            .map(function () {
                                const val = this.value;
                                return val && val !== '__select_all__' ? val : null;
                            })
                            .get();

                        $importTenantSelect.val(allIds).trigger('change');
                    }
                });

                $importTenantSelect.closest('form').on('submit', function () {
                    const selected = ($importTenantSelect.val() || []).filter(id => id !== '__select_all__');
                    $importTenantSelect.val(selected);
                });

                @if($errors->has('tenant_ids') || $errors->has('tenant_ids.*') || $errors->has('file'))
                    new bootstrap.Modal(document.getElementById('importMedicineModal')).show();
                @endif
            }

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