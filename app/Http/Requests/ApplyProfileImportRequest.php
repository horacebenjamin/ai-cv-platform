<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class ApplyProfileImportRequest extends FormRequest
{
    /** Collections a user may approve from an import. */
    private const COLLECTIONS = ['experiences', 'skills', 'projects', 'education', 'certifications'];

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
        $rules = [
            'professional' => ['nullable', 'array', 'max:20'],
            'professional.*' => ['required', 'string', 'max:50'],
        ];

        foreach (self::COLLECTIONS as $collection) {
            $rules[$collection] = ['nullable', 'array', 'max:200'];
            $rules["{$collection}.*"] = ['required', 'integer', 'min:0'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $selections = array_filter([
                $this->input('professional', []),
                ...array_map(fn (string $collection): mixed => $this->input($collection, []), self::COLLECTIONS),
            ], static fn (mixed $selection): bool => is_array($selection) && $selection !== []);

            if ($selections === []) {
                $validator->errors()->add('selections', 'Select at least one item to add to your Career Profile.');
            }
        });
    }

    /**
     * The approved selections, keyed by section.
     *
     * @return array<string, list<int>|list<string>>
     */
    public function selections(): array
    {
        $selections = ['professional' => array_values(array_map('strval', (array) $this->validated('professional', [])))];

        foreach (self::COLLECTIONS as $collection) {
            $selections[$collection] = array_values(array_map('intval', (array) $this->validated($collection, [])));
        }

        return $selections;
    }
}
