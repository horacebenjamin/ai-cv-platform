<?php

namespace App\Services\AI;

final class PromptCompiler
{
    private const PLACEHOLDERS = [
        'cv', 'job_description', 'company', 'profile', 'target_job', 'skills', 'experience', 'summary',
    ];

    /** @param array<string, mixed> $context */
    public function compile(string $template, array $context = []): string
    {
        $replacements = [];

        foreach (self::PLACEHOLDERS as $placeholder) {
            $replacements['{{'.$placeholder.'}}'] = $this->stringify($context[$placeholder] ?? '');
        }

        return strtr($template, $replacements);
    }

    private function stringify(mixed $value): string
    {
        if (is_string($value) || is_numeric($value)) {
            return (string) $value;
        }

        if ($value === null) {
            return '';
        }

        return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
    }
}
