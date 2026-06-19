@extends('hospital.layouts.app')
@section('title', 'My Profile')
@section('page-header', 'My Profile')

@section('content')
@php $isDoctor = $user->role?->slug === 'doctor'; @endphp
<div class="hospital-settings-page">
<style>
    .hospital-settings-page {
        --sp: #1B4F72;
        --ss: #2980B9;
        --sg: #27AE60;
        --sf: #ebf5fbeb;
        --sb: rgba(27,79,114,.12);
        --sm: rgba(27,79,114,.62);
        color: var(--sp);
        animation: sp-in 420ms ease both;
    }
    @keyframes sp-in { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }

    .sp-hero {
        display:flex; align-items:center; gap:.9rem; padding:1.2rem 1.25rem;
        margin-bottom:1rem; border:1px solid var(--sb); border-radius:22px;
        background:linear-gradient(135deg,rgba(235,245,251,.96),rgba(255,255,255,.94));
        box-shadow:0 18px 48px rgba(27,79,114,.10); position:relative; overflow:hidden;
    }
    .sp-hero::before { content:''; position:absolute; inset:0 auto 0 0; width:5px; background:var(--sg); }
    .sp-hero-icon {
        width:52px; height:52px; border-radius:17px; display:inline-flex;
        align-items:center; justify-content:center; flex-shrink:0;
        background:var(--sp); color:#fff; box-shadow:0 14px 30px rgba(27,79,114,.22);
    }
    .sp-hero h4 { color:var(--sp) !important; font-weight:900; }
    .sp-hero p  { margin:.15rem 0 0; color:var(--sm); font-size:.9rem; }

    .sp-card {
        border:1px solid var(--sb) !important; border-radius:22px;
        background:rgba(255,255,255,.88);
        box-shadow:0 18px 48px rgba(27,79,114,.10) !important; overflow:hidden;
    }
    .sp-card-header {
        background:linear-gradient(135deg,rgba(235,245,251,.92),rgba(255,255,255,.96)) !important;
        border-bottom:1px solid var(--sb) !important;
    }
    .sp-body { background:var(--sf) !important; padding:1.5rem !important; }
    .sp-section {
        border:1px solid var(--sb); border-radius:18px;
        background:rgba(255,255,255,.94); padding:1.5rem; margin-bottom:1rem;
    }
    .sp-section-title {
        font-size:.75rem; font-weight:800; text-transform:uppercase; letter-spacing:.07em;
        color:var(--sm); margin-bottom:1rem; display:flex; align-items:center; gap:.4rem;
    }

    .sp-label { color:var(--sp); font-weight:700; font-size:.875rem; margin-bottom:.35rem; }
    .sp-input {
        border:1.5px solid rgba(27,79,114,.16) !important; border-radius:12px;
        background:#fbfdff; color:var(--sp); font-weight:600; padding:.62rem .85rem;
        transition:border-color 170ms,box-shadow 170ms,background 170ms;
    }
    .sp-input:focus { background:#fff; border-color:var(--ss) !important; box-shadow:0 0 0 4px rgba(41,128,185,.12); outline:none; }
    .sp-input.is-invalid { border-color:#C0392B !important; }

    .sp-pw-wrap { position:relative; }
    .sp-pw-input { padding-right:3rem !important; }
    .sp-pw-toggle {
        position:absolute; right:.7rem; top:50%; transform:translateY(-50%);
        border:1px solid rgba(27,79,114,.14); background:rgba(255,255,255,.98);
        padding:0; color:var(--sp); width:2rem; height:2rem; border-radius:999px;
        display:inline-flex; align-items:center; justify-content:center; cursor:pointer;
        z-index:3; box-shadow:0 1px 2px rgba(27,79,114,.06);
        transition:color .2s,border-color .2s,box-shadow .2s,transform .15s;
    }
    .sp-pw-toggle:hover { border-color:rgba(27,79,114,.22); box-shadow:0 4px 10px rgba(27,79,114,.10); transform:translateY(-50%) scale(1.02); }
    .sp-pw-toggle svg { width:1rem; height:1rem; display:block; stroke:currentColor; fill:none; pointer-events:none; }

    .sp-file-hint { font-size:.72rem; color:var(--sm); margin-top:.3rem; }
    .sp-img-preview { max-height:60px; border:1px solid var(--sb); border-radius:8px; padding:3px; margin-top:.4rem; }

    .sp-divider { display:flex; align-items:center; gap:.75rem; margin:1.5rem 0 1rem; }
    .sp-divider::before,.sp-divider::after { content:''; flex:1; border-top:1px solid var(--sb); }
    .sp-divider-label { display:flex; align-items:center; gap:.4rem; font-size:.75rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:var(--sm); white-space:nowrap; }

    .sp-save-wrap {
        margin-top:1.25rem; padding:1rem 1.25rem; border:1px solid var(--sb); border-radius:16px;
        background:linear-gradient(135deg,rgba(235,245,251,.72),rgba(255,255,255,.96));
        display:flex; align-items:center; justify-content:space-between; gap:1rem;
    }
    .sp-save-hint { font-size:.78rem; color:var(--sm); }
    .sp-save-btn {
        border-radius:13px; font-weight:900; padding:.68rem 1.35rem !important;
        background:var(--sp) !important; border-color:var(--sp) !important; color:#fff !important;
        box-shadow:0 12px 26px rgba(27,79,114,.18); white-space:nowrap;
        transition:transform 170ms,box-shadow 170ms;
    }
    .sp-save-btn:hover { transform:translateY(-1px); box-shadow:0 16px 34px rgba(27,79,114,.24); color:#fff !important; }

    .sp-quick-link {
        display:flex; align-items:center; gap:.75rem; padding:.85rem 1rem;
        border:1px solid var(--sb); border-radius:14px; background:#fff;
        text-decoration:none; color:var(--sp); font-weight:700; font-size:.875rem;
        transition:background .18s,box-shadow .18s,transform .18s;
    }
    .sp-quick-link:hover { background:var(--sf); box-shadow:0 8px 20px rgba(27,79,114,.10); transform:translateY(-1px); color:var(--sp); }
    .sp-quick-link-icon { width:36px; height:36px; border-radius:10px; background:var(--sp); color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:1rem; }
</style>

{{-- Hero --}}
<div class="sp-hero">
    <span class="sp-hero-icon"><i class="bi bi-person-circle fs-4"></i></span>
    <div>
        <span style="display:inline-flex;align-items:center;color:var(--ss);font-size:.72rem;font-weight:900;letter-spacing:.08em;text-transform:uppercase;margin-bottom:.15rem;">
            <i class="bi bi-person me-1"></i> Account
        </span>
        <h4 class="fw-bold mb-0">My Profile</h4>
        <p>Update your personal information and credentials.</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3" style="border-radius:14px">
        <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger d-flex gap-2 mb-3" style="border-radius:14px">
        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
        <ul class="mb-0 ps-2">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<div class="card border-0 sp-card">
    <div class="card-header sp-card-header p-0 border-0">
        <div class="px-4 py-3 d-flex align-items-center gap-3">
            <span class="sp-hero-icon" style="width:44px;height:44px;border-radius:14px;">
                @if($isDoctor)
                    <i class="bi bi-person-badge-fill"></i>
                @else
                    <i class="bi bi-person-fill"></i>
                @endif
            </span>
            <div>
                <div class="fw-900" style="color:var(--sp);font-size:1rem;">{{ $user->name }}</div>
                <div style="font-size:.78rem;color:var(--sm);">{{ $user->role?->name ?? 'Staff' }}</div>
            </div>
        </div>
    </div>

    <div class="card-body sp-body">
        <form action="{{ route('hospital.profile.update', ['slug' => $slug]) }}"
              method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- ── Basic Info ── --}}
            <div class="sp-section">
                <div class="sp-section-title"><i class="bi bi-info-circle"></i> Basic Information</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="sp-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name"
                               class="form-control sp-input @error('name') is-invalid @enderror"
                               value="{{ old('name', $user->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="sp-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email"
                               class="form-control sp-input @error('email') is-invalid @enderror"
                               value="{{ old('email', $user->email) }}" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    @if($isDoctor)
                    <div class="col-md-6">
                        <label class="sp-label">Registration No.</label>
                        <input type="text" name="registration_no"
                               class="form-control sp-input @error('registration_no') is-invalid @enderror"
                               value="{{ old('registration_no', $user->registration_no) }}"
                               placeholder="e.g. MCI-12345">
                        @error('registration_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="sp-label">Experience <span style="font-size:.75rem;font-weight:400;color:var(--sm)">(years)</span></label>
                        <input type="number" name="experience_years" min="0" max="60"
                               class="form-control sp-input @error('experience_years') is-invalid @enderror"
                               value="{{ old('experience_years', $user->experience_years) }}"
                               placeholder="e.g. 5">
                        @error('experience_years')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @endif
                </div>
            </div>

            {{-- ── Doctor Files (Signature + Photo) ── --}}
            @if($isDoctor)
            <div class="sp-section">
                <div class="sp-section-title"><i class="bi bi-image"></i> Signature & Photo</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="sp-label">Signature <span style="font-size:.75rem;font-weight:400;color:var(--sm)">(JPG/PNG · max 20KB)</span></label>
                        @if($user->signature_path)
                            <div class="mb-2">
                                <span class="sp-file-hint">Current:</span><br>
                                <img src="{{ asset('storage/'.$user->signature_path) }}" alt="Signature" class="sp-img-preview">
                            </div>
                        @endif
                        <input type="file" name="signature" accept="image/jpg,image/jpeg,image/png"
                               class="form-control sp-input @error('signature') is-invalid @enderror"
                               onchange="spPreview(this,'sp-sig-preview')">
                        <div id="sp-sig-preview" style="display:none;"><img src="" class="sp-img-preview"></div>
                        <div class="sp-file-hint">{{ $user->signature_path ? 'Upload to replace current signature.' : 'Upload doctor signature image.' }}</div>
                        @error('signature')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="sp-label">Profile Photo <span style="font-size:.75rem;font-weight:400;color:var(--sm)">(JPG/PNG · max 20KB)</span></label>
                        @if($user->profile_photo_path)
                            <div class="mb-2">
                                <span class="sp-file-hint">Current:</span><br>
                                <img src="{{ asset('storage/'.$user->profile_photo_path) }}" alt="Photo" class="sp-img-preview" style="max-height:70px;">
                            </div>
                        @endif
                        <input type="file" name="profile_photo" accept="image/jpg,image/jpeg,image/png"
                               class="form-control sp-input @error('profile_photo') is-invalid @enderror"
                               onchange="spPreview(this,'sp-photo-preview')">
                        <div id="sp-photo-preview" style="display:none;"><img src="" class="sp-img-preview" style="max-height:70px;"></div>
                        <div class="sp-file-hint">{{ $user->profile_photo_path ? 'Upload to replace current photo.' : 'Upload profile photo.' }}</div>
                        @error('profile_photo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
            @endif

            {{-- ── Change Password ── --}}
            <div class="sp-section">
                <div class="sp-section-title"><i class="bi bi-lock-fill"></i> Change Password</div>
                <p style="font-size:.78rem;color:var(--sm);margin-top:-.5rem;margin-bottom:1rem;">
                    Leave all fields blank to keep current password unchanged.
                </p>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="sp-label">Current Password</label>
                        <div class="sp-pw-wrap">
                            <input type="password" name="current_password" id="spCurrPw"
                                   class="form-control sp-input sp-pw-input @error('current_password') is-invalid @enderror"
                                   autocomplete="current-password" placeholder="Enter current password">
                            <button type="button" class="sp-pw-toggle" onclick="spTogglePw('spCurrPw',this)">
                                <svg viewBox="0 0 24 24"><path d="M1.5 12s3.5-7 10.5-7 10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3.2" stroke-width="1.8"/></svg>
                            </button>
                        </div>
                        @error('current_password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="sp-label">New Password</label>
                        <div class="sp-pw-wrap">
                            <input type="password" name="new_password" id="spNewPw"
                                   class="form-control sp-input sp-pw-input @error('new_password') is-invalid @enderror"
                                   autocomplete="new-password" placeholder="Min 8 characters">
                            <button type="button" class="sp-pw-toggle" onclick="spTogglePw('spNewPw',this)">
                                <svg viewBox="0 0 24 24"><path d="M1.5 12s3.5-7 10.5-7 10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3.2" stroke-width="1.8"/></svg>
                            </button>
                        </div>
                        @error('new_password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="sp-label">Confirm New Password</label>
                        <div class="sp-pw-wrap">
                            <input type="password" name="new_password_confirmation" id="spConfPw"
                                   class="form-control sp-input sp-pw-input"
                                   autocomplete="new-password" placeholder="Repeat new password">
                            <button type="button" class="sp-pw-toggle" onclick="spTogglePw('spConfPw',this)">
                                <svg viewBox="0 0 24 24"><path d="M1.5 12s3.5-7 10.5-7 10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3.2" stroke-width="1.8"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Doctor Quick Links ── --}}
            @if($isDoctor)
            <div class="sp-section">
                <div class="sp-section-title"><i class="bi bi-grid"></i> Quick Access</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <a href="{{ route('hospital.masters.detail.index', ['slug' => $slug, 'type' => 'diagnosis']) }}"
                           class="sp-quick-link">
                            <span class="sp-quick-link-icon"><i class="bi bi-clipboard2-pulse-fill"></i></span>
                            <div>
                                <div>Diagnosis Master</div>
                                <div style="font-size:.72rem;font-weight:400;color:var(--sm)">Manage diagnosis options</div>
                            </div>
                            <i class="bi bi-chevron-right ms-auto" style="color:var(--sm)"></i>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="{{ route('hospital.medicines.index', ['slug' => $slug]) }}"
                           class="sp-quick-link">
                            <span class="sp-quick-link-icon"><i class="bi bi-capsule-pill"></i></span>
                            <div>
                                <div>Medicine Master</div>
                                <div style="font-size:.72rem;font-weight:400;color:var(--sm)">Manage medicines & groups</div>
                            </div>
                            <i class="bi bi-chevron-right ms-auto" style="color:var(--sm)"></i>
                        </a>
                    </div>
                </div>
            </div>
            @endif

            {{-- Save Bar --}}
            <div class="sp-save-wrap">
                <span class="sp-save-hint"><i class="bi bi-info-circle me-1"></i> All changes are saved immediately.</span>
                <button type="submit" class="btn sp-save-btn">
                    <i class="bi bi-save me-2"></i> Save Profile
                </button>
            </div>

        </form>
    </div>
</div>
</div>

@push('scripts')
<script>
function spTogglePw(inputId, btn) {
    var input = document.getElementById(inputId);
    if (!input) return;
    var isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    var eyeOpen = '<svg viewBox="0 0 24 24"><path d="M1.5 12s3.5-7 10.5-7 10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3.2" stroke-width="1.8"/></svg>';
    var eyeSlash = '<svg viewBox="0 0 24 24"><path d="M3 3l18 18" stroke-width="1.8" stroke-linecap="round"/><path d="M10.2 5.1A11.3 11.3 0 0 1 12 5c7 0 10.5 7 10.5 7a19 19 0 0 1-4.1 4.7" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M9.9 9.9A3.2 3.2 0 0 0 12 15.2c.5 0 1-.1 1.4-.3" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M7.1 7.4C4 9.6 1.5 12 1.5 12s3.5 7 10.5 7c1.7 0 3.2-.3 4.6-.8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    btn.innerHTML = isHidden ? eyeSlash : eyeOpen;
}
function spPreview(input, previewId) {
    var wrap = document.getElementById(previewId);
    if (!wrap) return;
    var img = wrap.querySelector('img');
    if (input.files && input.files[0]) {
        var r = new FileReader();
        r.onload = function(e) { img.src = e.target.result; wrap.style.display = ''; };
        r.readAsDataURL(input.files[0]);
    }
}
</script>
@endpush
@endsection
