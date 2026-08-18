<?php

namespace App\Services\CV;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class CVValidationService
{
    private const SECTIONS = [
        'skills', 'experience', 'education', 'projects', 'languages', 'certifications', 'references',
    ];

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function validate(array $data): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['present', 'nullable', 'string'],
        ];

        foreach (self::SECTIONS as $section) {
            $rules[$section] = ['required', 'array'];
            $rules["{$section}.*"] = ['required', 'array', function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value === []) {
                    $fail("The {$attribute} object must not be empty.");
                }
            }];
        }

        $rules += [
            'skills.*.name' => ['required', 'string', 'max:255'],
            'experience.*.company' => ['required', 'string', 'max:255'],
            'experience.*.job_title' => ['required', 'string', 'max:255'],
            'experience.*.start_date' => ['required', 'date'],
            'education.*.institution' => ['required', 'string', 'max:255'],
            'education.*.qualification' => ['required', 'string', 'max:255'],
            'projects.*.title' => ['required', 'string', 'max:255'],
            'languages.*.language' => ['required', 'string', 'max:255'],
            'certifications.*.name' => ['required', 'string', 'max:255'],
            'references.*.name' => ['required', 'string', 'max:255'],
        ];

        $validator = Validator::make($data, $rules, [
            '*.required' => 'The AI response is missing the :attribute field.',
            '*.array' => 'The :attribute field must be an array.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
