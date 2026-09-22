<?php

use App\Ai\Agents\ClassifyCvSectionsAgent;
use App\Ai\Agents\ImportCvAgent;
use App\Ai\Agents\ImportExperienceAgent;
use App\Jobs\ProcessAIRequest;
use App\Models\AiRequest;
use App\Models\Profile;
use App\Models\ProfileImport;
use App\Models\ProfileSkill;
use App\Models\User;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Usage;
use Laravel\Ai\Responses\StructuredTextResponse;

beforeEach(function (): void {
    config()->set('ai.default', 'openai');
    config()->set('ai.providers.openai.models.text.default', 'fake-model');
    config()->set('ai.credits.tokens_per_credit', 1000);
    config()->set('ai.credits.minimum', 1);
    ClassifyCvSectionsAgent::fake()->preventStrayPrompts();
});

function importedCvText(): string
{
    return <<<'TEXT'
    Alex Taylor
    Backend PHP Developer, Leeds

    Experience
    Senior Backend Developer, Acme Ltd, March 2022 to Present.
    Owned the PHP and Laravel billing platform and reduced invoice errors.

    Skills
    PHP, Laravel, MySQL

    Education
    BSc Computer Science, University of Manchester, 2015 to 2018.
    TEXT;
}

/** @return array<string, mixed> */
function importStructuredData(array $overrides = []): array
{
    return array_replace([
        'professional' => [
            'first_name' => 'Alex',
            'last_name' => 'Taylor',
            'headline' => 'Backend PHP Developer',
            'summary' => 'Builds billing platforms.',
            'location' => 'Leeds',
            'phone' => null,
            'website' => null,
            'linkedin_url' => null,
            'github_url' => null,
        ],
        'experiences' => [
            [
                'job_title' => 'Senior Backend Developer',
                'company' => 'Acme Ltd',
                'location' => 'Leeds',
                'employment_type' => null,
                'start_date' => '2022-03',
                'end_date' => null,
                'currently_employed' => true,
                'summary' => 'Owned the billing platform.',
                'achievements' => ['Reduced invoice errors'],
                'technologies' => ['PHP', 'Laravel'],
            ],
        ],
        'skills' => [
            ['name' => 'PHP', 'category' => 'Programming Languages', 'proficiency' => null],
            ['name' => 'Laravel', 'category' => 'Frameworks', 'proficiency' => null],
            ['name' => 'MySQL', 'category' => 'Databases', 'proficiency' => null],
        ],
        'projects' => [],
        'education' => [
            [
                'institution' => 'University of Manchester',
                'qualification' => 'BSc',
                'subject' => 'Computer Science',
                'grade' => null,
                'start_date' => '2015',
                'end_date' => '2018',
            ],
        ],
        'certifications' => [],
    ], $overrides);
}

function importAgentResponse(?array $data = null): StructuredTextResponse
{
    $structured = $data ?? importStructuredData();

    return new StructuredTextResponse(
        $structured,
        json_encode($structured, JSON_THROW_ON_ERROR),
        new Usage(promptTokens: 800, completionTokens: 200),
        new Meta(provider: 'openai', model: 'fake-model'),
    );
}

/** @return array<string, mixed> */
function experienceAgentData(array $experience, string $entryType = 'employment'): array
{
    return [
        'entry_type' => $entryType,
        'job_title' => $experience['job_title'] ?? null,
        'company' => $experience['company'] ?? null,
        'location' => $experience['location'] ?? null,
        'employment_type' => $experience['employment_type'] ?? null,
        'start_date' => $experience['start_date'] ?? null,
        'end_date' => $experience['end_date'] ?? null,
        'currently_employed' => $experience['currently_employed'] ?? false,
        'summary' => $experience['summary'] ?? null,
        'achievements' => $experience['achievements'] ?? [],
        'technologies' => $experience['technologies'] ?? [],
    ];
}

/**
 * @param  (Closure(string): StructuredTextResponse)|list<StructuredTextResponse>|null  $experienceResponses
 */
function readyImport(
    User $user,
    ?array $data = null,
    ?string $sourceText = null,
    Closure|array|null $experienceResponses = null,
): ProfileImport {
    $structured = $data ?? importStructuredData();
    $experienceResponses ??= array_map(
        fn (array $experience): StructuredTextResponse => importAgentResponse(experienceAgentData($experience)),
        $structured['experiences'] ?? [],
    );

    ImportCvAgent::fake([importAgentResponse($structured)])->preventStrayPrompts();
    ImportExperienceAgent::fake($experienceResponses)->preventStrayPrompts();

    $import = $user->profileImports()->create([
        'source_type' => 'pasted_text',
        'source_text' => $sourceText ?? importedCvText(),
        'status' => 'pending',
    ]);
    $request = AiRequest::query()->create([
        'user_id' => $user->id,
        'feature' => 'cv_import',
        'prompt' => json_encode(['import_id' => $import->id], JSON_THROW_ON_ERROR),
        'model' => 'fake-model',
        'status' => 'queued',
    ]);
    $import->forceFill(['ai_request_id' => $request->id])->save();

    app()->call([new ProcessAIRequest($request->id), 'handle']);

    return $import->refresh();
}

test('the AI agents use focused schemas without asking AI to extract skills', function (): void {
    $schema = new JsonSchemaTypeFactory;

    expect(array_keys((new ClassifyCvSectionsAgent)->schema($schema)))
        ->toBe(['sections'])
        ->and(array_keys((new ImportCvAgent)->schema($schema)))
        ->toBe(['professional', 'projects', 'education', 'certifications'])
        ->and(array_keys((new ImportExperienceAgent)->schema($schema)))
        ->toBe([
            'entry_type',
            'job_title',
            'company',
            'location',
            'employment_type',
            'start_date',
            'end_date',
            'currently_employed',
            'summary',
            'achievements',
            'technologies',
        ]);
});

test('common headings keep the deterministic fast path and do not invoke section classification', function (): void {
    Queue::fake();
    ClassifyCvSectionsAgent::fake()->preventStrayPrompts();

    $import = readyImport(User::factory()->create());

    expect($import->status)->toBe('ready');
    ClassifyCvSectionsAgent::assertNeverPrompted();
});

test('one fallback request resolves unusual headings into the existing specialised pipelines', function (): void {
    Queue::fake();

    $sourceText = <<<'TEXT'
        Alex Taylor
        Full Stack Developer

        Career Journey

        Senior Developer | Acme Ltd | January 2024 - Present
        - Built internal systems.

        Technology Stack

        Backend: PHP, Laravel
        Frontend: Vue.js, Tailwind CSS

        Academic Background

        BSc Computer Science
        Example University

        Professional Credentials

        AWS Certified Developer
        Amazon Web Services

        Selected Work

        Customer Portal
        Built a customer self-service portal.

        Interests

        Running, photography, travel
        TEXT;
    $data = importStructuredData([
        'experiences' => [[
            'job_title' => 'Senior Developer',
            'company' => 'Acme Ltd',
            'location' => null,
            'employment_type' => null,
            'start_date' => '2024-01',
            'end_date' => null,
            'currently_employed' => true,
            'summary' => 'Built internal systems.',
            'achievements' => ['Built internal systems.'],
            'technologies' => [],
        ]],
        'projects' => [[
            'name' => 'Customer Portal',
            'role' => null,
            'description' => 'Built a customer self-service portal.',
            'outcomes' => null,
            'technologies' => [],
            'url' => null,
            'repository_url' => null,
            'start_date' => null,
            'end_date' => null,
        ]],
        'education' => [[
            'institution' => 'Example University',
            'qualification' => 'BSc',
            'subject' => 'Computer Science',
            'grade' => null,
            'start_date' => null,
            'end_date' => null,
        ]],
        'certifications' => [[
            'name' => 'AWS Certified Developer',
            'organisation' => 'Amazon Web Services',
            'issue_date' => null,
            'expiry_date' => null,
            'credential_id' => null,
            'credential_url' => null,
        ]],
    ]);

    $classificationCalls = 0;
    ClassifyCvSectionsAgent::fake(function () use (&$classificationCalls): array {
        $classificationCalls++;

        return [
            'sections' => [
                ['heading' => 'Career Journey', 'type' => 'experience'],
                ['heading' => 'Technology Stack', 'type' => 'skills'],
                ['heading' => 'Academic Background', 'type' => 'education'],
                ['heading' => 'Professional Credentials', 'type' => 'certifications'],
                ['heading' => 'Selected Work', 'type' => 'projects'],
                ['heading' => 'Interests', 'type' => 'other'],
            ],
        ];
    })->preventStrayPrompts();

    $import = readyImport($user = User::factory()->create(), $data, $sourceText);

    expect($import->extracted['experiences'])
        ->toHaveCount(1)
        ->and($import->extracted['experiences'][0]['company'])->toBe('Acme Ltd')
        ->and($import->extracted['skills'])->toBe([
            ['name' => 'PHP', 'category' => 'Backend', 'proficiency' => null],
            ['name' => 'Laravel', 'category' => 'Backend', 'proficiency' => null],
            ['name' => 'Vue.js', 'category' => 'Frontend', 'proficiency' => null],
            ['name' => 'Tailwind CSS', 'category' => 'Frontend', 'proficiency' => null],
        ])
        ->and($import->extracted['education'][0]['institution'])->toBe('Example University')
        ->and($import->extracted['certifications'][0]['name'])->toBe('AWS Certified Developer')
        ->and($import->extracted['projects'][0]['name'])->toBe('Customer Portal')
        ->and($classificationCalls)->toBe(1)
        ->and($user->profile()->exists())->toBeFalse();

    ClassifyCvSectionsAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Career Journey')
        && $prompt->contains('Technology Stack')
        && $prompt->contains('Academic Background')
        && $prompt->contains('Professional Credentials')
        && $prompt->contains('Selected Work')
        && $prompt->contains('Interests')
        && ! $prompt->contains('Built internal systems.')
        && ! $prompt->contains('Frontend: Vue.js'));
    ImportExperienceAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Senior Developer')
        && $prompt->contains('Built internal systems.')
        && ! $prompt->contains('Technology Stack'));
    ImportCvAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Academic Background')
        && $prompt->contains('Professional Credentials')
        && $prompt->contains('Selected Work')
        && ! $prompt->contains('Career Journey')
        && ! $prompt->contains('Technology Stack'));
});

test('an unusual experience heading at the start of a CV reaches experience proposals', function (): void {
    Queue::fake();

    $sourceText = <<<'TEXT'
        Career Journey

        Senior Developer | Acme Ltd | January 2024 - Present
        - Built internal systems.
        - Improved reporting performance.

        Technology Stack

        Backend: PHP, Laravel
        Frontend: Vue.js, Tailwind CSS

        Academic Background

        BSc Computer Science
        Example University

        Professional Credentials

        AWS Certified Developer
        TEXT;
    $experience = [
        'job_title' => 'Senior Developer',
        'company' => 'Acme Ltd',
        'location' => null,
        'employment_type' => null,
        'start_date' => '2024-01',
        'end_date' => null,
        'currently_employed' => true,
        'summary' => null,
        'achievements' => [
            'Built internal systems.',
            'Improved reporting performance.',
        ],
        'technologies' => [],
    ];
    $data = importStructuredData([
        'experiences' => [$experience],
        'projects' => [],
        'education' => [[
            'institution' => 'Example University',
            'qualification' => 'BSc Computer Science',
            'subject' => null,
            'grade' => null,
            'start_date' => null,
            'end_date' => null,
        ]],
        'certifications' => [[
            'name' => 'AWS Certified Developer',
            'organisation' => null,
            'issue_date' => null,
            'expiry_date' => null,
            'credential_id' => null,
            'credential_url' => null,
        ]],
    ]);
    $classificationCalls = 0;

    ClassifyCvSectionsAgent::fake(function () use (&$classificationCalls): array {
        $classificationCalls++;

        return [
            'sections' => [
                ['heading' => 'Career Journey', 'type' => 'experience'],
                ['heading' => 'Technology Stack', 'type' => 'skills'],
                ['heading' => 'Academic Background', 'type' => 'education'],
                ['heading' => 'Professional Credentials', 'type' => 'certifications'],
            ],
        ];
    })->preventStrayPrompts();
    $import = readyImport(User::factory()->create(), $data, $sourceText, [
        importAgentResponse(experienceAgentData($experience)),
    ]);

    expect($classificationCalls)->toBe(1)
        ->and($import->extracted['experiences'])->toHaveCount(1)
        ->and($import->extracted['experiences'][0]['job_title'])->toBe('Senior Developer')
        ->and($import->extracted['experiences'][0]['company'])->toBe('Acme Ltd')
        ->and($import->extracted['experiences'][0]['achievements'])->toBe([
            'Built internal systems.',
            'Improved reporting performance.',
        ])
        ->and($import->extracted['experiences'][0]['technologies'])->toBe([])
        ->and($import->extracted['skills'])->toBe([
            ['name' => 'PHP', 'category' => 'Backend', 'proficiency' => null],
            ['name' => 'Laravel', 'category' => 'Backend', 'proficiency' => null],
            ['name' => 'Vue.js', 'category' => 'Frontend', 'proficiency' => null],
            ['name' => 'Tailwind CSS', 'category' => 'Frontend', 'proficiency' => null],
        ])
        ->and($import->extracted['education'][0]['qualification'])->toBe('BSc Computer Science')
        ->and($import->extracted['education'][0]['institution'])->toBe('Example University')
        ->and($import->extracted['certifications'][0]['name'])->toBe('AWS Certified Developer');

    ImportExperienceAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->prompt === <<<'TEXT'
Senior Developer | Acme Ltd | January 2024 - Present
- Built internal systems.
- Improved reporting performance.
TEXT);
});

test('a known non-career section stays outside fallback facts', function (): void {
    Queue::fake();
    ClassifyCvSectionsAgent::fake([[
        'sections' => [
            ['heading' => 'Interests', 'type' => 'other'],
        ],
    ]])->preventStrayPrompts();
    $sourceText = importedCvText().<<<'TEXT'


    Interests

    Running, photography, travel
    TEXT;

    $data = importStructuredData([
        'projects' => [[
            'name' => 'Running',
            'role' => null,
            'description' => 'Travel photography.',
            'outcomes' => null,
            'technologies' => [],
            'url' => null,
            'repository_url' => null,
            'start_date' => null,
            'end_date' => null,
        ]],
        'certifications' => [[
            'name' => 'Photography',
            'organisation' => null,
            'issue_date' => null,
            'expiry_date' => null,
            'credential_id' => null,
            'credential_url' => null,
        ]],
    ]);

    $import = readyImport(User::factory()->create(), $data, $sourceText);

    expect($import->extracted['projects'])->toBe([])
        ->and($import->extracted['certifications'])->toBe([]);
    ClassifyCvSectionsAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Interests'));
});

test('guests cannot import a CV or review proposed facts', function (): void {
    $user = User::factory()->create();
    $import = $user->profileImports()->create([
        'source_type' => 'pasted_text',
        'source_text' => importedCvText(),
        'status' => 'ready',
    ]);

    $this->post(route('career-profile.imports.store'))->assertRedirect(route('login'));
    $this->get(route('career-profile.imports.show', $import))->assertRedirect(route('login'));
    $this->post(route('career-profile.imports.apply', $import))->assertRedirect(route('login'));
});

test('pasting a CV queues extraction without touching the career profile', function (): void {
    Queue::fake();
    ImportCvAgent::fake()->preventStrayPrompts();
    ImportExperienceAgent::fake()->preventStrayPrompts();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('career-profile.imports.store'), ['source_text' => importedCvText()])
        ->assertSessionHasNoErrors();

    $import = ProfileImport::query()->sole();
    $request = AiRequest::query()->sole();

    expect($import->user_id)->toBe($user->id)
        ->and($import->status)->toBe('pending')
        ->and($import->extracted)->toBeNull()
        ->and($import->ai_request_id)->toBe($request->id)
        ->and($request->feature)->toBe('cv_import')
        ->and($request->status)->toBe('queued')
        ->and($user->profile()->exists())->toBeFalse();

    Queue::assertPushed(ProcessAIRequest::class, fn (ProcessAIRequest $job): bool => $job->aiRequestId === $request->id);
    ImportCvAgent::assertNeverPrompted();
    ImportExperienceAgent::assertNeverPrompted();
});

test('short or missing CV text is rejected', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('career-profile.edit'))
        ->post(route('career-profile.imports.store'), ['source_text' => 'Too short'])
        ->assertInvalid(['source_text']);

    expect(ProfileImport::query()->count())->toBe(0)
        ->and(AiRequest::query()->count())->toBe(0);
});

test('extraction stores proposals for review and leaves unreadable entries out', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $import = readyImport($user);

    expect($import->status)->toBe('ready')
        ->and($import->extracted['professional']['headline'])->toBe('Backend PHP Developer')
        ->and($import->extracted['experiences'])->toHaveCount(1)
        ->and($import->extracted['experiences'][0]['start_date'])->toBe('2022-03-01')
        ->and($import->extracted['education'][0]['start_date'])->toBe('2015-01-01')
        ->and($import->extracted['skills'])->toHaveCount(3)
        ->and($import->extracted['skipped'])->toBe(0)
        ->and($import->applied_at)->toBeNull()
        ->and($user->profile()->exists())->toBeFalse();

    $request = AiRequest::query()->sole();

    expect($request->status)->toBe('completed')
        ->and($request->provider)->toBe('openai')
        ->and($request->tokens_used)->toBe(2000);

    $this->actingAs($user)
        ->get(route('career-profile.imports.show', $import))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('CareerProfile/Import')
            ->where('import.status', 'ready')
            ->where('import.skippedCount', 0)
            ->has('proposed.experiences', 1)
            ->has('proposed.skills', 3)
            ->has('proposed.education', 1)
            ->where('proposed.professional.0.key', 'first_name')
            ->where('proposed.professional.0.isBlank', true)
            ->missing('import.sourceText'));
});

test('experience extraction receives only work history and skips career breaks', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $sourceText = <<<'TEXT'
        Alex Taylor
        Full Stack Developer

        Professional Experience
        Full Stack Developer | Frog Education - Education Technology Company | March 2022 — Present
        Built Laravel services for schools.

        Career Break | August 2021 — January 2022
        Temporary personal leave to support a family member.

        Technical Skills
        Backend: PHP, Laravel

        Education
        BSc Computer Science, University of Manchester, 2015 to 2018.
        TEXT;
    $data = importStructuredData([
        'experiences' => [
            [
                'job_title' => 'Full Stack Developer',
                'company' => 'Frog Education - Education Technology Company',
                'location' => null,
                'employment_type' => null,
                'start_date' => '2022-03',
                'end_date' => null,
                'currently_employed' => true,
                'summary' => 'Built Laravel services for schools.',
                'achievements' => [],
                'technologies' => ['Laravel', 'Rust'],
            ],
        ],
    ]);
    $experienceResponses = [
        importAgentResponse(experienceAgentData($data['experiences'][0])),
        importAgentResponse(experienceAgentData([], 'career_break')),
    ];

    $import = readyImport($user, $data, $sourceText, $experienceResponses);

    expect($import->extracted['experiences'])->toHaveCount(1)
        ->and($import->extracted['experiences'][0]['company'])->toBe('Frog Education - Education Technology Company')
        ->and($import->extracted['experiences'][0]['technologies'])->toBe(['Laravel'])
        ->and($import->extracted['skills'])->toBe([
            ['name' => 'PHP', 'category' => 'Backend', 'proficiency' => null],
            ['name' => 'Laravel', 'category' => 'Backend', 'proficiency' => null],
        ])
        ->and($import->extracted['skipped'])->toBe(1)
        ->and($user->profile()->exists())->toBeFalse();

    ImportExperienceAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Frog Education')
        && ! $prompt->contains('Career Break')
        && ! $prompt->contains('Technical Skills')
        && ! $prompt->contains('University of Manchester'));
    ImportExperienceAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Career Break')
        && ! $prompt->contains('Frog Education')
        && ! $prompt->contains('Technical Skills')
        && ! $prompt->contains('University of Manchester'));
    ImportCvAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('University of Manchester')
        && ! $prompt->contains('Frog Education')
        && ! $prompt->contains('Career Break')
        && ! $prompt->contains('Technical Skills'));
});

test('a career break remains separate from employment on both sides', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $sourceText = <<<'TEXT'
        Alex Taylor

        Employment History
        Software Engineer
        Company One
        January 2021 - August 2022

        • Built web applications.

        Career Break
        August 2022 - January 2023

        Temporary personal leave.

        Senior Developer
        Company Two
        February 2023 - Present

        • Built APIs.

        Skills
        PHP, Laravel
        TEXT;
    $companyOne = [
        'job_title' => 'Software Engineer',
        'company' => 'Company One',
        'location' => null,
        'employment_type' => null,
        'start_date' => '2021-01',
        'end_date' => '2022-08',
        'currently_employed' => false,
        'summary' => null,
        'achievements' => ['Built web applications.'],
        'technologies' => [],
    ];
    $companyTwo = [
        'job_title' => 'Senior Developer',
        'company' => 'Company Two',
        'location' => null,
        'employment_type' => null,
        'start_date' => '2023-02',
        'end_date' => null,
        'currently_employed' => true,
        'summary' => null,
        'achievements' => ['Built APIs.'],
        'technologies' => [],
    ];
    $responses = [
        importAgentResponse(experienceAgentData($companyOne)),
        importAgentResponse(experienceAgentData([], 'career_break')),
        importAgentResponse(experienceAgentData($companyTwo)),
    ];

    $import = readyImport(
        $user,
        importStructuredData([
            'experiences' => [],
            'education' => [],
        ]),
        $sourceText,
        $responses,
    );

    expect(array_column($import->extracted['experiences'], 'company'))->toBe(['Company One', 'Company Two'])
        ->and($import->extracted['skipped'])->toBe(1)
        ->and($user->profile()->exists())->toBeFalse();

    ImportExperienceAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Career Break')
        && ! $prompt->contains('Company One')
        && ! $prompt->contains('Company Two'));
});

test('a failed experience block is skipped while later blocks still succeed', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $sourceText = <<<'TEXT'
        Alex Taylor

        Work History
        Senior Software Engineer
        Acme Ltd
        January 2024 - Present

        • Built API integrations.

        Beta Ltd | Platform Engineer | 2022–2023
        • Maintained cloud infrastructure.

        Software Engineer, Gamma Ltd
        London, UK
        2020 - 2022

        Developed internal systems.

        Technical Skills
        PHP, Laravel

        Education
        BSc Computer Science
        TEXT;
    $prompts = [];
    $call = 0;
    $responses = function (string $prompt) use (&$prompts, &$call): StructuredTextResponse {
        $prompts[] = $prompt;
        $call++;

        if ($call === 2) {
            throw new RuntimeException('Simulated block timeout.');
        }

        $experience = $call === 1
            ? [
                'job_title' => 'Senior Software Engineer',
                'company' => 'Acme Ltd',
                'start_date' => '2024-01',
                'end_date' => null,
                'currently_employed' => true,
                'achievements' => ['Built API integrations.'],
                'technologies' => [],
            ]
            : [
                'job_title' => 'Software Engineer',
                'company' => 'Gamma Ltd',
                'location' => 'London, UK',
                'start_date' => '2020',
                'end_date' => '2022',
                'currently_employed' => false,
                'summary' => 'Developed internal systems.',
                'achievements' => [],
                'technologies' => [],
            ];

        return importAgentResponse(experienceAgentData($experience));
    };

    $import = readyImport(
        $user,
        importStructuredData([
            'experiences' => [],
            'education' => [],
        ]),
        $sourceText,
        $responses,
    );

    expect($prompts)->toHaveCount(3)
        ->and($prompts[0])->toContain('Acme Ltd')->not->toContain('Beta Ltd', 'Gamma Ltd', 'Technical Skills')
        ->and($prompts[1])->toContain('Beta Ltd')->not->toContain('Acme Ltd', 'Gamma Ltd', 'Education')
        ->and($prompts[2])->toContain('Gamma Ltd')->not->toContain('Acme Ltd', 'Beta Ltd', 'Technical Skills')
        ->and(array_column($import->extracted['experiences'], 'company'))->toBe(['Acme Ltd', 'Gamma Ltd'])
        ->and($import->extracted['skipped'])->toBe(1)
        ->and($import->aiRequest->tokens_used)->toBe(3000)
        ->and($user->profile()->exists())->toBeFalse();
});

test('extraction drops proposals that are unsupported by their source sections', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $data = importStructuredData([
        'projects' => [[
            'name' => 'Billing Platform',
            'role' => 'Senior Backend Developer',
            'description' => 'Built while employed at Acme Ltd.',
            'outcomes' => null,
            'technologies' => ['PHP'],
            'url' => null,
            'repository_url' => null,
            'start_date' => '2022',
            'end_date' => null,
        ]],
        'education' => [
            ...importStructuredData()['education'],
            [
                'institution' => 'University of Sheffield',
                'qualification' => 'BSc in Computer Science',
                'subject' => 'Computer Science',
                'grade' => 'First Class',
                'start_date' => '2015',
                'end_date' => '2019',
            ],
        ],
        'certifications' => [[
            'name' => 'AWS Certified Solutions Architect',
            'organisation' => 'Amazon Web Services',
            'issue_date' => '2022',
            'expiry_date' => '2025',
            'credential_id' => 'AWS-SA-2022-001',
            'credential_url' => 'https://aws.amazon.com/certification/solutions-architect/',
        ]],
    ]);

    $import = readyImport($user, $data);

    expect($import->extracted['skills'])->toBe([
        ['name' => 'PHP', 'category' => null, 'proficiency' => null],
        ['name' => 'Laravel', 'category' => null, 'proficiency' => null],
        ['name' => 'MySQL', 'category' => null, 'proficiency' => null],
    ])->and($import->extracted['projects'])->toBe([])
        ->and($import->extracted['education'])->toHaveCount(1)
        ->and($import->extracted['education'][0]['institution'])->toBe('University of Manchester')
        ->and($import->extracted['certifications'])->toBe([])
        ->and($import->extracted['skipped'])->toBe(3);
});

test('extraction keeps facts supported by explicit project and certification sections', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $sourceText = importedCvText().<<<'TEXT'


    Projects
    Invoice Portal, Lead Developer, 2023.

    Certifications
    AWS Certified Developer, Amazon Web Services, issued 2022, credential AWS-123.
    TEXT;
    $data = importStructuredData([
        'projects' => [[
            'name' => 'Invoice Portal',
            'role' => 'Lead Developer',
            'description' => 'An invoice management portal.',
            'outcomes' => null,
            'technologies' => ['PHP'],
            'url' => null,
            'repository_url' => null,
            'start_date' => '2023',
            'end_date' => null,
        ]],
        'certifications' => [[
            'name' => 'AWS Certified Developer',
            'organisation' => 'Amazon Web Services',
            'issue_date' => '2022',
            'expiry_date' => '2025',
            'credential_id' => 'AWS-123',
            'credential_url' => 'https://example.com/aws-123',
        ]],
    ]);

    $import = readyImport($user, $data, $sourceText);

    expect($import->extracted['projects'])->toHaveCount(1)
        ->and($import->extracted['projects'][0]['start_date'])->toBe('2023-01-01')
        ->and($import->extracted['certifications'])->toHaveCount(1)
        ->and($import->extracted['certifications'][0]['organisation'])->toBe('Amazon Web Services')
        ->and($import->extracted['certifications'][0]['issue_date'])->toBe('2022-01-01')
        ->and($import->extracted['certifications'][0]['expiry_date'])->toBeNull()
        ->and($import->extracted['certifications'][0]['credential_id'])->toBe('AWS-123')
        ->and($import->extracted['certifications'][0]['credential_url'])->toBeNull();
});

test('approving proposals appends facts and never overwrites saved profile values', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $profile = Profile::query()->create([
        'user_id' => $user->id,
        'first_name' => 'Alexandra',
        'last_name' => 'Taylor',
        'headline' => 'Existing headline',
    ]);
    ProfileSkill::query()->create(['profile_id' => $profile->id, 'name' => 'php', 'category' => 'Existing']);
    $import = readyImport($user);

    $this->actingAs($user)
        ->post(route('career-profile.imports.apply', $import), [
            'professional' => ['first_name', 'headline', 'location', 'summary'],
            'experiences' => [0],
            'skills' => [0, 1],
            'education' => [],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('career-profile.edit'));

    $profile->refresh();

    expect($profile->first_name)->toBe('Alexandra')
        ->and($profile->headline)->toBe('Existing headline')
        ->and($profile->location)->toBe('Leeds')
        ->and($profile->bio)->toBe('Builds billing platforms.')
        ->and($profile->experiences()->count())->toBe(1)
        ->and($profile->experiences()->sole()->company)->toBe('Acme Ltd')
        ->and($profile->experiences()->sole()->technologies)->toBe(['PHP', 'Laravel'])
        ->and($profile->skills()->orderBy('id')->pluck('name')->all())->toBe(['php', 'Laravel'])
        ->and($profile->education()->count())->toBe(0)
        ->and($import->refresh()->status)->toBe('applied')
        ->and($import->applied_at)->not->toBeNull();
});

test('an import cannot be applied twice or without a selection', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    Profile::query()->create([
        'user_id' => $user->id,
        'first_name' => 'Alex',
        'last_name' => 'Taylor',
    ]);
    $import = readyImport($user);

    $this->actingAs($user)
        ->from(route('career-profile.imports.show', $import))
        ->post(route('career-profile.imports.apply', $import), [])
        ->assertInvalid(['selections']);

    $this->actingAs($user)
        ->post(route('career-profile.imports.apply', $import), ['skills' => [0]])
        ->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->from(route('career-profile.imports.show', $import))
        ->post(route('career-profile.imports.apply', $import), ['skills' => [1]])
        ->assertInvalid(['selections']);

    expect($user->profile()->firstOrFail()->skills()->count())->toBe(1);
});

test('a user cannot review or apply another users import', function (): void {
    Queue::fake();

    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $import = readyImport($owner);

    $this->actingAs($otherUser)
        ->get(route('career-profile.imports.show', $import))
        ->assertNotFound();

    $this->actingAs($otherUser)
        ->post(route('career-profile.imports.apply', $import), ['skills' => [0]])
        ->assertNotFound();

    $this->actingAs($otherUser)
        ->delete(route('career-profile.imports.destroy', $import))
        ->assertNotFound();

    expect($import->refresh()->status)->toBe('ready')
        ->and($otherUser->profile()->exists())->toBeFalse();
});

test('an import can be discarded without changing the career profile', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $import = readyImport($user);

    $this->actingAs($user)
        ->delete(route('career-profile.imports.destroy', $import))
        ->assertRedirect(route('career-profile.edit', ['tab' => 'import']));

    expect($import->refresh()->status)->toBe('discarded')
        ->and($user->profile()->exists())->toBeFalse();
});

test('a failed extraction marks the import as failed and changes nothing', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $import = $user->profileImports()->create([
        'source_type' => 'pasted_text',
        'source_text' => importedCvText(),
        'status' => 'pending',
    ]);
    $request = AiRequest::query()->create([
        'user_id' => $user->id,
        'feature' => 'cv_import',
        'prompt' => json_encode(['import_id' => $import->id], JSON_THROW_ON_ERROR),
        'model' => 'fake-model',
        'status' => 'queued',
    ]);
    ImportCvAgent::fake(fn (): never => throw new RuntimeException('Simulated agent failure.'));
    ImportExperienceAgent::fake()->preventStrayPrompts();
    $job = new ProcessAIRequest($request->id);

    try {
        app()->call([$job, 'handle']);
    } catch (RuntimeException $exception) {
        $job->failed($exception);
    }

    expect($request->refresh()->status)->toBe('failed')
        ->and($import->refresh()->status)->toBe('failed')
        ->and($import->extracted)->toBeNull()
        ->and($user->profile()->exists())->toBeFalse();

    ImportExperienceAgent::assertNeverPrompted();
});
