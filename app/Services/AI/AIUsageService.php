<?php

namespace App\Services\AI;

final class AIUsageService
{
    /** @return array{prompt_tokens: int, completion_tokens: int, total_tokens: int, estimated_cost: float, credits_consumed: int} */
    public function calculate(AIProviderInterface|string $provider, int $promptTokens, int $completionTokens, ?string $model = null): array
    {
        $total = $promptTokens + $completionTokens;
        $tokensPerCredit = max(1, (int) config('ai.credits.tokens_per_credit', 1000));
        $minimum = max(0, (int) config('ai.credits.minimum', 1));

        return [
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $total,
            'estimated_cost' => $provider instanceof AIProviderInterface
                ? $provider->estimateCost($promptTokens, $completionTokens)
                : $this->estimateSdkCost($provider, $model, $promptTokens, $completionTokens),
            'credits_consumed' => $total > 0 ? max($minimum, (int) ceil($total / $tokensPerCredit)) : 0,
        ];
    }

    private function estimateSdkCost(string $provider, ?string $model, int $promptTokens, int $completionTokens): float
    {
        $providers = config('ai.providers', []);
        $providerConfig = is_array($providers[$provider] ?? null) ? $providers[$provider] : [];
        $modelConfig = $model === null || ! is_array($providerConfig['pricing']['models'][$model] ?? null)
            ? []
            : $providerConfig['pricing']['models'][$model];
        $inputRate = (float) ($modelConfig['input_cost_per_million'] ?? $providerConfig['input_cost_per_million'] ?? 0);
        $outputRate = (float) ($modelConfig['output_cost_per_million'] ?? $providerConfig['output_cost_per_million'] ?? 0);

        return round((($promptTokens / 1_000_000) * $inputRate) + (($completionTokens / 1_000_000) * $outputRate), 6);
    }
}
