<?php

namespace App\Services\AI;

interface AIProviderInterface
{
    /** @return array<string, mixed> */
    public function generate(string $prompt, array $options = []): array;

    public function healthCheck(): bool;

    public function estimateCost(int $promptTokens, int $completionTokens): float;

    public function modelName(): string;
}
