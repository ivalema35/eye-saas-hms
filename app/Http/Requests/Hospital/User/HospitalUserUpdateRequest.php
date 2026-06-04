<?php

namespace App\Http\Requests\Hospital\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HospitalUserUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth('hospital_user')->user();

        return $user?->role?->is_super === true;
    }

    public function rules(): array
    {
        $userId = (int) $this->route('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('hospital_users', 'email')
                    ->where(fn ($query) => $query->where('tenant_id', config('app.tenant_id')))
                    ->ignore($userId),
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
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'status' => ['required', 'in:active,inactive'],
            'doctor_type'   => ['nullable', 'in:primary,secondary'],
            'doctor_prefix' => ['nullable', 'string', 'max:5', 'regex:/^[A-Za-z]+$/'],
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
