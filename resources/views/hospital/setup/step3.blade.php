@extends('hospital.setup.layout')

@push('styles')
    <style>
        .password-wrap { position: relative; }
        .password-toggle {
            position: absolute;
            top: 50%;
            right: .7rem;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #64748B;
            cursor: pointer;
            padding: .1rem;
            line-height: 1;
        }
    </style>
@endpush

@section('wizard-content')
    <div class="wizard-card-header">
        <h2><i class="fa-solid fa-headset" style="color:#1B4F72; margin-right:.3rem"></i> Add Your First Receptionist</h2>
        <p>Create a receptionist account for patient registration and billing.</p>
    </div>

    <form method="POST" action="{{ route('hospital.setup.store', ['slug' => $slug, 'step' => $step]) }}">
        @csrf

        <div class="wizard-card-body">
            <div class="form-group">
                <label class="form-label" for="name">Receptionist Name <span style="color:#C0392B">*</span></label>
                <input type="text" id="name" name="name" class="form-input"
                       value="{{ old('name') }}" required autofocus placeholder="Jane Smith">
                @error('name') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem">
                <div class="form-group">
                    <label class="form-label" for="email">Email <span style="color:#C0392B">*</span></label>
                    <input type="email" id="email" name="email" class="form-input"
                           value="{{ old('email') }}" required placeholder="reception@example.com">
                    @error('email') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="contact">Contact Number</label>
                    <input type="text" id="contact" name="contact" class="form-input"
                           value="{{ old('contact') }}" maxlength="10" inputmode="numeric" pattern="[0-9]{10}" placeholder="9876543210">
                    @error('contact') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password <span style="color:#C0392B">*</span></label>
                <div class="password-wrap">
                    <input type="password" id="password" name="password" class="form-input"
                           required minlength="8" placeholder="Minimum 8 characters" style="padding-right:2.5rem">
                    <button type="button" class="password-toggle" id="togglePassword" aria-label="Toggle password visibility">
                        <i class="fa-solid fa-eye" id="togglePasswordIcon"></i>
                    </button>
                </div>
                @error('password') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="wizard-actions">
            <button type="button" class="btn-wizard btn-wizard-skip" onclick="document.getElementById('skip-form').submit()">
                <i class="fa-solid fa-forward"></i> Skip for now
            </button>

            <button type="submit" class="btn-wizard btn-wizard-primary">
                Save &amp; Continue <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>
    </form>

    <form id="skip-form" method="POST" action="{{ route('hospital.setup.skip', ['slug' => $slug, 'step' => $step]) }}" style="display:none">
        @csrf
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.getElementById('togglePassword');
            const toggleIcon = document.getElementById('togglePasswordIcon');

            if (!passwordInput || !toggleButton || !toggleIcon) {
                return;
            }

            toggleButton.addEventListener('click', function () {
                const isPassword = passwordInput.type === 'password';
                passwordInput.type = isPassword ? 'text' : 'password';
                toggleIcon.classList.toggle('fa-eye', !isPassword);
                toggleIcon.classList.toggle('fa-eye-slash', isPassword);
            });
        });
    </script>
@endpush
