<?php

namespace App\Services\CV;

use JsonException;
use UnexpectedValueException;

final class CVJsonParser
{
    /** @return array<string, mixed> */
    public function parse(string $response): array
    {
        $json = trim($response);

        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/is', $json, $matches) === 1) {
            $json = $matches[1];
        }

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new UnexpectedValueException('The AI response is not valid JSON: '.$exception->getMessage(), previous: $exception);
        }

        if (! is_array($decoded) || array_is_list($decoded)) {
            throw new UnexpectedValueException('The AI response must be a JSON object.');
        }

        return $decoded;
    }
}
