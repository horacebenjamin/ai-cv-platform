<?php

namespace App\Services;

use App\Models\Profile;

final class ProfileCompletenessService
{
    private const FIELDS = [
        'first_name' => ['label' => 'First name'],
        'last_name' => ['label' => 'Last name'],
        'headline' => ['label' => 'Professional headline'],
        'phone' => ['label' => 'Phone number'],
        'location' => ['label' => 'Location'],
        'linkedin_url' => ['label' => 'LinkedIn profile'],
        'bio' => ['label' => 'Professional summary'],
    ];

    private const SECTION_AREAS = [
        'professional_identity' => [
            'label' => 'Professional identity',
            'fields' => ['first_name', 'last_name', 'headline', 'location'],
            'minimum_fields' => 4,
        ],
        'professional_summary' => [
            'label' => 'Professional summary',
            'fields' => ['bio'],
            'minimum_fields' => 1,
        ],
        'professional_links' => [
            'label' => 'Professional contact info',
            'fields' => ['website', 'linkedin_url', 'github_url', 'portfolio_url'],
            'minimum_fields' => 1,
        ],
    ];

    /**
     * @return array{
     *     exists: bool,
     *     percentage: int,
     *     completedFields: int,
     *     totalFields: int,
     *     completedAreas: list<array{key: string, label: string}>,
     *     missingAreas: list<array{key: string, label: string}>,
     *     missingFields: list<string>,
     *     sectionCompleteness: array{
     *         attentionCount: int,
     *         summary: string,
     *         areas: list<array{key: string, label: string, status: 'complete'|'incomplete'}>
     *     }
     * }
     */
    public function for(?Profile $profile): array
    {
        $areas = collect(self::FIELDS)->map(function (array $details, string $field) use ($profile): array {
            return [
                'key' => $field,
                'label' => $details['label'],
                'complete' => filled($profile?->{$field}),
            ];
        });
        $completedAreas = $areas
            ->where('complete', true)
            ->map(fn (array $area): array => [
                'key' => $area['key'],
                'label' => $area['label'],
            ])
            ->values()
            ->all();
        $missingAreas = $areas
            ->where('complete', false)
            ->map(fn (array $area): array => [
                'key' => $area['key'],
                'label' => $area['label'],
            ])
            ->values()
            ->all();
        $totalFields = count(self::FIELDS);
        $completedFields = count($completedAreas);
        $percentage = (int) round(($completedFields / $totalFields) * 100);
        $sectionAreas = collect(self::SECTION_AREAS)
            ->map(function (array $details, string $key) use ($profile): array {
                $completedFields = collect($details['fields'])
                    ->filter(fn (string $field): bool => filled($profile?->{$field}))
                    ->count();

                return [
                    'key' => $key,
                    'label' => $details['label'],
                    'status' => $completedFields >= $details['minimum_fields'] ? 'complete' : 'incomplete',
                ];
            })
            ->values()
            ->all();
        $attentionCount = collect($sectionAreas)
            ->where('status', 'incomplete')
            ->count();
        $sectionSummary = match ($attentionCount) {
            0 => 'All profile sections complete',
            1 => '1 section needs attention',
            default => "{$attentionCount} sections need attention",
        };

        return [
            'exists' => $profile !== null,
            'percentage' => $percentage,
            'completedFields' => $completedFields,
            'totalFields' => $totalFields,
            'completedAreas' => $completedAreas,
            'missingAreas' => $missingAreas,
            'missingFields' => array_column($missingAreas, 'label'),
            'sectionCompleteness' => [
                'attentionCount' => $attentionCount,
                'summary' => $sectionSummary,
                'areas' => $sectionAreas,
            ],
        ];
    }
}
