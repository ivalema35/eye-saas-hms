{{--
    Form Group Component
    Usage: <x-form-group label="Email" name="email" required>
               <input type="email" name="email" class="hms-input" value="{{ old('email') }}">
           </x-form-group>
--}}
@props([
    'label'    => '',
    'name'     => '',
    'required' => false,
    'hint'     => '',
])

<div class="hms-form-group">
    @if($label)
        <label for="{{ $name }}">
            {{ $label }}
            @if($required)
                <span style="color:var(--hms-danger)">*</span>
            @endif
        </label>
    @endif

    {{ $slot }}

    @if($hint)
        <small style="display:block;font-size:.75rem;color:var(--hms-text-muted);margin-top:.25rem">{{ $hint }}</small>
    @endif

    @error($name)
        <span class="hms-error">{{ $message }}</span>
    @enderror
</div>
