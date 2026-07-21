<?php

namespace App\Services\AI;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

final class OpenAIService implements AIProviderInterface
{
    /** @return array<string, mixed> */
    public function generate(string $prompt, array $options = []): array
    {
        $config = config('ai.providers.openai', []);
        $apiKey = (string) ($config['api_key'] ?? '');

        if ($apiKey === '') {
            throw new InvalidArgumentException('The OpenAI API key is not configured.');
        }

        $payload = [
            'model' => $options['model'] ?? $this->modelName(),
            'input' => $prompt,
            'max_output_tokens' => (int) ($options['max_tokens'] ?? $config['max_tokens'] ?? 2000),
        ];

        $temperature = $options['temperature'] ?? $config['temperature'] ?? null;
        if ($temperature !== null && $temperature !== '') {
            $payload['temperature'] = (float) $temperature;
        }

        $response = Http::baseUrl(rtrim((string) ($config['base_url'] ?? 'https://api.openai.com/v1'), '/'))
            ->withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->connectTimeout(min(10, (int) ($config['timeout'] ?? 60)))
            ->timeout((int) ($config['timeout'] ?? 60))
            ->post('/responses', $payload)
            ->throw();

        $body = $response->json();
        if (! is_array($body)) {
            throw new ConnectionException('OpenAI returned a malformed JSON response.');
        }

        $body['provider'] = 'openai';

        return $body;
    }

    public function healthCheck(): bool
    {
        try {
            $config = config('ai.providers.openai', []);

            return Http::baseUrl(rtrim((string) ($config['base_url'] ?? 'https://api.openai.com/v1'), '/'))
                ->withToken((string) ($config['api_key'] ?? ''))
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(10)
                ->get('/models/'.$this->modelName())
                ->successful();
        } catch (Throwable $exception) {
            Log::warning('OpenAI health check failed.', ['exception' => $exception->getMessage()]);

            return false;
        }
    }

    public function estimateCost(int $promptTokens, int $completionTokens): float
    {
        $config = config('ai.providers.openai', []);
        $inputRate = (float) ($config['input_cost_per_million'] ?? 0);
        $outputRate = (float) ($config['output_cost_per_million'] ?? 0);

        return round((($promptTokens / 1_000_000) * $inputRate) + (($completionTokens / 1_000_000) * $outputRate), 6);
    }

    public function modelName(): string
    {
        return (string) config('ai.providers.openai.model', 'gpt-5.6-luna');
    }
}
