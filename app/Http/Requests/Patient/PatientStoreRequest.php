<?php

namespace App\Http\Requests\Patient;

use App\Support\EmailRules;
use App\Support\PhoneRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * PatientStoreRequest
 *
 * Validates new patient registration data.
 * Used in PatientController@store.
 */
class PatientStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('hospital_user')->check();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => PhoneRules::required(),
            'email' => EmailRules::nullable(),
            'dob' => ['nullable', 'date', 'before:today'],
            'gender' => ['required', 'in:male,female,other'],
            'address' => ['nullable', 'string', 'max:500'],
            'referred_by' => ['nullable', 'string', 'max:255'],
            'occupation' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            ...EmailRules::messages('email'),
            ...PhoneRules::messages('phone'),
            'name.required' => 'Patient name is required.',
            'phone.required' => 'Mobile number is required.',
            'gender.required' => 'Please select patient gender.',
            'gender.in' => 'Invalid gender value.',
            'dob.before' => 'Date of birth must be in the past.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Patient Name',
            'phone' => 'Mobile Number',
            'dob' => 'Date of Birth',
            'gender' => 'Gender',
            'referred_by' => 'Referred By',
        ];
    }
}
