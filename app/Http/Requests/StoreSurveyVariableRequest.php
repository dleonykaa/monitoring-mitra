<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSurveyVariableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('pegawai_bps') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'data_type' => ['required', Rule::in(['text', 'number'])],
            'example_format' => ['nullable', 'string', 'max:255'],
        ];
    }
}
