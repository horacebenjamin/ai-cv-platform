<?php

namespace App\Services\Profile;

use App\Models\Profile;
use App\Models\ProfileCertification;
use App\Models\ProfileEducation;
use App\Models\ProfileExperience;
use App\Models\ProfileProject;
use App\Models\ProfileSkill;
use App\Models\User;
use App\Services\ProfileCompletenessService;

/**
 * Builds the customer Career Profile payload from the user's verified facts.
 */
final class CareerProfileService
{
    public const TABS = [
        'overview', 'experience', 'skills', 'projects', 'education', 'certifications',
    ];

    public function __construct(private readonly ProfileCompletenessService $completeness) {}

    /**
     * @return array<string, mixed>
     */
    public function edit(User $user, string $activeTab = 'overview'): array
    {
        $profile = $user->profile()->first();

        if ($profile !== null) {
            $profile->load([
                'experiences' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
                'skills' => fn ($query) => $query->orderBy('category')->orderBy('sort_order')->orderBy('id'),
                'projects' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
                'education' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
                'certifications' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            ]);
        }

        return [
            'activeTab' => in_array($activeTab, self::TABS, true) ? $activeTab : 'overview',
            'profile' => $this->profileData($profile),
            'completeness' => $this->completeness->for($profile),
            'sections' => $this->sections($profile),
            'options' => [
                'skillCategories' => array_values((array) config('career.skill_categories', [])),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function profileData(?Profile $profile): array
    {
        return [
            'exists' => $profile !== null,
            'firstName' => $profile?->first_name,
            'lastName' => $profile?->last_name,
            'headline' => $profile?->headline,
            'phone' => $profile?->phone,
            'location' => $profile?->location,
            'website' => $profile?->website,
            'linkedinUrl' => $profile?->linkedin_url,
            'githubUrl' => $profile?->github_url,
            'portfolioUrl' => $profile?->portfolio_url,
            'bio' => $profile?->bio,
        ];
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private function sections(?Profile $profile): array
    {
        if ($profile === null) {
            return [
                'experiences' => [], 'skills' => [], 'projects' => [],
                'education' => [], 'certifications' => [],
            ];
        }

        return [
            'experiences' => $profile->experiences->map(fn (ProfileExperience $experience): array => [
                'id' => $experience->id,
                'jobTitle' => $experience->job_title,
                'company' => $experience->company,
                'location' => $experience->location,
                'employmentType' => $experience->employment_type,
                'startDate' => $experience->start_date?->toDateString(),
                'endDate' => $experience->end_date?->toDateString(),
                'currentlyEmployed' => $experience->currently_employed,
                'summary' => $experience->summary,
                'achievements' => $experience->achievements ?? [],
                'technologies' => $experience->technologies ?? [],
            ])->all(),
            'skills' => $profile->skills->map(fn (ProfileSkill $skill): array => [
                'id' => $skill->id,
                'name' => $skill->name,
                'category' => $skill->category,
                'proficiency' => $skill->proficiency,
            ])->all(),
            'projects' => $profile->projects->map(fn (ProfileProject $project): array => [
                'id' => $project->id,
                'name' => $project->name,
                'role' => $project->role,
                'description' => $project->description,
                'context' => $project->context,
                'responsibilities' => $project->responsibilities,
                'outcomes' => $project->outcomes,
                'technologies' => $project->technologies ?? [],
                'url' => $project->url,
                'repositoryUrl' => $project->repository_url,
                'startDate' => $project->start_date?->toDateString(),
                'endDate' => $project->end_date?->toDateString(),
            ])->all(),
            'education' => $profile->education->map(fn (ProfileEducation $education): array => [
                'id' => $education->id,
                'institution' => $education->institution,
                'qualification' => $education->qualification,
                'subject' => $education->subject,
                'grade' => $education->grade,
                'startDate' => $education->start_date?->toDateString(),
                'endDate' => $education->end_date?->toDateString(),
                'description' => $education->description,
            ])->all(),
            'certifications' => $profile->certifications->map(fn (ProfileCertification $certification): array => [
                'id' => $certification->id,
                'name' => $certification->name,
                'organisation' => $certification->organisation,
                'issueDate' => $certification->issue_date?->toDateString(),
                'expiryDate' => $certification->expiry_date?->toDateString(),
                'credentialId' => $certification->credential_id,
                'credentialUrl' => $certification->credential_url,
            ])->all(),
        ];
    }
}
