<?php

use App\Models\Profile;
use App\Models\ProfileCertification;
use App\Models\ProfileEducation;
use App\Models\ProfileExperience;
use App\Models\ProfileProject;
use App\Models\ProfileSkill;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function sectionProfile(User $user, array $attributes = []): Profile
{
    return Profile::query()->create($attributes + [
        'user_id' => $user->id,
        'first_name' => 'Alex',
        'last_name' => 'Taylor',
        'headline' => 'Backend PHP Developer',
    ]);
}

function targetingPayload(array $overrides = []): array
{
    return $overrides + [
        'first_name' => 'Alex',
        'last_name' => 'Taylor',
        'headline' => 'Backend PHP Developer',
    ];
}

test('guests cannot manage career profile sections', function (): void {
    $this->post(route('career-profile.experiences.store'))->assertRedirect(route('login'));
    $this->post(route('career-profile.skills.store'))->assertRedirect(route('login'));
    $this->post(route('career-profile.projects.store'))->assertRedirect(route('login'));
    $this->post(route('career-profile.education.store'))->assertRedirect(route('login'));
    $this->post(route('career-profile.certifications.store'))->assertRedirect(route('login'));
});

test('structured work experience persists with achievements and technologies', function (): void {
    $user = User::factory()->create();
    $profile = sectionProfile($user);

    $this->actingAs($user)
        ->post(route('career-profile.experiences.store'), [
            'job_title' => 'Senior Backend Developer',
            'company' => 'Acme Ltd',
            'location' => 'Manchester, UK',
            'employment_type' => 'Permanent',
            'start_date' => '2022-03-01',
            'currently_employed' => true,
            'summary' => 'Owned the billing platform.',
            'achievements' => ['Cut invoice errors by 40%', 'Led a team of four'],
            'technologies' => ['PHP', 'Laravel', 'MySQL'],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('career-profile.edit', ['tab' => 'experience']));

    $experience = $profile->experiences()->sole();

    expect($experience->job_title)->toBe('Senior Backend Developer')
        ->and($experience->company)->toBe('Acme Ltd')
        ->and($experience->currently_employed)->toBeTrue()
        ->and($experience->end_date)->toBeNull()
        ->and($experience->achievements)->toBe(['Cut invoice errors by 40%', 'Led a team of four'])
        ->and($experience->technologies)->toBe(['PHP', 'Laravel', 'MySQL'])
        ->and($experience->sort_order)->toBe(1);

    $this->actingAs($user)
        ->patch(route('career-profile.experiences.update', $experience), [
            'job_title' => 'Lead Backend Developer',
            'company' => 'Acme Ltd',
            'start_date' => '2022-03-01',
            'end_date' => '2026-01-31',
            'currently_employed' => false,
            'technologies' => ['PHP', 'Laravel'],
        ])
        ->assertSessionHasNoErrors();

    expect($experience->refresh()->job_title)->toBe('Lead Backend Developer')
        ->and($experience->end_date?->toDateString())->toBe('2026-01-31')
        ->and($experience->currently_employed)->toBeFalse();

    $this->actingAs($user)
        ->delete(route('career-profile.experiences.destroy', $experience))
        ->assertSessionHasNoErrors();

    expect($profile->experiences()->count())->toBe(0);
});

test('invalid work experience returns field errors without saving', function (): void {
    $user = User::factory()->create();
    $profile = sectionProfile($user);

    $this->actingAs($user)
        ->from(route('career-profile.edit'))
        ->post(route('career-profile.experiences.store'), [
            'job_title' => '',
            'company' => 'Acme Ltd',
            'start_date' => '2024-05-01',
            'end_date' => '2023-01-01',
        ])
        ->assertInvalid(['job_title', 'end_date']);

    expect($profile->experiences()->count())->toBe(0);
});

test('skills persist per category and reject duplicates for the same profile', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $profile = sectionProfile($user);
    $otherProfile = sectionProfile($otherUser);

    $this->actingAs($user)
        ->post(route('career-profile.skills.store'), [
            'name' => 'Laravel',
            'category' => 'Frameworks',
            'proficiency' => 'Advanced',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('career-profile.edit', ['tab' => 'skills']));

    $skill = $profile->skills()->sole();

    expect($skill->name)->toBe('Laravel')
        ->and($skill->category)->toBe('Frameworks');

    $this->actingAs($user)
        ->from(route('career-profile.edit'))
        ->post(route('career-profile.skills.store'), ['name' => 'Laravel'])
        ->assertInvalid(['name']);

    expect($profile->skills()->count())->toBe(1);

    $this->actingAs($otherUser)
        ->post(route('career-profile.skills.store'), ['name' => 'Laravel'])
        ->assertSessionHasNoErrors();

    expect($otherProfile->skills()->count())->toBe(1);
});

test('projects education and certifications persist their structured fields', function (): void {
    $user = User::factory()->create();
    $profile = sectionProfile($user);

    $this->actingAs($user)
        ->post(route('career-profile.projects.store'), [
            'name' => 'Deployment Platform',
            'role' => 'Lead developer',
            'description' => 'Internal deployment tooling.',
            'context' => 'Releases were manual and error prone.',
            'responsibilities' => 'Designed the pipeline and rollout.',
            'outcomes' => 'Release time reduced from hours to minutes.',
            'technologies' => ['Laravel', 'Docker'],
            'url' => 'https://example.com/platform',
            'repository_url' => 'https://github.com/example/platform',
            'start_date' => '2024-01-01',
            'end_date' => '2024-09-30',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('career-profile.edit', ['tab' => 'projects']));

    $this->actingAs($user)
        ->post(route('career-profile.education.store'), [
            'institution' => 'University of Manchester',
            'qualification' => 'BSc',
            'subject' => 'Computer Science',
            'grade' => 'First class',
            'start_date' => '2015-09-01',
            'end_date' => '2018-06-30',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('career-profile.edit', ['tab' => 'education']));

    $this->actingAs($user)
        ->post(route('career-profile.certifications.store'), [
            'name' => 'AWS Certified Developer',
            'organisation' => 'Amazon Web Services',
            'issue_date' => '2025-02-01',
            'expiry_date' => '2028-02-01',
            'credential_id' => 'AWS-1234',
            'credential_url' => 'https://example.com/credential',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('career-profile.edit', ['tab' => 'certifications']));

    $project = $profile->projects()->sole();
    $education = $profile->education()->sole();
    $certification = $profile->certifications()->sole();

    expect($project->technologies)->toBe(['Laravel', 'Docker'])
        ->and($project->outcomes)->toBe('Release time reduced from hours to minutes.')
        ->and($education->subject)->toBe('Computer Science')
        ->and($education->end_date?->toDateString())->toBe('2018-06-30')
        ->and($certification->organisation)->toBe('Amazon Web Services')
        ->and($certification->expiry_date?->toDateString())->toBe('2028-02-01');
});

test('a user cannot read edit or delete another users career facts', function (): void {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $ownerProfile = sectionProfile($owner);
    sectionProfile($otherUser);

    $experience = ProfileExperience::query()->create([
        'profile_id' => $ownerProfile->id,
        'job_title' => 'Backend Developer',
        'company' => 'Private Ltd',
        'start_date' => '2021-01-01',
    ]);
    $skill = ProfileSkill::query()->create(['profile_id' => $ownerProfile->id, 'name' => 'PHP']);
    $project = ProfileProject::query()->create(['profile_id' => $ownerProfile->id, 'name' => 'Private project']);
    $education = ProfileEducation::query()->create([
        'profile_id' => $ownerProfile->id,
        'institution' => 'Private University',
        'qualification' => 'BSc',
    ]);
    $certification = ProfileCertification::query()->create([
        'profile_id' => $ownerProfile->id,
        'name' => 'Private certification',
    ]);

    $this->actingAs($otherUser)
        ->patch(route('career-profile.experiences.update', $experience), [
            'job_title' => 'Hijacked',
            'company' => 'Hijacked',
            'start_date' => '2020-01-01',
        ])
        ->assertNotFound();

    $this->actingAs($otherUser)->delete(route('career-profile.skills.destroy', $skill))->assertNotFound();
    $this->actingAs($otherUser)->delete(route('career-profile.projects.destroy', $project))->assertNotFound();
    $this->actingAs($otherUser)->delete(route('career-profile.education.destroy', $education))->assertNotFound();
    $this->actingAs($otherUser)->delete(route('career-profile.certifications.destroy', $certification))->assertNotFound();

    expect($experience->refresh()->job_title)->toBe('Backend Developer')
        ->and(ProfileSkill::query()->whereKey($skill->id)->exists())->toBeTrue()
        ->and(ProfileProject::query()->whereKey($project->id)->exists())->toBeTrue()
        ->and(ProfileEducation::query()->whereKey($education->id)->exists())->toBeTrue()
        ->and(ProfileCertification::query()->whereKey($certification->id)->exists())->toBeTrue();

    $this->actingAs($otherUser)
        ->get(route('career-profile.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('sections.experiences', 0)
            ->has('sections.skills', 0)
            ->has('sections.projects', 0)
            ->has('sections.education', 0)
            ->has('sections.certifications', 0)
            ->etc());
});

test('career facts cannot be added before professional details are saved', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('career-profile.skills.store'), ['name' => 'PHP'])
        ->assertInvalid(['profile'])
        ->assertRedirect(route('career-profile.edit'));

    expect(ProfileSkill::query()->count())->toBe(0);
});

test('the career profile page exposes owned sections and skill category options', function (): void {
    $user = User::factory()->create();
    $profile = sectionProfile($user, ['location' => 'Leeds']);
    ProfileSkill::query()->create(['profile_id' => $profile->id, 'name' => 'PHP', 'category' => 'Programming Languages']);
    ProfileSkill::query()->create(['profile_id' => $profile->id, 'name' => 'Laravel', 'category' => 'Frameworks']);
    ProfileExperience::query()->create([
        'profile_id' => $profile->id,
        'job_title' => 'Backend Developer',
        'company' => 'Acme Ltd',
        'start_date' => '2021-01-01',
        'technologies' => ['PHP'],
    ]);

    $this->actingAs($user)
        ->get(route('career-profile.edit', ['tab' => 'skills']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('CareerProfile/Edit')
            ->where('activeTab', 'skills')
            ->has('sections.skills', 2)
            ->has('sections.experiences', 1)
            ->where('sections.experiences.0.technologies', ['PHP'])
            ->has('options.skillCategories')
            ->missing('sections.skills.0.profile_id')
            ->etc());
});

test('an unknown career profile tab falls back to the overview', function (): void {
    $user = User::factory()->create();
    sectionProfile($user);

    $this->actingAs($user)
        ->get(route('career-profile.edit', ['tab' => 'not-a-tab']))
        ->assertInertia(fn (Assert $page) => $page->where('activeTab', 'overview')->etc());
});

test('configured seniority values are accepted', function (string $seniority): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('career-profile.update'), targetingPayload(['seniority' => $seniority]))
        ->assertSessionHasNoErrors();

    expect($user->profile()->sole()->seniority)->toBe($seniority);
})->with([
    'junior' => 'junior',
    'mid-level' => 'mid',
    'senior' => 'senior',
    'lead' => 'lead',
    'principal' => 'principal',
]);

test('targeting persists without damaging existing professional fields', function (): void {
    $user = User::factory()->create();
    $profile = sectionProfile($user, [
        'headline' => 'Laravel Developer',
        'bio' => 'Builds reliable web applications.',
        'location' => 'Leeds',
    ]);

    $this->actingAs($user)
        ->patch(route('career-profile.update'), targetingPayload([
            'headline' => $profile->headline,
            'bio' => $profile->bio,
            'location' => $profile->location,
            'seniority' => 'lead',
            'preferred_roles' => ['Laravel Developer', 'Backend PHP Developer'],
        ]))
        ->assertSessionHasNoErrors();

    expect($profile->refresh()->seniority)->toBe('lead')
        ->and($profile->preferred_roles)->toBe(['Laravel Developer', 'Backend PHP Developer'])
        ->and($profile->headline)->toBe('Laravel Developer')
        ->and($profile->bio)->toBe('Builds reliable web applications.')
        ->and($profile->location)->toBe('Leeds');
});

test('saved targeting and configured options are returned to the career profile page', function (): void {
    $user = User::factory()->create();
    sectionProfile($user, [
        'seniority' => 'senior',
        'preferred_roles' => ['Platform Engineer', 'Cloud Engineer'],
    ]);

    $this->actingAs($user)
        ->get(route('career-profile.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('profile.seniority', 'senior')
            ->where('profile.preferredRoles', ['Platform Engineer', 'Cloud Engineer'])
            ->where('options.seniorities', [
                ['value' => 'junior', 'label' => 'Junior'],
                ['value' => 'mid', 'label' => 'Mid-level'],
                ['value' => 'senior', 'label' => 'Senior'],
                ['value' => 'lead', 'label' => 'Lead'],
                ['value' => 'principal', 'label' => 'Principal'],
            ])
            ->where('options.suggestedRoles.0', 'Software Engineer')
            ->etc());
});

test('invalid seniority is rejected without replacing saved targeting', function (): void {
    $user = User::factory()->create();
    $profile = sectionProfile($user, [
        'seniority' => 'senior',
        'preferred_roles' => ['Platform Engineer'],
    ]);

    $this->actingAs($user)
        ->from(route('career-profile.edit'))
        ->patch(route('career-profile.update'), targetingPayload([
            'seniority' => 'emperor',
            'preferred_roles' => ['Cloud Engineer'],
        ]))
        ->assertInvalid(['seniority']);

    expect($profile->refresh()->seniority)->toBe('senior')
        ->and($profile->preferred_roles)->toBe(['Platform Engineer']);
});

test('malformed preferred role payloads are rejected', function (mixed $preferredRoles, string $error): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('career-profile.update'), targetingPayload([
            'preferred_roles' => $preferredRoles,
        ]))
        ->assertInvalid([$error]);

    expect($user->profile()->exists())->toBeFalse();
})->with([
    'not an array' => ['Backend Developer', 'preferred_roles'],
    'numeric role' => [[123], 'preferred_roles.0'],
    'nested role' => [[['name' => 'Backend Developer']], 'preferred_roles.0'],
    'blank role' => [[''], 'preferred_roles.0'],
    'duplicate roles' => [['Laravel Developer', 'laravel developer'], 'preferred_roles.1'],
    'more than ten roles' => [array_map(fn (int $index): string => "Role {$index}", range(1, 11)), 'preferred_roles'],
]);

test('targeting updates cannot affect another users profile', function (): void {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $ownerProfile = sectionProfile($owner, [
        'seniority' => 'principal',
        'preferred_roles' => ['Platform Engineer'],
    ]);
    $otherProfile = sectionProfile($otherUser);

    $this->actingAs($otherUser)
        ->patch(route('career-profile.update'), targetingPayload([
            'profile_id' => $ownerProfile->id,
            'seniority' => 'junior',
            'preferred_roles' => ['Frontend Developer'],
        ]))
        ->assertSessionHasNoErrors();

    expect($ownerProfile->refresh()->seniority)->toBe('principal')
        ->and($ownerProfile->preferred_roles)->toBe(['Platform Engineer'])
        ->and($otherProfile->refresh()->seniority)->toBe('junior')
        ->and($otherProfile->preferred_roles)->toBe(['Frontend Developer']);
});
