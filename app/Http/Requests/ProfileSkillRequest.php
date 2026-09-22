<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileSkillRequest extends FormRequest
{
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
        $profileId = $this->user()?->profile()->value('id');

        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('profile_skills', 'name')
                    ->where('profile_id', $profileId)
                    ->ignore($this->route('skill'), 'id'),
            ],
            'category' => ['nullable', 'string', 'max:255'],
            'proficiency' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'This skill is already recorded on your Career Profile.',
        ];
    }
}
