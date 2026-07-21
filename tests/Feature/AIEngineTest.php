<?php

use App\Jobs\ProcessAIRequest;
use App\Models\CreditTransaction;
use App\Models\User;
use App\Services\AI\AIRequestService;
use App\Services\AI\OpenAIService;
use App\Services\AI\PromptCompiler;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Fakes\FakeAIProvider;

beforeEach(function (): void {
    config()->set('ai.default_provider', 'testing');
    config()->set('ai.providers.testing.driver', FakeAIProvider::class);
    config()->set('ai.credits.tokens_per_credit', 1000);
    config()->set('ai.credits.minimum', 1);
    FakeAIProvider::$shouldFail = false;
    FakeAIProvider::$response = [];
});

it('compiles every supported prompt placeholder', function (): void {
    $template = '{{cv}}|{{job_description}}|{{company}}|{{profile}}|{{target_job}}|{{skills}}|{{experience}}|{{summary}}';
    $context = [
        'cv' => 'CV', 'job_description' => 'JOB', 'company' => 'ACME', 'profile' => 'PROFILE',
        'target_job' => 'ENGINEER', 'skills' => ['PHP'], 'experience' => 'EXPERIENCE', 'summary' => 'SUMMARY',
    ];

    $compiled = app(PromptCompiler::class)->compile($template, $context);

    expect($compiled)
        ->toContain('CV|JOB|ACME|PROFILE|ENGINEER')
        ->toContain('"PHP"')
        ->toContain('EXPERIENCE|SUMMARY')
        ->not->toContain('{{');
});

it('queues an AI request and transitions it to queued', function (): void {
    Queue::fake();
    $user = User::factory()->create();
    $request = app(AIRequestService::class)->create([
        'user_id' => $user->id,
        'feature' => 'cv_rewrite',
        'prompt' => 'Rewrite this CV.',
        'model' => 'fake-model',
    ], queue: false);

    app(AIRequestService::class)->queue($request);

    expect($request->refresh()->status)->toBe('queued');
    Queue::assertPushed(ProcessAIRequest::class, fn (ProcessAIRequest $job): bool => $job->aiRequestId === $request->id);
});

it('processes a successful request and stores response usage cost and credits', function (): void {
    $user = User::factory()->create();
    $request = app(AIRequestService::class)->create([
        'user_id' => $user->id,
        'feature' => 'professional_summary',
        'prompt' => 'Write a professional summary.',
        'model' => 'fake-model',
    ], queue: false);

    app()->call([new ProcessAIRequest($request->id), 'handle']);

    $request->refresh();
    expect($request->status)->toBe('completed')
        ->and($request->response)->toBe('Generated CV content')
        ->and($request->tokens_used)->toBe(1000)
        ->and((float) $request->cost)->toBe(0.0014)
        ->and($request->processing_time_ms)->toBeInt();

    expect(CreditTransaction::query()->where('user_id', $user->id)->value('amount'))->toBe(-1);
});

it('marks an exhausted failed request as failed', function (): void {
    $user = User::factory()->create();
    $request = app(AIRequestService::class)->create([
        'user_id' => $user->id,
        'feature' => 'cv_rewrite',
        'prompt' => 'Rewrite this CV.',
        'model' => 'fake-model',
    ], queue: false);
    $job = new ProcessAIRequest($request->id);
    FakeAIProvider::$shouldFail = true;

    try {
        app()->call([$job, 'handle']);
    } catch (RuntimeException $exception) {
        $job->failed($exception);
    }

    expect($request->refresh()->status)->toBe('failed');
});

it('calls the OpenAI Responses API and returns its response', function (): void {
    config()->set('ai.providers.openai.api_key', 'test-key');
    config()->set('ai.providers.openai.base_url', 'https://api.openai.test/v1');
    config()->set('ai.providers.openai.model', 'gpt-test');
    config()->set('ai.providers.openai.temperature', null);

    Http::fake([
        'api.openai.test/v1/responses' => Http::response([
            'model' => 'gpt-test',
            'output_text' => 'OpenAI response',
            'usage' => ['input_tokens' => 10, 'output_tokens' => 5, 'total_tokens' => 15],
        ]),
    ]);

    $response = app(OpenAIService::class)->generate('Test prompt');

    expect($response['output_text'])->toBe('OpenAI response')
        ->and($response['provider'])->toBe('openai');
    Http::assertSent(fn ($request): bool => $request->url() === 'https://api.openai.test/v1/responses'
        && $request['model'] === 'gpt-test'
        && $request['input'] === 'Test prompt');
});
