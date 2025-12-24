<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EnhanceObjectiveTrainingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->objective);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'enhancement_prompt' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
