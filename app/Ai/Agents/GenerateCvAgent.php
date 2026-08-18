<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

final class GenerateCvAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
You generate a professional CV from explicitly labelled JSON context supplied by the application.

Use only facts present in the supplied profile, target_job, experience, education, skills, projects, languages, certifications, and references context. Never invent or infer employment, education, certifications, projects, skills, dates, contact details, personal information, employers, qualifications, or achievements. Preserve factual meaning while improving clarity and professional wording. Use null for unknown optional values, false for an unknown currently_working value, and an empty array for a section with no supplied facts.
INSTRUCTIONS;
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->max(255)->required(),
            'summary' => $schema->string()->nullable()->required(),
            'skills' => $schema->array()->items(
                $schema->object(fn (JsonSchema $schema): array => [
                    'category' => $schema->string()->max(255)->nullable()->required(),
                    'name' => $schema->string()->max(255)->required(),
                    'proficiency' => $schema->string()->max(255)->nullable()->required(),
                ])
            )->required(),
            'experience' => $schema->array()->items(
                $schema->object(fn (JsonSchema $schema): array => [
                    'company' => $schema->string()->max(255)->required(),
                    'job_title' => $schema->string()->max(255)->required(),
                    'employment_type' => $schema->string()->max(255)->nullable()->required(),
                    'location' => $schema->string()->max(255)->nullable()->required(),
                    'start_date' => $schema->string()->format('date')->required(),
                    'end_date' => $schema->string()->format('date')->nullable()->required(),
                    'currently_working' => $schema->boolean()->required(),
                    'description' => $schema->string()->nullable()->required(),
                ])
            )->required(),
            'education' => $schema->array()->items(
                $schema->object(fn (JsonSchema $schema): array => [
                    'institution' => $schema->string()->max(255)->required(),
                    'qualification' => $schema->string()->max(255)->required(),
                    'field_of_study' => $schema->string()->max(255)->nullable()->required(),
                    'grade' => $schema->string()->max(255)->nullable()->required(),
                    'start_date' => $schema->string()->format('date')->nullable()->required(),
                    'end_date' => $schema->string()->format('date')->nullable()->required(),
                    'description' => $schema->string()->nullable()->required(),
                ])
            )->required(),
            'projects' => $schema->array()->items(
                $schema->object(fn (JsonSchema $schema): array => [
                    'title' => $schema->string()->max(255)->required(),
                    'description' => $schema->string()->nullable()->required(),
                    'technologies' => $schema->array()->items($schema->string())->nullable()->required(),
                    'github_url' => $schema->string()->max(255)->nullable()->required(),
                    'demo_url' => $schema->string()->max(255)->nullable()->required(),
                    'start_date' => $schema->string()->format('date')->nullable()->required(),
                    'end_date' => $schema->string()->format('date')->nullable()->required(),
                ])
            )->required(),
            'languages' => $schema->array()->items(
                $schema->object(fn (JsonSchema $schema): array => [
                    'language' => $schema->string()->max(255)->required(),
                    'proficiency' => $schema->string()->max(255)->nullable()->required(),
                ])
            )->required(),
            'certifications' => $schema->array()->items(
                $schema->object(fn (JsonSchema $schema): array => [
                    'name' => $schema->string()->max(255)->required(),
                    'organisation' => $schema->string()->max(255)->nullable()->required(),
                    'issue_date' => $schema->string()->format('date')->nullable()->required(),
                    'expiry_date' => $schema->string()->format('date')->nullable()->required(),
                    'credential_id' => $schema->string()->max(255)->nullable()->required(),
                    'credential_url' => $schema->string()->max(255)->nullable()->required(),
                ])
            )->required(),
            'references' => $schema->array()->items(
                $schema->object(fn (JsonSchema $schema): array => [
                    'name' => $schema->string()->max(255)->required(),
                    'company' => $schema->string()->max(255)->nullable()->required(),
                    'job_title' => $schema->string()->max(255)->nullable()->required(),
                    'email' => $schema->string()->max(255)->nullable()->required(),
                    'phone' => $schema->string()->max(255)->nullable()->required(),
                    'relationship' => $schema->string()->max(255)->nullable()->required(),
                ])
            )->required(),
        ];
    }
}
