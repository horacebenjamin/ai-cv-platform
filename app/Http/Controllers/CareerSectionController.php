<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Shared behaviour for the Career Profile's structured fact sections.
 *
 * Records are always resolved through the authenticated user's profile
 * relationship, so an identifier belonging to another user cannot be read,
 * edited, or deleted.
 */
abstract class CareerSectionController extends Controller
{
    /** The Profile relationship that holds this section's records. */
    protected string $relation;

    /** The Career Profile tab this section belongs to. */
    protected string $tab;

    /**
     * Map the validated request to storable attributes.
     *
     * @return array<string, mixed>
     */
    protected function attributes(FormRequest $request): array
    {
        return $request->validated();
    }

    protected function createRecord(FormRequest $request): RedirectResponse
    {
        $profile = $this->profile($request);

        if ($profile === null) {
            return $this->missingProfileResponse();
        }

        $sortOrder = (int) $profile->{$this->relation}()->max('sort_order');

        $profile->{$this->relation}()->create([
            ...$this->attributes($request),
            'sort_order' => $sortOrder + 1,
        ]);

        return $this->sectionResponse();
    }

    protected function updateRecord(FormRequest $request, int $recordId): RedirectResponse
    {
        $profile = $this->profile($request);

        if ($profile === null) {
            return $this->missingProfileResponse();
        }

        $record = $this->ownedRecord($profile, $recordId);
        $record->update($this->attributes($request));

        return $this->sectionResponse();
    }

    protected function deleteRecord(Request $request, int $recordId): RedirectResponse
    {
        $profile = $this->profile($request);

        if ($profile === null) {
            return $this->missingProfileResponse();
        }

        $this->ownedRecord($profile, $recordId)->delete();

        return $this->sectionResponse();
    }

    private function profile(Request $request): ?Profile
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $user->profile()->first();
    }

    private function ownedRecord(Profile $profile, int $recordId): Model
    {
        return $profile->{$this->relation}()->findOrFail($recordId);
    }

    private function sectionResponse(): RedirectResponse
    {
        return to_route('career-profile.edit', ['tab' => $this->tab]);
    }

    private function missingProfileResponse(): RedirectResponse
    {
        return to_route('career-profile.edit')->withErrors([
            'profile' => 'Save your professional details before adding career facts.',
        ]);
    }
}
