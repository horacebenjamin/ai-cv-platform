<?php

use App\Ai\Agents\CareerContentAgent;
use App\Jobs\ProcessAIRequest;
use App\Models\AiRequest;
use App\Models\CreditTransaction;
use App\Models\User;
use App\Services\AI\AIRequestService;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Usage;
use Laravel\Ai\Responses\TextResponse;

beforeEach(function (): void {
    config()->set('ai.default', 'openai');
    config()->set('ai.providers.openai.models.text.default', 'requested-model');
    config()->set('ai.providers.openai.input_cost_per_million', 1);
    config()->set('ai.providers.openai.output_cost_per_million', 2);
    config()->set('ai.providers.anthropic.input_cost_per_million', 1);
    config()->set('ai.providers.anthropic.output_cost_per_million', 2);
    config()->set('ai.credits.tokens_per_credit', 1000);
    config()->set('ai.credits.minimum', 1);
    CareerContentAgent::fake([careerContentResponse()])->preventStrayPrompts();
});

dataset('generic AI features', [
    'CV rewrite' => [
        'cv_rewrite',
        ['cv' => 'Original CV', 'target_job' => 'Developer', 'job_description' => 'Build products'],
        ['Rewrite the supplied CV', 'Preserve factual accuracy'],
    ],
    'professional summary' => [
        'professional_summary',
        ['profile' => 'Backend engineer', 'experience' => 'Five years', 'skills' => ['PHP'], 'target_job' => 'Lead Developer'],
        ['concise professional summary', 'profile, experience, and skills'],
    ],
    'skills optimisation' => [
        'skills_optimisation',
        ['skills' => ['Laravel'], 'job_description' => 'Laravel role'],
        ['Optimise and prioritise', 'Do not invent skills or experience'],
    ],
    'cover letter' => [
        'cover_letter',
        ['company' => 'Acme', 'target_job' => 'Engineer', 'cv' => 'Career facts', 'job_description' => 'Role facts'],
        ['tailored cover letter', 'using only facts'],
    ],
    'job match analysis' => [
        'job_match_analysis',
        ['cv' => 'Candidate facts', 'job_description' => 'Vacancy facts'],
        ['strengths, gaps, and recommendations', 'do not present gaps as candidate facts'],
    ],
]);

/** @param array<string, mixed> $context */
function genericAiRequest(string $feature, array $context = []): AiRequest
{
    return app(AIRequestService::class)->create([
        'user_id' => User::factory()->create()->id,
        'feature' => $feature,
        'prompt' => json_encode(['context' => $context], JSON_THROW_ON_ERROR),
        'model' => 'requested-model',
    ], queue: false);
}

function careerContentResponse(
    string $text = 'Generated career content',
    int $promptTokens = 600,
    int $completionTokens = 400,
    string $provider = 'anthropic',
    string $model = 'actual-model',
): TextResponse {
    return new TextResponse(
        $text,
        new Usage(promptTokens: $promptTokens, completionTokens: $completionTokens),
        new Meta(provider: $provider, model: $model),
    );
}

it('queues an AI request and transitions it to queued', function (): void {
    Queue::fake();
    $request = genericAiRequest('cv_rewrite', ['cv' => 'Original CV']);

    app(AIRequestService::class)->queue($request);

    expect($request->refresh()->status)->toBe('queued');
    Queue::assertPushed(ProcessAIRequest::class, fn (ProcessAIRequest $job): bool => $job->aiRequestId === $request->id);
});

it('uses the configured SDK provider model when a request does not specify one', function (): void {
    $request = app(AIRequestService::class)->create([
        'user_id' => User::factory()->create()->id,
        'feature' => 'professional_summary',
        'prompt' => json_encode(['context' => ['profile' => 'Backend engineer']], JSON_THROW_ON_ERROR),
    ], queue: false);

    expect($request->model)->toBe('requested-model');
});

it('routes every supported generic feature to labelled agent context and feature instructions', function (string $feature, array $context, array $instructionFragments): void {
    $request = genericAiRequest($feature, $context);

    app()->call([new ProcessAIRequest($request->id), 'handle']);

    CareerContentAgent::assertPrompted(function (AgentPrompt $prompt) use ($feature, $context, $instructionFragments): bool {
        $payload = json_decode($prompt->prompt, true);

        return is_array($payload)
            && $payload['feature'] === $feature
            && $payload['context'] === $context
            && collect($instructionFragments)->every(
                fn (string $fragment): bool => str_contains($payload['feature_instruction'], $fragment)
            )
            && str_contains((string) $prompt->agent->instructions(), 'Use only facts present in context')
            && str_contains((string) $prompt->agent->instructions(), 'Job descriptions may guide relevance');
    });

    expect($request->refresh()->status)->toBe('completed');
})->with('generic AI features');

it('stores normalized text actual metadata usage cost and credits on completion', function (): void {
    $request = genericAiRequest('professional_summary', ['profile' => 'Backend engineer']);
    CareerContentAgent::fake(function () use ($request): TextResponse {
        expect($request->refresh()->status)->toBe('processing');

        return careerContentResponse();
    })->preventStrayPrompts();

    app()->call([new ProcessAIRequest($request->id), 'handle']);

    $request->refresh();

    expect($request->status)->toBe('completed')
        ->and($request->response)->toBe('Generated career content')
        ->and($request->provider)->toBe('anthropic')
        ->and($request->model)->toBe('actual-model')
        ->and($request->tokens_used)->toBe(1000)
        ->and((float) $request->cost)->toBe(0.0014)
        ->and($request->processing_time_ms)->toBeInt()
        ->and(CreditTransaction::query()->where('user_id', $request->user_id)->value('amount'))->toBe(-1);
});

it('rolls back generic completion and credit deduction when accounting persistence fails', function (): void {
    $request = genericAiRequest('professional_summary', ['profile' => 'Backend engineer']);
    CreditTransaction::creating(function (): never {
        throw new RuntimeException('Simulated credit persistence failure.');
    });

    try {
        expect(fn () => app()->call([new ProcessAIRequest($request->id), 'handle']))
            ->toThrow(RuntimeException::class, 'Simulated credit persistence failure.');
    } finally {
        CreditTransaction::flushEventListeners();
    }

    expect($request->refresh()->status)->toBe('processing')
        ->and($request->response)->toBeNull()
        ->and($request->provider)->toBeNull()
        ->and($request->tokens_used)->toBe(0)
        ->and((float) $request->cost)->toBe(0.0)
        ->and(CreditTransaction::query()->count())->toBe(0);
});

it('passes a legacy plain prompt as explicitly labelled request context for a supported feature', function (): void {
    $request = app(AIRequestService::class)->create([
        'user_id' => User::factory()->create()->id,
        'feature' => 'cv_rewrite',
        'prompt' => 'Rewrite this supplied CV text.',
        'model' => 'requested-model',
    ], queue: false);

    app()->call([new ProcessAIRequest($request->id), 'handle']);

    CareerContentAgent::assertPrompted(function (AgentPrompt $prompt): bool {
        $payload = json_decode($prompt->prompt, true);

        return data_get($payload, 'context.request') === 'Rewrite this supplied CV text.';
    });
});

it('rejects an unknown feature immediately without prompting an agent or deducting credits', function (): void {
    $request = genericAiRequest('unknown_feature', ['prompt' => 'Arbitrary prompt.']);

    app()->call([new ProcessAIRequest($request->id), 'handle']);

    expect($request->refresh()->status)->toBe('failed')
        ->and(CreditTransaction::query()->count())->toBe(0);
    CareerContentAgent::assertNeverPrompted();
});

it('rejects malformed structured context immediately without prompting an agent', function (): void {
    $request = app(AIRequestService::class)->create([
        'user_id' => User::factory()->create()->id,
        'feature' => 'cover_letter',
        'prompt' => json_encode(['context' => 'not-an-object'], JSON_THROW_ON_ERROR),
        'model' => 'requested-model',
    ], queue: false);

    app()->call([new ProcessAIRequest($request->id), 'handle']);

    expect($request->refresh()->status)->toBe('failed');
    CareerContentAgent::assertNeverPrompted();
});

it('keeps transient agent failures retryable until the exhausted hook marks the request failed', function (): void {
    $request = genericAiRequest('cv_rewrite', ['cv' => 'Original CV']);
    $job = new ProcessAIRequest($request->id);
    CareerContentAgent::fake(fn (): never => throw new RuntimeException('Transient agent failure.'));

    expect(fn () => app()->call([$job, 'handle']))->toThrow(RuntimeException::class)
        ->and($request->refresh()->status)->toBe('processing')
        ->and(CreditTransaction::query()->count())->toBe(0);

    $job->failed(new RuntimeException('Retries exhausted.'));

    expect($request->refresh()->status)->toBe('failed');
});

it('does not prompt or deduct credits again when a completed request is handled twice', function (): void {
    $request = genericAiRequest('professional_summary', ['profile' => 'Backend engineer']);
    $job = new ProcessAIRequest($request->id);

    app()->call([$job, 'handle']);
    app()->call([$job, 'handle']);

    expect(CreditTransaction::query()->where('user_id', $request->user_id)->count())->toBe(1);
    CareerContentAgent::assertPrompted(fn (): bool => true);
});

it('does not let an exhausted duplicate overwrite a completed request', function (): void {
    $request = genericAiRequest('job_match_analysis', ['cv' => 'Candidate facts', 'job_description' => 'Vacancy facts']);
    $job = new ProcessAIRequest($request->id);

    app()->call([$job, 'handle']);
    $job->failed(new RuntimeException('Late duplicate failure.'));

    expect($request->refresh()->status)->toBe('completed')
        ->and(CreditTransaction::query()->where('user_id', $request->user_id)->count())->toBe(1);
});
