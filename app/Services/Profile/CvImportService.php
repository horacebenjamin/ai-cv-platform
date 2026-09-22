<?php

namespace App\Services\Profile;

use App\Ai\Agents\ImportCvAgent;
use App\Ai\Agents\ImportExperienceAgent;
use App\Models\AiRequest;
use App\Models\Profile;
use App\Models\ProfileImport;
use App\Models\User;
use App\Services\AI\AIRequestService;
use App\Services\AI\AIUsageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Laravel\Ai\Responses\StructuredAgentResponse;
use RuntimeException;
use Throwable;

/**
 * Imports an existing CV into the career profile as reviewable proposals.
 *
 * Extraction never writes to the career profile. Proposed facts are stored on
 * the import record until the owning user approves them, and approval only ever
 * appends records or fills fields the user has left blank.
 *
 * Only pasted CV text is supported. Binary document formats (PDF, DOCX) need a
 * text-extraction dependency and are handled by extendTextExtraction().
 */
final class CvImportService
{
    private const EXPERIENCE_SECTION_HEADINGS = [
        'professional experience',
        'work experience',
        'employment',
        'employment history',
        'career history',
        'experience',
        'work history',
        'professional history',
    ];

    private const SKILLS_SECTION_HEADINGS = [
        'technical skills',
        'skills',
        'core skills',
        'key skills',
    ];

    private const SKILL_CATEGORY_HEADINGS = [
        'backend',
        'cloud & devops',
        'cloud and devops',
        'database',
        'databases',
        'frontend',
        'testing',
        'tools',
    ];

    /** Profile columns an import may propose, mapped from extracted keys. */
    private const PROFILE_FIELDS = [
        'first_name' => 'first_name',
        'last_name' => 'last_name',
        'headline' => 'headline',
        'summary' => 'bio',
        'location' => 'location',
        'phone' => 'phone',
        'website' => 'website',
        'linkedin_url' => 'linkedin_url',
        'github_url' => 'github_url',
    ];

    public function __construct(
        private readonly ImportCvAgent $agent,
        private readonly ImportExperienceAgent $experienceAgent,
        private readonly ExperienceBlockSplitter $experienceBlocks,
        private readonly TechnicalSkillsParser $skillsParser,
        private readonly CvTextSectionExtractor $sections,
        private readonly CvSectionLocator $sectionLocator,
        private readonly AIRequestService $requests,
        private readonly AIUsageService $usage,
    ) {}

    /** Queue extraction for a CV the user has pasted. */
    public function create(User $user, string $sourceText): ProfileImport
    {
        $sourceText = trim($sourceText);

        if ($sourceText === '') {
            throw new InvalidArgumentException('Paste the text of your existing CV before importing it.');
        }

        return DB::transaction(function () use ($user, $sourceText): ProfileImport {
            $import = $user->profileImports()->create([
                'source_type' => 'pasted_text',
                'source_text' => $sourceText,
                'status' => 'pending',
            ]);

            $request = $this->requests->create([
                'user_id' => $user->getKey(),
                'feature' => 'cv_import',
                'prompt' => json_encode(['import_id' => $import->getKey()], JSON_THROW_ON_ERROR),
            ]);

            $import->forceFill(['ai_request_id' => $request->getKey()])->save();

            return $import->refresh();
        });
    }

    /** Extract proposed facts for a queued import request. */
    public function process(AiRequest $request): ProfileImport
    {
        $import = $this->importFor($request);

        if ($request->status === 'completed') {
            return $import;
        }

        $this->requests->markProcessing($request);

        $requestedProvider = (string) config('ai.default', 'openai');
        $providerTimeout = (int) config("ai.providers.{$requestedProvider}.timeout", 60);
        $experienceText = $this->sectionLocator->experience(
            $import->source_text,
        );
        $experienceBlocks = $experienceText === null
            ? []
            : $this->experienceBlocks->split($experienceText);
        $semanticSourceText = $this->sections->without(
            $import->source_text,
            [...self::EXPERIENCE_SECTION_HEADINGS, ...self::SKILLS_SECTION_HEADINGS],
        );
        $startedAt = hrtime(true);
        $response = $this->agent->prompt(
            $semanticSourceText,
            provider: $requestedProvider,
            model: $request->model,
            timeout: $providerTimeout,
        );
        $experienceExtraction = $this->extractExperiences(
            $import,
            $experienceBlocks,
            $requestedProvider,
            $request->model,
            $providerTimeout,
        );
        $processingTime = (int) round((hrtime(true) - $startedAt) / 1_000_000);

        if (! $response instanceof StructuredAgentResponse) {
            throw new RuntimeException('The CV import agent returned an unexpected response type.');
        }

        $provider = $response->meta->provider ?? $requestedProvider;
        $model = $response->meta->model ?? $request->model;
        $responseData = $response->toArray();
        $responseData['experiences'] = $experienceExtraction['experiences'];
        $responseData['experience_skipped'] = $experienceExtraction['skipped'];
        $responseData['skills'] = $this->skillsParser->parse($import->source_text, allowUnsectioned: false);

        $extracted = $this->normalize(
            $responseData,
            $import->source_text,
        );

        $calculated = $this->usage->calculate(
            $provider,
            $response->usage->promptTokens + $experienceExtraction['prompt_tokens'],
            $response->usage->completionTokens + $experienceExtraction['completion_tokens'],
            $model,
        );

        return DB::transaction(function () use ($import, $request, $extracted, $provider, $model, $processingTime, $calculated): ProfileImport {
            $import->forceFill([
                'extracted' => $extracted,
                'status' => 'ready',
            ])->save();

            $this->requests->complete($request, [
                'content' => json_encode($extracted, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'provider' => $provider,
                'model' => $model,
                'prompt_tokens' => $calculated['prompt_tokens'],
                'completion_tokens' => $calculated['completion_tokens'],
                'total_tokens' => $calculated['total_tokens'],
                'estimated_cost' => $calculated['estimated_cost'],
                'processing_time' => $processingTime,
            ], $calculated['credits_consumed']);

            return $import->refresh();
        });
    }

    /**
     * @param  list<string>  $blocks
     * @return array{experiences: list<array<string, mixed>>, skipped: int, prompt_tokens: int, completion_tokens: int}
     */
    private function extractExperiences(
        ProfileImport $import,
        array $blocks,
        string $provider,
        ?string $model,
        int $timeout,
    ): array {
        $experiences = [];
        $skipped = 0;
        $promptTokens = 0;
        $completionTokens = 0;

        foreach ($blocks as $index => $block) {
            try {
                $response = $this->experienceAgent->prompt(
                    $block,
                    provider: $provider,
                    model: $model,
                    timeout: $timeout,
                );

                if (! $response instanceof StructuredAgentResponse) {
                    throw new RuntimeException('The CV experience import agent returned an unexpected response type.');
                }

                $promptTokens += $response->usage->promptTokens;
                $completionTokens += $response->usage->completionTokens;
                $candidate = $response->toArray();
                $entryType = $this->text($candidate['entry_type'] ?? null);

                if (! in_array($entryType, ['employment', 'internship', 'volunteering'], true)) {
                    $skipped++;

                    continue;
                }

                $experiences[] = [
                    'job_title' => $candidate['job_title'] ?? null,
                    'company' => $candidate['company'] ?? null,
                    'location' => $candidate['location'] ?? null,
                    'employment_type' => $candidate['employment_type'] ?? null,
                    'start_date' => $candidate['start_date'] ?? null,
                    'end_date' => $candidate['end_date'] ?? null,
                    'currently_employed' => $candidate['currently_employed'] ?? false,
                    'summary' => $candidate['summary'] ?? null,
                    'achievements' => $candidate['achievements'] ?? [],
                    'technologies' => $candidate['technologies'] ?? [],
                    '_source_block' => $block,
                ];
            } catch (Throwable $exception) {
                $skipped++;

                Log::warning('CV experience block extraction failed.', [
                    'profile_import_id' => $import->getKey(),
                    'block_index' => $index,
                    'exception' => $exception::class,
                ]);
            }
        }

        return [
            'experiences' => $experiences,
            'skipped' => $skipped,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
        ];
    }

    /** Record that extraction failed so the customer sees a truthful status. */
    public function markFailed(AiRequest $request): void
    {
        $payload = json_decode((string) $request->prompt, true);

        if (! is_array($payload) || ! isset($payload['import_id'])) {
            return;
        }

        ProfileImport::query()
            ->where('user_id', $request->user_id)
            ->whereKey($payload['import_id'])
            ->whereIn('status', ['pending', 'ready'])
            ->update(['status' => 'failed']);
    }

    /**
     * Append the approved proposals to the owner's career profile.
     *
     * @param  array{professional?: list<string>, experiences?: list<int>, skills?: list<int>, projects?: list<int>, education?: list<int>, certifications?: list<int>}  $selections
     * @return array<string, int>
     */
    public function apply(ProfileImport $import, array $selections): array
    {
        if ($import->status !== 'ready') {
            throw new InvalidArgumentException('This import is no longer awaiting review.');
        }

        $extracted = $import->extracted ?? [];
        $professionalFields = $this->approvedProfileFields($extracted, $selections['professional'] ?? []);

        return DB::transaction(function () use ($import, $extracted, $selections, $professionalFields): array {
            $profile = $this->resolveProfile($import, $professionalFields);
            $applied = ['profile' => 0];

            foreach ($professionalFields as $column => $value) {
                if (blank($profile->{$column})) {
                    $profile->{$column} = $value;
                    $applied['profile']++;
                }
            }

            if ($profile->isDirty()) {
                $profile->save();
            }

            $applied['experiences'] = $this->appendRecords(
                $profile, 'experiences', $extracted['experiences'] ?? [], $selections['experiences'] ?? []
            );
            $applied['skills'] = $this->appendSkills($profile, $extracted['skills'] ?? [], $selections['skills'] ?? []);
            $applied['projects'] = $this->appendRecords(
                $profile, 'projects', $extracted['projects'] ?? [], $selections['projects'] ?? []
            );
            $applied['education'] = $this->appendRecords(
                $profile, 'education', $extracted['education'] ?? [], $selections['education'] ?? []
            );
            $applied['certifications'] = $this->appendRecords(
                $profile, 'certifications', $extracted['certifications'] ?? [], $selections['certifications'] ?? []
            );

            $import->forceFill(['status' => 'applied', 'applied_at' => now()])->save();

            return $applied;
        });
    }

    public function discard(ProfileImport $import): void
    {
        $import->forceFill(['status' => 'discarded'])->save();
    }

    /**
     * Reserved boundary for binary CV formats.
     *
     * Extracting text from PDF or DOCX uploads requires an additional document
     * parsing dependency. Until that is approved, imports accept pasted text
     * only and this method documents where extraction would be added.
     */
    public function extendTextExtraction(): never
    {
        throw new RuntimeException('Only pasted CV text is supported. Document parsing is not implemented.');
    }

    /**
     * @return array<string, mixed>
     */
    public function reviewPayload(ProfileImport $import): array
    {
        $extracted = $import->extracted ?? [];
        $profile = $import->user->profile()->first();

        return [
            'import' => [
                'id' => $import->id,
                'status' => $import->status,
                'sourceType' => $import->source_type,
                'createdAt' => $import->created_at?->toIso8601String(),
                'appliedAt' => $import->applied_at?->toIso8601String(),
                'skippedCount' => (int) ($extracted['skipped'] ?? 0),
            ],
            'proposed' => [
                'professional' => $this->proposedProfileFields($extracted, $profile),
                'experiences' => array_values($extracted['experiences'] ?? []),
                'skills' => array_values($extracted['skills'] ?? []),
                'projects' => array_values($extracted['projects'] ?? []),
                'education' => array_values($extracted['education'] ?? []),
                'certifications' => array_values($extracted['certifications'] ?? []),
            ],
        ];
    }

    private function importFor(AiRequest $request): ProfileImport
    {
        $payload = json_decode((string) $request->prompt, true);

        if (! is_array($payload) || ! isset($payload['import_id'])) {
            throw new InvalidArgumentException('The queued CV import request has an invalid payload.');
        }

        $import = ProfileImport::query()->with('user')->find($payload['import_id']);

        if (! $import || $import->user_id !== $request->user_id) {
            throw new InvalidArgumentException('The selected import is invalid or does not belong to this user.');
        }

        return $import;
    }

    /**
     * @param  array<string, mixed>  $professionalFields
     */
    private function resolveProfile(ProfileImport $import, array $professionalFields): Profile
    {
        $profile = $import->user->profile()->first();

        if ($profile !== null) {
            return $profile;
        }

        if (blank($professionalFields['first_name'] ?? null) || blank($professionalFields['last_name'] ?? null)) {
            throw new InvalidArgumentException(
                'Save your name on the Career Profile overview, or approve the imported name, before applying an import.'
            );
        }

        return $import->user->profile()->create([
            'first_name' => $professionalFields['first_name'],
            'last_name' => $professionalFields['last_name'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $extracted
     * @param  list<string>  $approved
     * @return array<string, string>
     */
    private function approvedProfileFields(array $extracted, array $approved): array
    {
        $professional = is_array($extracted['professional'] ?? null) ? $extracted['professional'] : [];
        $fields = [];

        foreach (self::PROFILE_FIELDS as $key => $column) {
            if (! in_array($key, $approved, true)) {
                continue;
            }

            $value = $professional[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                $fields[$column] = trim($value);
            }
        }

        return $fields;
    }

    /**
     * @param  array<string, mixed>  $extracted
     * @return list<array{key: string, label: string, value: string, currentValue: string|null, isBlank: bool}>
     */
    private function proposedProfileFields(array $extracted, ?Profile $profile): array
    {
        $labels = [
            'first_name' => 'First name',
            'last_name' => 'Last name',
            'headline' => 'Professional headline',
            'summary' => 'Professional summary',
            'location' => 'Location',
            'phone' => 'Phone number',
            'website' => 'Website',
            'linkedin_url' => 'LinkedIn',
            'github_url' => 'GitHub',
        ];
        $professional = is_array($extracted['professional'] ?? null) ? $extracted['professional'] : [];
        $fields = [];

        foreach (self::PROFILE_FIELDS as $key => $column) {
            $value = $professional[$key] ?? null;

            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            $currentValue = $profile?->{$column};

            $fields[] = [
                'key' => $key,
                'label' => $labels[$key],
                'value' => trim($value),
                'currentValue' => is_string($currentValue) && $currentValue !== '' ? $currentValue : null,
                'isBlank' => blank($currentValue),
            ];
        }

        return $fields;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @param  list<int>  $approvedIndexes
     */
    private function appendRecords(Profile $profile, string $relation, array $items, array $approvedIndexes): int
    {
        $sortOrder = (int) $profile->{$relation}()->max('sort_order');
        $created = 0;

        foreach ($this->approvedItems($items, $approvedIndexes) as $item) {
            $profile->{$relation}()->create([...$item, 'sort_order' => ++$sortOrder]);
            $created++;
        }

        return $created;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @param  list<int>  $approvedIndexes
     */
    private function appendSkills(Profile $profile, array $items, array $approvedIndexes): int
    {
        $existing = $profile->skills()->pluck('name')
            ->map(fn (string $name): string => mb_strtolower($name))
            ->all();
        $sortOrder = (int) $profile->skills()->max('sort_order');
        $created = 0;

        foreach ($this->approvedItems($items, $approvedIndexes) as $skill) {
            $name = mb_strtolower((string) $skill['name']);

            if (in_array($name, $existing, true)) {
                continue;
            }

            $profile->skills()->create([...$skill, 'sort_order' => ++$sortOrder]);
            $existing[] = $name;
            $created++;
        }

        return $created;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @param  list<int>  $approvedIndexes
     * @return list<array<string, mixed>>
     */
    private function approvedItems(array $items, array $approvedIndexes): array
    {
        $items = array_values($items);
        $approved = [];

        foreach ($approvedIndexes as $index) {
            if (isset($items[$index]) && is_array($items[$index])) {
                $approved[] = $items[$index];
            }
        }

        return $approved;
    }

    /**
     * Reduce the model response to storable proposals, dropping unusable rows.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data, string $sourceText): array
    {
        $skipped = max(0, (int) ($data['experience_skipped'] ?? 0));
        $professional = [];

        foreach (array_keys(self::PROFILE_FIELDS) as $key) {
            $professional[$key] = $this->text($data['professional'][$key] ?? null);
        }

        $experiences = $this->normalizeList($data['experiences'] ?? null, ['job_title', 'company'], $skipped, fn (array $item): array => [
            'job_title' => $this->text($item['job_title']),
            'company' => $this->text($item['company']),
            'location' => $this->text($item['location'] ?? null),
            'employment_type' => $this->text($item['employment_type'] ?? null),
            'start_date' => $this->date($item['start_date'] ?? null),
            'end_date' => $this->date($item['end_date'] ?? null),
            'currently_employed' => (bool) ($item['currently_employed'] ?? false),
            'summary' => $this->text($item['summary'] ?? null),
            'achievements' => $this->textList($item['achievements'] ?? null),
            'technologies' => $this->textList($item['technologies'] ?? null),
            '_source_block' => is_string($item['_source_block'] ?? null) ? $item['_source_block'] : null,
        ]);
        $skills = $this->normalizeList($data['skills'] ?? null, ['name'], $skipped, fn (array $item): array => [
            'name' => $this->text($item['name']),
            'category' => $this->text($item['category'] ?? null),
            'proficiency' => $this->text($item['proficiency'] ?? null),
        ]);
        $projects = $this->normalizeList($data['projects'] ?? null, ['name'], $skipped, fn (array $item): array => [
            'name' => $this->text($item['name']),
            'role' => $this->text($item['role'] ?? null),
            'description' => $this->text($item['description'] ?? null),
            'outcomes' => $this->text($item['outcomes'] ?? null),
            'technologies' => $this->textList($item['technologies'] ?? null),
            'url' => $this->text($item['url'] ?? null),
            'repository_url' => $this->text($item['repository_url'] ?? null),
            'start_date' => $this->date($item['start_date'] ?? null),
            'end_date' => $this->date($item['end_date'] ?? null),
        ]);
        $education = $this->normalizeList($data['education'] ?? null, ['institution', 'qualification'], $skipped, fn (array $item): array => [
            'institution' => $this->text($item['institution']),
            'qualification' => $this->text($item['qualification']),
            'subject' => $this->text($item['subject'] ?? null),
            'grade' => $this->text($item['grade'] ?? null),
            'start_date' => $this->date($item['start_date'] ?? null),
            'end_date' => $this->date($item['end_date'] ?? null),
        ]);
        $certifications = $this->normalizeList($data['certifications'] ?? null, ['name'], $skipped, fn (array $item): array => [
            'name' => $this->text($item['name']),
            'organisation' => $this->text($item['organisation'] ?? null),
            'issue_date' => $this->date($item['issue_date'] ?? null),
            'expiry_date' => $this->date($item['expiry_date'] ?? null),
            'credential_id' => $this->text($item['credential_id'] ?? null),
            'credential_url' => $this->text($item['credential_url'] ?? null),
        ]);

        $experiences = $this->groundExperiences($experiences, $skipped);
        $skills = $this->groundSkills($skills, $sourceText, $skipped);
        $projects = $this->groundProjects($projects, $sourceText, $skipped);
        $education = $this->groundEducation($education, $sourceText, $skipped);
        $certifications = $this->groundCertifications($certifications, $sourceText, $skipped);

        // A stored experience must have a start date, so unreadable dates are
        // reported as skipped rather than guessed.
        $datedExperiences = array_values(array_filter(
            $experiences,
            static fn (array $experience): bool => $experience['start_date'] !== null,
        ));
        $skipped += count($experiences) - count($datedExperiences);
        $experiences = $datedExperiences;

        return [
            'professional' => $professional,
            'experiences' => $experiences,
            'skills' => $skills,
            'projects' => $projects,
            'education' => $education,
            'certifications' => $certifications,
            'skipped' => $skipped,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $experiences
     * @return list<array<string, mixed>>
     */
    private function groundExperiences(array $experiences, int &$skipped): array
    {
        $grounded = [];

        foreach ($experiences as $experience) {
            $jobTitle = (string) $experience['job_title'];
            $company = (string) $experience['company'];
            $evidence = is_string($experience['_source_block'] ?? null) ? $experience['_source_block'] : '';
            unset($experience['_source_block']);

            if ($this->isCareerBreak($jobTitle)) {
                $skipped++;
                continue;
            }

            if ($evidence === ''
                || $this->isPlaceholderCompany($company)
                || ! $this->sourceContainsComparable($evidence, $jobTitle)
                || ! $this->sourceContainsComparable($evidence, $company)) {
                $skipped++;

                continue;
            }

            $experience['location'] = $this->supportedText($experience['location'], $evidence);
            $experience['employment_type'] = $this->supportedText($experience['employment_type'], $evidence);
            $experience['start_date'] = $this->supportedDate($experience['start_date'], $evidence);
            $experience['end_date'] = $this->supportedDate($experience['end_date'], $evidence);
            $experience['technologies'] = array_values(array_filter(
                $experience['technologies'],
                fn (string $technology): bool => $this->sourceContains($evidence, $technology),
            ));
            $grounded[] = $experience;
        }

        return $grounded;
    }

    private function sourceContainsComparable(string $sourceText, string $value): bool
    {
        $sourceText = $this->comparableText($sourceText);
        $value = $this->comparableText($value);

        return $value !== '' && Str::contains($sourceText, $value);
    }

    private function comparableText(string $value): string
    {
        $value = Str::lower(str_replace(['–', '—', '−'], '-', $value));
        $value = preg_replace('/[^\pL\pN]+/u', ' ', $value) ?? '';

        return (string) Str::of($value)->squish();
    }

    private function isCareerBreak(string $jobTitle): bool
    {
        return preg_match('/\bcareer\s+(?:break|gap)\b/iu', $jobTitle) === 1;
    }

    private function isPlaceholderCompany(string $company): bool
    {
        return in_array((string) Str::of($company)->trim()->lower(), [
            'n/a',
            'na',
            'none',
            'not applicable',
            'not provided',
            'unknown',
        ], true);
    }

    /**
     * @param  list<array<string, mixed>>  $skills
     * @return list<array<string, mixed>>
     */
    private function groundSkills(array $skills, string $sourceText, int &$skipped): array
    {
        $section = $this->sectionLocator->skills($sourceText);

        return $this->groundedItems($skills, $section, $skipped, function (array $skill, string $section): ?array {
            $name = mb_strtolower((string) $skill['name']);

            if (in_array($name, self::SKILL_CATEGORY_HEADINGS, true) || ! $this->sourceContains($section, $skill['name'])) {
                return null;
            }

            $skill['category'] = $this->supportedText($skill['category'], $section);
            $skill['proficiency'] = $this->supportedText($skill['proficiency'], $section);

            return $skill;
        });
    }

    /**
     * @param  list<array<string, mixed>>  $projects
     * @return list<array<string, mixed>>
     */
    private function groundProjects(array $projects, string $sourceText, int &$skipped): array
    {
        $section = $this->sectionLocator->projects($sourceText);

        return $this->groundedItems($projects, $section, $skipped, function (array $project, string $section): ?array {
            if (! $this->sourceContains($section, $project['name'])) {
                return null;
            }

            $project['start_date'] = $this->supportedDate($project['start_date'], $section);
            $project['end_date'] = $this->supportedDate($project['end_date'], $section);

            return $project;
        });
    }

    /**
     * @param  list<array<string, mixed>>  $education
     * @return list<array<string, mixed>>
     */
    private function groundEducation(array $education, string $sourceText, int &$skipped): array
    {
        $section = $this->sectionLocator->education($sourceText);

        return $this->groundedItems($education, $section, $skipped, function (array $record, string $section): ?array {
            if (! $this->sourceContains($section, $record['institution'])
                || ! $this->sourceContains($section, $record['qualification'])) {
                return null;
            }

            $record['subject'] = $this->supportedText($record['subject'], $section);
            $record['grade'] = $this->supportedText($record['grade'], $section);
            $record['start_date'] = $this->supportedDate($record['start_date'], $section);
            $record['end_date'] = $this->supportedDate($record['end_date'], $section);

            return $record;
        });
    }

    /**
     * @param  list<array<string, mixed>>  $certifications
     * @return list<array<string, mixed>>
     */
    private function groundCertifications(array $certifications, string $sourceText, int &$skipped): array
    {
        $section = $this->sectionLocator->certifications($sourceText);

        return $this->groundedItems($certifications, $section, $skipped, function (array $certification, string $section): ?array {
            if (! $this->sourceContains($section, $certification['name'])) {
                return null;
            }

            $certification['organisation'] = $this->supportedText($certification['organisation'], $section);
            $certification['issue_date'] = $this->supportedDate($certification['issue_date'], $section);
            $certification['expiry_date'] = $this->supportedDate($certification['expiry_date'], $section);
            $certification['credential_id'] = $this->supportedText($certification['credential_id'], $section);
            $certification['credential_url'] = $this->supportedText($certification['credential_url'], $section);

            return $certification;
        });
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @param  callable(array<string, mixed>, string): ?array<string, mixed>  $ground
     * @return list<array<string, mixed>>
     */
    private function groundedItems(array $items, ?string $section, int &$skipped, callable $ground): array
    {
        if ($section === null) {
            $skipped += count($items);

            return [];
        }

        $grounded = [];

        foreach ($items as $item) {
            $groundedItem = $ground($item, $section);

            if ($groundedItem === null) {
                $skipped++;

                continue;
            }

            $grounded[] = $groundedItem;
        }

        return $grounded;
    }

    /** @param list<string> $headings */
    private function sectionText(string $sourceText, array $headings): ?string
    {
        return $this->sections->extract($sourceText, $headings);
    }

    private function supportedText(mixed $value, string $sourceText): ?string
    {
        $value = $this->text($value);

        return $value !== null && $this->sourceContains($sourceText, $value) ? $value : null;
    }

    private function supportedDate(mixed $value, string $sourceText): ?string
    {
        $value = $this->date($value);

        if ($value === null) {
            return null;
        }

        return $this->sourceContains($sourceText, $value)
            || $this->sourceContains($sourceText, Str::substr($value, 0, 4))
                ? $value
                : null;
    }

    private function sourceContains(string $sourceText, mixed $value): bool
    {
        $value = $this->text($value);

        return $value !== null && Str::of($sourceText)->contains($value, ignoreCase: true);
    }

    /**
     * @param  list<string>  $requiredKeys
     * @param  callable(array<string, mixed>): array<string, mixed>  $map
     * @return list<array<string, mixed>>
     */
    private function normalizeList(mixed $items, array $requiredKeys, int &$skipped, callable $map): array
    {
        if (! is_array($items)) {
            return [];
        }

        $normalized = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                $skipped++;

                continue;
            }

            $missingRequired = false;

            foreach ($requiredKeys as $key) {
                if ($this->text($item[$key] ?? null) === null) {
                    $missingRequired = true;
                }
            }

            if ($missingRequired) {
                $skipped++;

                continue;
            }

            $normalized[] = $map($item);
        }

        return $normalized;
    }

    private function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return is_int($value) || is_float($value) ? (string) $value : null;
        }

        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        return $value === '' ? null : $value;
    }

    /**
     * @return list<string>
     */
    private function textList(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        $list = [];

        foreach ($values as $value) {
            $text = $this->text($value);

            if ($text !== null) {
                $list[] = $text;
            }
        }

        return array_values(array_unique($list));
    }

    /** Accept full, month, or year precision dates and reject anything else. */
    private function date(mixed $value): ?string
    {
        $value = $this->text($value);

        if ($value === null) {
            return null;
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches) === 1) {
            return checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1]) ? $value : null;
        }

        if (preg_match('/^(\d{4})-(\d{2})$/', $value, $matches) === 1) {
            return checkdate((int) $matches[2], 1, (int) $matches[1]) ? "{$value}-01" : null;
        }

        if (preg_match('/^(\d{4})$/', $value) === 1) {
            return "{$value}-01-01";
        }

        return null;
    }
}
