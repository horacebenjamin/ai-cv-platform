<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCareerProfileRequest;
use App\Models\Profile;
use App\Models\User;
use App\Services\ProfileCompletenessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CareerProfileController extends Controller
{
    public function __construct(private readonly ProfileCompletenessService $profileCompleteness) {}

    public function edit(Request $request): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        $profile = $user->profile()->first();

        return Inertia::render('CareerProfile/Edit', [
            'profile' => $this->profileData($profile),
            'completeness' => $this->profileCompleteness->for($profile),
        ]);
    }

    public function update(UpdateCareerProfileRequest $request): RedirectResponse
    {
        $request->user()->profile()->updateOrCreate([], $request->validated());

        return to_route('career-profile.edit')->with('status', 'career-profile-updated');
    }

    /**
     * @return array{
     *     firstName: string|null,
     *     lastName: string|null,
     *     headline: string|null,
     *     phone: string|null,
     *     location: string|null,
     *     website: string|null,
     *     linkedinUrl: string|null,
     *     githubUrl: string|null,
     *     portfolioUrl: string|null,
     *     bio: string|null
     * }
     */
    private function profileData(?Profile $profile): array
    {
        return [
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
}
