<?php

namespace App\Services\Profile;

/**
 * Divides an isolated work-history section into conservative candidate blocks.
 */
final class ExperienceBlockSplitter
{
    /**
     * @return list<string>
     */
    public function split(string $sectionText): array
    {
        $sectionText = trim(str_replace(["\r\n", "\r"], "\n", $sectionText));

        if ($sectionText === '') {
            return [];
        }

        $paragraphs = preg_split('/\n[ \t]*\n+/u', $sectionText) ?: [];
        $paragraphs = array_values(array_filter(array_map(
            fn (string $paragraph): string => trim($paragraph, "\n"),
            $paragraphs,
        ), fn (string $paragraph): bool => trim($paragraph) !== ''));
        $fragments = [];

        foreach ($paragraphs as $paragraph) {
            array_push($fragments, ...$this->splitParagraphAtDateBoundaries($paragraph));
        }

        if (! collect($fragments)->contains($this->containsDateRange(...))) {
            return $this->splitWithoutDates($fragments);
        }

        return $this->groupAroundDatedHeaders($fragments);
    }

    /**
     * @param  list<string>  $fragments
     * @return list<string>
     */
    private function groupAroundDatedHeaders(array $fragments): array
    {
        $blocks = [];
        $current = [];
        $pending = [];
        $currentHasDate = false;

        foreach ($fragments as $fragment) {
            if (! $this->containsDateRange($fragment)) {
                $pending[] = $fragment;

                continue;
            }

            if (! $currentHasDate) {
                $current = [...$pending, $fragment];
                $pending = [];
                $currentHasDate = true;

                continue;
            }

            [$body, $nextHeader] = $this->partitionPendingParagraphs($pending);
            $current = [...$current, ...$body];
            $this->appendBlock($blocks, $current);
            $current = [...$nextHeader, $fragment];
            $pending = [];
        }

        $current = [...$current, ...$pending];
        $this->appendBlock($blocks, $current);

        return $blocks;
    }

    /**
     * @param  list<string>  $paragraphs
     * @return list<string>
     */
    private function splitWithoutDates(array $paragraphs): array
    {
        $blocks = [];
        $current = [];

        foreach ($paragraphs as $paragraph) {
            $previous = $current[array_key_last($current)] ?? null;

            if ($current !== []
                && is_string($previous)
                && $this->isBulletParagraph($previous)
                && $this->isLikelyHeaderParagraph($paragraph)) {
                $this->appendBlock($blocks, $current);
                $current = [];
            }

            $current[] = $paragraph;
        }

        $this->appendBlock($blocks, $current);

        return $blocks;
    }

    /**
     * @return list<string>
     */
    private function splitParagraphAtDateBoundaries(string $paragraph): array
    {
        $lines = explode("\n", $paragraph);
        $dateIndexes = [];

        foreach ($lines as $index => $line) {
            if ($this->containsDateRange($line)) {
                $dateIndexes[] = $index;
            }
        }

        if (count($dateIndexes) < 2) {
            return [$paragraph];
        }

        $boundaries = [0];

        foreach (array_slice($dateIndexes, 1) as $dateIndex) {
            $boundary = $dateIndex;

            if ($this->isDateOnlyLine($lines[$dateIndex])) {
                $scanned = 0;

                while ($boundary > 0 && $scanned < 3 && $this->isLikelyHeaderLine($lines[$boundary - 1])) {
                    $boundary--;
                    $scanned++;
                }
            }

            if ($boundary > end($boundaries)) {
                $boundaries[] = $boundary;
            }
        }

        $fragments = [];

        foreach ($boundaries as $index => $boundary) {
            $end = $boundaries[$index + 1] ?? count($lines);
            $fragment = trim(implode("\n", array_slice($lines, $boundary, $end - $boundary)));

            if ($fragment !== '') {
                $fragments[] = $fragment;
            }
        }

        return $fragments;
    }

    /**
     * @param  list<string>  $pending
     * @return array{list<string>, list<string>}
     */
    private function partitionPendingParagraphs(array $pending): array
    {
        $headerStart = count($pending);
        $scanned = 0;

        for ($index = count($pending) - 1; $index >= 0 && $scanned < 3; $index--) {
            if (! $this->isLikelyHeaderParagraph($pending[$index])) {
                break;
            }

            $headerStart = $index;
            $scanned++;
        }

        return [
            array_slice($pending, 0, $headerStart),
            array_slice($pending, $headerStart),
        ];
    }

    private function containsDateRange(string $text): bool
    {
        return preg_match($this->dateRangePattern(), $text) === 1;
    }

    private function isDateOnlyLine(string $line): bool
    {
        if (preg_match($this->dateRangePattern(), $line, $matches, PREG_OFFSET_CAPTURE) !== 1) {
            return false;
        }

        [$match, $offset] = $matches[0];
        $before = mb_substr($line, 0, $offset);
        $after = mb_substr($line, $offset + mb_strlen($match));
        $after = preg_replace('/^[\s·|,;:()\-–—]*(?:\d+\s*(?:yrs?|years?|mos?|months?))?[\s·|,;:()\-–—]*$/iu', '', $after) ?? $after;

        return trim($before, " \t|·,;:()\-–—") === '' && trim($after) === '';
    }

    private function dateRangePattern(): string
    {
        $month = '(?:jan(?:uary)?|feb(?:ruary)?|mar(?:ch)?|apr(?:il)?|may|jun(?:e)?|jul(?:y)?|aug(?:ust)?|sep(?:t(?:ember)?)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?)\.?';
        $endpoint = "(?:{$month}\s+\d{4}|\d{1,2}[\/.]\d{4}|\d{4})";

        return "/(?<!\d){$endpoint}\s*(?:-|–|—|to)\s*(?:present|current|now|{$endpoint})(?!\d)/iu";
    }

    private function isBulletParagraph(string $paragraph): bool
    {
        $lines = preg_split('/\R/u', $paragraph) ?: [];

        return collect($lines)->contains(fn (string $line): bool => $this->isBulletLine($line));
    }

    private function isLikelyHeaderParagraph(string $paragraph): bool
    {
        $lines = preg_split('/\R/u', $paragraph) ?: [];

        return $lines !== []
            && count($lines) <= 3
            && collect($lines)->every(fn (string $line): bool => $this->isLikelyHeaderLine($line));
    }

    private function isLikelyHeaderLine(string $line): bool
    {
        $line = trim($line);

        if ($line === '' || mb_strlen($line) > 120 || $this->isBulletLine($line)) {
            return false;
        }

        if (preg_match('/^(?:responsibilities|achievements|duties|key achievements)\s*:/iu', $line) === 1) {
            return false;
        }

        if (preg_match('/^(?:built|created|delivered|designed|developed|implemented|improved|integrated|led|maintained|managed|migrated|reduced|supported|worked)\b/iu', $line) === 1) {
            return false;
        }

        if (preg_match('/[!?;:]$/u', $line) === 1) {
            return false;
        }

        return preg_match('/\.$/u', $line) !== 1
            || preg_match('/\b(?:co|inc|ltd)\.$/iu', $line) === 1;
    }

    private function isBulletLine(string $line): bool
    {
        return preg_match('/^\s*(?:[-*•▪◦]|\d+[.)])\s+/u', $line) === 1;
    }

    /**
     * @param  list<string>  $blocks
     * @param  list<string>  $parts
     */
    private function appendBlock(array &$blocks, array $parts): void
    {
        $block = trim(implode("\n\n", $parts));

        if ($block !== '') {
            $blocks[] = $block;
        }
    }
}
