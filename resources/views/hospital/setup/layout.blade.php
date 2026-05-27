<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Setup Wizard — Step {{ $step }} of {{ $maxSteps }}</title>
    <link rel="stylesheet" href="{{ asset('css/design-system.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body { margin: 0; font-family: var(--hms-font, 'Inter', sans-serif); background: #F4F6F9; min-height: 100vh; }

        .wizard-container { max-width: 720px; margin: 40px auto; padding: 0 1rem; }

        .wizard-brand { text-align: center; margin-bottom: 1.5rem; }
        .wizard-brand a {
            display: inline-flex; align-items: center; gap: .5rem;
            text-decoration: none; color: #0D2137; font-size: 1.125rem; font-weight: 700;
        }
        .wizard-brand i { color: #1ABC9C; font-size: 1.25rem; }

        .wizard-steps { display: flex; justify-content: center; gap: .5rem; margin-bottom: 2rem; }
        .wizard-step {
            display: flex; align-items: center; gap: .4rem;
            padding: .5rem 1rem; border-radius: 24px;
            font-size: .8125rem; font-weight: 600;
            background: #E5E7EB; color: #6B7280;
            transition: all .2s;
        }
        .wizard-step.active { background: #1B4F72; color: #fff; }
        .wizard-step.done { background: #27AE60; color: #fff; }
        .wizard-step .step-num {
            width: 22px; height: 22px; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: .75rem; font-weight: 800;
            background: rgba(255,255,255,.2);
        }
        .wizard-step.active .step-num { background: rgba(255,255,255,.3); }

        .wizard-card {
            background: #fff; border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,.08);
            border-top: 4px solid #1B4F72; overflow: hidden;
        }
        .wizard-card-header { padding: 1.5rem 2rem 0; }
        .wizard-card-header h2 { font-size: 1.25rem; font-weight: 700; color: #0D2137; margin: 0 0 .25rem; }
        .wizard-card-header p { font-size: .875rem; color: #64748B; margin: 0; }
        .wizard-card-body { padding: 1.5rem 2rem 2rem; }

        .wizard-actions {
            display: flex; justify-content: space-between; align-items: center;
            padding: 1rem 2rem 1.5rem; border-top: 1px solid #F1F5F9;
        }

        .btn-wizard {
            padding: .6rem 1.5rem; border-radius: 8px; font-size: .875rem; font-weight: 600;
            border: none; cursor: pointer; display: inline-flex; align-items: center; gap: .4rem;
            transition: background .15s;
        }
        .btn-wizard-primary { background: #1B4F72; color: #fff; }
        .btn-wizard-primary:hover { background: #154360; }
        .btn-wizard-skip { background: transparent; color: #6B7280; border: 1.5px solid #D1D5DB; }
        .btn-wizard-skip:hover { background: #F9FAFB; }

        .form-group { margin-bottom: 1rem; }
        .form-label { display: block; font-size: .8125rem; font-weight: 600; color: #374151; margin-bottom: .4rem; }
        .form-input {
            width: 100%; padding: .6rem .875rem; font-size: .875rem; color: #111827;
            border: 1.5px solid #D1D5DB; border-radius: 8px; background: #fff; outline: none;
            box-sizing: border-box; transition: border-color .2s, box-shadow .2s;
        }
        .form-input:focus { border-color: #2980B9; box-shadow: 0 0 0 3px rgba(41,128,185,.12); }
        .form-select { appearance: auto; }
        .form-error { font-size: .75rem; color: #C0392B; margin-top: .25rem; }

        .alert { padding: .75rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: .875rem; display: flex; align-items: center; gap: .5rem; }
        .alert-success { background: #D5F5E3; color: #1A5C3A; border-left: 3px solid #27AE60; }
        .alert-danger { background: #FADBD8; color: #641E16; border-left: 3px solid #C0392B; }

        @media (max-width: 600px) {
            .wizard-card-body { padding: 1rem 1.25rem 1.5rem; }
            .wizard-actions { padding: .75rem 1.25rem 1rem; flex-direction: column; gap: .5rem; }
        }
    </style>
    @stack('styles')
</head>
<body>

<div class="wizard-container">
    {{-- Brand --}}
    <div class="wizard-brand">
        <a href="#">
            <i class="fa-solid fa-eye"></i>
            <span>Eye<span style="color:#1ABC9C">HMS</span> — Setup Wizard</span>
        </a>
    </div>

    {{-- Steps Indicator --}}
    <div class="wizard-steps">
        @php
            $stepLabels = [1 => 'Profile', 2 => 'Doctor', 3 => 'Receptionist', 4 => 'Cases'];
        @endphp
        @foreach($stepLabels as $num => $label)
            <div class="wizard-step {{ $num === $step ? 'active' : ($num < $step ? 'done' : '') }}">
                <span class="step-num">
                    @if($num < $step)
                        <i class="fa-solid fa-check" style="font-size:.6rem"></i>
                    @else
                        {{ $num }}
                    @endif
                </span>
                {{ $label }}
            </div>
        @endforeach
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i> {{ $errors->first() }}
        </div>
    @endif

    {{-- Card --}}
    <div class="wizard-card">
        @yield('wizard-content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
@stack('scripts')
</body>
</html>
