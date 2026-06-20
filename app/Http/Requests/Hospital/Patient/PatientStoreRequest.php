<?php

namespace App\Http\Requests\Hospital\Patient;

use Illuminate\Foundation\Http\FormRequest;

class PatientStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'age' => ['required', 'integer', 'min:0', 'max:150'],
            'gender' => ['required', 'in:male,female,other'],
            'occupation' => ['nullable', 'string', 'max:100'],
            'contact_no' => ['required', 'string', 'regex:/^\d{10}$/'],
            'whatsapp_no' => ['nullable', 'string', 'regex:/^\d{10}$/'],
            'location_id' => ['required', 'integer', 'exists:tbl_master_cities,id'],
            'appointment_date' => ['required', 'date'],
            'slot_id' => ['nullable', 'integer', 'exists:tbl_slots,id'],
            'doctor_id' => ['required', 'integer', 'exists:hospital_users,id'],
            'case_id' => ['required', 'integer', 'exists:tbl_cases,id'],
            'case_fee' => ['required', 'numeric', 'min:0'],
            'referrer_id' => ['nullable', 'integer', 'exists:tbl_referrers,id'],
            'is_old_patient' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'Patient first name is required.',
            'last_name.required' => 'Patient last name is required.',
            'doctor_id.required' => 'Please select a doctor.',
            'case_id.required' => 'Please select a case type.',
            'case_fee.required' => 'Case fee is required.',
            'contact_no.required' => 'Contact number is required.',
            'contact_no.regex'    => 'Contact number must be exactly 10 digits.',
            'whatsapp_no.regex'   => 'WhatsApp number must be exactly 10 digits.',
        ];
    }
}
