<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileExperienceRequest extends FormRequest
{
    use NormalizesFactLists;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'job_title' => ['required', 'string', 'max:255'],
            'company' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'currently_employed' => ['boolean'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'achievements' => ['nullable', 'array', 'max:20'],
            'achievements.*' => ['required', 'string', 'max:500'],
            'technologies' => ['nullable', 'array', 'max:40'],
            'technologies.*' => ['required', 'string', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'currently_employed' => $this->boolean('currently_employed'),
            'achievements' => $this->factList('achievements'),
            'technologies' => $this->factList('technologies'),
        ]);

        if ($this->boolean('currently_employed')) {
            $this->merge(['end_date' => null]);
        }
    }
}
