<?php

namespace App\Services\AI;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

final class AIService
{
    public function __construct(
        private readonly Container $container,
        private readonly ResponseParser $parser,
    ) {}

    public function provider(?string $name = null): AIProviderInterface
    {
        $name ??= (string) config('ai.default_provider', 'openai');
        $driver = config("ai.providers.{$name}.driver");

        if (! is_string($driver) || $driver === '') {
            throw new InvalidArgumentException("AI provider [{$name}] is not configured.");
        }

        $provider = $this->container->make($driver);
        if (! $provider instanceof AIProviderInterface) {
            throw new InvalidArgumentException("AI provider [{$name}] must implement ".AIProviderInterface::class.'.');
        }

        return $provider;
    }

    /** @return array{content: string, model: string, provider: string, prompt_tokens: int, completion_tokens: int, total_tokens: int, processing_time: int, estimated_cost: float} */
    public function generate(string $prompt, array $options = [], ?string $providerName = null): array
    {
        $provider = $this->provider($providerName);
        $startedAt = hrtime(true);
        $response = $provider->generate($prompt, $options);
        $processingTime = (int) round((hrtime(true) - $startedAt) / 1_000_000);

        return $this->parser->parse($response, $provider, $processingTime);
    }
}
