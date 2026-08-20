<?php

namespace App\Services;

use App\Models\AiRequest;
use App\Models\CV;
use App\Models\JobApplication;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Str;

final class DashboardService
{
    private const ACTIVE_APPLICATION_STATUSES = [
        'draft',
        'ready',
        'applied',
        'interview',
        'technical_test',
        'final_interview',
        'offer',
    ];

    private const INTERVIEW_STATUSES = [
        'interview',
        'technical_test',
        'final_interview',
    ];

    private const OPEN_SAVED_JOB_STATUSES = [
        'saved',
        'interested',
    ];

    private const PROFILE_FIELDS = [
        'first_name' => 'First name',
        'last_name' => 'Last name',
        'headline' => 'Professional headline',
        'phone' => 'Phone number',
        'location' => 'Location',
        'linkedin_url' => 'LinkedIn profile',
        'bio' => 'Professional summary',
    ];

    /**
     * @return array{
     *     overview: array{totalCvs: int, activeApplications: int, savedJobs: int, coverLetters: int, interviewProcesses: int},
     *     profile: array{exists: bool, percentage: int, completedFields: int, totalFields: int, missingFields: list<string>},
     *     credits: array{available: int|null, plan: string|null, renewalDate: string|null, used: int},
     *     recentCvs: list<array{id: int, title: string, status: string, targetJobTitle: string|null, historyCount: int, updatedAt: string|null}>,
     *     recentApplications: list<array{id: int, company: string, role: string|null, status: string, appliedAt: string|null, updatedAt: string|null}>,
     *     recentAiRequests: list<array{id: int, feature: string, status: string, tokensUsed: int, createdAt: string|null}>,
     *     nextFocus: array{key: string, title: string, description: string}
     * }
     */
    public function for(User $user): array
    {
        $profile = $user->profile()
            ->first(['id', 'user_id', ...array_keys(self::PROFILE_FIELDS)]);
        $profileCompleteness = $this->profileCompleteness($profile);
        $totalCvs = $user->cvs()->count();
        $activeApplications = $user->jobApplications()
            ->whereIn('status', self::ACTIVE_APPLICATION_STATUSES)
            ->count();
        $savedJobs = $user->savedJobs()
            ->whereIn('status', self::OPEN_SAVED_JOB_STATUSES)
            ->count();
        $coverLetters = $user->coverLetters()->count();
        $interviewProcesses = $user->jobApplications()
            ->whereIn('status', self::INTERVIEW_STATUSES)
            ->count();

        return [
            'overview' => [
                'totalCvs' => $totalCvs,
                'activeApplications' => $activeApplications,
                'savedJobs' => $savedJobs,
                'coverLetters' => $coverLetters,
                'interviewProcesses' => $interviewProcesses,
            ],
            'profile' => $profileCompleteness,
            'credits' => $this->credits($user),
            'recentCvs' => $this->recentCvs($user),
            'recentApplications' => $this->recentApplications($user),
            'recentAiRequests' => $this->recentAiRequests($user),
            'nextFocus' => $this->nextFocus(
                profileCompleteness: $profileCompleteness,
                totalCvs: $totalCvs,
                activeApplications: $activeApplications,
                savedJobs: $savedJobs,
                coverLetters: $coverLetters,
                interviewProcesses: $interviewProcesses,
            ),
        ];
    }

    /**
     * @return array{exists: bool, percentage: int, completedFields: int, totalFields: int, missingFields: list<string>}
     */
    private function profileCompleteness(?Profile $profile): array
    {
        $missingFields = collect(self::PROFILE_FIELDS)
            ->reject(fn (string $label, string $field): bool => filled($profile?->{$field}))
            ->values()
            ->all();
        $totalFields = count(self::PROFILE_FIELDS);
        $completedFields = $totalFields - count($missingFields);

        return [
            'exists' => $profile !== null,
            'percentage' => (int) round(($completedFields / $totalFields) * 100),
            'completedFields' => $completedFields,
            'totalFields' => $totalFields,
            'missingFields' => $missingFields,
        ];
    }

    /** @return array{available: int|null, plan: string|null, renewalDate: string|null, used: int} */
    private function credits(User $user): array
    {
        $subscription = $user->subscriptions()
            ->where('status', 'active')
            ->latest('updated_at')
            ->first(['id', 'plan', 'credits_remaining', 'renewal_date']);
        $creditsUsed = -(int) $user->creditTransactions()
            ->where('amount', '<', 0)
            ->sum('amount');

        return [
            'available' => $subscription?->credits_remaining,
            'plan' => $subscription?->plan,
            'renewalDate' => $subscription?->renewal_date?->toDateString(),
            'used' => $creditsUsed,
        ];
    }

    /**
     * @return list<array{id: int, title: string, status: string, targetJobTitle: string|null, historyCount: int, updatedAt: string|null}>
     */
    private function recentCvs(User $user): array
    {
        return $user->cvs()
            ->select(['id', 'user_id', 'title', 'status', 'target_job_title', 'updated_at'])
            ->withCount('histories')
            ->latest('updated_at')
            ->limit(3)
            ->get()
            ->map(fn (CV $cv): array => [
                'id' => $cv->id,
                'title' => $cv->title,
                'status' => $cv->status,
                'targetJobTitle' => $cv->target_job_title,
                'historyCount' => $cv->histories_count,
                'updatedAt' => $cv->updated_at?->toIso8601String(),
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, company: string, role: string|null, status: string, appliedAt: string|null, updatedAt: string|null}>
     */
    private function recentApplications(User $user): array
    {
        return $user->jobApplications()
            ->select([
                'id',
                'user_id',
                'company_id',
                'job_description_id',
                'status',
                'applied_at',
                'updated_at',
            ])
            ->whereIn('status', self::ACTIVE_APPLICATION_STATUSES)
            ->with([
                'company:id,name',
                'jobDescription:id,title',
            ])
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (JobApplication $application): array => [
                'id' => $application->id,
                'company' => $application->company->name,
                'role' => $application->jobDescription?->title,
                'status' => $application->status,
                'appliedAt' => $application->applied_at?->toIso8601String(),
                'updatedAt' => $application->updated_at?->toIso8601String(),
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, feature: string, status: string, tokensUsed: int, createdAt: string|null}>
     */
    private function recentAiRequests(User $user): array
    {
        return $user->aiRequests()
            ->select(['id', 'user_id', 'feature', 'status', 'tokens_used', 'created_at'])
            ->latest()
            ->limit(4)
            ->get()
            ->map(fn (AiRequest $request): array => [
                'id' => $request->id,
                'feature' => $request->feature,
                'status' => $request->status,
                'tokensUsed' => $request->tokens_used,
                'createdAt' => $request->created_at?->toIso8601String(),
            ])
            ->all();
    }

    /**
     * @param  array{exists: bool, percentage: int, completedFields: int, totalFields: int, missingFields: list<string>}  $profileCompleteness
     * @return array{key: string, title: string, description: string}
     */
    private function nextFocus(
        array $profileCompleteness,
        int $totalCvs,
        int $activeApplications,
        int $savedJobs,
        int $coverLetters,
        int $interviewProcesses,
    ): array {
        if ($interviewProcesses > 0) {
            $verb = $interviewProcesses === 1 ? 'needs' : 'need';

            return [
                'key' => 'interviews',
                'title' => 'Prepare for your interview process',
                'description' => $interviewProcesses.' '.Str::plural('application', $interviewProcesses)." currently {$verb} interview preparation.",
            ];
        }

        if ($profileCompleteness['percentage'] < 100) {
            $remaining = count($profileCompleteness['missingFields']);
            $verb = $remaining === 1 ? 'needs' : 'need';

            return [
                'key' => 'profile',
                'title' => 'Strengthen your career profile',
                'description' => $remaining.' core profile '.Str::plural('detail', $remaining)." still {$verb} completing.",
            ];
        }

        if ($totalCvs === 0) {
            return [
                'key' => 'cv',
                'title' => 'Create your first CV',
                'description' => 'Turn your saved career facts into a reusable master CV for future applications.',
            ];
        }

        if ($savedJobs > 0) {
            $verb = $savedJobs === 1 ? 'is' : 'are';

            return [
                'key' => 'saved_jobs',
                'title' => 'Move a saved role forward',
                'description' => $savedJobs.' saved '.Str::plural('role', $savedJobs)." {$verb} ready to review and progress.",
            ];
        }

        if ($activeApplications > 0) {
            return [
                'key' => 'applications',
                'title' => 'Review your active applications',
                'description' => 'Keep statuses and notes current so your next follow-up is clear.',
            ];
        }

        if ($coverLetters === 0) {
            return [
                'key' => 'cover_letters',
                'title' => 'Prepare for your next application',
                'description' => 'Save a suitable role, then tailor your CV and cover letter using your factual profile.',
            ];
        }

        return [
            'key' => 'workspace',
            'title' => 'Keep your workspace current',
            'description' => 'Review recent documents and applications before beginning your next opportunity.',
        ];
    }
}
