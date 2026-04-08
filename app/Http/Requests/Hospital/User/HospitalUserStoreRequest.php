<?php

namespace App\Http\Requests\Hospital\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HospitalUserStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth('hospital_user')->user();

        return $user?->role?->slug === 'hospital_admin';
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('hospital_users', 'email')
                    ->where(fn ($query) => $query->where('tenant_id', config('app.tenant_id'))),
            ],
            'contact' => ['nullable', 'string', 'max:15'],
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')
                    ->where(fn ($query) => $query
                        ->where('tenant_id', config('app.tenant_id'))
                        ->whereNull('deleted_at')),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'status' => ['required', 'in:active,inactive'],
            'doctor_type' => ['nullable', 'in:primary,secondary'],
            'foc_permission' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'role_id.required' => 'Please select a role.',
            'role_id.exists' => 'Selected role is invalid for this hospital.',
        ];
    }
}
