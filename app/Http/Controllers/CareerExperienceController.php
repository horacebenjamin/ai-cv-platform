<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileExperienceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CareerExperienceController extends CareerSectionController
{
    protected string $relation = 'experiences';

    protected string $tab = 'experience';

    public function store(ProfileExperienceRequest $request): RedirectResponse
    {
        return $this->createRecord($request);
    }

    public function update(ProfileExperienceRequest $request, int $experience): RedirectResponse
    {
        return $this->updateRecord($request, $experience);
    }

    public function destroy(Request $request, int $experience): RedirectResponse
    {
        return $this->deleteRecord($request, $experience);
    }
}
