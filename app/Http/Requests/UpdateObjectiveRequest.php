<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateObjectiveRequest extends FormRequest
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
            'type' => ['sometimes', 'required', 'string', 'in:5 km,10 km,21.1 km,42.2 km,Speed'],
            'target_date' => ['sometimes', 'required', 'date', 'after:today'],
            'status' => ['sometimes', 'required', 'string', 'in:active,completed,abandoned'],
            'description' => ['nullable', 'string', 'max:1000'],
            'running_days' => ['sometimes', 'required', 'array', 'min:1'],
            'running_days.*' => ['string', 'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday'],
        ];
    }
}
