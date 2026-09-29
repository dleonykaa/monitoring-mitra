<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignMitraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('pegawai_bps') ?? false;
    }

    public function rules(): array
    {
        return [
            'mitra_id' => ['required', 'exists:users,id'],
            'target' => ['required', 'integer', 'min:1'],
        ];
    }
}
