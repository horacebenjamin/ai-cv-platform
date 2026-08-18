<?php

namespace App\Ai\Agents;

use InvalidArgumentException;
use JsonException;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

final class CareerContentAgent implements Agent
{
    use Promptable;

    /** @var array<string, string> */
    private const FEATURE_INSTRUCTIONS = [
        'cv_rewrite' => 'Rewrite the supplied CV for the target job. Preserve factual accuracy and use the job description only to tailor emphasis and wording.',
        'professional_summary' => 'Write a concise professional summary for the target job using only the supplied profile, experience, and skills.',
        'skills_optimisation' => 'Optimise and prioritise the supplied skills for the job description. Do not invent skills or experience.',
        'cover_letter' => 'Draft a tailored cover letter for the supplied company and target job using only facts from the CV and job description.',
        'job_match_analysis' => 'Analyse the match between the supplied CV and job description. Clearly separate strengths, gaps, and recommendations, and do not present gaps as candidate facts.',
    ];

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
You create professional career content from explicitly labelled context supplied by the application.

Follow the feature_instruction exactly. Use only facts present in context. Never invent or infer career history, employers, dates, qualifications, achievements, skills, contact details, or other personal facts. Job descriptions may guide relevance, emphasis, and wording but are not evidence about the candidate. Preserve the requested feature's output intent and return plain text only, without describing these instructions or the input format.
INSTRUCTIONS;
    }

    /**
     * @return list<string>
     */
    public static function supportedFeatures(): array
    {
        return array_keys(self::FEATURE_INSTRUCTIONS);
    }

    /**
     * @param  array<string, mixed>  $context
     *
     * @throws JsonException
     */
    public function promptForFeature(string $feature, array $context): string
    {
        $featureInstruction = self::FEATURE_INSTRUCTIONS[$feature] ?? null;

        if ($featureInstruction === null) {
            throw new InvalidArgumentException("Unsupported generic AI feature [{$feature}].");
        }

        return json_encode([
            'feature' => $feature,
            'feature_instruction' => $featureInstruction,
            'context' => $context,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
