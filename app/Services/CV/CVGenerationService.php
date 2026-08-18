<?php

namespace App\Services\CV;

use App\Ai\Agents\GenerateCvAgent;
use App\Models\AiRequest;
use App\Models\CreditTransaction;
use App\Models\CV;
use App\Models\CVTemplate;
use App\Models\JobDescription;
use App\Models\Profile;
use App\Services\AI\AIRequestService;
use App\Services\AI\AIUsageService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Laravel\Ai\Responses\StructuredAgentResponse;
use RuntimeException;

final class CVGenerationService
{
    public function __construct(
        private readonly GenerateCvAgent $agent,
        private readonly AIUsageService $usage,
        private readonly AIRequestService $requests,
        private readonly CVValidationService $validator,
        private readonly CVBuilderService $builder,
        private readonly CVHistoryService $history,
    ) {}

    public function queue(Profile $profile, ?JobDescription $targetJob = null, ?CVTemplate $template = null): AiRequest
    {
        $this->assertUsableProfile($profile);
        $this->assertActiveTemplate($template);

        return $this->requests->create([
            'user_id' => $profile->user_id,
            'feature' => 'cv_generation',
            'prompt' => json_encode([
                'profile_id' => $profile->getKey(),
                'target_job_id' => $targetJob?->getKey(),
                'template_id' => $template?->getKey(),
            ], JSON_THROW_ON_ERROR),
            'model' => config('ai.providers.'.config('ai.default_provider').'.model'),
        ]);
    }

    public function process(AiRequest $request): CV
    {
        if ($request->status === 'completed') {
            return $this->completedCv($request);
        }

        $payload = json_decode($request->prompt, true);
        if (! is_array($payload) || ! isset($payload['profile_id'])) {
            throw new InvalidArgumentException('The queued CV generation request has an invalid payload.');
        }

        $profile = Profile::query()->with('user')->find($payload['profile_id']);
        if (! $profile || $profile->user_id !== $request->user_id) {
            throw new InvalidArgumentException('The selected profile is invalid or does not belong to this user.');
        }

        $targetJob = isset($payload['target_job_id'])
            ? JobDescription::query()->with('company')->find($payload['target_job_id'])
            : null;
        $template = isset($payload['template_id']) ? CVTemplate::query()->find($payload['template_id']) : null;

        $this->assertUsableProfile($profile);
        $this->assertActiveTemplate($template);
        $context = $this->profileContext($profile, $targetJob);
        $prompt = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $this->requests->markProcessing($request);
        $requestedProvider = (string) config('ai.default', 'openai');
        $startedAt = hrtime(true);
        $response = $this->agent->prompt(
            $prompt,
            provider: $requestedProvider,
            model: $request->model,
            timeout: (int) config("ai.providers.{$requestedProvider}.timeout", 60),
        );
        $processingTime = (int) round((hrtime(true) - $startedAt) / 1_000_000);

        if (! $response instanceof StructuredAgentResponse) {
            throw new RuntimeException('The CV generation agent returned an unexpected response type.');
        }

        $provider = $response->meta->provider ?? $requestedProvider;
        $model = $response->meta->model ?? $request->model;
        $data = $this->validator->validate($response->toArray());
        $calculated = $this->usage->calculate(
            $provider,
            $response->usage->promptTokens,
            $response->usage->completionTokens,
            $model,
        );
        $normalizedResponse = $response->toJson(JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return DB::transaction(function () use ($request, $profile, $targetJob, $template, $prompt, $provider, $model, $processingTime, $normalizedResponse, $data, $calculated): CV {
            $lockedRequest = AiRequest::query()->lockForUpdate()->findOrFail($request->getKey());

            if ($lockedRequest->status === 'completed') {
                return $this->completedCv($lockedRequest);
            }

            $cv = $this->builder->build($profile, $data, $template, $targetJob?->title);
            $this->history->snapshot($cv);

            $lockedRequest->forceFill([
                'cv_id' => $cv->getKey(),
                'prompt' => $prompt,
                'response' => $normalizedResponse,
                'provider' => $provider,
                'model' => $model,
                'tokens_used' => $calculated['total_tokens'],
                'cost' => $calculated['estimated_cost'],
                'processing_time_ms' => $processingTime,
                'status' => 'completed',
            ])->save();

            if ($calculated['credits_consumed'] > 0) {
                CreditTransaction::query()->create([
                    'user_id' => $profile->user_id,
                    'amount' => -$calculated['credits_consumed'],
                    'type' => 'cv_generation',
                    'description' => "CV generation request #{$request->getKey()} (cost: {$calculated['estimated_cost']})",
                ]);
            }

            return $cv->fresh([...CVHistoryService::RELATIONS, 'histories']);
        });
    }

    private function completedCv(AiRequest $request): CV
    {
        if ($request->cv_id === null) {
            throw new InvalidArgumentException('The completed CV generation request does not reference a CV.');
        }

        return CV::query()
            ->with([...CVHistoryService::RELATIONS, 'histories'])
            ->whereBelongsTo($request->user)
            ->findOrFail($request->cv_id);
    }

    /** @return array<string, mixed> */
    private function profileContext(Profile $profile, ?JobDescription $targetJob): array
    {
        $source = $profile->user->cvs()->with(CVHistoryService::RELATIONS)->latest('updated_at')->first();

        return [
            'profile' => array_filter([
                'name' => trim("{$profile->first_name} {$profile->last_name}"),
                'headline' => $profile->headline,
                'summary' => $profile->bio,
                'phone' => $profile->phone,
                'location' => $profile->location,
                'website' => $profile->website,
                'linkedin_url' => $profile->linkedin_url,
                'github_url' => $profile->github_url,
                'portfolio_url' => $profile->portfolio_url,
            ], static fn (mixed $value): bool => $value !== null && $value !== ''),
            'experience' => $source?->experiences?->toArray() ?? [],
            'education' => $source?->education?->toArray() ?? [],
            'skills' => $source?->skills?->toArray() ?? [],
            'projects' => $source?->projects?->toArray() ?? [],
            'languages' => $source?->languages?->toArray() ?? [],
            'certifications' => $source?->certifications?->toArray() ?? [],
            'references' => $source?->references?->toArray() ?? [],
            'target_job' => $targetJob ? array_filter([
                'title' => $targetJob->title,
                'company' => $targetJob->company?->name,
                'description' => $targetJob->description,
            ]) : '',
        ];
    }

    private function assertUsableProfile(Profile $profile): void
    {
        $hasProfessionalData = collect([
            $profile->headline, $profile->bio, $profile->location, $profile->website,
            $profile->linkedin_url, $profile->github_url, $profile->portfolio_url,
        ])->contains(static fn (mixed $value): bool => is_string($value) && trim($value) !== '');

        $hasSourceCv = $profile->user()->first()?->cvs()->exists() ?? false;
        if (! $hasProfessionalData && ! $hasSourceCv) {
            throw new InvalidArgumentException('The profile is empty. Add professional details before generating a CV.');
        }
    }

    private function assertActiveTemplate(?CVTemplate $template): void
    {
        if ($template && ! $template->active) {
            throw new InvalidArgumentException('The selected CV template is not active.');
        }
    }
}
