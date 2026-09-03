@extends('landing.layouts.app')

@section('title', 'Registration Pending — EYENOSIS')
@section('meta_description', 'Your hospital registration is pending SuperAdmin approval.')

@section('content')
    <div class="reg-page"
        style="min-height:70vh;display:flex;align-items:center;justify-content:center;padding:3rem 1.25rem">
        <div
            style="max-width:560px;width:100%;background:#fff;border:1px solid rgba(15,79,134,.12);border-radius:20px;padding:2.25rem 2rem;text-align:center;box-shadow:0 12px 40px rgba(15,79,134,.06)">
            <div
                style="width:64px;height:64px;margin:0 auto 1.25rem;border-radius:18px;background:rgba(15,79,134,.1);color:#0f4f86;display:flex;align-items:center;justify-content:center;font-size:1.75rem">
                <i class="fa-solid fa-hourglass-half"></i>
            </div>
            <div
                style="display:inline-block;font-size:.72rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#0f4f86;background:rgba(15,79,134,.08);padding:.35rem .75rem;border-radius:999px;margin-bottom:1rem">
                Pending approval
            </div>
            <h1 style="font-size:1.45rem;font-weight:800;color:#0a1628;margin:0 0 .75rem;letter-spacing:-.02em">
                Your hospital request is pending
            </h1>
            <p style="font-size:.95rem;line-height:1.6;color:#5d7084;margin:0 0 1.25rem">
                Thank you for registering <strong style="color:#0f4f86">{{ $hospitalName }}</strong>.
                Eyenosis must accept your request before you can log in and continue setup.
            </p>
            <p style="font-size:.88rem;line-height:1.55;color:#64748b;margin:0 0 1.75rem">
                You will be able to log in only after Eyenosis accepts this hospital.
                This page will update automatically once approved.
            </p>
            <div id="pending-approved-banner" hidden
                style="margin:0 0 1.25rem;padding:.85rem 1rem;border-radius:12px;background:#D1FAE5;color:#065F46;font-size:.9rem;font-weight:600">
                Approved! Redirecting you to login…
            </div>
            <a href="{{ url('/') }}" class="hms-btn hms-btn-primary"
                style="display:inline-flex;align-items:center;gap:.4rem;background:#0f4f86;color:#fff;padding:.65rem 1.25rem;border-radius:12px;text-decoration:none;font-weight:700">
                Back to Home
            </a>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                const statusUrl = @json(route('register.pending.status', ['slug' => $slug]));
                const banner = document.getElementById('pending-approved-banner');

                async function pollApproval() {
                    try {
                        const response = await fetch(statusUrl, {
                            headers: { 'Accept': 'application/json' },
                            cache: 'no-store',
                        });

                        if (!response.ok) {
                            return;
                        }

                        const data = await response.json();

                        if (data.approved) {
                            if (banner) {
                                banner.hidden = false;
                            }

                            window.location.href = data.login_url;
                        }
                    } catch (error) {
                        // Ignore transient network errors.
                    }
                }

                pollApproval();
                setInterval(pollApproval, 5000);
            })();
        </script>
    @endpush
@endsection