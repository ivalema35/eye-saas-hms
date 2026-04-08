<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Platform Admin &mdash; Eye HMS SaaS</title>
    <link rel="stylesheet" href="{{ asset('css/design-system.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body, html { height: 100%; margin: 0; font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
        .split-layout { display: flex; min-height: 100vh; }

        /* ── Left banner ────────────────────────────────────── */
        .auth-banner {
            flex: 1;
            background: linear-gradient(135deg, #0D0D0D 0%, #1a1a2e 60%, #16213e 100%);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 48px 40px;
            position: relative;
            overflow: hidden;
        }
        .auth-banner::before {
            content: '';
            position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
            background: radial-gradient(circle, rgba(26,188,156,.06) 0%, transparent 60%);
            animation: pulse 10s ease-in-out infinite alternate;
        }
        @keyframes pulse { 0% { transform: scale(1); } 100% { transform: scale(1.12); } }
        .banner-content { position: relative; z-index: 1; text-align: center; max-width: 380px; animation: fadeInUp .9s ease-out; }
        .banner-logo {
            width: 80px; height: 80px; border-radius: 20px;
            background: rgba(26,188,156,.12);
            border: 1px solid rgba(26,188,156,.2);
            display: inline-flex; align-items: center; justify-content: center;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 32px rgba(0,0,0,.4);
        }
        .banner-logo i { font-size: 2rem; color: #1ABC9C; }
        .banner-content h1 { font-size: 1.75rem; font-weight: 800; margin: 0 0 .75rem; letter-spacing: -.025em; }
        .banner-content p { font-size: 1rem; color: rgba(255,255,255,.5); margin: 0 0 2rem; line-height: 1.6; }
        .banner-features { list-style: none; padding: 0; margin: 0; text-align: left; }
        .banner-features li { display: flex; align-items: center; gap: .625rem; font-size: .875rem; color: rgba(255,255,255,.5); padding: .35rem 0; }
        .banner-features li i { font-size: .7rem; color: rgba(26,188,156,.6); width: 14px; text-align: center; }

        /* ── Right form ─────────────────────────────────────── */
        .auth-form-side { flex: 1; display: flex; justify-content: center; align-items: center; background: #fff; padding: 48px 40px; }
        .auth-form-box { width: 100%; max-width: 420px; animation: fadeIn .8s ease-out; }
        .back-link { display: inline-flex; align-items: center; gap: .375rem; font-size: .8rem; color: #9CA3AF; text-decoration: none; margin-bottom: 2rem; transition: color .2s; }
        .back-link:hover { color: #0D2137; }
        .form-header { margin-bottom: 1.75rem; }
        .form-header h2 { font-size: 1.5rem; font-weight: 800; color: #0D2137; margin: 0 0 .3rem; letter-spacing: -.025em; }
        .form-header p { font-size: .875rem; color: #64748B; margin: 0; }
        .restricted-badge {
            display: flex; align-items: center; justify-content: center; gap: .5rem;
            background: linear-gradient(135deg, #EAF2F8, #D6EAF8);
            border: 1px solid rgba(13,33,55,.12);
            border-radius: 8px; padding: .5625rem 1rem;
            font-size: .75rem; font-weight: 700; color: #0D2137;
            text-transform: uppercase; letter-spacing: .08em;
            margin-bottom: 1.5rem;
        }
        .form-group { margin-bottom: 1.125rem; }
        .form-label { display: block; font-size: .8125rem; font-weight: 600; color: #374151; margin-bottom: .375rem; }
        .input-wrap { position: relative; }
        .input-icon { position: absolute; left: .875rem; top: 50%; transform: translateY(-50%); color: #9CA3AF; font-size: .875rem; pointer-events: none; }
        .form-input {
            width: 100%; padding: .7rem .875rem .7rem 2.5rem;
            font-size: .875rem; color: #111827;
            border: 1.5px solid #D1D5DB; border-radius: 8px;
            background: #fff; outline: none; box-sizing: border-box;
            transition: border-color .2s, box-shadow .2s;
        }
        .form-input:focus { border-color: #1ABC9C; box-shadow: 0 0 0 3px rgba(26,188,156,.12); }
        .form-input.is-error { border-color: #C0392B; }
        .form-error { font-size: .75rem; color: #C0392B; margin-top: .3rem; display: flex; align-items: center; gap: .3rem; }
        .btn-login {
            width: 100%; padding: .85rem; border: none; border-radius: 8px;
            background: #0D2137; color: #fff;
            font-size: .9375rem; font-weight: 700; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: .5rem;
            margin-top: 1.5rem;
            box-shadow: 0 4px 12px rgba(13,33,55,.3);
            transition: background .15s, box-shadow .15s, transform .1s;
        }
        .btn-login:hover { background: #081524; box-shadow: 0 6px 16px rgba(13,33,55,.4); }
        .btn-login:active { transform: scale(.99); }
        .form-footer { text-align: center; margin-top: 1.5rem; font-size: .8125rem; color: #9CA3AF; }
        .form-footer a { color: #64748B; text-decoration: none; }
        .form-footer a:hover { color: #0D2137; }

        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @media (max-width: 768px) { .auth-banner { display: none; } .auth-form-side { padding: 32px 24px; } }
        @media (max-width: 480px) { .auth-form-side { padding: 24px 16px; } }
    </style>
</head>
<body>

<div class="split-layout">

    {{-- Left: Branding banner --}}
    <div class="auth-banner">
        <div class="banner-content">
            <div class="banner-logo">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <h1>Master Control Panel</h1>
            <p>System administration and tenant management.</p>
            <ul class="banner-features">
                <li><i class="fa-solid fa-circle-check"></i> Tenant Management</li>
                <li><i class="fa-solid fa-circle-check"></i> Subscription Control</li>
                <li><i class="fa-solid fa-circle-check"></i> Platform Monitoring</li>
                <li><i class="fa-solid fa-circle-check"></i> System Configuration</li>
            </ul>
        </div>
    </div>

    {{-- Right: Form panel --}}
    <div class="auth-form-side">
        <div class="auth-form-box">

            <a href="{{ route('home') }}" class="back-link">
                <i class="fa-solid fa-arrow-left"></i> Back to Eye HMS
            </a>

            <div class="form-header">
                <h2>Platform Admin</h2>
                <p>Super Admin access only.</p>
            </div>

            <div class="restricted-badge">
                <i class="fa-solid fa-lock"></i>
                Restricted Area &mdash; Authorized Personnel Only
            </div>

            <form method="POST" action="{{ route('superadmin.login.post') }}">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email"
                               class="form-input @error('email') is-error @enderror"
                               value="{{ old('email') }}"
                               placeholder="admin@eyehms.com"
                               required autofocus autocomplete="email">
                    </div>
                    @error('email')
                        <div class="form-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input type="password" id="password" name="password"
                               class="form-input @error('password') is-error @enderror"
                               placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                               required autocomplete="current-password">
                    </div>
                    @error('password')
                        <div class="form-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn-login">
                    <i class="fa-solid fa-right-to-bracket"></i> Sign In to Platform
                </button>
            </form>

            <div class="form-footer">
                <a href="{{ route('home') }}"><i class="fa-solid fa-arrow-left"></i> Back to Eye HMS</a>
            </div>

        </div>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false,
            timer: 4000, timerProgressBar: true,
        });
        @if(session('error'))
            Toast.fire({ icon: 'error', title: @json(session('error')) });
        @endif
        @if(session('success'))
            Toast.fire({ icon: 'success', title: @json(session('success')) });
        @endif
    });
</script>

</body>
</html>