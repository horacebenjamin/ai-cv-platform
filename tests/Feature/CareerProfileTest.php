<?php

use App\Models\Profile;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function createCareerProfile(User $user, array $attributes = []): Profile
{
    return Profile::query()->create($attributes + [
        'user_id' => $user->id,
        'first_name' => 'Alex',
        'last_name' => 'Taylor',
    ]);
}

test('guests cannot access the career profile', function (): void {
    $this->get(route('career-profile.edit'))->assertRedirect(route('login'));
    $this->patch(route('career-profile.update'))->assertRedirect(route('login'));
});

test('authenticated users can view an empty career profile workspace', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('career-profile.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('CareerProfile/Edit')
            ->where('profile.firstName', null)
            ->where('profile.lastName', null)
            ->where('completeness.percentage', 0)
            ->where('completeness.completedFields', 0)
            ->where('completeness.totalFields', 7)
            ->has('completeness.completedAreas', 0)
            ->has('completeness.missingAreas', 7)
            ->where('completeness.sectionCompleteness.attentionCount', 3)
            ->where('completeness.sectionCompleteness.summary', '3 sections need attention')
            ->has('completeness.sectionCompleteness.areas', 3)
            ->missing('profile.id')
            ->missing('profile.user_id'));
});

test('career profile responses contain only the authenticated users profile', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    createCareerProfile($user, ['first_name' => 'Current']);
    createCareerProfile($otherUser, [
        'first_name' => 'Other',
        'headline' => 'Other user headline',
        'phone' => '+44 7700 900999',
        'location' => 'Other location',
        'linkedin_url' => 'https://www.linkedin.com/in/other-user',
        'bio' => 'Private profile content belonging to another user.',
    ]);

    $this->actingAs($user)
        ->get(route('career-profile.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('profile.firstName', 'Current')
            ->where('profile.bio', null)
            ->where('completeness.percentage', 29)
            ->where('completeness.sectionCompleteness.attentionCount', 3)
            ->missing('profile.id')
            ->missing('profile.userId')
            ->etc());
});

test('valid career profile details are created and updated', function (): void {
    $user = User::factory()->create();
    $attributes = [
        'first_name' => 'Alex',
        'last_name' => 'Morgan',
        'headline' => 'Senior Product Designer',
        'phone' => '+44 7700 900123',
        'location' => 'London, UK',
        'website' => 'https://alex.example.com',
        'linkedin_url' => 'https://www.linkedin.com/in/alex-morgan',
        'github_url' => 'https://github.com/alex-morgan',
        'portfolio_url' => 'https://portfolio.example.com',
        'bio' => 'Product designer focused on accessible workflow tools.',
    ];

    $this->actingAs($user)
        ->patch(route('career-profile.update'), $attributes)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('career-profile.edit'));

    $profile = $user->profile()->firstOrFail();

    expect($profile->only(array_keys($attributes)))->toMatchArray($attributes);

    $this->actingAs($user)
        ->patch(route('career-profile.update'), [
            ...$attributes,
            'headline' => 'Lead Product Designer',
        ])
        ->assertSessionHasNoErrors();

    expect($profile->refresh()->headline)->toBe('Lead Product Designer')
        ->and($user->profile()->count())->toBe(1);
});

test('invalid career profile details return field errors and preserve input', function (): void {
    $user = User::factory()->create();
    $profile = createCareerProfile($user, ['headline' => 'Existing headline']);

    $this->actingAs($user)
        ->from(route('career-profile.edit'))
        ->patch(route('career-profile.update'), [
            'first_name' => '',
            'last_name' => 'Taylor',
            'headline' => 'Preserve this value',
            'website' => 'not-a-url',
        ])
        ->assertInvalid(['first_name', 'website'])
        ->assertSessionHasInput('headline', 'Preserve this value')
        ->assertRedirect(route('career-profile.edit'));

    expect($profile->refresh()->headline)->toBe('Existing headline');
});

test('frontend identifiers and unrelated fields cannot alter profile ownership', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $profile = createCareerProfile($user);
    $otherProfile = createCareerProfile($otherUser, ['first_name' => 'Other']);

    $this->actingAs($user)
        ->patch(route('career-profile.update'), [
            'profile_id' => $otherProfile->id,
            'user_id' => $otherUser->id,
            'avatar' => 'other-user-avatar.png',
            'first_name' => 'Updated',
            'last_name' => 'Taylor',
        ])
        ->assertSessionHasNoErrors();

    expect($profile->refresh()->first_name)->toBe('Updated')
        ->and($profile->user_id)->toBe($user->id)
        ->and($profile->avatar)->toBeNull()
        ->and($otherProfile->refresh()->first_name)->toBe('Other')
        ->and($otherProfile->user_id)->toBe($otherUser->id);
});

test('career profile completeness covers minimal partial and complete profiles', function (): void {
    $user = User::factory()->create();
    $profile = createCareerProfile($user);

    $this->actingAs($user)
        ->get(route('career-profile.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('completeness.percentage', 29)
            ->where('completeness.completedFields', 2)
            ->has('completeness.missingAreas', 5)
            ->where('completeness.sectionCompleteness.attentionCount', 3)
            ->where('completeness.sectionCompleteness.summary', '3 sections need attention')
            ->where('completeness.sectionCompleteness.areas.0.key', 'professional_identity')
            ->where('completeness.sectionCompleteness.areas.0.status', 'incomplete')
            ->where('completeness.sectionCompleteness.areas.1.status', 'incomplete')
            ->where('completeness.sectionCompleteness.areas.2.status', 'incomplete')
            ->etc());

    $profile->update([
        'headline' => 'Product Designer',
        'phone' => '+44 7700 900123',
        'location' => 'London',
    ]);

    $this->actingAs($user)
        ->get(route('career-profile.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('completeness.percentage', 71)
            ->where('completeness.completedFields', 5)
            ->has('completeness.missingAreas', 2)
            ->where('completeness.sectionCompleteness.attentionCount', 2)
            ->where('completeness.sectionCompleteness.summary', '2 sections need attention')
            ->where('completeness.sectionCompleteness.areas.0.status', 'complete')
            ->where('completeness.sectionCompleteness.areas.1.status', 'incomplete')
            ->where('completeness.sectionCompleteness.areas.2.status', 'incomplete')
            ->etc());

    $profile->update([
        'bio' => 'Experienced product designer.',
    ]);

    $this->actingAs($user)
        ->get(route('career-profile.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('completeness.percentage', 86)
            ->where('completeness.completedFields', 6)
            ->has('completeness.missingAreas', 1)
            ->where('completeness.sectionCompleteness.attentionCount', 1)
            ->where('completeness.sectionCompleteness.summary', '1 section needs attention')
            ->where('completeness.sectionCompleteness.areas.0.status', 'complete')
            ->where('completeness.sectionCompleteness.areas.1.status', 'complete')
            ->where('completeness.sectionCompleteness.areas.2.status', 'incomplete')
            ->etc());

    $profile->update([
        'linkedin_url' => 'https://www.linkedin.com/in/alex-taylor',
    ]);

    $this->actingAs($user)
        ->get(route('career-profile.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('completeness.percentage', 100)
            ->where('completeness.completedFields', 7)
            ->has('completeness.missingAreas', 0)
            ->where('completeness.sectionCompleteness.attentionCount', 0)
            ->where('completeness.sectionCompleteness.summary', 'All profile sections complete')
            ->where('completeness.sectionCompleteness.areas.0.status', 'complete')
            ->where('completeness.sectionCompleteness.areas.1.status', 'complete')
            ->where('completeness.sectionCompleteness.areas.2.status', 'complete')
            ->etc());
});

test('professional links section ignores phone and accepts supported profile links', function (): void {
    $user = User::factory()->create();
    $profile = createCareerProfile($user, [
        'phone' => '+44 7700 900123',
    ]);

    $this->actingAs($user)
        ->get(route('career-profile.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('completeness.sectionCompleteness.areas.0.key', 'professional_identity')
            ->where('completeness.sectionCompleteness.areas.0.status', 'incomplete')
            ->where('completeness.sectionCompleteness.areas.1.key', 'professional_summary')
            ->where('completeness.sectionCompleteness.areas.1.status', 'incomplete')
            ->where('completeness.sectionCompleteness.areas.2.key', 'professional_links')
            ->where('completeness.sectionCompleteness.areas.2.label', 'Professional contact info')
            ->where('completeness.sectionCompleteness.areas.2.status', 'incomplete')
            ->etc());

    $profile->update([
        'website' => 'https://alex.example.com',
    ]);

    $this->actingAs($user)
        ->get(route('career-profile.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('completeness.sectionCompleteness.areas.0.status', 'incomplete')
            ->where('completeness.sectionCompleteness.areas.1.status', 'incomplete')
            ->where('completeness.sectionCompleteness.areas.2.status', 'complete')
            ->etc());
});

test('dashboard and career profile use the same completeness result', function (): void {
    $user = User::factory()->create();
    createCareerProfile($user, [
        'headline' => 'Platform Engineer',
        'phone' => '+44 7700 900123',
        'location' => 'Manchester',
    ]);

    $careerCompleteness = $this->actingAs($user)
        ->get(route('career-profile.edit'))
        ->inertiaProps('completeness');
    $dashboardCompleteness = $this->actingAs($user)
        ->get(route('dashboard'))
        ->inertiaProps('profile');

    expect($careerCompleteness)->toBe($dashboardCompleteness);
});

test('dashboard next focus changes after completing the career profile', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('nextFocus.key', 'profile')
            ->etc());

    createCareerProfile($user, [
        'headline' => 'Platform Engineer',
        'phone' => '+44 7700 900123',
        'location' => 'Manchester',
        'linkedin_url' => 'https://www.linkedin.com/in/alex-taylor',
        'bio' => 'Experienced platform engineer.',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('profile.percentage', 100)
            ->where('nextFocus.key', 'cv')
            ->etc());
});
