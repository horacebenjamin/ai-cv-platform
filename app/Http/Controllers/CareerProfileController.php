<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCareerProfileRequest;
use App\Models\User;
use App\Services\Profile\CareerProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CareerProfileController extends Controller
{
    public function __construct(private readonly CareerProfileService $careerProfile) {}

    public function edit(Request $request): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        $tab = $request->query('tab');

        return Inertia::render('CareerProfile/Edit', [
            ...$this->careerProfile->edit($user, is_string($tab) ? $tab : 'overview'),
        ]);
    }

    public function update(UpdateCareerProfileRequest $request): RedirectResponse
    {
        $request->user()->profile()->updateOrCreate([], $request->validated());

        return to_route('career-profile.edit')->with('status', 'career-profile-updated');
    }
}
