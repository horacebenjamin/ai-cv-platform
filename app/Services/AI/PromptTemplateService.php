<?php

namespace App\Services\AI;

use InvalidArgumentException;

final class PromptTemplateService
{
    /** @var array<string, string> */
    private const TEMPLATES = [
        'cv_generation' => "Create a professional CV for {{target_job}} using this profile:\n{{profile}}\n\nExperience:\n{{experience}}\n\nSkills:\n{{skills}}",
        'cv_rewrite' => "Rewrite the following CV for {{target_job}} while preserving factual accuracy:\n{{cv}}\n\nJob description:\n{{job_description}}",
        'professional_summary' => "Write a concise professional summary for {{target_job}}.\nProfile: {{profile}}\nExperience: {{experience}}\nSkills: {{skills}}",
        'skills_optimisation' => "Optimise these skills for the target job without inventing experience.\nSkills: {{skills}}\nJob: {{job_description}}",
        'cover_letter' => "Draft a tailored cover letter for {{company}} and {{target_job}}.\nCV: {{cv}}\nJob description: {{job_description}}",
        'job_match_analysis' => "Analyse the match between this CV and job.\nCV: {{cv}}\nJob: {{job_description}}\nReturn strengths, gaps, and recommendations.",
    ];

    public function __construct(private readonly PromptCompiler $compiler) {}

    /** @return array<string, string> */
    public function templates(): array
    {
        return self::TEMPLATES;
    }

    /** @param array<string, mixed> $context */
    public function compile(string $name, array $context = [], ?string $fallback = null): string
    {
        $template = self::TEMPLATES[$name] ?? $fallback;

        if ($template === null) {
            throw new InvalidArgumentException("Unknown prompt template [{$name}].");
        }

        return $this->compiler->compile($template, $context);
    }
}
