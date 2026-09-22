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

/**
 * Extracts proposed career facts from an existing CV supplied by its owner.
 *
 * Everything this agent returns is a proposal for the user to review. The
 * application never writes extracted content to a career profile without
 * explicit approval.
 */
#[Temperature(0.1)]
#[MaxTokens(2000)]
final class ImportCvAgent implements Agent, HasProviderOptions, HasStructuredOutput
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): string
    {
        return <<<'PROMPT'
You extract factual career information from CV text.

Strict rules:

- Never invent or infer unsupported facts.
- If a nullable value is not explicitly present in the CV, return null.
- Never invent dates.
- Never invent certifications or qualifications.
- Never infer a certification from a technology, employer, or cloud platform mentioned in the CV.
- Education records must come only from explicit education or qualification content.
- If education dates are not explicitly present in the CV, return null for start_date and end_date.
- Do not infer education dates from employment dates, age, graduation assumptions, or chronology.
- Only create a certification when the CV explicitly names a certification or credential.
- Do not convert technology experience into a certification.
- For example, experience using AWS does not mean the person is AWS Certified.
- Preserve facts in their original semantic section. Do not move information between education, projects, and certifications simply because words overlap between those sections.

Projects:

- Only extract projects from an explicit Projects, Personal Projects, Portfolio Projects, or equivalent section of the CV.
- Do not turn systems, applications, platforms, integrations, responsibilities, achievements, or products mentioned inside work experience into separate project records.
- Work completed for an employer must remain evidence within that work experience record unless the CV explicitly presents it separately as a project.
- If the CV contains no explicit project section, return an empty projects array.
- Never invent project names.

CV section boundaries:

- Respect explicit section headings in the source CV.
- Content under "Education" belongs to education.
- Never classify a company or job as education merely because its company description contains words such as "Education", "Education Technology", "University", "Training", or "Software Provider".
- A job title such as "Full Stack Developer" can never be an education qualification.

Accuracy is more important than completeness.
When uncertain about an individual nullable field, return null. When uncertain whether an entire certification, education record, or project is genuinely supported by the CV, omit that item rather than guessing.
PROMPT;
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'professional' => $schema->object(fn (JsonSchema $schema): array => [
                'first_name' => $schema->string()->max(255)->nullable()->required(),
                'last_name' => $schema->string()->max(255)->nullable()->required(),
                'headline' => $schema->string()->max(255)->nullable()->required(),
                'summary' => $schema->string()->nullable()->required(),
                'location' => $schema->string()->max(255)->nullable()->required(),
                'phone' => $schema->string()->max(50)->nullable()->required(),
                'website' => $schema->string()->max(255)->nullable()->required(),
                'linkedin_url' => $schema->string()->max(255)->nullable()->required(),
                'github_url' => $schema->string()->max(255)->nullable()->required(),
            ])->required(),
            'projects' => $schema->array()->items(
                $schema->object(fn (JsonSchema $schema): array => [
                    'name' => $schema->string()->max(255)->required(),
                    'role' => $schema->string()->max(255)->nullable()->required(),
                    'description' => $schema->string()->nullable()->required(),
                    'outcomes' => $schema->string()->nullable()->required(),
                    'technologies' => $schema->array()->items($schema->string())->required(),
                    'url' => $schema->string()->max(255)->nullable()->required(),
                    'repository_url' => $schema->string()->max(255)->nullable()->required(),
                    'start_date' => $schema->string()->nullable()->required(),
                    'end_date' => $schema->string()->nullable()->required(),
                ])
            )->required(),
            'education' => $schema->array()->items(
                $schema->object(fn (JsonSchema $schema): array => [
                    'institution' => $schema->string()->max(255)->required(),
                    'qualification' => $schema->string()->max(255)->required(),
                    'subject' => $schema->string()->max(255)->nullable()->required(),
                    'grade' => $schema->string()->max(255)->nullable()->required(),
                    'start_date' => $schema->string()->nullable()->required(),
                    'end_date' => $schema->string()->nullable()->required(),
                ])
            )->required(),
            'certifications' => $schema->array()->items(
                $schema->object(fn (JsonSchema $schema): array => [
                    'name' => $schema->string()->max(255)->required(),
                    'organisation' => $schema->string()->max(255)->nullable()->required(),
                    'issue_date' => $schema->string()->nullable()->required(),
                    'expiry_date' => $schema->string()->nullable()->required(),
                    'credential_id' => $schema->string()->max(255)->nullable()->required(),
                    'credential_url' => $schema->string()->max(255)->nullable()->required(),
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
