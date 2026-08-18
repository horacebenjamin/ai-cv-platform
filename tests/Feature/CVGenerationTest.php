<?php

use App\Ai\Agents\CareerContentAgent;
use App\Ai\Agents\GenerateCvAgent;
use App\Jobs\ProcessAIRequest;
use App\Models\AiRequest;
use App\Models\CreditTransaction;
use App\Models\CV;
use App\Models\CvHistory;
use App\Models\CVTemplate;
use App\Models\Profile;
use App\Models\User;
use App\Services\CV\CVGenerationService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Usage;
use Laravel\Ai\Responses\StructuredTextResponse;

beforeEach(function (): void {
    config()->set('ai.default', 'openai');
    config()->set('ai.providers.openai.model', 'fake-model');
    config()->set('ai.providers.openai.models.text.default', 'fake-model');
    config()->set('ai.providers.openai.input_cost_per_million', 1);
    config()->set('ai.providers.openai.output_cost_per_million', 2);
    config()->set('ai.credits.tokens_per_credit', 1000);
    config()->set('ai.credits.minimum', 1);
    GenerateCvAgent::fake([cvAgentResponse()])->preventStrayPrompts();
    CareerContentAgent::fake()->preventStrayPrompts();
});

/** @return array<string, mixed> */
function cvStructuredData(): array
{
    return [
        'title' => 'Senior Laravel Developer',
        'summary' => 'Experienced Laravel developer.',
        'skills' => [['name' => 'PHP', 'category' => 'Backend', 'proficiency' => 'Expert']],
        'experience' => [[
            'company' => 'Acme', 'job_title' => 'Developer', 'employment_type' => null,
            'location' => null, 'start_date' => '2022-01-01', 'end_date' => null,
            'currently_working' => true, 'description' => 'Built products.',
        ]],
        'education' => [[
            'institution' => 'University', 'qualification' => 'BSc', 'field_of_study' => null,
            'grade' => null, 'start_date' => null, 'end_date' => null, 'description' => null,
        ]],
        'projects' => [[
            'title' => 'CV Platform', 'description' => null, 'technologies' => ['Laravel'],
            'github_url' => null, 'demo_url' => null, 'start_date' => null, 'end_date' => null,
        ]],
        'languages' => [['language' => 'English', 'proficiency' => 'Native']],
        'certifications' => [[
            'name' => 'Laravel Certification', 'organisation' => null, 'issue_date' => null,
            'expiry_date' => null, 'credential_id' => null, 'credential_url' => null,
        ]],
        'references' => [[
            'name' => 'Jane Doe', 'company' => 'Acme', 'job_title' => null,
            'email' => null, 'phone' => null, 'relationship' => null,
        ]],
    ];
}

/** @param array<string, mixed>|null $data */
function cvAgentResponse(?array $data = null): StructuredTextResponse
{
    $structured = $data ?? cvStructuredData();

    return new StructuredTextResponse(
        $structured,
        json_encode($structured, JSON_THROW_ON_ERROR),
        new Usage(promptTokens: 600, completionTokens: 400),
        new Meta(provider: 'openai', model: 'fake-model'),
    );
}

function cvProfile(array $attributes = []): Profile
{
    $user = User::factory()->create();

    return Profile::query()->create($attributes + [
        'user_id' => $user->id,
        'first_name' => 'Alex',
        'last_name' => 'Taylor',
        'headline' => 'Laravel Developer',
        'bio' => 'Builds reliable web applications.',
    ]);
}

it('queues and successfully generates a complete CV', function (): void {
    Queue::fake();
    $profile = cvProfile();
    $template = CVTemplate::query()->create(['name' => 'Modern', 'slug' => 'modern', 'active' => true]);
    $request = app(CVGenerationService::class)->queue($profile, template: $template);

    Queue::assertPushed(ProcessAIRequest::class, fn (ProcessAIRequest $job): bool => $job->aiRequestId === $request->id);
    app()->call([new ProcessAIRequest($request->id), 'handle']);

    $request->refresh();
    $cv = CV::query()->with(['experiences', 'education', 'skills', 'projects', 'languages', 'certifications', 'references'])->findOrFail($request->cv_id);

    expect($request->status)->toBe('completed')
        ->and($request->provider)->toBe('openai')
        ->and($request->tokens_used)->toBe(1000)
        ->and((float) $request->cost)->toBe(0.0014)
        ->and($request->processing_time_ms)->toBeInt()
        ->and($cv->title)->toBe('Senior Laravel Developer')
        ->and($cv->professional_summary)->toBe('Experienced Laravel developer.')
        ->and($cv->template_id)->toBe($template->id)
        ->and($cv->experiences)->toHaveCount(1)
        ->and($cv->experiences->sole()->company)->toBe('Acme')
        ->and($cv->education)->toHaveCount(1)
        ->and($cv->skills)->toHaveCount(1)
        ->and($cv->skills->sole()->name)->toBe('PHP')
        ->and($cv->projects)->toHaveCount(1)
        ->and($cv->languages)->toHaveCount(1)
        ->and($cv->certifications)->toHaveCount(1)
        ->and($cv->references)->toHaveCount(1);

    GenerateCvAgent::assertPrompted(function (AgentPrompt $prompt): bool {
        $context = json_decode($prompt->prompt, true);

        return is_array($context)
            && data_get($context, 'profile.headline') === 'Laravel Developer'
            && isset($context['experience'], $context['education'], $context['skills'], $context['projects'])
            && isset($context['languages'], $context['certifications'], $context['references'], $context['target_job']);
    });
    CareerContentAgent::assertNeverPrompted();
});

it('transitions invalid generated data from queued to failed without partial writes', function (): void {
    Queue::fake();
    $profile = cvProfile();
    GenerateCvAgent::fake([cvAgentResponse([
        'title' => 'Invalid CV',
        'summary' => null,
        'skills' => [],
        'experience' => [],
        'education' => [],
        'projects' => [],
        'languages' => [],
        'certifications' => [],
        'references' => [['company' => 'Acme']],
    ])])->preventStrayPrompts();
    $request = app(CVGenerationService::class)->queue($profile);

    expect($request->status)->toBe('queued');

    app()->call([new ProcessAIRequest($request->id), 'handle']);

    expect($request->refresh()->status)->toBe('failed')
        ->and(CV::query()->count())->toBe(0)
        ->and(CvHistory::query()->count())->toBe(0)
        ->and(CreditTransaction::query()->count())->toBe(0);
});

it('rejects invalid structured data without creating a CV', function (): void {
    Queue::fake();
    $profile = cvProfile();
    GenerateCvAgent::fake([cvAgentResponse(['title' => 'Missing sections'])])->preventStrayPrompts();
    $request = app(CVGenerationService::class)->queue($profile);

    expect(fn () => app(CVGenerationService::class)->process($request))->toThrow(ValidationException::class)
        ->and(CV::query()->count())->toBe(0);
});

it('propagates provider failures without creating partial records', function (): void {
    Queue::fake();
    $request = app(CVGenerationService::class)->queue(cvProfile());
    GenerateCvAgent::fake(fn (): never => throw new RuntimeException('Simulated agent failure.'));

    expect(fn () => app(CVGenerationService::class)->process($request))->toThrow(RuntimeException::class)
        ->and(CV::query()->count())->toBe(0)
        ->and(CreditTransaction::query()->count())->toBe(0);
});

it('marks an exhausted provider failure as failed without partial records', function (): void {
    Queue::fake();
    $request = app(CVGenerationService::class)->queue(cvProfile());
    $job = new ProcessAIRequest($request->id);
    GenerateCvAgent::fake(fn (): never => throw new RuntimeException('Simulated agent failure.'));

    try {
        app()->call([$job, 'handle']);
    } catch (RuntimeException $exception) {
        $job->failed($exception);
    }

    expect($request->refresh()->status)->toBe('failed')
        ->and(CV::query()->count())->toBe(0)
        ->and(CvHistory::query()->count())->toBe(0)
        ->and(CreditTransaction::query()->count())->toBe(0);
});

it('rejects an empty profile before queueing', function (): void {
    Queue::fake();
    $profile = cvProfile(['headline' => null, 'bio' => null]);

    expect(fn () => app(CVGenerationService::class)->queue($profile))
        ->toThrow(InvalidArgumentException::class, 'profile is empty');
    Queue::assertNothingPushed();
});

it('rejects an inactive template before queueing', function (): void {
    Queue::fake();
    $profile = cvProfile();
    $template = CVTemplate::query()->create(['name' => 'Inactive', 'slug' => 'inactive', 'active' => false]);

    expect(fn () => app(CVGenerationService::class)->queue($profile, template: $template))
        ->toThrow(InvalidArgumentException::class, 'template is not active');
    Queue::assertNothingPushed();
});

it('does not process an already completed request again', function (): void {
    Queue::fake();
    $profile = cvProfile();
    $cv = $profile->user->cvs()->create([
        'title' => 'Existing CV',
        'professional_summary' => 'Already generated.',
        'status' => 'draft',
        'is_master' => true,
    ]);
    $request = app(CVGenerationService::class)->queue($profile);
    $request->forceFill(['status' => 'completed', 'cv_id' => $cv->id])->save();

    app()->call([new ProcessAIRequest($request->id), 'handle']);

    expect(CV::query()->count())->toBe(1)
        ->and(CvHistory::query()->count())->toBe(0)
        ->and(CreditTransaction::query()->count())->toBe(0)
        ->and($request->refresh()->cv_id)->toBe($cv->id);
    GenerateCvAgent::assertNeverPrompted();
});

it('deducts credits and creates one complete generated history snapshot', function (): void {
    Queue::fake();
    $profile = cvProfile();
    $request = app(CVGenerationService::class)->queue($profile);
    $cv = app(CVGenerationService::class)->process($request);

    $transaction = CreditTransaction::query()->sole();
    $history = CvHistory::query()->sole();

    expect($transaction->user_id)->toBe($profile->user_id)
        ->and($transaction->amount)->toBe(-1)
        ->and($transaction->type)->toBe('cv_generation')
        ->and($history->action)->toBe('generated')
        ->and($history->snapshot['id'])->toBe($cv->id)
        ->and($history->snapshot['skills'])->toHaveCount(1)
        ->and(AiRequest::query()->findOrFail($request->id)->cost)->not->toBeNull();
});

it('rolls back the CV aggregate history request completion and credits when persistence fails', function (): void {
    Queue::fake();
    $request = app(CVGenerationService::class)->queue(cvProfile());
    CreditTransaction::creating(function (): never {
        throw new RuntimeException('Simulated credit persistence failure.');
    });

    try {
        expect(fn () => app(CVGenerationService::class)->process($request))
            ->toThrow(RuntimeException::class, 'Simulated credit persistence failure.');
    } finally {
        CreditTransaction::flushEventListeners();
    }

    $request->refresh();

    expect($request->status)->toBe('processing')
        ->and($request->cv_id)->toBeNull()
        ->and($request->response)->toBeNull()
        ->and($request->tokens_used)->toBe(0)
        ->and((float) $request->cost)->toBe(0.0)
        ->and(CV::query()->count())->toBe(0)
        ->and(CvHistory::query()->count())->toBe(0)
        ->and(CreditTransaction::query()->count())->toBe(0);
});
