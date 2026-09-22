<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileEducationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CareerEducationController extends CareerSectionController
{
    protected string $relation = 'education';

    protected string $tab = 'education';

    public function store(ProfileEducationRequest $request): RedirectResponse
    {
        return $this->createRecord($request);
    }

    public function update(ProfileEducationRequest $request, int $education): RedirectResponse
    {
        return $this->updateRecord($request, $education);
    }

    public function destroy(Request $request, int $education): RedirectResponse
    {
        return $this->deleteRecord($request, $education);
    }
}
