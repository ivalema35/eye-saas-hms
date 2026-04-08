<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reset Password &mdash; {{ $hospitalName }}</title>
    <link rel="stylesheet" href="{{ asset('css/design-system.css') }}">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body { margin: 0; font-family: var(--hms-font, 'Inter', sans-serif); }
        .login-page {
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #060E1A 0%, #0D2137 60%, #1B4F72 100%);
            padding: 1.5rem;
        }
        .login-wrap { width: 100%; max-width: 420px; }
        .login-card {
            background: #fff; border-radius: 16px;
            box-shadow: 0 24px 64px rgba(0,0,0,.35);
            overflow: hidden; border-top: 4px solid #1B4F72;
        }
        .login-body { padding: 2.25rem 2rem 2rem; }
        .login-header { text-align: center; margin-bottom: 1.75rem; }
        .login-icon {
            width: 64px; height: 64px; border-radius: 16px;
            background: linear-gradient(135deg, #1B4F72, #2980B9);
            display: inline-flex; align-items: center; justify-content: center;
            margin-bottom: .875rem;
            box-shadow: 0 8px 24px rgba(27,79,114,.3);
        }
        .login-icon i { color: #fff; font-size: 1.625rem; }
        .login-header h1 {
            font-size: 1.375rem; font-weight: 800;
            color: #0D2137; margin: 0 0 .25rem; letter-spacing: -.02em;
        }
        .login-header p { font-size: .875rem; color: #64748B; margin: 0; }
        .form-group { margin-bottom: 1.125rem; }
        .form-label {
            display: block; font-size: .8125rem; font-weight: 600;
            color: #374151; margin-bottom: .375rem;
        }
        .form-input {
            width: 100%; padding: .65rem .875rem;
            font-size: .875rem; color: #111827;
            border: 1.5px solid #D1D5DB; border-radius: 8px;
            background: #fff; outline: none; box-sizing: border-box;
            transition: border-color .2s, box-shadow .2s;
        }
        .form-input:focus { border-color: #2980B9; box-shadow: 0 0 0 3px rgba(41,128,185,.12); }
        .form-input.is-error { border-color: #C0392B; }
        .form-error {
            font-size: .75rem; color: #C0392B;
            margin-top: .3rem; display: flex; align-items: center; gap: .3rem;
        }
        .btn-login {
            width: 100%; padding: .8rem; border: none; border-radius: 8px;
            background: #1B4F72; color: #fff;
            font-size: .9375rem; font-weight: 700;
            cursor: pointer; display: flex; align-items: center; justify-content: center; gap: .5rem;
            transition: background .15s, transform .1s;
            box-shadow: 0 4px 12px rgba(27,79,114,.25);
        }
        .btn-login:hover { background: #154360; }
        .btn-login:active { transform: scale(.99); }
        .login-footer {
            text-align: center; margin-top: 1.5rem;
            font-size: .8125rem; color: #9CA3AF;
        }
        .login-footer a { color: #2980B9; text-decoration: none; }
        .login-footer a:hover { text-decoration: underline; }
        .alert {
            padding: .75rem 1rem; border-radius: 8px; margin-bottom: 1.25rem;
            font-size: .875rem; display: flex; align-items: flex-start; gap: .5rem;
        }
        .alert-danger { background: #FADBD8; color: #641E16; border-left: 3px solid #C0392B; }
        .alert-success { background: #D5F5E3; color: #1A5C3A; border-left: 3px solid #27AE60; }
    </style>
</head>
<body>
<div class="login-page">
    <div class="login-wrap">

        <div style="text-align:center;margin-bottom:1.25rem">
            <a href="{{ route('home') }}" style="display:inline-flex;align-items:center;gap:.5rem;text-decoration:none;color:#fff;font-size:1rem;font-weight:700">
                <i class="fa-solid fa-eye" style="font-size:1.25rem;color:#1ABC9C"></i>
                <span>Eye<span style="color:#1ABC9C">HMS</span></span>
            </a>
        </div>

        <div class="login-card">
            <div class="login-body">
                <div class="login-header">
                    <div class="login-icon">
                        <i class="fa-solid fa-key"></i>
                    </div>
                    <h1>Reset Password</h1>
                    <p>Set your new password below</p>
                </div>

                @if($errors->any())
                    <div class="alert alert-danger">
                        <i class="fa-solid fa-circle-exclamation" style="flex-shrink:0;margin-top:.1rem"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form method="POST"
                      action="{{ route('hospital.password.update', ['slug' => $slug]) }}">
                    @csrf

                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <input type="email" id="email" name="email"
                               class="form-input @error('email') is-error @enderror"
                               value="{{ old('email', $email) }}"
                               required autocomplete="email">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">New Password</label>
                        <input type="password" id="password" name="password"
                               class="form-input @error('password') is-error @enderror"
                               placeholder="Min. 8 characters"
                               required autocomplete="new-password">
                        @error('password')
                            <div class="form-error">
                                <i class="fa-solid fa-circle-exclamation"></i> {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password_confirmation">Confirm Password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                               class="form-input"
                               placeholder="Re-enter password"
                               required autocomplete="new-password">
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="fa-solid fa-check"></i> Reset Password
                    </button>
                </form>

                <div class="login-footer">
                    <a href="{{ route('hospital.login', ['slug' => $slug]) }}">
                        <i class="fa-solid fa-arrow-left"></i> Back to Login
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>
</body>
</html>
