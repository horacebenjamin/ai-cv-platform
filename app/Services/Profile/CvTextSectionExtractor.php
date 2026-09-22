<?php

namespace App\Services\Profile;

use Illuminate\Support\Str;

/**
 * Locates explicit top-level sections in pasted CV text.
 */
final class CvTextSectionExtractor
{
    private const TOP_LEVEL_HEADINGS = [
        'awards',
        'career history',
        'certification',
        'certifications',
        'contact',
        'contact details',
        'core skills',
        'credentials',
        'education',
        'education and qualifications',
        'employment',
        'employment history',
        'experience',
        'interests',
        'key skills',
        'languages',
        'licenses and certifications',
        'personal details',
        'personal profile',
        'personal projects',
        'portfolio',
        'portfolio projects',
        'professional certifications',
        'professional experience',
        'professional history',
        'professional profile',
        'professional summary',
        'projects',
        'qualifications',
        'references',
        'skills',
        'summary',
        'technical skills',
        'training',
        'work experience',
        'work history',
    ];

    /**
     * @param  list<string>  $headings
     */
    public function extract(string $sourceText, array $headings): ?string
    {
        $targetHeadings = array_map($this->normalizeHeading(...), $headings);
        $lines = preg_split('/\R/u', $sourceText) ?: [];
        $sectionLines = [];
        $inSection = false;

        foreach ($lines as $line) {
            $heading = $this->recognizedHeading($line);

            if ($heading !== null && in_array($heading, $targetHeadings, true)) {
                $inSection = true;

                continue;
            }

            if ($inSection && $heading !== null) {
                break;
            }

            if ($inSection) {
                $sectionLines[] = $line;
            }
        }

        return $inSection ? trim(implode("\n", $sectionLines)) : null;
    }

    /**
     * Remove selected explicit top-level sections while preserving all other text.
     *
     * @param  list<string>  $headings
     */
    public function without(string $sourceText, array $headings): string
    {
        $excludedHeadings = array_map($this->normalizeHeading(...), $headings);
        $lines = preg_split('/\R/u', $sourceText) ?: [];
        $result = [];
        $excluding = false;

        foreach ($lines as $line) {
            $heading = $this->recognizedHeading($line);

            if ($heading !== null) {
                $excluding = in_array($heading, $excludedHeadings, true);

                if (! $excluding) {
                    $result[] = $line;
                }

                continue;
            }

            if (! $excluding) {
                $result[] = $line;
            }
        }

        return trim(implode("\n", $result));
    }

    public function hasRecognizedHeading(string $sourceText): bool
    {
        $lines = preg_split('/\R/u', $sourceText) ?: [];

        foreach ($lines as $line) {
            if ($this->recognizedHeading($line) !== null) {
                return true;
            }
        }

        return false;
    }

    private function recognizedHeading(string $line): ?string
    {
        $heading = $this->normalizeHeading($line);

        return in_array($heading, self::TOP_LEVEL_HEADINGS, true) ? $heading : null;
    }

    private function normalizeHeading(string $line): string
    {
        return (string) Str::of($line)
            ->trim()
            ->trim('#*_')
            ->trim()
            ->trim(':')
            ->squish()
            ->lower();
    }
}
