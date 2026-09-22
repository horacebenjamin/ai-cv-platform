<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileSkillRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CareerSkillController extends CareerSectionController
{
    protected string $relation = 'skills';

    protected string $tab = 'skills';

    public function store(ProfileSkillRequest $request): RedirectResponse
    {
        return $this->createRecord($request);
    }

    public function update(ProfileSkillRequest $request, int $skill): RedirectResponse
    {
        return $this->updateRecord($request, $skill);
    }

    public function destroy(Request $request, int $skill): RedirectResponse
    {
        return $this->deleteRecord($request, $skill);
    }
}
