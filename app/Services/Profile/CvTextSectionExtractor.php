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
     * @param  list<string>  $additionalBoundaries
     */
    public function without(string $sourceText, array $headings, array $additionalBoundaries = []): string
    {
        $excludedHeadings = array_map($this->normalizeHeading(...), $headings);
        $boundaryHeadings = array_map($this->normalizeHeading(...), $additionalBoundaries);
        $lines = preg_split('/\R/u', $sourceText) ?: [];
        $result = [];
        $excluding = false;

        foreach ($lines as $line) {
            $heading = $this->recognizedHeading($line)
                ?? $this->matchingHeading($line, $boundaryHeadings);

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

    /**
     * Extract a section headed by an exact source heading, stopping at any
     * deterministic or supplied candidate heading.
     *
     * @param  list<string>  $additionalBoundaries
     */
    public function extractFromHeading(string $sourceText, string $targetHeading, array $additionalBoundaries = []): ?string
    {
        $targetHeading = $this->normalizeHeading($targetHeading);
        $boundaryHeadings = array_map($this->normalizeHeading(...), $additionalBoundaries);
        $lines = preg_split('/\R/u', $sourceText) ?: [];
        $sectionLines = [];
        $inSection = false;

        foreach ($lines as $line) {
            $normalizedLine = $this->normalizeHeading($line);
            $heading = $this->recognizedHeading($line)
                ?? $this->matchingHeading($line, $boundaryHeadings);

            if (! $inSection && $normalizedLine === $targetHeading) {
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

    private function recognizedHeading(string $line): ?string
    {
        $heading = $this->normalizeHeading($line);

        return in_array($heading, self::TOP_LEVEL_HEADINGS, true) ? $heading : null;
    }

    /** @param list<string> $headings */
    private function matchingHeading(string $line, array $headings): ?string
    {
        $heading = $this->normalizeHeading($line);

        return in_array($heading, $headings, true) ? $heading : null;
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
