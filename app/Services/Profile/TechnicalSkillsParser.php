<?php

namespace App\Services\Profile;

use Illuminate\Support\Str;

/**
 * Parses explicit technical skill lists without an AI request.
 */
final class TechnicalSkillsParser
{
    private const SECTION_HEADINGS = [
        'technical skills',
        'skills',
        'core skills',
        'key skills',
    ];

    private const CATEGORY_HEADINGS = [
        'backend',
        'cloud',
        'cloud & devops',
        'cloud and devops',
        'database',
        'databases',
        'devops',
        'frameworks',
        'frontend',
        'languages',
        'methodologies',
        'operating systems',
        'platforms',
        'programming languages',
        'testing',
        'tools',
    ];

    public function __construct(private readonly CvTextSectionExtractor $sections) {}

    /**
     * @return list<array{name: string, category: string|null, proficiency: null}>
     */
    public function parse(string $sourceText, bool $allowUnsectioned = true): array
    {
        $sectionText = $this->sections->extract($sourceText, self::SECTION_HEADINGS);

        if ($sectionText === null) {
            if (! $allowUnsectioned || $this->sections->hasRecognizedHeading($sourceText)) {
                return [];
            }

            $sectionText = $sourceText;
        }

        $skills = [];
        $seen = [];
        $category = null;
        $lines = preg_split('/\R/u', $sectionText) ?: [];

        foreach ($lines as $line) {
            $line = $this->cleanLine($line);

            if ($line === '') {
                continue;
            }

            [$lineCategory, $skillList] = $this->categoryAndSkillList($line);

            if ($lineCategory !== null) {
                $category = in_array(Str::lower($lineCategory), self::SECTION_HEADINGS, true)
                    ? null
                    : $lineCategory;

                if ($skillList === '') {
                    continue;
                }
            } elseif ($this->isCategoryHeading($line)) {
                $category = $line;

                continue;
            }

            foreach ($this->splitTopLevelCommas($skillList) as $name) {
                if ($this->isCategoryHeading($name)) {
                    continue;
                }

                $comparisonName = (string) Str::of($name)->squish()->lower();

                if (isset($seen[$comparisonName])) {
                    continue;
                }

                $seen[$comparisonName] = true;
                $skills[] = [
                    'name' => $name,
                    'category' => $category,
                    'proficiency' => null,
                ];
            }
        }

        return $skills;
    }

    private function cleanLine(string $line): string
    {
        $line = preg_replace('/^\s*(?:[-*•▪◦]|\d+[.)])\s+/u', '', $line) ?? $line;

        return (string) Str::of($line)->trim()->squish();
    }

    /**
     * @return array{string|null, string}
     */
    private function categoryAndSkillList(string $line): array
    {
        $characters = mb_str_split($line);
        $depth = 0;

        foreach ($characters as $index => $character) {
            $depth = $this->updatedDepth($character, $depth);

            if ($character !== ':' || $depth !== 0) {
                continue;
            }

            $category = trim(implode('', array_slice($characters, 0, $index)));
            $skillList = trim(implode('', array_slice($characters, $index + 1)));

            if ($this->looksLikeCategory($category)) {
                return [$category, $skillList];
            }
        }

        return [null, $line];
    }

    private function looksLikeCategory(string $value): bool
    {
        return $value !== ''
            && mb_strlen($value) <= 80
            && ! Str::contains($value, ['/', '\\', '.', '#', '+', '(', ')'])
            && ! in_array(Str::lower($value), ['http', 'https'], true);
    }

    /**
     * @return list<string>
     */
    private function splitTopLevelCommas(string $skillList): array
    {
        $skills = [];
        $current = '';
        $depth = 0;

        foreach (mb_str_split($skillList) as $character) {
            if ($character === ',' && $depth === 0) {
                $this->appendSkill($skills, $current);
                $current = '';

                continue;
            }

            $current .= $character;
            $depth = $this->updatedDepth($character, $depth);
        }

        $this->appendSkill($skills, $current);

        return $skills;
    }

    private function updatedDepth(string $character, int $depth): int
    {
        if (in_array($character, ['(', '[', '{'], true)) {
            return $depth + 1;
        }

        if (in_array($character, [')', ']', '}'], true)) {
            return max(0, $depth - 1);
        }

        return $depth;
    }

    /**
     * @param  list<string>  $skills
     */
    private function appendSkill(array &$skills, string $value): void
    {
        $value = trim($value);

        if ($value !== '') {
            $skills[] = $value;
        }
    }

    private function isCategoryHeading(string $value): bool
    {
        return in_array((string) Str::of($value)->trim()->trim(':')->squish()->lower(), self::CATEGORY_HEADINGS, true);
    }
}
