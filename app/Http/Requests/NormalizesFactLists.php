<?php

namespace App\Http\Requests;

/**
 * Accepts list-style career facts as either an array or newline-separated text.
 */
trait NormalizesFactLists
{
    /**
     * @return list<string>
     */
    protected function factList(string $key): array
    {
        $value = $this->input($key);

        if (is_string($value)) {
            $value = preg_split('/\R/u', $value) ?: [];
        }

        if (! is_array($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $item) {
            if (! is_string($item) && ! is_int($item) && ! is_float($item)) {
                continue;
            }

            $item = trim((string) $item);

            if ($item !== '') {
                $items[] = $item;
            }
        }

        return array_values(array_unique($items));
    }
}
