<?php

namespace App\Jobs;

use App\Ai\Agents\CareerContentAgent;
use App\Models\AiRequest;
use App\Services\AI\AIRequestService;
use App\Services\AI\AIUsageService;
use App\Services\CV\CVGenerationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use JsonException;
use Laravel\Ai\Responses\TextResponse;
use LogicException;
use RuntimeException;
use Throwable;
use UnexpectedValueException;

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
        AIUsageService $usage,
        CareerContentAgent $careerContent,
        CVGenerationService $cvGeneration,
    ): void {
        $request = AiRequest::query()->findOrFail($this->aiRequestId);

        if ($request->status === 'completed') {
            return;
        }

        if ($request->feature === 'cv_generation') {
            try {
                $cvGeneration->process($request);
            } catch (ValidationException|UnexpectedValueException|InvalidArgumentException $exception) {
                $requests->fail($request, $exception);
                $this->fail($exception);
            }

            return;
        }

        $requests->markProcessing($request);

        try {
            $prompt = $careerContent->promptForFeature($request->feature, $this->genericContext($request));
            $requestedProvider = (string) config('ai.default', 'openai');
            $startedAt = hrtime(true);
            $response = $careerContent->prompt(
                $prompt,
                provider: $requestedProvider,
                model: $request->model,
                timeout: (int) config("ai.providers.{$requestedProvider}.timeout", 60),
            );
            $processingTime = (int) round((hrtime(true) - $startedAt) / 1_000_000);

            if (! $response instanceof TextResponse || trim($response->text) === '') {
                throw new RuntimeException('The career content agent returned an unexpected empty response.');
            }

            $provider = $response->meta->provider ?? $requestedProvider;
            $model = $response->meta->model ?? $request->model;
            $calculatedUsage = $usage->calculate(
                $provider,
                $response->usage->promptTokens,
                $response->usage->completionTokens,
                $model,
            );
            $result = [
                'content' => $response->text,
                'provider' => $provider,
                'model' => $model,
                'prompt_tokens' => $calculatedUsage['prompt_tokens'],
                'completion_tokens' => $calculatedUsage['completion_tokens'],
                'total_tokens' => $calculatedUsage['total_tokens'],
                'estimated_cost' => $calculatedUsage['estimated_cost'],
                'processing_time' => $processingTime,
            ];

            $requests->complete($request, $result, $calculatedUsage['credits_consumed']);
        } catch (Throwable $exception) {
            Log::warning('AI request attempt failed.', [
                'ai_request_id' => $request->getKey(),
                'feature' => $request->feature,
                'attempt' => $this->attempts(),
                'provider' => config('ai.default', 'openai'),
                'model' => $request->model,
                'status' => $request->status,
                'exception' => $exception::class,
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
        if ($request && ! in_array($request->status, ['completed', 'failed'], true)) {
            app(AIRequestService::class)->fail($request, $exception ?? new RuntimeException('AI request exhausted its retries.'));
        }
    }

    /** @return array<string, mixed> */
    private function genericContext(AiRequest $request): array
    {
        $payload = json_decode($request->prompt, true);

        if (! is_array($payload) || (! array_key_exists('context', $payload) && ! array_key_exists('template', $payload))) {
            return ['request' => $request->prompt];
        }

        if (array_key_exists('context', $payload) && ! is_array($payload['context'])) {
            throw new InvalidArgumentException('Generic AI request context must be an object.');
        }

        return $payload['context'] ?? [];
    }

    private function isNonRetryable(Throwable $exception): bool
    {
        return $exception instanceof InvalidArgumentException
            || $exception instanceof JsonException
            || $exception instanceof LogicException
            || $exception instanceof RequestException
            && in_array($exception->response->status(), [400, 401, 403, 404, 422], true);
    }
}
