<?php

namespace App\Services\Profile;

use Illuminate\Support\Str;

/**
 * Finds conservative top-level heading candidates without interpreting them.
 */
final class CvHeadingCandidateExtractor
{
    /**
     * @return list<array{heading: string, context: string}>
     */
    public function extract(string $sourceText): array
    {
        $lines = preg_split('/\R/u', $sourceText) ?: [];
        $candidates = [];

        foreach ($lines as $index => $line) {
            $heading = trim($line);

            if (! $this->looksLikeHeading($heading)) {
                continue;
            }

            $nextIsBlank = isset($lines[$index + 1]) && trim($lines[$index + 1]) === '';

            if (! $nextIsBlank) {
                continue;
            }

            $context = $this->nextContentLine($lines, $index + 1);

            if ($context === null) {
                continue;
            }

            $previousIsBlank = $index > 0 && trim($lines[$index - 1]) === '';
            $startsDocumentSection = $index === 0 && $this->hasStrongSectionContext($context);

            if (! $previousIsBlank && ! $startsDocumentSection) {
                continue;
            }

            $candidates[] = [
                'heading' => $heading,
                'context' => Str::limit($context, 160, ''),
            ];
        }

        return $candidates;
    }

    private function looksLikeHeading(string $line): bool
    {
        if ($line === '' || mb_strlen($line) > 80 || str_word_count($line) > 8) {
            return false;
        }

        if (preg_match('/^(?:[-*•▪◦]|\d+[.)])\s+/u', $line) === 1
            || preg_match('/(?:https?:\/\/|www\.|\S+@\S+)/iu', $line) === 1
            || preg_match('/\+?\d[\d\s().-]{7,}\d/u', $line) === 1
            || preg_match('/\b(?:19|20)\d{2}\b/u', $line) === 1) {
            return false;
        }

        if (preg_match('/[|]/u', $line) === 1
            || substr_count($line, ',') > 1
            || preg_match('/[.!?;]$/u', $line) === 1) {
            return false;
        }

        if (preg_match('/\b(?:co(?:mpany)?|corp(?:oration)?|inc(?:orporated)?|llc|ltd|limited|plc)\.?$/iu', $line) === 1) {
            return false;
        }

        return preg_match('/\b(?:administrator|analyst|architect|assistant|consultant|coordinator|developer|director|engineer|intern|manager|officer|specialist)\b\s*:?[\s#*_]*$/iu', $line) !== 1;
    }

    private function hasStrongSectionContext(string $line): bool
    {
        return preg_match('/[|]/u', $line) === 1
            || preg_match('/\b(?:19|20)\d{2}\b/u', $line) === 1
            || preg_match('/^[^:]{1,40}:\s*\S/u', $line) === 1;
    }

    /**
     * @param  list<string>  $lines
     */
    private function nextContentLine(array $lines, int $start): ?string
    {
        for ($index = $start; $index < count($lines); $index++) {
            $line = trim($lines[$index]);

            if ($line !== '') {
                return $line;
            }
        }

        return null;
    }
}
