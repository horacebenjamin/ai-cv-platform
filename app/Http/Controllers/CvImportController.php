<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApplyProfileImportRequest;
use App\Http\Requests\StoreProfileImportRequest;
use App\Models\ProfileImport;
use App\Models\User;
use App\Services\Profile\CvImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

/**
 * Imports an existing CV into the Career Profile after user review.
 *
 * Extracted facts are proposals only: nothing is written to the profile until
 * the owner approves it here.
 */
class CvImportController extends Controller
{
    public function __construct(private readonly CvImportService $imports) {}

    public function store(StoreProfileImportRequest $request): RedirectResponse
    {
        $import = $this->imports->create($this->user($request), (string) $request->validated('source_text'));

        return to_route('career-profile.imports.show', $import);
    }

    public function show(Request $request, int $import): Response
    {
        return Inertia::render(
            'CareerProfile/Import',
            $this->imports->reviewPayload($this->ownedImport($request, $import))
        );
    }

    public function apply(ApplyProfileImportRequest $request, int $import): RedirectResponse
    {
        $profileImport = $this->ownedImport($request, $import);

        try {
            $applied = $this->imports->apply($profileImport, $request->selections());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['selections' => $exception->getMessage()]);
        }

        return to_route('career-profile.edit')->with('importSummary', $applied);
    }

    public function destroy(Request $request, int $import): RedirectResponse
    {
        $this->imports->discard($this->ownedImport($request, $import));

        return to_route('career-profile.edit', ['tab' => 'import']);
    }

    private function ownedImport(Request $request, int $import): ProfileImport
    {
        return $this->user($request)->profileImports()->with('user')->findOrFail($import);
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
