<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSurveyEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'respondent_name' => ['required', 'string', 'max:255'],
            'district_id' => ['required', 'exists:districts,id'],
            'village_id' => ['nullable', 'exists:villages,id'],
            'evidence_photo' => ['required', 'image', 'max:5120'],
            'variables' => ['required', 'array'],
            'variables.*.survey_variable_id' => ['required', 'exists:survey_variables,id'],
            'variables.*.value' => ['nullable', 'string'],
        ];
    }
}
