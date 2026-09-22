<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileProjectRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CareerProjectController extends CareerSectionController
{
    protected string $relation = 'projects';

    protected string $tab = 'projects';

    public function store(ProfileProjectRequest $request): RedirectResponse
    {
        return $this->createRecord($request);
    }

    public function update(ProfileProjectRequest $request, int $project): RedirectResponse
    {
        return $this->updateRecord($request, $project);
    }

    public function destroy(Request $request, int $project): RedirectResponse
    {
        return $this->deleteRecord($request, $project);
    }
}
