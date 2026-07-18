<?php

namespace App\Http\Requests\Patient;

use App\Support\EmailRules;
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
            'phone' => ['required', 'string', 'max:15', 'regex:/^[6-9]\d{9}$/'],
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
            'name.required' => 'Patient name is required.',
            'phone.required' => 'Mobile number is required.',
            'phone.regex' => 'Please enter a valid 10-digit Indian mobile number.',
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
