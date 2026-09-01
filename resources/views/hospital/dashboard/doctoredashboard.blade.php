@extends('hospital.layouts.app')
@section('title', 'Doctor Dashboard')

@push('styles')
    <style>
        .doctor-page-wrap {
            background: #ffffff;
            padding: 0 1.5rem 1.5rem 1.5rem;
            min-height: 100vh;
            font-family: system-ui, -apple-system, sans-serif;
        }

        /* Card visuals: soft shadow and rounded corners */
        .doctor-page-wrap .card {
            border: none !important;
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(11, 35, 50, 0.06);
        }

        /* Top headers inside cards (override inline black header) */
        .doctor-page-wrap .card>.px-3.py-2,
        .doctor-page-wrap .card>.px-3.py-2.text-white {
            background: var(--shell-secondary) !important;
            color: #ffffff !important;
            border-radius: 10px 10px 0 0;
            padding: -0.4rem 1rem !important;
            font-weight: 800;
        }

        /* Small stat tiles */
        .doctor-page-wrap .card .p-2.border.rounded {
            background: #ffffff;
            border: 1px solid rgba(27, 79, 114, 0.06) !important;
        }

        .doctor-page-wrap .card .p-2 .d-block {
            color: rgba(0, 0, 0, 0.6);
        }

        /* Doctors block: subtle primary tint */
        .doctor-page-wrap .card.bg-tinted {
            background: var(--shell-primary) !important;
        }

        /* Table header and controls */
        .doctor-page-wrap table thead {
            background: rgba(27, 79, 114, 0.04) !important;
        }

        .doctor-page-wrap .form-control,
        .doctor-page-wrap .form-select {
            border-radius: 6px;
        }

        /* Buttons */
        .doctor-page-wrap .btn-primary,
        .doctor-page-wrap .btn-examine {
            background: var(--shell-secondary) !important;
            border-color: var(--shell-secondary) !important;
        }

        /* ==================== DOCTOR PROFILE CARDS ==================== */
        .doctor-cards-container {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            gap: 15px;
            padding-bottom: 10px;
        }

        .doctor-cards-container::-webkit-scrollbar {
            height: 6px;
        }

        .doctor-cards-container::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .doc-profile-card {
            min-width: 280px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .doc-profile-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(27, 79, 114, 0.08);
            border-color: #cde5f5;
        }

        .doc-avatar {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background: #eef2f6;
            color: #1B4F72;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: 800;
            text-transform: uppercase;
            flex-shrink: 0;
        }

        .doc-info {
            flex-grow: 1;
        }

        .doc-name {
            font-size: 15px;
            font-weight: 800;
            color: #1B4F72;
            margin-bottom: 2px;
            text-transform: capitalize;
        }

        .doc-assigned {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 8px;
        }

        .doc-badges {
            display: flex;
            gap: 8px;
        }

        .doc-badge {
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #64748b;
            font-weight: 600;
        }

        .doc-badge.active {
            background: #1B4F72;
            color: #ffffff;
            border-color: #1B4F72;
        }

        /* ==================== TOP 4 DASHBOARD CARDS ==================== */

        .metric-grid {
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
        }

        .metric-card-item {
            flex: 1;
            min-width: 0;
            min-height: 120px;
            background: #fff;
            border: 1px solid #cde5f5;
            border-radius: 14px;
            padding: 14px 18px;
            position: relative;
            transition: all .3s ease;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .04);
        }

        .metric-card-item:hover {
            border-color: #7bb3d9;
            box-shadow: 0 10px 25px rgba(27, 79, 114, .12);
            transform: translateY(-2px);
        }

        .metric-title {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #64748b;
            margin-top: 8px;
        }

        .metric-number {
            font-size: 22px;
            font-weight: 800;
            color: #1B4F72;
            line-height: 1;
            margin-bottom: 0;
        }

        .metric-card-item .doc-badges {
            gap: 6px;
        }

        .metric-card-item .doc-badge {
            font-size: 10px;
            padding: 2px 8px;
        }

        .metric-icon {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .metric-link {
            font-size: 15px;
            font-weight: 700;
            color: #111827;
            text-decoration: none;
        }

        @media(max-width:768px) {
            .metric-grid {
                flex-wrap: wrap;
            }

            .metric-card-item {
                width: calc(50% - 8px);
                flex: none;
            }
        }

        @media(max-width:480px) {
            .metric-card-item {
                width: 100%;
            }
        }

        .dil-override-btn:hover {
            background-color: #fef3c7 !important;
            border-color: #fbbf24 !important;
        }

        .dil-override-btn {
            cursor: pointer !important;
        }

        /* Custom Tooltip Style */
        .tooltip-container {
            position: relative;
            display: inline-block;
        }

        .tooltip-container .tooltip-text {
            visibility: hidden;
            width: 220px;
            background-color: #1B4F72;
            color: #fff;
            text-align: center;
            border-radius: 8px;
            padding: 10px;
            position: absolute;
            z-index: 9999;
            bottom: 125%;
            /* બટનની ઉપર દેખાશે */
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 12px;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        /* Custom Tooltip Style */
        .tooltip-container {
            position: relative;
            display: inline-block;
        }

        .tooltip-container .tooltip-text {
            visibility: hidden;
            width: 250px;
            /* થોડી પહોળાઈ વધારી */
            background-color: #1B4F72;
            color: #fff;
            text-align: center;
            border-radius: 8px;
            padding: 10px 12px;
            position: absolute;
            z-index: 9999;

            /* પોઝિશનિંગ - બટનની ઉપર બરાબર વચ્ચે */
            bottom: 140%;
            left: 50%;
            transform: translateX(-50%);

            opacity: 0;
            transition: opacity 0.3s, visibility 0.3s;
            font-size: 13px;
            font-weight: 500;
            line-height: 1.4;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            pointer-events: none;
            /* જેથી માઉસ હોવર વખતે અડચણ ન આવે */
        }

        /* ટૂલટીપ માટે એરો */
        .tooltip-container .tooltip-text::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -6px;
            border-width: 6px;
            border-style: solid;
            border-color: #1B4F72 transparent transparent transparent;
        }

        .tooltip-container:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }

        /* ── Wait Status Pill ── */
        .wait-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            padding: 3px 10px 3px 3px;
            font-weight: 700;
            white-space: nowrap;
            transition: background .4s, box-shadow .4s;
            vertical-align: middle;
        }

        .wait-pill.wait-green {
            background: rgba(22, 163, 74, .10);
            box-shadow: 0 0 0 1px rgba(22, 163, 74, .25);
        }

        .wait-pill.wait-orange {
            background: rgba(234, 88, 12, .10);
            box-shadow: 0 0 0 1px rgba(234, 88, 12, .25);
        }

        .wait-pill.wait-red {
            background: rgba(220, 38, 38, .10);
            box-shadow: 0 0 0 1px rgba(220, 38, 38, .25);
        }

        /* ── Clickable doctor cards ── */
        a.doc-profile-card {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        a.doc-profile-card:hover {
            border-color: #1B4F72 !important;
            background: #e8f4fb !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(27, 79, 114, .15);
        }

        a.doc-profile-card.doc-selected {
            border: 2px solid #1B4F72 !important;
            background: #ddeef9 !important;
        }

        .wait-pill.wait-fire {
            background: rgba(220, 38, 38, .10);
            box-shadow: 0 0 0 1px rgba(220, 38, 38, .35);
            animation: fire-glow 1s ease-in-out infinite alternate;
        }

        @keyframes fire-glow {
            from {
                box-shadow: 0 0 0 1px rgba(220, 38, 38, .35), 0 0 6px rgba(234, 88, 12, .4);
            }

            to {
                box-shadow: 0 0 0 2px rgba(220, 38, 38, .55), 0 0 12px rgba(234, 88, 12, .6);
            }
        }

        @keyframes dilModalIn {
            from {
                opacity: 0;
                transform: translateY(12px) scale(.97);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .wp-r {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            font-size: .65rem;
            font-weight: 900;
            color: #fff;
            flex-shrink: 0;
        }

        .wait-green .wp-r {
            background: #16a34a;
        }

        .wait-orange .wp-r {
            background: #ea580c;
        }

        .wait-red .wp-r {
            background: #dc2626;
        }

        .wait-fire .wp-r {
            background: linear-gradient(135deg, #dc2626, #ea580c);
        }

        .wp-time {
            font-size: .72rem;
            font-weight: 700;
        }

        .wait-green .wp-time {
            color: #15803d;
        }

        .wait-orange .wp-time {
            color: #c2410c;
        }

        .wait-red .wp-time {
            color: #b91c1c;
        }

        .wait-fire .wp-time {
            color: #dc2626;
        }

        /* OT dashboard strip */
        .ot-dash-icon {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(123, 44, 191, 0.12);
            color: #7b2cbf;
            font-size: 15px;
        }

        .ot-summary-pill {
            display: inline-flex;
            align-items: center;
            background: #fff;
            border: 1px solid rgba(27, 79, 114, 0.14);
            color: #1B4F72;
            font-size: 12px;
            font-weight: 800;
            padding: 6px 12px;
            border-radius: 999px;
        }

        .ot-summary-pill:hover {
            background: #EBF5FB;
            color: #1B4F72;
        }

        .ot-dash-card .doc-profile-card:hover {
            border-color: #c4a3e0 !important;
            background: #f3e9fb !important;
        }

        /* ==================== DOCTOR OVERVIEW: featured card + all-doctor list ==================== */
        .doc-overview-panel {
            display: flex;
            align-items: stretch;
            gap: 14px;
            background: #f4f8fb;
            border: 1px solid rgba(15, 79, 134, 0.08);
            border-radius: 12px;
            padding: 10px 12px;
        }

        .doc-overview-featured {
            flex: 0 0 auto;
        }

        .doc-overview-divider {
            width: 1px;
            align-self: stretch;
            background: linear-gradient(180deg, transparent, rgba(15, 79, 134, .16), transparent);
        }

        .doc-overview-list {
            flex: 1 1 320px;
            min-width: 260px;
        }

        @media(max-width:768px) {
            .doc-overview-panel {
                flex-direction: column;
            }

            .doc-overview-divider {
                display: none;
            }

            .doc-main-card {
                max-width: 100%;
                width: 100%;
            }
        }

        .doc-main-card {
            display: flex;
            flex-direction: column;
            gap: 4px;
            background: #fff;
            border: 1px solid rgba(15, 79, 134, 0.22);
            border-radius: 8px;
            padding: 6px;
            width: 196px;
            max-width: 196px;
            overflow: visible;
            box-shadow: 0 1px 4px rgba(15, 79, 134, 0.06);
        }

        .doc-ref-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .02em;
            line-height: 1.15;
            border-radius: 5px;
            overflow: hidden;
        }

        .doc-ref-bar span {
            flex: 1;
            text-align: center;
            padding: 4px 3px;
        }

        .doc-ref-bar .doc-ref-div {
            width: 1px;
            align-self: stretch;
            background: rgba(255, 255, 255, .35);
            flex: 0 0 1px;
            padding: 0;
        }

        .doc-ref-bar--top {
            background: #0f4f86;
        }

        .doc-ref-bar--opd {
            background: #0a2f52;
        }

        .doc-ref-bar--ot {
            background: #0d9488;
        }

        .doc-ref-mid {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px 6px;
            background: #fff;
        }

        .doc-main-card .doc-avatar {
            width: 34px;
            height: 34px;
            font-size: 15px;
            background: #0d9488;
            color: #fff;
            border-radius: 50%;
        }

        .doc-main-card .doc-name {
            font-size: 13px;
            font-weight: 800;
            color: #0f4f86;
            margin-bottom: 0;
            line-height: 1.2;
        }

        .doc-you-pill {
            display: none;
        }

        .patient-queue-no {
            display: inline-block;
            font-size: 13px;
            font-weight: 800;
            color: #0f4f86;
            min-width: 28px;
        }

        .doctor-page-wrap .patient-col-no {
            width: 44px;
            min-width: 44px;
            max-width: 44px;
            padding-left: 6px !important;
            padding-right: 6px !important;
        }

        .doctor-page-wrap .patient-col-name {
            min-width: 220px;
            width: 32%;
            text-align: left !important;
        }

        .doctor-list-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(196px, 1fr));
            gap: 14px;
        }

        .doc-list-card {
            display: block;
            background: #fff;
            border: 1px solid rgba(15, 79, 134, 0.2);
            border-radius: 8px;
            padding: 8px 10px 7px;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 1px 3px rgba(15, 79, 134, 0.05);
            transition: transform .18s ease, border-color .18s ease;
        }

        .doc-list-card:hover {
            transform: translateY(-1px);
            border-color: #0f4f86;
        }

        .doc-list-card.doc-selected {
            outline-color: #0a2f52 !important;
            background: #f4f9fc;
        }

        .doc-list-name {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 800;
            color: #0f4f86;
        }

        .doc-list-name i {
            color: #0d9488;
            font-size: 15px;
        }

        .doc-ref-btns {
            display: flex;
            gap: 6px;
        }

        .doc-ref-btns+.doc-ref-btns {
            margin-top: 6px;
        }

        .doc-ref-btn {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            background: #0a2f52;
            color: #fff;
            font-size: 11px;
            font-weight: 800;
            padding: 5px 4px;
            border-radius: 5px;
        }

        .doc-ref-btn i {
            font-size: 11px;
            opacity: .9;
        }

        .doc-ref-btns--ot .doc-ref-btn {
            background: #0d9488;
        }

        .doctor-list-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 32px 16px;
            color: #94a3b8;
            text-align: center;
            width: 100%;
        }

        .doctor-list-empty i {
            font-size: 26px;
            color: #cde5f5;
        }

        @media(max-width:768px) {
            .doctor-list-grid {
                grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
            }
        }
    </style>
@endpush

@section('content')
    <div class="doctor-page-wrap">

        @php
            $waitStatusLabel = $waitStatusLabel ?? 'Waiting';
            $wGreen = (int) hospital_setting('wait_green_max', 30);
            $wOrange = (int) hospital_setting('wait_orange_max', 60);
            $wRed = (int) hospital_setting('wait_red_max', 120);
            $wDGreen = (int) hospital_setting('wait_d_green_max', 40);
            $wDOrange = (int) hospital_setting('wait_d_orange_max', 90);
            $wDRed = (int) hospital_setting('wait_d_red_max', 120);
            $wNdGreen = (int) hospital_setting('wait_nd_green_max', 20);
            $wNdOrange = (int) hospital_setting('wait_nd_orange_max', 60);
            $wNdRed = (int) hospital_setting('wait_nd_red_max', 120);
            $formatQueueNo = function ($patient): string {
                if (empty($patient->doctor_patient_no)) {
                    return '—';
                }

                return '#' . (int) $patient->doctor_patient_no;
            };
        @endphp

        {{-- Subscription Alert --}}
        @if($subscriptionDaysLeft !== null && $subscriptionDaysLeft <= 14)
            <div
                class="alert {{ $subscriptionDaysLeft <= 3 ? 'alert-danger' : 'alert-warning' }} d-flex align-items-center gap-2 rounded-3 mb-4 shadow-sm">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>
                    Subscription expires in <strong>{{ $subscriptionDaysLeft }} days</strong>. Please renew soon.
                </span>
            </div>
        @endif

        {{-- ==================== DOCTOR OVERVIEW: featured card + all-doctor list (OPD + OT combined) ====================
        --}}
        @php
            $doctorCards = $doctorCards ?? collect();
            $otDoctorCards = $otDoctorCards ?? collect();
            $loggedInId = auth('hospital_user')->id();
            $activeDoctorId = $viewingDoctor->id ?? $loggedInId;
            $otStatsById = $otDoctorCards->keyBy('id');
            $activeOtStats = $otStatsById->get($activeDoctorId);
            $otherDoctorCards = $doctorCards->reject(
                fn($d) => $d->role?->slug === 'ot_assistant' || $d->id === $activeDoctorId
            )->values();
        @endphp

        <div class="doc-overview-panel mb-4">
            {{-- Featured card: whichever doctor's dashboard is currently open --}}
            <div class="doc-overview-featured">
                <div class="doc-main-card">
                    <div class="doc-ref-bar doc-ref-bar--top" title="Hospital today">
                        <span>C: {{ $todayPatients ?? 0 }}</span>
                        <span class="doc-ref-div"></span>
                        <span>PC: {{ $todayPrimary ?? 0 }}</span>
                        <span class="doc-ref-div"></span>
                        <span>SC: {{ $todaySecondary ?? 0 }}</span>
                    </div>
                    <div class="doc-ref-mid">
                        <div class="doc-avatar">
                            <i class="bi bi-person-badge"></i>
                        </div>
                        <div>
                            <div class="doc-name">{{ $doctorName ?? 'Doctor' }}</div>
                            @if($viewingDoctor)
                                <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}" class="text-decoration-none"
                                    style="font-size:10px;font-weight:700;color:#0f4f86;">
                                    &larr; Back
                                </a>
                            @endif
                        </div>
                    </div>
                    <div class="doc-ref-bar doc-ref-bar--opd" title="My OPD">
                        <span>Patient: {{ $doctorAssignedPatients ?? 0 }}</span>
                        <span class="doc-ref-div"></span>
                        <span>P: {{ $doctorPrimaryDone ?? 0 }}</span>
                        <span class="doc-ref-div"></span>
                        <span>S: {{ $doctorSecondaryDone ?? 0 }}</span>
                    </div>
                    <div class="doc-ref-bar doc-ref-bar--ot" title="OT">
                        <span>OT: {{ $activeOtStats->ot_total ?? 0 }}</span>
                        <span class="doc-ref-div"></span>
                        <span>OP: {{ $activeOtStats->ot_pending ?? 0 }}</span>
                        <span class="doc-ref-div"></span>
                        <span>OC: {{ $activeOtStats->ot_complete ?? 0 }}</span>
                    </div>
                </div>
            </div>

            <div class="doc-overview-divider"></div>

            {{-- All Doctor List: each card = OPD + OT combined --}}
            <div class="doc-overview-list">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="ot-dash-icon" style="width:26px;height:26px;font-size:12px;"><i
                            class="bi bi-people-fill"></i></span>
                    <h6 class="fw-bold mb-0" style="color: #1B4F72;">All Doctor List</h6>
                </div>

                <div class="doctor-list-grid">
                    @forelse($otherDoctorCards as $doc)
                        @php
                            $isSelf = $doc->id === $loggedInId;
                            $cardUrl = $isSelf
                                ? route('hospital.dashboard', ['slug' => $slug])
                                : route('hospital.dashboard', ['slug' => $slug]) . '?view_doctor=' . $doc->id;
                            $docOt = $otStatsById->get($doc->id);
                        @endphp
                        <a href="{{ $cardUrl }}" class="doc-list-card">
                            <div class="doc-list-name">
                                <i class="bi bi-person-badge"></i>
                                {{ $doc->name }}
                            </div>
                            <div class="doc-ref-btns">
                                <span class="doc-ref-btn" title="Patients"><i
                                        class="bi bi-people-fill"></i>{{ $doc->assigned_today ?? 0 }}</span>
                                <span class="doc-ref-btn" title="Primary"><i
                                        class="bi bi-clipboard2-pulse"></i>{{ $doc->primary_count ?? 0 }}</span>
                                <span class="doc-ref-btn" title="Secondary"><i
                                        class="bi bi-display"></i>{{ $doc->secondary_count ?? 0 }}</span>
                            </div>
                            <div class="doc-ref-btns doc-ref-btns--ot">
                                <span class="doc-ref-btn" title="OT"><i
                                        class="bi bi-scissors"></i>{{ $docOt->ot_total ?? 0 }}</span>
                                <span class="doc-ref-btn" title="Pending"><i
                                        class="bi bi-hourglass-split"></i>{{ $docOt->ot_pending ?? 0 }}</span>
                                <span class="doc-ref-btn" title="Complete"><i
                                        class="bi bi-check2-circle"></i>{{ $docOt->ot_complete ?? 0 }}</span>
                            </div>
                        </a>
                    @empty
                        <div class="doctor-list-empty">
                            <i class="bi bi-people"></i>
                            <span>No other doctors on duty right now.</span>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Viewing another doctor banner --}}
        @if($viewingDoctor)
            <div class="d-flex align-items-center gap-3 mb-3 px-3 py-2 rounded-3"
                style="background:#fff8e1; border:1px solid #ffc107;">
                <i class="bi bi-eye-fill" style="color:#f59e0b; font-size:16px;"></i>
                <span style="font-size:14px; font-weight:600; color:#7c5c00;">
                    Viewing <strong>Dr. {{ $viewingDoctor->name }}</strong>'s patients
                </span>
                <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}" class="btn btn-sm ms-auto"
                    style="background:#1B4F72; color:#fff; border-radius:6px; font-size:12px; padding:4px 12px;">
                    Back to My Patients
                </a>
            </div>
        @endif

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 10px;">
                    <div class="px-3 py-2 text-white fw-bold fs-5" style="background-color: #000000; font-size: 16px;">
                        Primary Patient
                    </div>
                    <div class="p-3 bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex align-items-center gap-1 text-secondary small">
                                Show entries
                                <select class="form-select form-select-sm w-auto">
                                    <option>10</option>
                                    <option>20</option>
                                    <option>30</option>
                                </select>
                            </div>
                            <div class="d-flex align-items-center gap-1 text-secondary small">
                                search <input type="search" class="form-control form-control-sm w-auto"
                                    style="border: 1px solid #cbd5e1;">
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle text-center mb-0"
                                style="font-size: 13.5px; border-color: #e2e8f0;">
                                <thead class="table-light text-secondary">
                                    <tr>
                                        <th class="patient-col-no">#</th>
                                        <th class="patient-col-name">Patient Name</th>
                                        <th>Gender</th>
                                        <th>Age</th>
                                        <th>City</th>
                                        <th>{{ $waitStatusLabel }}</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($primaryQueue ?? [] as $i => $patient)
                                        <tr>
                                            <td class="patient-col-no"><span
                                                    class="patient-queue-no">{{ $formatQueueNo($patient) }}</span></td>
                                            <td class="patient-col-name fw-semibold" style="color: #1B4F72;">
                                                {{ $patient->first_name }} {{ $patient->last_name }}
                                            </td>
                                            <td>{{ $patient->gender ? ucfirst($patient->gender) : '—' }}</td>
                                            <td>{{ $patient->age }}</td>
                                            <td>{{ $patient->cityName ?: '-' }}</td>
                                            <td>
                                                @php
                                                    $wMins = (int) $patient->created_at->diffInMinutes(now());
                                                    $wCls = $wMins < $wGreen ? 'wait-green' : ($wMins < $wOrange ? 'wait-orange' : ($wMins < $wRed ? 'wait-red' : 'wait-fire'));
                                                    $wFmt = $wMins < 60 ? $wMins . 'm' : floor($wMins / 60) . 'h' . ($wMins % 60 > 0 ? ' ' . ($wMins % 60) . 'm' : '');
                                                @endphp
                                                <span class="wait-pill {{ $wCls }}"
                                                    data-wait-from="{{ $patient->created_at->toIso8601String() }}">
                                                    <span class="wp-r">R</span>
                                                    <span class="wp-time">{{ $wFmt }}</span>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1 justify-content-center flex-wrap">
                                                    <a href="{{ route('hospital.exam.primary.show', ['slug' => $slug, 'id' => $patient->id]) }}"
                                                        class="btn btn-sm text-white px-3 fw-semibold"
                                                        style="background-color: #1B4F72; border-radius: 4px;">
                                                        Examine
                                                    </a>
                                                    @if($patient->has_history ?? false)
                                                        <a href="{{ route('hospital.patients.history', ['slug' => $slug]) }}?patient_ids={{ $patient->all_patient_ids ?? $patient->id }}"
                                                            class="btn btn-sm px-3 fw-semibold"
                                                            style="background-color: #0d9488; color:#fff; border-radius: 4px;"
                                                            target="_blank" title="View Patient History">
                                                            <i class="bi bi-clock-history"></i> View
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="py-4 text-muted bg-light">No Data Found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 small text-muted">
                            <div>Showing 1 to entries of 0 entries</div>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-secondary px-3" disabled>Previous</button>
                                <button class="btn btn-sm btn-outline-secondary px-3" disabled>Next</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Table Panel: Secondary Patient --}}
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 10px;">
                    <div class="px-3 py-2 text-white fw-bold fs-5" style="background-color: #000000; font-size: 16px;">
                        Secondary Patient
                    </div>
                    <div class="p-3 bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex align-items-center gap-1 text-secondary small">
                                Show entries
                                <select class="form-select form-select-sm w-auto">
                                    <option>10</option>
                                    <option>20</option>
                                    <option>30</option>
                                </select>
                            </div>
                            <div class="d-flex align-items-center gap-1 text-secondary small">
                                search <input type="search" class="form-control form-control-sm w-auto"
                                    style="border: 1px solid #cbd5e1;">
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle text-center mb-0"
                                style="font-size: 13.5px; border-color: #e2e8f0;">
                                <thead class="table-light text-secondary">
                                    <tr>
                                        <th class="patient-col-no">#</th>
                                        <th class="patient-col-name">Patient Name</th>
                                        <th>Gender</th>
                                        <th>Age</th>
                                        <th>City</th>
                                        <th>{{ $waitStatusLabel }}</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($secondaryQueue ?? [] as $i => $patient)
                                        @php
                                            $sRMins = (int) ($patient->checked_in_at ?? $patient->created_at)->diffInMinutes(now());
                                            $sRCls = $sRMins < $wGreen ? 'wait-green' : ($sRMins < $wOrange ? 'wait-orange' : ($sRMins < $wRed ? 'wait-red' : 'wait-fire'));
                                            $sRFmt = $sRMins < 60 ? $sRMins . 'm' : floor($sRMins / 60) . 'h' . ($sRMins % 60 > 0 ? ' ' . ($sRMins % 60) . 'm' : '');
                                            $sPExam = $patient->primaryExamination;
                                            $sIsDil = $sPExam && ($sPExam->exam_data['dilate'] ?? 'No') === 'Yes';
                                            $sPrimeMins = $sPExam ? (int) \Carbon\Carbon::parse($sPExam->examined_at ?? $patient->primary_done_at)->diffInMinutes(now()) : 0;
                                            $sPrimeFmt = $sPrimeMins < 60 ? $sPrimeMins . 'm' : floor($sPrimeMins / 60) . 'h' . ($sPrimeMins % 60 > 0 ? ' ' . ($sPrimeMins % 60) . 'm' : '');
                                            $sDClass = $sPrimeMins < $wDGreen ? 'wait-green' : ($sPrimeMins < $wDOrange ? 'wait-orange' : ($sPrimeMins < $wDRed ? 'wait-red' : 'wait-fire'));
                                            $sNdClass = $sPrimeMins < $wNdGreen ? 'wait-green' : ($sPrimeMins < $wNdOrange ? 'wait-orange' : ($sPrimeMins < $wNdRed ? 'wait-red' : 'wait-fire'));
                                            // Dilation lock
                                            $isDilLocked = false;
                                            $dilUnlockMs = 0;
                                            $dilCountdown = '';
                                            if ($sIsDil && $sPExam && $sPExam->dilation_time) {
                                                $unlockTime = $sPExam->updated_at->copy()->addMinutes($sPExam->dilation_time);
                                                $isDilLocked = now()->lessThan($unlockTime);
                                                $dilUnlockMs = $unlockTime->timestamp * 1000;
                                                if ($isDilLocked) {
                                                    $diff = $unlockTime->diffInSeconds(now());
                                                    $dilCountdown = sprintf('%02d:%02d', intdiv($diff, 60), $diff % 60);
                                                }
                                            }
                                        @endphp
                                        <tr>
                                            <td class="patient-col-no"><span
                                                    class="patient-queue-no">{{ $formatQueueNo($patient) }}</span></td>
                                            <td class="patient-col-name fw-semibold" style="color: #1B4F72;">
                                                {{ $patient->first_name }} {{ $patient->last_name }}
                                            </td>
                                            <td>{{ $patient->gender ? ucfirst($patient->gender) : '—' }}</td>
                                            <td>{{ $patient->age }}</td>
                                            <td>{{ $patient->cityName ?: '-' }}</td>
                                            <td>
                                                <div class="d-flex flex-column align-items-center gap-1">
                                                    <span class="wait-pill {{ $sRCls }}"
                                                        data-wait-from="{{ ($patient->checked_in_at ?? $patient->created_at)->toIso8601String() }}">
                                                        <span class="wp-r">R</span>
                                                        <span class="wp-time">{{ $sRFmt }}</span>
                                                    </span>
                                                    @if($sIsDil && $sPExam)
                                                        <span class="wait-pill {{ $sDClass }}"
                                                            data-wait-from="{{ \Carbon\Carbon::parse($sPExam->examined_at ?? $patient->primary_done_at)->toIso8601String() }}"
                                                            data-thresholds="{{ $wDGreen }},{{ $wDOrange }},{{ $wDRed }}">
                                                            <span class="wp-r">D</span>
                                                            <span class="wp-time">{{ $sPrimeFmt }}</span>
                                                        </span>
                                                    @elseif($sPExam)
                                                        <span class="wait-pill {{ $sNdClass }}"
                                                            data-wait-from="{{ \Carbon\Carbon::parse($sPExam->examined_at ?? $patient->primary_done_at)->toIso8601String() }}"
                                                            data-thresholds="{{ $wNdGreen }},{{ $wNdOrange }},{{ $wNdRed }}">
                                                            <span class="wp-r" style="font-size:.58rem;">ND</span>
                                                            <span class="wp-time">{{ $sPrimeFmt }}</span>
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @if($isDilLocked)
                                                    <div class="tooltip-container">
                                                        <button class="btn btn-sm fw-semibold px-3 dil-override-btn"
                                                            style="background:#fff8ed; color:#92400e; border-radius:4px; cursor:pointer; border:1px solid #fcd34d;"
                                                            data-force-url="{{ route('hospital.exam.secondary.show', ['slug' => $slug, 'id' => $patient->id]) }}?force=1"
                                                            data-patient-name="{{ $patient->first_name }} {{ $patient->last_name }}">
                                                            <i class="bi bi-hourglass-split me-1" style="color:#f59e0b;"></i>
                                                            <span class="dilation-countdown"
                                                                data-unlock-ms="{{ $dilUnlockMs }}">{{ $dilCountdown }}</span>
                                                        </button>

                                                        <span class="tooltip-text">
                                                            ⚠️ Dilation in progress.<br>Double click to override and proceed.
                                                        </span>
                                                    </div>
                                                @else
                                                    <a href="{{ route('hospital.exam.secondary.show', ['slug' => $slug, 'id' => $patient->id]) }}"
                                                        class="btn btn-sm text-white px-3 fw-semibold"
                                                        style="background-color: #1B4F72; border-radius: 4px;">
                                                        Examine
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="py-4 text-muted bg-light">No Data Found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 small text-muted">
                            <div>Showing 1 to entries of 0 entries</div>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-secondary px-3" disabled>Previous</button>
                                <button class="btn btn-sm btn-outline-secondary px-3" disabled>Next</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- Dilation Override Modal --}}
    <div id="dilOverrideModal"
        style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(15,23,42,.5); backdrop-filter:blur(3px); align-items:center; justify-content:center;">
        <div id="dilOverrideCard"
            style="background:#fff; border-radius:16px; width:100%; max-width:380px; margin:0 16px; box-shadow:0 24px 64px rgba(0,0,0,.18); animation:dilModalIn .18s ease;">
            {{-- Icon area --}}
            <div style="padding:28px 24px 0; text-align:center;">
                <div
                    style="display:inline-flex; align-items:center; justify-content:center; width:56px; height:56px; border-radius:50%; background:#fff8ed; border:2px solid #fcd34d; margin-bottom:14px;">
                    <i class="bi bi-hourglass-split" style="font-size:22px; color:#f59e0b;"></i>
                </div>
                <h6 style="margin:0 0 6px; font-size:15px; font-weight:700; color:#1e293b; letter-spacing:.01em;">Dilation
                    In Progress</h6>
                <p style="margin:0; font-size:13px; color:#64748b; line-height:1.55;">
                    <strong id="dilModalPatientName" style="color:#1B4F72;"></strong> is currently dilating.<br>
                    Override the lock and proceed to secondary exam?
                </p>
            </div>
            {{-- Divider --}}
            <div style="margin:20px 24px 0; border-top:1px solid #f1f5f9;"></div>
            {{-- Actions --}}
            <div style="padding:16px 24px 22px; display:flex; gap:10px;">
                <button id="dilModalCancel"
                    style="flex:1; background:#f8fafc; color:#64748b; border:1px solid #e2e8f0; border-radius:8px; padding:9px 0; font-size:13px; font-weight:600; cursor:pointer; transition:background .15s;">
                    Cancel
                </button>
                <a id="dilModalConfirm" href="#"
                    style="flex:1; background:#1B4F72; color:#fff; border-radius:8px; padding:9px 0; font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; display:flex; align-items:center; justify-content:center; gap:6px; transition:background .15s;">
                    <i class="bi bi-arrow-right-circle-fill" style="font-size:14px;"></i> Proceed
                </a>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        (function () {
            const W = { green: {{ $wGreen }}, orange: {{ $wOrange }}, red: {{ $wRed }} };
            function getWC(m, g, o, r) { return m < g ? 'wait-green' : m < o ? 'wait-orange' : m < r ? 'wait-red' : 'wait-fire'; }
            function getWaitClass(m) { return getWC(m, W.green, W.orange, W.red); }
            function fmtTime(m) { return m < 60 ? m + 'm' : Math.floor(m / 60) + 'h' + (m % 60 > 0 ? ' ' + (m % 60) + 'm' : ''); }

            function updateWaitPills() {
                const now = Date.now();
                document.querySelectorAll('.wait-pill[data-wait-from]').forEach(function (pill) {
                    const mins = Math.floor((now - new Date(pill.dataset.waitFrom).getTime()) / 60000);
                    const thr = pill.dataset.thresholds ? pill.dataset.thresholds.split(',').map(Number) : null;
                    pill.className = 'wait-pill ' + (thr ? getWC(mins, thr[0], thr[1], thr[2]) : getWaitClass(mins));
                    const t = pill.querySelector('.wp-time');
                    if (t) t.textContent = fmtTime(mins);
                });
            }

            function updateDilationCountdowns() {
                document.querySelectorAll('.dilation-countdown').forEach(function (el) {
                    const unlockMs = parseInt(el.getAttribute('data-unlock-ms'));
                    const diff = unlockMs - Date.now();
                    if (diff <= 0) {
                        el.textContent = '00:00';
                        el.closest('button')?.setAttribute('disabled', false);
                    } else {
                        const m = Math.floor(diff / 60000);
                        const s = Math.floor((diff % 60000) / 1000);
                        el.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                    }
                });
            }

            updateWaitPills();
            updateDilationCountdowns();
            setInterval(updateWaitPills, 30000);
            setInterval(updateDilationCountdowns, 1000);

            // Dilation override modal
            const dilModal = document.getElementById('dilOverrideModal');
            const dilConfirm = document.getElementById('dilModalConfirm');
            const dilCancel = document.getElementById('dilModalCancel');
            const dilName = document.getElementById('dilModalPatientName');

            function openDilModal(url, patientName) {
                dilName.textContent = patientName;
                dilConfirm.href = url;
                dilModal.style.display = 'flex';
            }
            function closeDilModal() {
                dilModal.style.display = 'none';
                dilConfirm.href = '#';
            }

            document.querySelectorAll('.dil-override-btn').forEach(function (btn) {
                btn.addEventListener('dblclick', function (e) {
                    e.stopPropagation();
                    openDilModal(btn.dataset.forceUrl, btn.dataset.patientName);
                });
            });
            dilCancel.addEventListener('click', closeDilModal);
            document.getElementById('dilOverrideCard').addEventListener('click', function (e) { e.stopPropagation(); });
            dilModal.addEventListener('click', closeDilModal);
        })();
    </script>
@endpush