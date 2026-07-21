<?php

namespace App\Services\AI;

use RuntimeException;

final class ResponseParser
{
    /** @param array<string, mixed> $response
     * @return array{content: string, model: string, provider: string, prompt_tokens: int, completion_tokens: int, total_tokens: int, processing_time: int, estimated_cost: float}
     */
    public function parse(array $response, AIProviderInterface $provider, int $processingTime): array
    {
        $content = $response['output_text'] ?? $this->extractOutputText($response['output'] ?? []);
        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('The AI provider returned a malformed response without text content.');
        }

        $usage = is_array($response['usage'] ?? null) ? $response['usage'] : [];
        $promptTokens = (int) ($usage['input_tokens'] ?? $usage['prompt_tokens'] ?? 0);
        $completionTokens = (int) ($usage['output_tokens'] ?? $usage['completion_tokens'] ?? 0);

        return [
            'content' => $content,
            'model' => (string) ($response['model'] ?? $provider->modelName()),
            'provider' => (string) ($response['provider'] ?? config('ai.default_provider', 'openai')),
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => (int) ($usage['total_tokens'] ?? ($promptTokens + $completionTokens)),
            'processing_time' => $processingTime,
            'estimated_cost' => $provider->estimateCost($promptTokens, $completionTokens),
        ];
    }

    private function extractOutputText(mixed $output): string
    {
        if (! is_array($output)) {
            return '';
        }

        $parts = [];
        foreach ($output as $item) {
            foreach (is_array($item['content'] ?? null) ? $item['content'] : [] as $content) {
                if (($content['type'] ?? null) === 'output_text' && is_string($content['text'] ?? null)) {
                    $parts[] = $content['text'];
                }
            }
        }

        return implode("\n", $parts);
    }
}
