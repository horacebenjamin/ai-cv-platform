<?php

namespace App\Services\AI;

final class AIUsageService
{
    /** @return array{prompt_tokens: int, completion_tokens: int, total_tokens: int, estimated_cost: float, credits_consumed: int} */
    public function calculate(AIProviderInterface $provider, int $promptTokens, int $completionTokens): array
    {
        $total = $promptTokens + $completionTokens;
        $tokensPerCredit = max(1, (int) config('ai.credits.tokens_per_credit', 1000));
        $minimum = max(0, (int) config('ai.credits.minimum', 1));

        return [
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $total,
            'estimated_cost' => $provider->estimateCost($promptTokens, $completionTokens),
            'credits_consumed' => $total > 0 ? max($minimum, (int) ceil($total / $tokensPerCredit)) : 0,
        ];
    }
}
