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

#[Temperature(0.0)]
#[MaxTokens(1000)]
final class ClassifyCvSectionsAgent implements Agent, HasProviderOptions, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'PROMPT'
Classify only the supplied candidate CV section headings.

Strict rules:

- Return each heading exactly as supplied. Never invent, omit, or rewrite a heading.
- Classify each heading as professional, experience, skills, projects, education, certifications, or other.
- Return other when uncertain.
- Never classify an employer, organisation name, job title, qualification, project name, or ordinary content line as a section.
- Context is provided only to disambiguate the heading. Never extract or return facts from it.
- Never extract employers, jobs, skills, dates, qualifications, certifications, projects, or any other career facts.
- Do not explain the classifications.
PROMPT;
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'sections' => $schema->array()->items(
                $schema->object(fn (JsonSchema $schema): array => [
                    'heading' => $schema->string()->max(120)->required(),
                    'type' => $schema->string()->enum([
                        'professional',
                        'experience',
                        'skills',
                        'projects',
                        'education',
                        'certifications',
                        'other',
                    ])->required(),
                ])
            )->required(),
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
