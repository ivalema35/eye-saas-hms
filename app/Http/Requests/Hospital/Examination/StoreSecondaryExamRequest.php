<?php

namespace App\Http\Requests\Hospital\Examination;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSecondaryExamRequest extends FormRequest
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
            'doctor_id' => ['required', 'integer', 'exists:hospital_users,id'],
            'advice'    => ['nullable', 'string', 'max:2000'],
            'exam_data' => ['required', 'array'],
            'exam_data.complaints' => ['nullable', 'array'],
            'exam_data.history'    => ['nullable', 'string', 'max:2000'],
            'exam_data.co_rows'    => ['nullable', 'array'],
            'exam_data.co_rows.*.complaint' => ['nullable', 'string', 'max:255'],
            'exam_data.co_rows.*.since'     => ['nullable', 'string', 'max:10'],
            'exam_data.co_rows.*.unit'      => ['nullable', 'string', 'in:Days,Weeks,Months,Years,Longtime'],
            'exam_data.co_rows.*.eye'       => ['nullable', 'string', 'in:RE,LE,Both,OU,'],
            'exam_data.co_rows.*.comment'   => ['nullable', 'string', 'max:500'],
            'exam_data.complaint_duration' => ['nullable', 'string', 'max:100'],
            'exam_data.kcos' => ['nullable', 'array'],
            'exam_data.kco_rows'   => ['nullable', 'array'],
            'exam_data.kco_rows.*.condition' => ['nullable', 'string', 'max:255'],
            'exam_data.kco_rows.*.since'     => ['nullable', 'string', 'max:10'],
            'exam_data.kco_rows.*.unit'      => ['nullable', 'string', 'in:Days,Weeks,Months,Years,Longtime'],
            'exam_data.kco_rows.*.comment'   => ['nullable', 'string', 'max:500'],
            'exam_data.vision' => ['nullable', 'array'],
            'exam_data.pg' => ['nullable', 'array'],
            'exam_data.st' => ['nullable', 'array'],
            'exam_data.nct' => ['nullable', 'array'],
            'exam_data.oe' => ['nullable', 'array'],
            'exam_data.fundus' => ['nullable', 'array'],
            'exam_data.diagnoses' => ['nullable', 'array'],
            'exam_data.followup_date' => ['nullable', 'date'],
            'exam_data.followup_duration' => ['nullable', 'string', 'max:50'],
            'exam_data.special_advice' => ['nullable', 'string', 'max:500'],
            'exam_data.advice' => ['nullable', 'string', 'max:2000'],
            'medicines' => ['nullable', 'array'],
            'medicines.*.medicine_id' => ['nullable', 'integer', 'exists:medicines,id'],
            'medicines.*.name' => ['nullable', 'string', 'max:255'],
            'medicines.*.dosage_id' => ['nullable', 'integer', 'exists:dosages,id'],
            'medicines.*.duration' => ['nullable', 'string', 'max:50'],
            'medicines.*.quantity' => ['nullable', 'integer', 'min:1'],
            'medicines.*.route_id' => ['nullable', 'integer', 'exists:medicine_routes,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'doctor_id.required' => 'Please select the examining doctor.',
            'doctor_id.exists' => 'Selected doctor is invalid.',
        ];
    }
}
