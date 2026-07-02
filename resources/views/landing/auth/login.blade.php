<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login &mdash; Eye HMS SaaS</title>
    <link rel="stylesheet" href="{{ asset('css/design-system.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body,
        html {
            min-height: 100%;
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #EBF5FB 0%, #fff 60%, #EBF5FB 100%);
        }

        .split-layout {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 28px;
            position: relative;
            overflow: hidden;
        }

        .split-layout::before,
        .split-layout::after {
            content: '';
            position: absolute;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            background: rgba(27, 79, 114, .06);
            filter: blur(1px);
            animation: floatOrb 12s ease-in-out infinite;
        }

        .split-layout::before {
            top: 6%;
            left: -80px;
        }

        .split-layout::after {
            right: -70px;
            bottom: 10%;
            animation-delay: -5s;
        }

        .page-bubble {
            position: absolute;
            border-radius: 999px;
            display: flex;
            justify-content: center;
            align-items: center;
            background: rgba(255, 255, 255, .82);
            border: 1px solid rgba(41, 128, 185, .16);
            color: #1B4F72;
            box-shadow: 0 10px 28px rgba(13, 33, 55, .14);
            z-index: 1;
            animation: bubbleRise var(--dur, 24s) linear infinite;
            backdrop-filter: blur(1px);
            pointer-events: none;
        }

        .page-bubble i {
            opacity: .85;
        }

        .page-bubble.b1 {
            width: 92px;
            height: 92px;
            left: 4%;
            bottom: -140px;
            font-size: 2rem;
            animation-delay: -1s;
            --dur: 28s;
            --drift-x: 8vw;
            --rise: 132vh;
        }

        .page-bubble.b2 {
            width: 74px;
            height: 74px;
            left: 11%;
            bottom: -110px;
            font-size: 1.5rem;
            animation-delay: -2.4s;
            --dur: 24s;
            --drift-x: 12vw;
            --rise: 128vh;
        }

        .page-bubble.b3 {
            width: 70px;
            height: 70px;
            right: 9%;
            bottom: -105px;
            font-size: 1.45rem;
            animation-delay: -3.2s;
            --dur: 26s;
            --drift-x: -10vw;
            --rise: 130vh;
        }

        .page-bubble.b4 {
            width: 110px;
            height: 110px;
            right: 5%;
            bottom: -155px;
            font-size: 2.1rem;
            animation-delay: -4.3s;
            --dur: 30s;
            --drift-x: -14vw;
            --rise: 136vh;
        }

        .page-bubble.b5 {
            width: 64px;
            height: 64px;
            right: 20%;
            bottom: -95px;
            font-size: 1.3rem;
            animation-delay: -5.1s;
            --dur: 22s;
            --drift-x: 7vw;
            --rise: 126vh;
        }

        .page-bubble.b6 {
            width: 86px;
            height: 86px;
            left: 24%;
            bottom: -125px;
            font-size: 1.7rem;
            animation-delay: -6.3s;
            --dur: 27s;
            --drift-x: 10vw;
            --rise: 134vh;
        }

        .page-bubble.b7 {
            width: 60px;
            height: 60px;
            left: 32%;
            bottom: -90px;
            font-size: 1.2rem;
            animation-delay: -7.2s;
            --dur: 21s;
            --drift-x: -8vw;
            --rise: 124vh;
        }

        .page-bubble.b8 {
            width: 78px;
            height: 78px;
            right: 30%;
            bottom: -118px;
            font-size: 1.5rem;
            animation-delay: -8.1s;
            --dur: 25s;
            --drift-x: 11vw;
            --rise: 129vh;
        }

        .page-bubble.b9 {
            width: 68px;
            height: 68px;
            right: 36%;
            bottom: -96px;
            font-size: 1.35rem;
            animation-delay: -9s;
            --dur: 23s;
            --drift-x: -9vw;
            --rise: 127vh;
        }

        .page-bubble.b10 {
            width: 56px;
            height: 56px;
            left: 46%;
            bottom: -84px;
            font-size: 1.1rem;
            animation-delay: -10.2s;
            --dur: 20s;
            --drift-x: 6vw;
            --rise: 123vh;
        }

        .auth-shell {
            width: min(1200px, 100%);
            min-height: 680px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: #fff;
            border: 1.5px solid rgba(41, 128, 185, .12);
            border-radius: 48px;
            box-shadow: 0 24px 60px rgba(13, 33, 55, .16);
            position: relative;
            z-index: 2;
            overflow: hidden;
            animation: shellIn .9s ease-out;
        }

        .auth-shell::before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: 50%;
            width: 1px;
            background: rgba(27, 79, 114, .12);
            pointer-events: none;
        }

        .auth-banner {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 44px;
            overflow: hidden;
            background: linear-gradient(180deg, rgba(235, 245, 251, .85) 0%, rgba(255, 255, 255, .96) 100%);
        }

        .banner-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 380px;
            animation: fadeInUp .9s ease-out;
        }

        .banner-logo {
            width: 95px;
            height: 95px;
            border-radius: 24px;
            background: rgba(255, 255, 255, .95);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 20px rgba(13, 33, 55, .14);
            overflow: hidden;
        }

        .banner-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .banner-logo i {
            font-size: 2rem;
            color: #1B4F72;
        }

        .hero-pet {
            width: 210px;
            height: 210px;
            margin: 0 auto 1rem;
            border-radius: 28px;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(145deg, #fff 0%, #EBF5FB 100%);
            box-shadow: inset 0 2px 0 rgba(255, 255, 255, .9), 0 12px 24px rgba(13, 33, 55, .12);
            animation: breathe 5s ease-in-out infinite;
        }

        .hero-pet i {
            font-size: 6.2rem;
            color: #1B4F72;
            opacity: .9;
        }

        .banner-content h1 {
            font-size: 1.9rem;
            font-weight: 800;
            margin: 0 0 .75rem;
            letter-spacing: -.025em;
            line-height: 1.15;
            color: #0D2137;
        }

        .banner-content p {
            font-size: 1rem;
            color: #64748B;
            margin: 0 0 1.5rem;
            line-height: 1.5;
        }

        .banner-features {
            list-style: none;
            padding: 0;
            margin: 0;
            text-align: left;
            display: inline-block;
        }

        .banner-features li {
            display: flex;
            align-items: center;
            gap: .625rem;
            font-size: .875rem;
            color: #374151;
            padding: .3rem 0;
        }

        .banner-features li i {
            color: #2980B9;
            font-size: .8rem;
            width: 14px;
            text-align: center;
        }

        .floating-chip {
            position: absolute;
            width: 72px;
            height: 72px;
            border-radius: 999px;
            display: flex;
            justify-content: center;
            align-items: center;
            background: rgba(255, 255, 255, .88);
            border: 1px solid rgba(41, 128, 185, .2);
            color: #1B4F72;
            font-size: 1.3rem;
            box-shadow: 0 10px 22px rgba(13, 33, 55, .14);
            animation: bob 6s ease-in-out infinite;
            z-index: 1;
        }

        .chip-1 {
            top: 16%;
            left: 11%;
            animation-delay: -1s;
        }

        .chip-2 {
            right: 13%;
            top: 27%;
            width: 62px;
            height: 62px;
            animation-delay: -2.2s;
        }

        .chip-3 {
            left: 14%;
            bottom: 24%;
            width: 58px;
            height: 58px;
            animation-delay: -3.2s;
        }

        .chip-4 {
            right: 21%;
            bottom: 18%;
            width: 54px;
            height: 54px;
            animation-delay: -4.1s;
        }

        .auth-form-side {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 44px;
            background: #fff;
        }

        .auth-form-box {
            width: 100%;
            max-width: 430px;
            animation: fadeIn .8s ease-out;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: .375rem;
            font-size: .8rem;
            color: #9CA3AF;
            text-decoration: none;
            margin-bottom: 1.5rem;
            transition: color .2s;
        }

        .back-link:hover {
            color: #1B4F72;
        }

        .form-header {
            margin-bottom: 1.4rem;
        }

        .form-header h2 {
            font-size: 2rem;
            font-weight: 800;
            color: #0D2137;
            margin: 0 0 .3rem;
            letter-spacing: -.03em;
            line-height: 1.12;
        }

        .form-header p {
            font-size: .98rem;
            color: #64748B;
            margin: 0;
        }

        .role-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            background: #EBF5FB;
            border: 1px solid rgba(27, 79, 114, .12);
            border-radius: 999px;
            padding: .56rem 1rem;
            font-size: .8rem;
            color: #1B4F72;
            font-weight: 600;
            margin-bottom: 1.4rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            font-size: .83rem;
            font-weight: 700;
            color: #374151;
            margin-bottom: .38rem;
        }

        .input-wrap {
            position: relative;
        }

        .password-field-wrap {
            position: relative;
        }

        .password-field-input {
            padding-right: 3rem;
        }

        .password-field-toggle {
            position: absolute;
            right: .7rem;
            top: 50%;
            transform: translateY(-50%);
            border: 1px solid rgba(13, 33, 55, .10);
            background: rgba(255, 255, 255, .98);
            padding: 0;
            margin: 0;
            color: #1B4F72;
            width: 2rem;
            height: 2rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            line-height: 1;
            z-index: 3;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(13, 33, 55, .06);
            transition: color .2s ease, border-color .2s ease, box-shadow .2s ease, transform .15s ease;
        }

        .password-field-toggle:hover {
            border-color: rgba(13, 33, 55, .18);
            box-shadow: 0 4px 10px rgba(13, 33, 55, .10);
            transform: translateY(-50%) scale(1.02);
        }

        .password-field-toggle:focus-visible {
            outline: 2px solid rgba(27, 79, 114, .22);
            outline-offset: 2px;
        }

        .password-field-toggle svg {
            width: 1rem;
            height: 1rem;
            display: block;
            stroke: currentColor;
            fill: none;
            color: inherit;
            pointer-events: none;
        }

        .input-icon {
            position: absolute;
            left: .875rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9CA3AF;
            font-size: .875rem;
            pointer-events: none;
        }

        .form-input {
            width: 100%;
            padding: .76rem .95rem .76rem 2.5rem;
            font-size: .93rem;
            color: #111827;
            border: 1.5px solid #D1D5DB;
            border-radius: 999px;
            background: #fff;
            outline: none;
            box-sizing: border-box;
            transition: border-color .2s, box-shadow .2s, transform .15s;
        }

        .form-input:focus {
            border-color: #2980B9;
            box-shadow: 0 0 0 3px rgba(41, 128, 185, .12);
            transform: translateY(-1px);
        }

        .form-input.is-error {
            border-color: #C0392B;
        }

        .form-error {
            font-size: .75rem;
            color: #C0392B;
            margin-top: .3rem;
            display: flex;
            align-items: center;
            gap: .3rem;
        }

        .row-between {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: .375rem;
        }

        .forgot-link {
            font-size: .82rem;
            color: #2980B9;
            text-decoration: none;
        }

        .forgot-link:hover {
            color: #154360;
        }

        .remember-row {
            display: flex;
            align-items: center;
            gap: .5rem;
            margin: .9rem 0 1.4rem;
        }

        .remember-row input[type=checkbox] {
            accent-color: #1B4F72;
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .remember-row label {
            font-size: .96rem;
            color: #4A5568;
            cursor: pointer;
        }

        .btn-login {
            width: 100%;
            padding: .9rem;
            border: none;
            border-radius: 999px;
            background: #1B4F72;
            color: #fff;
            font-size: 1.05rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            box-shadow: 0 10px 22px rgba(27, 79, 114, .34);
            transition: background .15s, box-shadow .15s, transform .12s;
        }

        .btn-login:hover {
            background: #154360;
            box-shadow: 0 14px 28px rgba(27, 79, 114, .38);
            transform: translateY(-1px);
        }

        .btn-login:active {
            transform: translateY(1px);
        }

        .form-footer {
            text-align: center;
            margin-top: 1.2rem;
            font-size: .82rem;
            color: #9CA3AF;
        }

        .form-footer a {
            color: #2980B9;
            text-decoration: none;
        }


        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(24px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes shellIn {
            from {
                opacity: 0;
                transform: translateY(24px) scale(.985);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes floatOrb {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-18px);
            }
        }

        @keyframes bob {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        @keyframes breathe {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.04);
            }
        }

        @keyframes bubbleRise {
            0% {
                transform: translate(0, 0) scale(.88);
                opacity: 0;
            }

            12% {
                opacity: .92;
            }

            40% {
                transform: translate(calc(var(--drift-x, 0vw) * .35), calc(var(--rise, 124vh) * -.38)) scale(1);
            }

            70% {
                transform: translate(calc(var(--drift-x, 0vw) * -.18), calc(var(--rise, 124vh) * -.72)) scale(1.05);
            }

            100% {
                transform: translate(var(--drift-x, 0vw), calc(var(--rise, 124vh) * -1));
                opacity: 0;
            }
        }

        @media (max-width: 1024px) {
            .auth-shell {
                border-radius: 32px;
                min-height: auto;
            }

            .auth-banner,
            .auth-form-side {
                padding: 32px;
            }

            .auth-shell::before {
                display: none;
            }

            .auth-shell {
                grid-template-columns: 1fr;
            }

            .auth-banner {
                border-bottom: 1px solid rgba(27, 79, 114, .12);
            }
        }

        @media (max-width: 768px) {
            .split-layout {
                padding: 16px;
            }

            .auth-shell {
                border-radius: 22px;
            }

            .auth-banner {
                display: none;
            }

            .auth-form-side {
                padding: 28px 18px;
            }

            .form-header h2 {
                font-size: 1.6rem;
            }

            .role-badge {
                font-size: .73rem;
            }

            .page-bubble {
                opacity: .55;
                --rise: 112vh;
                --drift-x: 5vw;
            }

            .page-bubble.b1,
            .page-bubble.b4,
            .page-bubble.b6,
            .page-bubble.b8 {
                display: none;
            }

            .page-bubble.b3,
            .page-bubble.b7,
            .page-bubble.b9 {
                --drift-x: -6vw;
            }
        }
    </style>
</head>

<body>

    <div class="split-layout">
        <div class="page-bubble b1"><i class="fa-solid fa-circle"></i></div>
        <div class="page-bubble b2"><i class="fa-solid fa-circle"></i></div>
        <div class="page-bubble b3"><i class="fa-solid fa-hospital"></i></div>
        <div class="page-bubble b4"><i class="fa-solid fa-stethoscope"></i></div>
        <div class="page-bubble b5"><i class="fa-solid fa-notes-medical"></i></div>
        <div class="page-bubble b6"><i class="fa-solid fa-kit-medical"></i></div>
        <div class="page-bubble b7"><i class="fa-solid fa-circle"></i></div>
        <div class="page-bubble b8"><i class="fa-solid fa-user-doctor"></i></div>
        <div class="page-bubble b9"><i class="fa-solid fa-truck-medical"></i></div>
        <div class="page-bubble b10"><i class="fa-solid fa-briefcase-medical"></i></div>
        <div class="page-bubble b11"><i class="fa-solid fa-plus"></i></div>
        <div class="page-bubble b12"><i class="fa-solid fa-heart-pulse"></i></div>

        <div class="auth-shell">

            <div class="auth-banner">
                <div class="floating-chip chip-1"><i class="fa-solid fa-plus"></i></div>
                <div class="floating-chip chip-2"><i class="fa-solid fa-hospital"></i></div>
                <div class="floating-chip chip-3"><i class="fa-solid fa-user-doctor"></i></div>
                <div class="floating-chip chip-4"><i class="fa-solid fa-stethoscope"></i></div>

                <div class="banner-content">
                    <div class="banner-logo">
                        <img src="{{ platform_logo_url() }}" alt="Eye HMS Logo">
                    </div>
                    <div class="hero-pet">
                        <i class="fa-solid fa-hospital-user"></i>
                    </div>
                    <h1>Unified Staff Portal</h1>
                    <p>Access your hospital workspace seamlessly.</p>
                    <ul class="banner-features">
                        <li><i class="fa-solid fa-circle-check"></i> Single Sign-On for All Roles</li>
                        <li><i class="fa-solid fa-circle-check"></i> Multi-Hospital Support</li>
                        <li><i class="fa-solid fa-circle-check"></i> Doctor &amp; Receptionist Portals</li>
                        <li><i class="fa-solid fa-circle-check"></i> Secure Cloud Platform</li>
                    </ul>
                </div>
            </div>

            <div class="auth-form-side">
                <div class="auth-form-box">

                    <a href="{{ route('home') }}" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Eye
                        HMS</a>

                    <div class="form-header">
                        <h2>Welcome Back</h2>
                        <p>Enter your credentials to continue.</p>
                    </div>

                    <div class="role-badge"><i class="fa-solid fa-users"></i> Admin &bull; Doctor &bull; Receptionist
                        &bull; OT Staff</div>

                    <form method="POST" action="{{ route('login.post') }}">
                        @csrf

                        <div class="form-group">
                            <label class="form-label" for="email">Email or Phone Number</label>
                            <div class="input-wrap">
                                <i class="fa-solid fa-envelope input-icon"></i>
                                <input type="text" id="email" name="email"
                                    class="form-input @error('email') is-error @enderror" value="{{ old('email') }}"
                                    placeholder="yourname@hospital.com or 9876543210" required autofocus
                                    autocomplete="username">
                            </div>
                            @error('email')<div class="form-error"><i class="fa-solid fa-circle-exclamation"></i>
                            {{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <div
                                style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.2rem;">
                                <label class="form-label" for="password" style="margin:0">Password</label>
                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}" class="forgot-link"
                                        style="font-size:.85rem;color:#2980B9;text-decoration:none;">Forgot Password?</a>
                                @else
                                    <span class="forgot-link" style="font-size:.85rem;color:#64748B;">Forgot
                                        Password?</span>
                                @endif
                            </div>
                            <div class="input-wrap password-field-wrap">
                                <i class="fa-solid fa-lock input-icon"></i>
                                <input type="password" id="password" name="password"
                                    class="form-input password-field-input @error('password') is-error @enderror"
                                    placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" required
                                    autocomplete="current-password">
                                <button type="button" id="togglePasswordVisibility" class="password-field-toggle"
                                    aria-label="Toggle password visibility">
                                    <svg id="passwordEyeIcon" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M1.5 12s3.5-7 10.5-7 10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z"
                                            stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                                        <circle cx="12" cy="12" r="3.2" stroke-width="1.8"></circle>
                                    </svg>
                                </button>
                            </div>
                            @error('password')<div class="form-error"><i class="fa-solid fa-circle-exclamation"></i>
                            {{ $message }}</div>@enderror
                        </div>

                        <div class="remember-row">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">Keep me signed in</label>
                        </div>

                        <button type="submit" class="btn-login"><i class="fa-solid fa-right-to-bracket"></i> Sign
                            In</button>
                    </form>



                </div>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const passwordInput = document.getElementById('password');
            const togglePassword = document.getElementById('togglePasswordVisibility');
            const passwordEye = document.getElementById('passwordEyeIcon');
            const passwordEyeSvg = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1.5 12s3.5-7 10.5-7 10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path><circle cx="12" cy="12" r="3.2" stroke-width="1.8"></circle></svg>';
            const passwordEyeSlashSvg = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18" stroke-width="1.8" stroke-linecap="round"></path><path d="M10.2 5.1A11.3 11.3 0 0 1 12 5c7 0 10.5 7 10.5 7a19 19 0 0 1-4.1 4.7" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path><path d="M9.9 9.9A3.2 3.2 0 0 0 12 15.2c.5 0 1-.1 1.4-.3" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path><path d="M7.1 7.4C4 9.6 1.5 12 1.5 12s3.5 7 10.5 7c1.7 0 3.2-.3 4.6-.8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path></svg>';

            if (togglePassword && passwordInput && passwordEye) {
                togglePassword.addEventListener('click', function () {
                    const isHidden = passwordInput.type === 'password';
                    passwordInput.type = isHidden ? 'text' : 'password';
                    passwordEye.innerHTML = isHidden ? passwordEyeSlashSvg : passwordEyeSvg;
                });
            }

            const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 4000, timerProgressBar: true });
        @if(session('error')) Toast.fire({ icon: 'error', title: @json(session('error')) }); @endif
            @if(session('success')) Toast.fire({ icon: 'success', title: @json(session('success')) }); @endif
    });
    </script>

</body>

</html>