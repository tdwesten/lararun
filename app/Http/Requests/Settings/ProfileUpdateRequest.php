<?php

namespace App\Http\Requests\Settings;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],

            'locale' => ['required', 'string', Rule::in(['en', 'nl'])],

            'age' => ['nullable', 'integer', 'min:13', 'max:120'],

            'weight_kg' => ['nullable', 'numeric', 'min:30', 'max:300'],

            'fitness_level' => ['nullable', 'string', Rule::in(['beginner', 'intermediate', 'advanced', 'elite'])],

            'injury_history' => ['nullable', 'string', 'max:1000'],

            'training_preferences' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
