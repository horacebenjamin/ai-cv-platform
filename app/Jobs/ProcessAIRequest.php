<?php

namespace App\Jobs;

use App\Models\AiRequest;
use App\Services\AI\AIRequestService;
use App\Services\AI\AIService;
use App\Services\AI\AIUsageService;
use App\Services\AI\PromptCompiler;
use App\Services\AI\PromptTemplateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class ProcessAIRequest implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public readonly int $aiRequestId) {}

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(
        AIRequestService $requests,
        AIService $ai,
        AIUsageService $usage,
        PromptCompiler $compiler,
        PromptTemplateService $templates,
    ): void {
        $request = AiRequest::query()->findOrFail($this->aiRequestId);

        if ($request->status === 'completed') {
            return;
        }

        $requests->markProcessing($request);

        try {
            $prompt = $this->compilePrompt($request, $compiler, $templates);
            $providerName = (string) config('ai.default_provider', 'openai');
            $provider = $ai->provider($providerName);
            $result = $ai->generate($prompt, ['model' => $request->model], $providerName);
            $calculatedUsage = $usage->calculate($provider, $result['prompt_tokens'], $result['completion_tokens']);
            $result['estimated_cost'] = $calculatedUsage['estimated_cost'];
            $result['total_tokens'] = $calculatedUsage['total_tokens'];

            $requests->complete($request, $result, $calculatedUsage['credits_consumed']);
        } catch (Throwable $exception) {
            Log::warning('AI request attempt failed.', [
                'ai_request_id' => $request->getKey(),
                'attempt' => $this->attempts(),
                'error' => $exception->getMessage(),
            ]);

            if ($this->isNonRetryable($exception)) {
                $requests->fail($request, $exception);
                $this->fail($exception);

                return;
            }

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $request = AiRequest::query()->find($this->aiRequestId);
        if ($request && $request->status !== 'failed') {
            app(AIRequestService::class)->fail($request, $exception ?? new \RuntimeException('AI request exhausted its retries.'));
        }
    }

    private function compilePrompt(AiRequest $request, PromptCompiler $compiler, PromptTemplateService $templates): string
    {
        $payload = json_decode($request->prompt, true);

        if (is_array($payload) && (isset($payload['context']) || isset($payload['template']))) {
            $context = is_array($payload['context'] ?? null) ? $payload['context'] : [];

            return $templates->compile(
                (string) ($payload['template'] ?? $request->feature),
                $context,
                isset($payload['prompt']) ? (string) $payload['prompt'] : $request->prompt,
            );
        }

        return $compiler->compile($request->prompt);
    }

    private function isNonRetryable(Throwable $exception): bool
    {
        return $exception instanceof InvalidArgumentException
            || $exception instanceof RequestException
            && in_array($exception->response->status(), [400, 401, 403, 404, 422], true);
    }
}
