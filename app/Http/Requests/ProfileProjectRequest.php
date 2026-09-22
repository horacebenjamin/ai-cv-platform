<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileProjectRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'context' => ['nullable', 'string', 'max:5000'],
            'responsibilities' => ['nullable', 'string', 'max:5000'],
            'outcomes' => ['nullable', 'string', 'max:5000'],
            'technologies' => ['nullable', 'array', 'max:40'],
            'technologies.*' => ['required', 'string', 'max:100'],
            'url' => ['nullable', 'url:http,https', 'max:2048'],
            'repository_url' => ['nullable', 'url:http,https', 'max:2048'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['technologies' => $this->factList('technologies')]);
    }
}
