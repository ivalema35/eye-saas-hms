{{-- Hospital History: total count + table + pagination only. Re-rendered on every AJAX
     filter/page request — the filter form stays in the parent view and is never replaced. --}}
<div class="mb-3">
    <span class="fw-semibold" style="color:#1B4F72; font-size:14px;">
        <i class="bi bi-hospitals me-1"></i>
        Total: <strong>{{ $hospitals->total() }}</strong> hospitals
    </span>
</div>

<div class="card custom-history-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle text-center mb-0" style="font-size:14px;">
            <thead>
                <tr>
                    <th style="width:55px;">#</th>
                    <th style="width:65px;">Logo</th>
                    <th class="text-start">Hospital Name</th>
                    <th>City</th>
                    <th>District</th>
                    <th>State</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($hospitals as $i => $hospital)
                    @php $reqInfo = $requestMap[$hospital->id] ?? null; @endphp
                    <tr>
                        <td>{{ $hospitals->firstItem() + $i }}</td>
                        <td>
                            @if($hospital->logo_path)
                                <img src="{{ asset('storage/' . $hospital->logo_path) }}" alt="{{ $hospital->name }}" class="hosp-logo">
                            @else
                                <span class="hosp-logo-placeholder">
                                    <i class="bi bi-hospital" style="color:#1B4F72; font-size:18px;"></i>
                                </span>
                            @endif
                        </td>
                        <td class="text-start fw-semibold" style="color:#1B4F72;">{{ $hospital->name }}</td>
                        <td class="location-tag"><i class="bi bi-geo-alt-fill me-1"></i>{{ $hospital->city ?? '—' }}</td>
                        <td class="location-tag"><i class="bi bi-map me-1"></i>{{ $hospital->district ?? '—' }}</td>
                        <td class="location-tag"><i class="bi bi-globe me-1"></i>{{ $hospital->state ?? '—' }}</td>
                        <td>
                            @php $planStatus = $hospital->displayStatus(); @endphp
                            <span class="hms-badge hms-badge-{{ $planStatus }}">
                                {{ ucfirst($planStatus) }}
                            </span>
                        </td>

                             <td>
                            <div class="d-flex justify-content-center align-items-center gap-1 flex-nowrap">
                            <button type="button" class="btn btn-sm btn-outline-primary view-hosp-btn"
                                style="border-radius:8px; color:#000; background:#f6c23e; border:none; font-weight:600;" data-id="{{ $hospital->id }}">
                                <i class="bi bi-eye-fill"></i> View
                            </button>

                            @if(!$reqInfo)
                                <form method="POST" action="{{ route('hospital.hospital.share.send', ['slug' => $slug, 'toTenantId' => $hospital->id]) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm fw-bold"
                                        style="background:#1B4F72;color:#fff;border-radius:8px;">
                                        <i class="bi bi-send me-1"></i>Request
                                    </button>
                                </form>
                            @elseif($reqInfo['status'] === 'accepted')
                                <form method="POST" action="{{ route('hospital.hospital.share.remove', ['slug' => $slug, 'requestId' => $reqInfo['id']]) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger fw-bold"
                                        style="border-radius:8px; color:white; background:#dc3545;"
                                        onclick="return confirm('Do you want to remove this connection?')">
                                       Cancel
                                    </button>
                                </form>
                            @elseif($reqInfo['direction'] === 'sent')
                                <form method="POST" action="{{ route('hospital.hospital.share.remove', ['slug' => $slug, 'requestId' => $reqInfo['id']]) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-secondary fw-bold"
                                        style="border-radius:8px; color:#fff; background:#dc3545;">
                                         Cancel
                                    </button>
                                </form>
                            @else
                                {{-- Incoming: Accept or Remove from hospital tab too --}}
                                <form method="POST" action="{{ route('hospital.hospital.share.accept', ['slug' => $slug, 'requestId' => $reqInfo['id']]) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm fw-bold"
                                        style="background:#16a34a;color:#fff;border-radius:8px;">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('hospital.hospital.share.remove', ['slug' => $slug, 'requestId' => $reqInfo['id']]) }}" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger fw-bold" style="border-radius:8px;">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                            @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-5 text-muted">
                            <i class="bi bi-building-slash me-2"></i>No trial or grace hospitals found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($hospitals->hasPages())
    <div class="d-flex justify-content-center p-3 border-top" style="background-color:#fcfcfc;">
    {{ $hospitals->appends(request()->only(['hosp_name', 'hosp_city', 'hosp_district', 'hosp_state', '_tab']))->appends(['_tab' => 'hospital'])->links() }}
    </div>
    @endif
</div>
