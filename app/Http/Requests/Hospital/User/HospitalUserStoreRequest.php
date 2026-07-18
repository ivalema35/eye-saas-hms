<?php

namespace App\Http\Requests\Hospital\User;

use App\Support\EmailRules;
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
                ...EmailRules::required(),
                Rule::unique('hospital_users', 'email')
                    ->where(fn ($query) => $query->where('tenant_id', config('app.tenant_id'))),
            ],
            'contact' => [
                'nullable',
                'string',
                'regex:/^[0-9]{10}$/',
                Rule::unique('hospital_users', 'contact')
                    ->where(fn ($query) => $query->where('tenant_id', config('app.tenant_id'))),
            ],
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
            'doctor_type'     => ['nullable', 'in:primary,secondary'],
            'doctor_prefix'   => ['nullable', 'string', 'max:5', 'regex:/^[A-Za-z]+$/'],
            'foc_permission'  => ['nullable', 'boolean'],
            'registration_no' => ['nullable', 'string', 'max:100'],
            'experience_years'=> ['nullable', 'integer', 'min:0', 'max:60'],
            'signature'       => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:20'],
            'profile_photo'   => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            ...EmailRules::messages('email'),
            'email.unique' => 'This email is already registered.',
            'contact.regex' => 'Contact number must be exactly 10 digits.',
            'contact.unique' => 'This phone number is already registered.',
            'role_id.required' => 'Please select a role.',
            'role_id.exists' => 'Selected role is invalid for this hospital.',
        ];
    }
}
