<?php

namespace App\Http\Requests\Hospital\Foc;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreFocRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'doctor_id' => ['nullable', 'integer', 'exists:hospital_users,id'],
            'reception_id' => ['nullable', 'integer', 'exists:hospital_users,id'],
            'foc_fee' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'patient_id.required' => 'Please select a patient.',
            'patient_id.exists' => 'Selected patient is invalid.',
            'foc_fee.required' => 'FOC fee amount is required.',
            'reason.required' => 'Reason for FOC request is required.',
        ];
    }
}
