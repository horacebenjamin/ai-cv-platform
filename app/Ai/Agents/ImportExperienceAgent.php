<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

#[Temperature(0.1)]
#[MaxTokens(2000)]
final class ImportExperienceAgent implements Agent, HasProviderOptions, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'PROMPT'
Classify and extract one candidate timeline entry from CV work-history text.

Strict rules:

- Classify entry_type as employment, internship, volunteering, career_break, or other.
- Employment, internships, and volunteering may be returned as experience proposals.
- Career breaks and unrelated timeline entries must be classified accurately instead of forced into employment.
- Use only facts explicitly supported by the supplied text.
- Never invent or infer employers, job titles, dates, locations, employment types, achievements, or technologies.
- Return null for an optional scalar value that is not supported by the text.
- Return an empty array for achievements or technologies when none are supported.
- Never infer dates from another role.
- Never turn a responsibility into a technology unless the technology is explicitly named.
- For employment-like entries, return null for job_title or company when either is unsupported so the application can skip the entry.
- Return dates using only the precision present in the CV: YYYY, YYYY-MM, or YYYY-MM-DD.
- Never create a placeholder employer such as "N/A", "Unknown", or "Not provided".
- For career_break or other, leave employment fields null and return empty achievements and technologies.

Accuracy is more important than completeness.
PROMPT;
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'entry_type' => $schema->string()->enum([
                'employment',
                'internship',
                'volunteering',
                'career_break',
                'other',
            ])->required(),
            'job_title' => $schema->string()->max(255)->nullable()->required(),
            'company' => $schema->string()->max(255)->nullable()->required(),
            'location' => $schema->string()->max(255)->nullable()->required(),
            'employment_type' => $schema->string()->max(255)->nullable()->required(),
            'start_date' => $schema->string()->nullable()->required(),
            'end_date' => $schema->string()->nullable()->required(),
            'currently_employed' => $schema->boolean()->required(),
            'summary' => $schema->string()->nullable()->required(),
            'achievements' => $schema->array()->items($schema->string())->required(),
            'technologies' => $schema->array()->items($schema->string())->required(),
        ];
    }

    public function providerOptions(Lab|string $provider): array
    {
        if ($provider === Lab::Ollama || $provider === 'ollama') {
            return [
                'think' => false,
            ];
        }

        return [];
    }
}
