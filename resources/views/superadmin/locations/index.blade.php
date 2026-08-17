@extends('superadmin.layouts.app')

@section('title', 'Location Master')
{{-- Layout page-header intentionally unused — the heading, breadcrumb, tabs
and list all sit inside one bordered card, matching the Medicine Master / OT
panel design. --}}

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@push('styles')
    <style>
        .loc-premium-card {
            background: #ffffff;
            border: 1px solid rgba(15, 79, 134, 0.12);
            border-radius: 16px;
            box-shadow: 0 12px 32px rgba(15, 79, 134, 0.08);
            overflow: hidden;
        }

        .loc-header-block {
            padding: 1.25rem 1.5rem 1rem;
        }

        .loc-header-title {
            font-weight: 800;
            font-size: 1.3rem;
            color: #1B4F72;
            letter-spacing: -.015em;
            display: flex;
            align-items: center;
            gap: .55rem;
        }

        .loc-header-title i {
            color: #1B4F72;
            font-size: 1.2rem;
        }

        .loc-breadcrumb {
            margin-top: .4rem;
            display: flex;
            align-items: center;
            gap: .4rem;
            font-size: .85rem;
            color: #8891A0;
        }

        .loc-breadcrumb a {
            color: #8891A0;
            text-decoration: none;
        }

        .loc-breadcrumb a:hover {
            color: #1B4F72;
        }

        .loc-breadcrumb-sep {
            color: #C3C9D3;
        }

        .loc-breadcrumb-current {
            color: #4A5568;
            font-weight: 600;
        }

        .loc-tabs-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .75rem;
            padding: 0 1.5rem;
            border-bottom: 2px solid #E2E8F0;
        }

        .loc-tab-nav {
            display: flex;
            gap: 0;
            flex-wrap: wrap;
        }

        .loc-tab-btn {
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
        }

        .loc-tab-btn:hover {
            color: #1B4F72;
        }

        .loc-tab-btn.active {
            color: #1B4F72;
            border-bottom-color: #1B4F72;
        }

        .loc-tab-btn .badge-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #EFF6FF;
            color: #1B4F72;
            font-size: .7rem;
            font-weight: 700;
            border-radius: 999px;
            padding: .1rem .45rem;
            margin-left: .35rem;
        }

        .loc-tab-actions {
            display: flex;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap;
            padding: .6rem 0;
        }

        .loc-panel-body {
            padding: 1.25rem 1.5rem 1.5rem;
        }

        .loc-panel-body .hms-card:last-child {
            margin-bottom: 0;
        }

        .loc-empty {
            padding: 3rem;
            text-align: center;
            color: #94A3B8;
        }

        .loc-empty i {
            font-size: 2.5rem;
            display: block;
            margin-bottom: .75rem;
        }

        .loc-empty p {
            margin: 0;
            font-weight: 600;
        }

        .loc-hint {
            margin: .25rem 0 0;
            font-size: .85rem;
        }

        .filter-bar {
            background: var(--hms-primary);
            border: 1px solid #ffffff;
            border-radius: 10px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.25rem;
        }
    </style>
@endpush

@section('content')
<div class="loc-premium-card">

    <div class="loc-header-block">
        <div class="loc-header-title"><i class="bi bi-geo-alt-fill"></i> Location Master</div>
        <nav class="loc-breadcrumb" aria-label="breadcrumb">
            <a href="{{ route('superadmin.dashboard') }}">Home</a>
            <span class="loc-breadcrumb-sep">/</span>
            <span>Masters</span>
            <span class="loc-breadcrumb-sep">/</span>
            <span class="loc-breadcrumb-current">Location Master</span>
        </nav>
    </div>

    {{-- ══════════════════════════════════════
    Tab Navigation + contextual actions
    ══════════════════════════════════════ --}}
    <div class="loc-tabs-row">
        <div class="loc-tab-nav">
            <a href="{{ route('superadmin.locations.index', ['tab' => 'countries']) }}"
                class="loc-tab-btn {{ $tab === 'countries' ? 'active' : '' }}">
                <i class="bi bi-globe2"></i> Countries
                <span class="badge-count">{{ $countries->total() }}</span>
            </a>
            <a href="{{ route('superadmin.locations.index', ['tab' => 'states', 'country_id' => $filterCountryId]) }}"
                class="loc-tab-btn {{ $tab === 'states' ? 'active' : '' }}">
                <i class="bi bi-map"></i> States
            </a>
            <a href="{{ route('superadmin.locations.index', ['tab' => 'districts', 'country_id' => $filterCountryId, 'state_id' => $filterStateId]) }}"
                class="loc-tab-btn {{ $tab === 'districts' ? 'active' : '' }}">
                <i class="bi bi-geo"></i> Districts
            </a>
            <a href="{{ route('superadmin.locations.index', ['tab' => 'cities', 'country_id' => $filterCountryId, 'state_id' => $filterStateId]) }}"
                class="loc-tab-btn {{ $tab === 'cities' ? 'active' : '' }}">
                <i class="bi bi-building"></i> Cities / Villages
            </a>
        </div>
        <div class="loc-tab-actions">
            <button type="button" class="hms-btn hms-btn-outline hms-btn-sm" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="bi bi-file-earmark-arrow-up"></i> Import Excel
            </button>
            @if($tab === 'countries')
                <button type="button" class="hms-btn hms-btn-primary hms-btn-sm" data-bs-toggle="modal"
                    data-bs-target="#addCountryModal">
                    <i class="bi bi-plus-lg"></i> Add Country
                </button>
            @elseif($tab === 'states')
                <button type="button" class="hms-btn hms-btn-primary hms-btn-sm" data-bs-toggle="modal"
                    data-bs-target="#addStateModal">
                    <i class="bi bi-plus-lg"></i> Add State
                </button>
            @elseif($tab === 'districts')
                <button type="button" class="hms-btn hms-btn-primary hms-btn-sm" data-bs-toggle="modal"
                    data-bs-target="#addDistrictModal">
                    <i class="bi bi-plus-lg"></i> Add District
                </button>
            @elseif($tab === 'cities')
                <button type="button" class="hms-btn hms-btn-primary hms-btn-sm" data-bs-toggle="modal"
                    data-bs-target="#addCityModal">
                    <i class="bi bi-plus-lg"></i> Add City
                </button>
            @endif
        </div>
    </div>

    <div class="loc-panel-body">

    {{-- ══════════════════════════════════════
    TAB: COUNTRIES
    ══════════════════════════════════════ --}}
    @if($tab === 'countries')
        <div class="filter-bar">
            <form method="GET" action="{{ route('superadmin.locations.index') }}"
                style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end">
                <input type="hidden" name="tab" value="countries">
                <div class="hms-form-group" style="margin-bottom:0;flex:1;min-width:220px">
                    <label class="hms-label">Search</label>
                    <input type="text" name="search" class="hms-input" placeholder="Search country, code, currency, timezone..."
                        value="{{ $search }}">
                </div>
                <div style="display:flex;gap:.5rem">
                    <button type="submit" class="hms-btn hms-btn-primary hms-btn-sm"><i class="bi bi-search"></i>
                        Filter</button>
                    <a href="{{ route('superadmin.locations.index', ['tab' => 'countries']) }}"
                        class="hms-btn hms-btn-outline hms-btn-sm">Clear</a>
                </div>
            </form>
        </div>

        <div class="hms-card" style="padding:0">
            <div class="hms-card-header">
                <h3 class="hms-card-title"><i class="bi bi-globe2" style="color:#ffffff"></i> Country List</h3>
                <span class="hms-badge hms-badge-info">{{ $countries->total() }} total</span>
            </div>
            @if($countries->isEmpty())
                <div class="loc-empty">
                    <i class="bi bi-globe2"></i>
                    <p>{{ $search !== '' ? 'No countries match your search' : 'No countries yet' }}</p>
                    <p class="loc-hint">
                        {{ $search !== '' ? 'Try a different keyword or clear the filter.' : 'Click "Add Country" to get started.' }}
                    </p>
                </div>
            @else
                <div class="hms-table-wrap" style="border:none">
                    <table class="hms-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Country Name</th>
                                <th>Code</th>
                                <th>Default Timezone</th>
                                <th>Currency</th>
                                <th>FX (INR/unit)</th>
                                <th>States</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($countries as $i => $c)
                                <tr>
                                    <td style="color:#94A3B8;font-size:.8rem">{{ $countries->firstItem() + $i }}</td>
                                    <td style="font-weight:600;color:#1B4F72">{{ $c->name }}</td>
                                    <td>
                                        <span
                                            style="font-size:.8rem;background:#F8FAFC;color:#334155;padding:.2rem .5rem;border-radius:6px;font-weight:700">
                                            {{ $c->country_code ?: '—' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span
                                            style="font-size:.8rem;background:#EFF6FF;color:#1B4F72;padding:.2rem .5rem;border-radius:6px">
                                            <i class="bi bi-clock me-1"></i>{{ $c->default_timezone ?? 'UTC' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span
                                            style="font-size:.8rem;background:#ECFDF5;color:#047857;padding:.2rem .5rem;border-radius:6px">
                                            {{ $c->currency_symbol ?? '₹' }} {{ $c->currency_code ?? 'INR' }}
                                        </span>
                                    </td>
                                    <td style="font-size:.85rem;color:#475569">
                                        {{ number_format((float) ($c->fx_inr_per_unit ?: 1), 4) }}
                                    </td>
                                    <td>
                                        <a href="{{ route('superadmin.locations.index', ['tab' => 'states', 'country_id' => $c->id]) }}"
                                            style="color:#1B4F72;font-size:.85rem">
                                            {{ $c->states_count ?? $c->states()->count() }} states
                                            <i class="bi bi-arrow-right-short"></i>
                                        </a>
                                    </td>
                                    <td>
                                        <button
                                            class="hms-badge toggle-btn {{ $c->is_active ? 'hms-badge-success' : 'hms-badge-secondary' }}"
                                            data-url="{{ route('superadmin.locations.countries.toggle', $c->id) }}"
                                            style="border:none;cursor:pointer;padding:.25rem .6rem;font-size:.75rem">
                                            {{ $c->is_active ? 'Active' : 'Inactive' }}
                                        </button>
                                    </td>
                                    <td class="text-end">
                                        <div style="display:flex;gap:.4rem;justify-content:flex-end">
                                            <button class="hms-btn hms-btn-outline hms-btn-xs edit-country-btn" data-id="{{ $c->id }}"
                                                data-name="{{ $c->name }}" data-country-code="{{ $c->country_code ?? '' }}"
                                                data-timezone="{{ $c->default_timezone ?? 'UTC' }}"
                                                data-currency-code="{{ $c->currency_code ?? 'INR' }}"
                                                data-currency-symbol="{{ $c->currency_symbol ?? '₹' }}"
                                                data-currency-name="{{ $c->currency_name ?? '' }}"
                                                data-fx="{{ (float) ($c->fx_inr_per_unit ?: 1) }}">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                            <form method="POST" action="{{ route('superadmin.locations.countries.destroy', $c->id) }}"
                                                class="del-form">
                                                @csrf @method('DELETE')
                                                <button class="hms-btn hms-btn-xs" style="background:#FEE2E2;color:#B91C1C;border:none">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($countries->hasPages())
                    <div style="padding:1rem 1.25rem;border-top:1px solid #F1F5F9">
                        {{ $countries->links() }}
                    </div>
                @endif
            @endif
        </div>
    @endif

    {{-- ══════════════════════════════════════
    TAB: STATES
    ══════════════════════════════════════ --}}
    @if($tab === 'states')
        <div class="filter-bar">
            <form method="GET" action="{{ route('superadmin.locations.index') }}"
                style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end">
                <input type="hidden" name="tab" value="states">
                <div class="hms-form-group" style="margin-bottom:0;min-width:200px">
                    <label class="hms-label">Country <span style="color:#E53E3E">*</span></label>
                    <select name="country_id" class="hms-select">
                        <option value="">— All Countries —</option>
                        @foreach($countries as $c)
                            <option value="{{ $c->id }}" {{ $filterCountryId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="hms-form-group" style="margin-bottom:0;flex:1;min-width:180px">
                    <label class="hms-label">Search</label>
                    <input type="text" name="search" class="hms-input" placeholder="Search state..." value="{{ $search }}">
                </div>
                <div style="display:flex;gap:.5rem">
                    <button type="submit" class="hms-btn hms-btn-primary hms-btn-sm"><i class="bi bi-search"></i>
                        Filter</button>
                    <a href="{{ route('superadmin.locations.index', ['tab' => 'states']) }}"
                        class="hms-btn hms-btn-outline hms-btn-sm">Clear</a>
                </div>
            </form>
        </div>

        <div class="hms-card" style="padding:0">
            <div class="hms-card-header">
                <h3 class="hms-card-title"><i class="bi bi-map" style="color:#ffffff"></i> State List</h3>
                @if($states instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    <span class="hms-badge hms-badge-info">{{ $states->total() }} total</span>
                @endif
            </div>
            @if($states->isEmpty())
                <div class="loc-empty"><i class="bi bi-map"></i>
                    <p>No states found</p>
                    <p class="loc-hint">Click "Add State" to add one.</p>
                </div>
            @else
                <div class="hms-table-wrap" style="border:none">
                    <table class="hms-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Country</th>
                                <th>State</th>
                                <th>Districts</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($states as $i => $s)
                                <tr>
                                    <td style="color:#94A3B8;font-size:.8rem">{{ $states->firstItem() + $i }}</td>
                                    <td style="color:#64748B;font-size:.875rem">{{ $s->country->name }}</td>
                                    <td style="font-weight:600">{{ $s->name }}</td>
                                    <td>
                                        <a href="{{ route('superadmin.locations.index', ['tab' => 'districts', 'country_id' => $filterCountryId, 'state_id' => $s->id]) }}"
                                            style="color:#1B4F72;font-size:.85rem">
                                            {{ $s->districts()->count() }} districts <i class="bi bi-arrow-right-short"></i>
                                        </a>
                                    </td>
                                    <td>
                                        <button
                                            class="hms-badge toggle-btn {{ $s->is_active ? 'hms-badge-success' : 'hms-badge-secondary' }}"
                                            data-url="{{ route('superadmin.locations.states.toggle', $s->id) }}"
                                            style="border:none;cursor:pointer;padding:.25rem .6rem;font-size:.75rem">
                                            {{ $s->is_active ? 'Active' : 'Inactive' }}
                                        </button>
                                    </td>
                                    <td class="text-end">
                                        <div style="display:flex;gap:.4rem;justify-content:flex-end">
                                            <button class="hms-btn hms-btn-outline hms-btn-xs edit-state-btn" data-id="{{ $s->id }}"
                                                data-name="{{ $s->name }}" data-country-id="{{ $s->country_id }}">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                            <form method="POST" action="{{ route('superadmin.locations.states.destroy', $s->id) }}"
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
                @if($states->hasPages())
                    <div style="padding:1rem 1.25rem;border-top:1px solid #F1F5F9">{{ $states->links() }}</div>
                @endif
            @endif
        </div>
    @endif

    {{-- ══════════════════════════════════════
    TAB: DISTRICTS
    ══════════════════════════════════════ --}}
    @if($tab === 'districts')
        <div class="filter-bar">
            <form method="GET" action="{{ route('superadmin.locations.index') }}"
                style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end" id="districtFilterForm">
                <input type="hidden" name="tab" value="districts">
                <div class="hms-form-group" style="margin-bottom:0;min-width:180px">
                    <label class="hms-label">Country</label>
                    <select name="country_id" class="hms-select" id="dfCountry">
                        <option value="">— All Countries —</option>
                        @foreach($countries as $c)
                            <option value="{{ $c->id }}" {{ $filterCountryId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="hms-form-group" style="margin-bottom:0;min-width:180px">
                    <label class="hms-label">State</label>
                    <select name="state_id" class="hms-select" id="dfState">
                        <option value="">— All States —</option>
                        @foreach($statesForFilter as $s)
                            <option value="{{ $s->id }}" {{ $filterStateId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="hms-form-group" style="margin-bottom:0;flex:1;min-width:160px">
                    <label class="hms-label">Search</label>
                    <input type="text" name="search" class="hms-input" placeholder="Search district..." value="{{ $search }}">
                </div>
                <div style="display:flex;gap:.5rem">
                    <button type="submit" class="hms-btn hms-btn-primary hms-btn-sm"><i class="bi bi-search"></i>
                        Filter</button>
                    <a href="{{ route('superadmin.locations.index', ['tab' => 'districts']) }}"
                        class="hms-btn hms-btn-outline hms-btn-sm">Clear</a>
                </div>
            </form>
        </div>

        <div class="hms-card" style="padding:0">
            <div class="hms-card-header">
                <h3 class="hms-card-title"><i class="bi bi-geo" style="color:#ffffff"></i> District List</h3>
                @if($districts instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    <span class="hms-badge hms-badge-info">{{ $districts->total() }} total</span>
                @endif
            </div>
            @if($districts->isEmpty())
                <div class="loc-empty"><i class="bi bi-geo"></i>
                    <p>No districts found</p>
                    <p class="loc-hint">Click "Add District" to add one.</p>
                </div>
            @else
                <div class="hms-table-wrap" style="border:none">
                    <table class="hms-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Country</th>
                                <th>State</th>
                                <th>District Name</th>
                                <th>Cities</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($districts as $i => $d)
                                <tr>
                                    <td style="color:#94A3B8;font-size:.8rem">{{ $districts->firstItem() + $i }}</td>
                                    <td style="color:#64748B;font-size:.875rem">{{ $d->state->country->name }}</td>
                                    <td style="color:#64748B;font-size:.875rem">{{ $d->state->name }}</td>
                                    <td style="font-weight:600">{{ $d->name }}</td>
                                    <td>
                                        <a href="{{ route('superadmin.locations.index', ['tab' => 'cities', 'country_id' => $filterCountryId, 'state_id' => $filterStateId, 'district_id' => $d->id]) }}"
                                            style="color:#1B4F72;font-size:.85rem">
                                            {{ $d->cities()->count() }} cities <i class="bi bi-arrow-right-short"></i>
                                        </a>
                                    </td>
                                    <td>
                                        <button
                                            class="hms-badge toggle-btn {{ $d->is_active ? 'hms-badge-success' : 'hms-badge-secondary' }}"
                                            data-url="{{ route('superadmin.locations.districts.toggle', $d->id) }}"
                                            style="border:none;cursor:pointer;padding:.25rem .6rem;font-size:.75rem">
                                            {{ $d->is_active ? 'Active' : 'Inactive' }}
                                        </button>
                                    </td>
                                    <td class="text-end">
                                        <div style="display:flex;gap:.4rem;justify-content:flex-end">
                                            <button class="hms-btn hms-btn-outline hms-btn-xs edit-district-btn" data-id="{{ $d->id }}"
                                                data-name="{{ $d->name }}" data-state-id="{{ $d->state_id }}">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                            <form method="POST" action="{{ route('superadmin.locations.districts.destroy', $d->id) }}"
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
                @if($districts->hasPages())
                    <div style="padding:1rem 1.25rem;border-top:1px solid #F1F5F9">{{ $districts->links() }}</div>
                @endif
            @endif
        </div>
    @endif

    {{-- ══════════════════════════════════════
    TAB: CITIES
    ══════════════════════════════════════ --}}
    @if($tab === 'cities')
        <div class="filter-bar">
            <form method="GET" action="{{ route('superadmin.locations.index') }}"
                style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end" id="cityFilterForm">
                <input type="hidden" name="tab" value="cities">
                <div class="hms-form-group" style="margin-bottom:0;min-width:170px">
                    <label class="hms-label">Country</label>
                    <select name="country_id" class="hms-select" id="cfCountry">
                        <option value="">— All Countries —</option>
                        @foreach($countries as $c)
                            <option value="{{ $c->id }}" {{ $filterCountryId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="hms-form-group" style="margin-bottom:0;min-width:170px">
                    <label class="hms-label">State</label>
                    <select name="state_id" class="hms-select" id="cfState">
                        <option value="">— All States —</option>
                        @foreach($statesForFilter as $s)
                            <option value="{{ $s->id }}" {{ $filterStateId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="hms-form-group" style="margin-bottom:0;min-width:170px">
                    <label class="hms-label">District</label>
                    <select name="district_id" class="hms-select" id="cfDistrict">
                        <option value="">All Districts</option>
                        @foreach($districtsForFilter as $d)
                            <option value="{{ $d->id }}" {{ $filterDistrictId == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="hms-form-group" style="margin-bottom:0;flex:1;min-width:150px">
                    <label class="hms-label">Search</label>
                    <input type="text" name="search" class="hms-input" placeholder="Search city..." value="{{ $search }}">
                </div>
                <div style="display:flex;gap:.5rem">
                    <button type="submit" class="hms-btn hms-btn-primary hms-btn-sm"><i class="bi bi-search"></i>
                        Filter</button>
                    <a href="{{ route('superadmin.locations.index', ['tab' => 'cities']) }}"
                        class="hms-btn hms-btn-outline hms-btn-sm">Clear</a>
                </div>
            </form>
        </div>

        <div class="hms-card" style="padding:0">
            <div class="hms-card-header">
                <h3 class="hms-card-title"><i class="bi bi-building" style="color:#ffffff"></i> City / Village List</h3>
                @if($cities instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    <span class="hms-badge hms-badge-info">{{ $cities->total() }} total</span>
                @endif
            </div>
            @if($cities->isEmpty())
                <div class="loc-empty"><i class="bi bi-building"></i>
                    <p>No cities found</p>
                    <p class="loc-hint">Click "Add City" to add one.</p>
                </div>
            @else
                <div class="hms-table-wrap" style="border:none">
                    <table class="hms-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Country</th>
                                <th>State</th>
                                <th>District</th>
                                <th>City / Village</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cities as $i => $ci)
                                <tr>
                                    <td style="color:#94A3B8;font-size:.8rem">{{ $cities->firstItem() + $i }}</td>
                                    <td style="color:#64748B;font-size:.875rem">{{ $ci->state->country->name }}</td>
                                    <td style="color:#64748B;font-size:.875rem">{{ $ci->state->name }}</td>
                                    <td style="color:#64748B;font-size:.875rem">{{ $ci->district?->name ?? '—' }}</td>
                                    <td style="font-weight:600">{{ $ci->name }}</td>
                                    <td>
                                        <button
                                            class="hms-badge toggle-btn {{ $ci->is_active ? 'hms-badge-success' : 'hms-badge-secondary' }}"
                                            data-url="{{ route('superadmin.locations.cities.toggle', $ci->id) }}"
                                            style="border:none;cursor:pointer;padding:.25rem .6rem;font-size:.75rem">
                                            {{ $ci->is_active ? 'Active' : 'Inactive' }}
                                        </button>
                                    </td>
                                    <td class="text-end">
                                        <div style="display:flex;gap:.4rem;justify-content:flex-end">
                                            <button class="hms-btn hms-btn-outline hms-btn-xs edit-city-btn" data-id="{{ $ci->id }}"
                                                data-name="{{ $ci->name }}" data-state-id="{{ $ci->state_id }}"
                                                data-district-id="{{ $ci->district_id }}">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                            <form method="POST" action="{{ route('superadmin.locations.cities.destroy', $ci->id) }}"
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
                @if($cities->hasPages())
                    <div style="padding:1rem 1.25rem;border-top:1px solid #F1F5F9">{{ $cities->links() }}</div>
                @endif
            @endif
        </div>
    @endif

    </div>{{-- /.loc-panel-body --}}
</div>{{-- /.loc-premium-card --}}

    {{-- ══════════════════════════════════════════════════════
    MODALS
    ══════════════════════════════════════════════════════ --}}

    {{-- Add Country --}}
    <div class="modal fade" id="addCountryModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:12px;border:none;box-shadow:0 20px 60px rgba(0,0,0,.15)">
                <div class="modal-header" style="background:#1B4F72;border-radius:12px 12px 0 0;padding:.875rem 1.25rem">
                    <h5 class="modal-title" style="color:#fff;font-weight:700;font-size:.95rem"><i
                            class="bi bi-globe2 me-2"></i>Add Country</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('superadmin.locations.countries.store') }}">
                    @csrf
                    <div class="modal-body" style="padding:1.25rem">
                        <div class="mb-3">
                            <label class="hms-label">Country Name <span style="color:#E53E3E">*</span></label>
                            <input type="text" name="name" class="hms-input" placeholder="e.g. India" required autofocus>
                            <p style="font-size:.78rem;color:#64748B;margin:.5rem 0 0">Name will be auto-formatted (Title
                                Case).</p>
                        </div>
                        <div class="mb-3">
                            <label class="hms-label">Country Code (ISO) <span style="color:#E53E3E">*</span></label>
                            <select name="country_code" id="addCountryCode" class="hms-input" required>
                                <option value="">— Select Code —</option>
                                @foreach(\App\Services\Platform\CurrencyService::commonCountryCodes() as $iso => $hint)
                                    <option value="{{ $iso }}" @selected($iso === 'IN')>{{ $iso }} — {{ $hint }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="hms-label">Default Timezone <span style="color:#E53E3E">*</span></label>
                            <select name="default_timezone" id="addCountryTimezone" class="hms-input" required>
                                @foreach(\App\Services\Platform\TimezoneService::groupedTimezones() as $region => $zones)
                                    <optgroup label="{{ $region }}">
                                        @foreach($zones as $tz)
                                            <option value="{{ $tz }}" @selected($tz === 'UTC')>{{ $tz }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="hms-label">Currency <span style="color:#E53E3E">*</span></label>
                            <select name="currency_code" id="addCountryCurrency" class="hms-input" required>
                                @foreach(\App\Services\Platform\CurrencyService::commonCurrencies() as $cur)
                                    <option value="{{ $cur['code'] }}" data-symbol="{{ $cur['symbol'] }}"
                                        data-name="{{ $cur['name'] }}"
                                        data-fx="{{ \App\Services\Platform\CurrencyService::fxForCurrencyCode($cur['code']) }}"
                                        @selected($cur['code'] === 'INR')>
                                        {{ $cur['code'] }} ({{ $cur['symbol'] }}) — {{ $cur['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="currency_symbol" id="addCountryCurrencySymbol" value="₹">
                            <input type="hidden" name="currency_name" id="addCountryCurrencyName" value="Indian Rupee">
                        </div>
                        <div>
                            <label class="hms-label">FX Rate (INR per 1 unit) <span style="color:#E53E3E">*</span></label>
                            <input type="number" step="0.0001" min="0.0001" name="fx_inr_per_unit" id="addCountryFx"
                                class="hms-input" value="1" required>
                            <p style="font-size:.78rem;color:#64748B;margin:.5rem 0 0">Used to convert SaaS plan prices on
                                register. Example: AUD ≈ 55 (1 A$ ≈ ₹55).</p>
                        </div>
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

    {{-- Edit Country --}}
    <div class="modal fade" id="editCountryModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:12px;border:none;box-shadow:0 20px 60px rgba(0,0,0,.15)">
                <div class="modal-header" style="background:#1B4F72;border-radius:12px 12px 0 0;padding:.875rem 1.25rem">
                    <h5 class="modal-title" style="color:#fff;font-weight:700;font-size:.95rem"><i
                            class="bi bi-pencil-fill me-2"></i>Edit Country</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editCountryForm" action="">
                    @csrf @method('PUT')
                    <div class="modal-body" style="padding:1.25rem">
                        <div class="mb-3">
                            <label class="hms-label">Country Name <span style="color:#E53E3E">*</span></label>
                            <input type="text" name="name" id="editCountryName" class="hms-input" required>
                        </div>
                        <div class="mb-3">
                            <label class="hms-label">Country Code (ISO) <span style="color:#E53E3E">*</span></label>
                            <select name="country_code" id="editCountryCode" class="hms-input" required>
                                <option value="">— Select Code —</option>
                                @foreach(\App\Services\Platform\CurrencyService::commonCountryCodes() as $iso => $hint)
                                    <option value="{{ $iso }}">{{ $iso }} — {{ $hint }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="hms-label">Default Timezone <span style="color:#E53E3E">*</span></label>
                            <select name="default_timezone" id="editCountryTimezone" class="hms-input select2-timezone"
                                required>
                                @foreach(\App\Services\Platform\TimezoneService::groupedTimezones() as $region => $zones)
                                    <optgroup label="{{ $region }}">
                                        @foreach($zones as $tz)
                                            <option value="{{ $tz }}">{{ $tz }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <p style="font-size:.78rem;color:#F59E0B;margin:.5rem 0 0">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                Updating will cascade to hospitals that have not overridden their timezone.
                            </p>
                        </div>
                        <div class="mb-3">
                            <label class="hms-label">Currency <span style="color:#E53E3E">*</span></label>
                            <select name="currency_code" id="editCountryCurrency" class="hms-input" required>
                                @foreach(\App\Services\Platform\CurrencyService::commonCurrencies() as $cur)
                                    <option value="{{ $cur['code'] }}" data-symbol="{{ $cur['symbol'] }}"
                                        data-name="{{ $cur['name'] }}"
                                        data-fx="{{ \App\Services\Platform\CurrencyService::fxForCurrencyCode($cur['code']) }}">
                                        {{ $cur['code'] }} ({{ $cur['symbol'] }}) — {{ $cur['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="currency_symbol" id="editCountryCurrencySymbol" value="₹">
                            <input type="hidden" name="currency_name" id="editCountryCurrencyName" value="">
                            <p style="font-size:.78rem;color:#F59E0B;margin:.5rem 0 0">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                Currency cascades to hospitals that have not overridden it.
                            </p>
                        </div>
                        <div>
                            <label class="hms-label">FX Rate (INR per 1 unit) <span style="color:#E53E3E">*</span></label>
                            <input type="number" step="0.0001" min="0.0001" name="fx_inr_per_unit" id="editCountryFx"
                                class="hms-input" value="1" required>
                            <p style="font-size:.78rem;color:#64748B;margin:.5rem 0 0">Register plan prices convert using
                                this rate.</p>
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

    {{-- Add State --}}
    <div class="modal fade" id="addStateModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:12px;border:none;box-shadow:0 20px 60px rgba(0,0,0,.15)">
                <div class="modal-header" style="background:#1B4F72;border-radius:12px 12px 0 0;padding:.875rem 1.25rem">
                    <h5 class="modal-title" style="color:#fff;font-weight:700;font-size:.95rem"><i
                            class="bi bi-map me-2"></i>Add State</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('superadmin.locations.states.store') }}">
                    @csrf
                    <div class="modal-body" style="padding:1.25rem">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="hms-label">Country <span style="color:#E53E3E">*</span></label>
                                <select name="country_id" class="hms-select" required>
                                    <option value="">— Select Country —</option>
                                    @foreach($countries as $c)
                                        <option value="{{ $c->id }}" {{ $filterCountryId == $c->id ? 'selected' : '' }}>
                                            {{ $c->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="hms-label">State Name <span style="color:#E53E3E">*</span></label>
                                <input type="text" name="name" class="hms-input" placeholder="e.g. Gujarat" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #F1F5F9;padding:.75rem 1.25rem">
                        <button type="button" class="hms-btn hms-btn-outline hms-btn-sm"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="hms-btn hms-btn-primary hms-btn-sm"><i class="bi bi-plus-lg"></i> Add
                            State</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit State --}}
    <div class="modal fade" id="editStateModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:12px;border:none;box-shadow:0 20px 60px rgba(0,0,0,.15)">
                <div class="modal-header" style="background:#1B4F72;border-radius:12px 12px 0 0;padding:.875rem 1.25rem">
                    <h5 class="modal-title" style="color:#fff;font-weight:700;font-size:.95rem"><i
                            class="bi bi-pencil-fill me-2"></i>Edit State</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editStateForm" action="">
                    @csrf @method('PUT')
                    <div class="modal-body" style="padding:1.25rem">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="hms-label">Country <span style="color:#E53E3E">*</span></label>
                                <select name="country_id" id="editStateCountry" class="hms-select" required>
                                    <option value="">— Select Country —</option>
                                    @foreach($countries as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="hms-label">State Name <span style="color:#E53E3E">*</span></label>
                                <input type="text" name="name" id="editStateName" class="hms-input" required>
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

    {{-- Add District --}}
    <div class="modal fade" id="addDistrictModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:12px;border:none;box-shadow:0 20px 60px rgba(0,0,0,.15)">
                <div class="modal-header" style="background:#1B4F72;border-radius:12px 12px 0 0;padding:.875rem 1.25rem">
                    <h5 class="modal-title" style="color:#fff;font-weight:700;font-size:.95rem"><i
                            class="bi bi-geo me-2"></i>Add District</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('superadmin.locations.districts.store') }}">
                    @csrf
                    <div class="modal-body" style="padding:1.25rem">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="hms-label">Country <span style="color:#E53E3E">*</span></label>
                                <select class="hms-select modal-country-sel" data-target="addDistStateSelect" required>
                                    <option value="">— Select —</option>
                                    @foreach($countries as $c)
                                        <option value="{{ $c->id }}" {{ $filterCountryId == $c->id ? 'selected' : '' }}>
                                            {{ $c->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="hms-label">State <span style="color:#E53E3E">*</span></label>
                                <select name="state_id" class="hms-select" id="addDistStateSelect" required>
                                    <option value="">— Select State —</option>
                                    @foreach($statesForFilter as $s)
                                        <option value="{{ $s->id }}" {{ $filterStateId == $s->id ? 'selected' : '' }}>
                                            {{ $s->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="hms-label">District Name <span style="color:#E53E3E">*</span></label>
                                <input type="text" name="name" class="hms-input" placeholder="e.g. Mehsana" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #F1F5F9;padding:.75rem 1.25rem">
                        <button type="button" class="hms-btn hms-btn-outline hms-btn-sm"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="hms-btn hms-btn-primary hms-btn-sm"><i class="bi bi-plus-lg"></i> Add
                            District</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit District --}}
    <div class="modal fade" id="editDistrictModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:12px;border:none;box-shadow:0 20px 60px rgba(0,0,0,.15)">
                <div class="modal-header" style="background:#1B4F72;border-radius:12px 12px 0 0;padding:.875rem 1.25rem">
                    <h5 class="modal-title" style="color:#fff;font-weight:700;font-size:.95rem"><i
                            class="bi bi-pencil-fill me-2"></i>Edit District</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editDistrictForm" action="">
                    @csrf @method('PUT')
                    <div class="modal-body" style="padding:1.25rem">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="hms-label">Country</label>
                                <select class="hms-select modal-country-sel" data-target="editDistStateSelect">
                                    <option value="">— Select —</option>
                                    @foreach($countries as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="hms-label">State <span style="color:#E53E3E">*</span></label>
                                <select name="state_id" class="hms-select" id="editDistStateSelect" required>
                                    <option value="">— Select State —</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="hms-label">District Name <span style="color:#E53E3E">*</span></label>
                                <input type="text" name="name" id="editDistrictName" class="hms-input" required>
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

    {{-- Add City --}}
    <div class="modal fade" id="addCityModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:12px;border:none;box-shadow:0 20px 60px rgba(0,0,0,.15)">
                <div class="modal-header" style="background:#1B4F72;border-radius:12px 12px 0 0;padding:.875rem 1.25rem">
                    <h5 class="modal-title" style="color:#fff;font-weight:700;font-size:.95rem"><i
                            class="bi bi-building me-2"></i>Add City / Village</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('superadmin.locations.cities.store') }}">
                    @csrf
                    <div class="modal-body" style="padding:1.25rem">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="hms-label">Country <span style="color:#E53E3E">*</span></label>
                                <select class="hms-select modal-country-sel" data-target="addCityStateSelect" required>
                                    <option value="">— Select —</option>
                                    @foreach($countries as $c)
                                        <option value="{{ $c->id }}" {{ $filterCountryId == $c->id ? 'selected' : '' }}>
                                            {{ $c->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="hms-label">State <span style="color:#E53E3E">*</span></label>
                                <select name="state_id" class="hms-select cascade-state" id="addCityStateSelect"
                                    data-district-target="addCityDistrictSelect" required>
                                    <option value="">— Select State —</option>
                                    @foreach($statesForFilter as $s)
                                        <option value="{{ $s->id }}" {{ $filterStateId == $s->id ? 'selected' : '' }}>
                                            {{ $s->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="hms-label">District <span
                                        style="color:#94A3B8;font-weight:400">(optional)</span></label>
                                <select name="district_id" class="hms-select" id="addCityDistrictSelect">
                                    <option value="">— None —</option>
                                    @foreach($districtsForFilter as $d)
                                        <option value="{{ $d->id }}" {{ $filterDistrictId == $d->id ? 'selected' : '' }}>
                                            {{ $d->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="hms-label">City / Village <span style="color:#E53E3E">*</span></label>
                                <input type="text" name="name" class="hms-input" placeholder="e.g. Mahesana" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #F1F5F9;padding:.75rem 1.25rem">
                        <button type="button" class="hms-btn hms-btn-outline hms-btn-sm"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="hms-btn hms-btn-primary hms-btn-sm"><i class="bi bi-plus-lg"></i> Add
                            City</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit City --}}
    <div class="modal fade" id="editCityModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:12px;border:none;box-shadow:0 20px 60px rgba(0,0,0,.15)">
                <div class="modal-header" style="background:#1B4F72;border-radius:12px 12px 0 0;padding:.875rem 1.25rem">
                    <h5 class="modal-title" style="color:#fff;font-weight:700;font-size:.95rem"><i
                            class="bi bi-pencil-fill me-2"></i>Edit City</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editCityForm" action="">
                    @csrf @method('PUT')
                    <div class="modal-body" style="padding:1.25rem">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="hms-label">Country</label>
                                <select class="hms-select modal-country-sel" data-target="editCityStateSelect">
                                    <option value="">— Select —</option>
                                    @foreach($countries as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="hms-label">State <span style="color:#E53E3E">*</span></label>
                                <select name="state_id" class="hms-select cascade-state" id="editCityStateSelect"
                                    data-district-target="editCityDistrictSelect" required>
                                    <option value="">— Select State —</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="hms-label">District</label>
                                <select name="district_id" class="hms-select" id="editCityDistrictSelect">
                                    <option value="">— None —</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="hms-label">City / Village <span style="color:#E53E3E">*</span></label>
                                <input type="text" name="name" id="editCityName" class="hms-input" required>
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

    {{-- Import Modal --}}
    <div class="modal fade" id="importModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:12px;border:none;box-shadow:0 20px 60px rgba(0,0,0,.15)">
                <div class="modal-header" style="background:#1B4F72;border-radius:12px 12px 0 0;padding:.875rem 1.25rem">
                    <h5 class="modal-title" style="color:#fff;font-weight:700;font-size:.95rem"><i
                            class="bi bi-file-earmark-arrow-up me-2"></i>Import via Excel</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('superadmin.locations.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body" style="padding:1.25rem">
                        <div
                            style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px;padding:1rem;margin-bottom:1.25rem">
                            <p style="margin:0 0 .5rem;font-weight:700;color:#1D4ED8;font-size:.875rem">
                                <i class="bi bi-info-circle-fill me-1"></i> Required Excel Format
                            </p>
                            <div style="margin-top:.5rem;display:flex;gap:.4rem;flex-wrap:wrap">
                                <code
                                    style="background:#DBEAFE;color:#1D4ED8;padding:.15rem .4rem;border-radius:4px;font-size:.78rem">country</code>
                                <code
                                    style="background:#DBEAFE;color:#1D4ED8;padding:.15rem .4rem;border-radius:4px;font-size:.78rem">state</code>
                                <code
                                    style="background:#F3F4F6;color:#6B7280;padding:.15rem .4rem;border-radius:4px;font-size:.78rem">district</code>
                                <code
                                    style="background:#DBEAFE;color:#1D4ED8;padding:.15rem .4rem;border-radius:4px;font-size:.78rem">city</code>
                            </div>
                            <p style="margin:.5rem 0 0;font-size:.78rem;color:#6B7280">
                                Blue = required &nbsp;|&nbsp; district = optional &nbsp;|&nbsp;
                                Hierarchy auto-resolved. Duplicates skipped. All names auto Title-Cased.
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

@endsection

@push('scripts')

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function () {

            $('#addCountryTimezone').select2({
                width: '100%',
                dropdownParent: $('#addCountryModal'),
                placeholder: 'Select Timezone'
            });

            $('#editCountryTimezone').select2({
                width: '100%',
                dropdownParent: $('#editCountryModal'),
                placeholder: 'Select Timezone'
            });

        });
    </script>

    <script>
        (function () {
            const ajaxStatesUrl = @json(route('superadmin.locations.ajax.states'));
            const ajaxDistrictsUrl = @json(route('superadmin.locations.ajax.districts'));

            /* ── Fetch states for a country_id, populate a <select> ── */
            function loadStates(countryId, selectEl, preselectId) {
                selectEl.innerHTML = '<option value="">— Select State —</option>';
                if (!countryId) return;
                fetch(ajaxStatesUrl + '?country_id=' + countryId, { headers: { Accept: 'application/json' } })
                    .then(r => r.json())
                    .then(states => {
                        states.forEach(s => {
                            const opt = new Option(s.name, s.id, false, String(s.id) === String(preselectId));
                            selectEl.append(opt);
                        });
                    });
            }

            /* ── Fetch districts for a state_id, populate a <select> ── */
            function loadDistricts(stateId, selectEl, preselectId) {
                selectEl.innerHTML = '<option value="">— None —</option>';
                if (!stateId) return;
                fetch(ajaxDistrictsUrl + '?state_id=' + stateId, { headers: { Accept: 'application/json' } })
                    .then(r => r.json())
                    .then(districts => {
                        districts.forEach(d => {
                            const opt = new Option(d.name, d.id, false, String(d.id) === String(preselectId));
                            selectEl.append(opt);
                        });
                    });
            }

            /* ── Filter bar: country change → reload page with state_id cleared ── */
            ['dfCountry', 'cfCountry'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.addEventListener('change', function () {
                    this.closest('form').querySelector('[name="state_id"]').value = '';
                    this.closest('form').querySelector('[name="district_id"]') &&
                        (this.closest('form').querySelector('[name="district_id"]').value = '');
                    this.closest('form').submit();
                });
            });

            /* ── Filter bar: state change → reload page (district_id cleared) ── */
            ['dfState', 'cfState'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.addEventListener('change', function () {
                    const distSel = this.closest('form').querySelector('[name="district_id"]');
                    if (distSel) distSel.value = '';
                    this.closest('form').submit();
                });
            });

            /* ── Filter bar: district change → reload page ── */
            const cfDistrict = document.getElementById('cfDistrict');
            if (cfDistrict) cfDistrict.addEventListener('change', function () {
                this.closest('form').submit();
            });

            /* ── Modal: country selects → cascade states ── */
            document.querySelectorAll('.modal-country-sel').forEach(sel => {
                sel.addEventListener('change', function () {
                    const targetId = this.dataset.target;
                    const stateSel = document.getElementById(targetId);
                    if (stateSel) loadStates(this.value, stateSel, null);

                    // Also clear district if present
                    const stateEl = document.getElementById(targetId);
                    if (stateEl) {
                        const distTarget = stateEl.dataset.districtTarget;
                        if (distTarget) {
                            const distSel = document.getElementById(distTarget);
                            if (distSel) distSel.innerHTML = '<option value="">— None —</option>';
                        }
                    }
                });
            });

            /* ── Modal: state selects → cascade districts ── */
            document.querySelectorAll('.cascade-state').forEach(sel => {
                sel.addEventListener('change', function () {
                    const distTarget = this.dataset.districtTarget;
                    if (distTarget) {
                        const distSel = document.getElementById(distTarget);
                        if (distSel) loadDistricts(this.value, distSel, null);
                    }
                });
            });

            /* ── Currency select → fill hidden symbol/name + FX ── */
            function bindCurrencySelect(selId, symbolId, nameId, fxId) {
                const sel = document.getElementById(selId);
                if (!sel) return;
                const sync = () => {
                    const opt = sel.options[sel.selectedIndex];
                    const symbolEl = document.getElementById(symbolId);
                    const nameEl = document.getElementById(nameId);
                    const fxEl = document.getElementById(fxId);
                    if (symbolEl) symbolEl.value = opt?.dataset?.symbol || '';
                    if (nameEl) nameEl.value = opt?.dataset?.name || '';
                    if (fxEl && opt?.dataset?.fx) fxEl.value = opt.dataset.fx;
                };
                sel.addEventListener('change', sync);
                sync();
            }
            bindCurrencySelect('addCountryCurrency', 'addCountryCurrencySymbol', 'addCountryCurrencyName', 'addCountryFx');
            bindCurrencySelect('editCountryCurrency', 'editCountryCurrencySymbol', 'editCountryCurrencyName', 'editCountryFx');

            /* ── Edit Country ── */
            document.querySelectorAll('.edit-country-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    document.getElementById('editCountryForm').action =
                        '{{ route("superadmin.locations.countries.update", ":id") }}'.replace(':id', this.dataset.id);
                    document.getElementById('editCountryName').value = this.dataset.name;
                    const codeSel = document.getElementById('editCountryCode');
                    if (codeSel) codeSel.value = this.dataset.countryCode || '';
                    const tzSel = document.getElementById('editCountryTimezone');
                    if (tzSel) tzSel.value = this.dataset.timezone || 'UTC';
                    const curSel = document.getElementById('editCountryCurrency');
                    if (curSel) {
                        curSel.value = this.dataset.currencyCode || 'INR';
                        curSel.dispatchEvent(new Event('change'));
                    }
                    const fxEl = document.getElementById('editCountryFx');
                    if (fxEl) fxEl.value = this.dataset.fx || '1';
                    new bootstrap.Modal(document.getElementById('editCountryModal')).show();
                });
            });

            /* ── Edit State ── */
            document.querySelectorAll('.edit-state-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    document.getElementById('editStateForm').action =
                        '{{ route("superadmin.locations.states.update", ":id") }}'.replace(':id', this.dataset.id);
                    document.getElementById('editStateName').value = this.dataset.name;
                    document.getElementById('editStateCountry').value = this.dataset.countryId;
                    new bootstrap.Modal(document.getElementById('editStateModal')).show();
                });
            });

            /* ── Edit District ── */
            document.querySelectorAll('.edit-district-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    const stateId = this.dataset.stateId;
                    document.getElementById('editDistrictForm').action =
                        '{{ route("superadmin.locations.districts.update", ":id") }}'.replace(':id', this.dataset.id);
                    document.getElementById('editDistrictName').value = this.dataset.name;

                    // We need to find country for this state — load all states and find
                    const editDistStateSel = document.getElementById('editDistStateSelect');
                    editDistStateSel.innerHTML = '<option value="">Loading...</option>';

                    // Pre-select the state once states are loaded
                    // Trigger country load by matching state from countries list
                    // Simpler: we pass state_id directly (no country cascade needed for editing)
                    fetch(ajaxStatesUrl + '?country_id=0', { headers: { Accept: 'application/json' } }) // dummy call
                        .catch(() => { });

                    // Direct approach: set state option manually
                    editDistStateSel.innerHTML = `<option value="${stateId}" selected>Current State</option>`;

                    new bootstrap.Modal(document.getElementById('editDistrictModal')).show();
                });
            });

            /* ── Edit City ── */
            document.querySelectorAll('.edit-city-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    const stateId = this.dataset.stateId;
                    const districtId = this.dataset.districtId;
                    document.getElementById('editCityForm').action =
                        '{{ route("superadmin.locations.cities.update", ":id") }}'.replace(':id', this.dataset.id);
                    document.getElementById('editCityName').value = this.dataset.name;

                    const stateSel = document.getElementById('editCityStateSelect');
                    const distSel = document.getElementById('editCityDistrictSelect');

                    stateSel.innerHTML = `<option value="${stateId}" selected>Current State</option>`;
                    distSel.innerHTML = districtId
                        ? `<option value="">— None —</option><option value="${districtId}" selected>Current District</option>`
                        : `<option value="">— None —</option>`;

                    new bootstrap.Modal(document.getElementById('editCityModal')).show();
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
                        text: 'This will also delete all child records (states/districts/cities).',
                        icon: 'warning', showCancelButton: true,
                        confirmButtonColor: '#B91C1C', confirmButtonText: 'Yes, delete',
                    }).then(r => { if (r.isConfirmed) form.submit(); });
                });
            });

        })();
    </script>
@endpush