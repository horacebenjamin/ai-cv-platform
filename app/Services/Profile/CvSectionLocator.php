<?php

namespace App\Services\Profile;

use Illuminate\Support\Str;

final class CvSectionLocator
{
    private const PROFESSIONAL_HEADINGS = [
        'personal profile',
        'professional profile',
        'professional summary',
        'summary',
    ];

    private const EXPERIENCE_HEADINGS = [
        'professional experience',
        'work experience',
        'employment',
        'employment history',
        'career history',
        'experience',
        'work history',
        'professional history',
    ];

    private const SKILLS_HEADINGS = [
        'technical skills',
        'skills',
        'core skills',
        'key skills',
    ];

    private const EDUCATION_HEADINGS = [
        'education',
        'qualifications',
        'education and qualifications',
    ];

    private const PROJECT_HEADINGS = [
        'projects',
        'personal projects',
        'portfolio projects',
    ];

    private const CERTIFICATION_HEADINGS = [
        'certification',
        'certifications',
        'credentials',
        'licenses and certifications',
        'professional certifications',
    ];

    public function __construct(
        private readonly CvTextSectionExtractor $sections,
    ) {}

    public function experience(string $sourceText): ?string
    {
        return $this->sections->extract($sourceText, self::EXPERIENCE_HEADINGS);
    }

    public function skills(string $sourceText): ?string
    {
        return $this->sections->extract($sourceText, self::SKILLS_HEADINGS);
    }

    public function education(string $sourceText): ?string
    {
        return $this->sections->extract($sourceText, self::EDUCATION_HEADINGS);
    }

    public function projects(string $sourceText): ?string
    {
        return $this->sections->extract($sourceText, self::PROJECT_HEADINGS);
    }

    public function certifications(string $sourceText): ?string
    {
        return $this->sections->extract($sourceText, self::CERTIFICATION_HEADINGS);
    }

    public function semanticType(string $heading): ?string
    {
        $heading = (string) Str::of($heading)
            ->trim()
            ->trim('#*_')
            ->trim()
            ->trim(':')
            ->squish()
            ->lower();

        return match (true) {
            in_array($heading, self::PROFESSIONAL_HEADINGS, true) => 'professional',
            in_array($heading, self::EXPERIENCE_HEADINGS, true) => 'experience',
            in_array($heading, self::SKILLS_HEADINGS, true) => 'skills',
            in_array($heading, self::EDUCATION_HEADINGS, true) => 'education',
            in_array($heading, self::PROJECT_HEADINGS, true) => 'projects',
            in_array($heading, self::CERTIFICATION_HEADINGS, true) => 'certifications',
            default => null,
        };
    }
}
