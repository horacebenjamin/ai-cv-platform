<?php

namespace Tests\Fakes;

use App\Services\AI\AIProviderInterface;
use RuntimeException;

class FakeAIProvider implements AIProviderInterface
{
    public static bool $shouldFail = false;

    /** @var array<string, mixed> */
    public static array $response = [];

    public function generate(string $prompt, array $options = []): array
    {
        if (self::$shouldFail) {
            throw new RuntimeException('Simulated provider failure.');
        }

        return self::$response ?: [
            'output_text' => 'Generated CV content',
            'model' => 'fake-model',
            'provider' => 'testing',
            'usage' => ['input_tokens' => 600, 'output_tokens' => 400, 'total_tokens' => 1000],
        ];
    }

    public function healthCheck(): bool
    {
        return ! self::$shouldFail;
    }

    public function estimateCost(int $promptTokens, int $completionTokens): float
    {
        return round(($promptTokens * 0.000001) + ($completionTokens * 0.000002), 6);
    }

    public function modelName(): string
    {
        return 'fake-model';
    }
}
