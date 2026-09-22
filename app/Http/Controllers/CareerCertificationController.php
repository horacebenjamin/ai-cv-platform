<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileCertificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CareerCertificationController extends CareerSectionController
{
    protected string $relation = 'certifications';

    protected string $tab = 'certifications';

    public function store(ProfileCertificationRequest $request): RedirectResponse
    {
        return $this->createRecord($request);
    }

    public function update(ProfileCertificationRequest $request, int $certification): RedirectResponse
    {
        return $this->updateRecord($request, $certification);
    }

    public function destroy(Request $request, int $certification): RedirectResponse
    {
        return $this->deleteRecord($request, $certification);
    }
}
