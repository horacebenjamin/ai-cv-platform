<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProfileImportRequest extends FormRequest
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
        return [
            'source_text' => ['required', 'string', 'min:80', 'max:50000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'source_text.required' => 'Paste the text of your existing CV to import it.',
            'source_text.min' => 'Paste more of your CV so its facts can be read reliably.',
            'source_text.max' => 'This CV text is too long to import. Paste the relevant sections instead.',
        ];
    }
}
