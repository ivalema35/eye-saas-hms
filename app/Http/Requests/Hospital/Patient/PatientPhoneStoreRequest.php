<?php

namespace App\Http\Requests\Hospital\Patient;

use App\Support\PhoneRules;
use Illuminate\Foundation\Http\FormRequest;

class PatientPhoneStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name'       => ['required', 'string', 'max:100'],
            'middle_name'      => ['nullable', 'string', 'max:100'],
            'last_name'        => ['required', 'string', 'max:100'],
            'age'              => ['required', 'integer', 'min:0', 'max:150'],
            'gender'           => ['required', 'in:male,female,other'],
            'occupation'       => ['nullable', 'string', 'max:100'],
            'contact_no'       => PhoneRules::required(),
            'whatsapp_no'      => PhoneRules::nullable(),
            'location_id'      => ['required', 'integer', 'exists:tbl_master_cities,id'],
            'appointment_date' => ['required', 'date'],
            'slot_id'          => ['nullable', 'integer', 'exists:tbl_slots,id'],
            'doctor_id'        => ['required', 'integer', 'exists:hospital_users,id'],
            'referrer_id'      => ['nullable', 'integer', 'exists:tbl_referrers,id'],
            'is_old_patient'   => ['nullable', 'boolean'],
            // case_id and case_fee are NOT collected at phone booking —
            // they are set during check-in when the patient arrives
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required'  => 'Patient first name is required.',
            'last_name.required'   => 'Patient last name is required.',
            'doctor_id.required'   => 'Please select a doctor.',
            'contact_no.required'  => 'Contact number is required.',
            ...PhoneRules::messages('contact_no'),
            ...PhoneRules::messages('whatsapp_no'),
        ];
    }
}
