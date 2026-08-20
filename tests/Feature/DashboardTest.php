<?php

use App\Models\AiRequest;
use App\Models\Company;
use App\Models\CoverLetter;
use App\Models\CreditTransaction;
use App\Models\CV;
use App\Models\CvHistory;
use App\Models\JobApplication;
use App\Models\JobDescription;
use App\Models\Profile;
use App\Models\SavedJob;
use App\Models\Subscription;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('dashboard presents real user scoped workflow data', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $company = Company::query()->create(['name' => 'Acme Ltd']);
    $jobDescription = JobDescription::query()->create([
        'company_id' => $company->id,
        'title' => 'Senior Product Designer',
        'description' => 'Lead product design across the platform.',
    ]);

    Profile::query()->create([
        'user_id' => $user->id,
        'first_name' => 'Alex',
        'last_name' => 'Taylor',
        'headline' => 'Product Designer',
        'location' => 'London',
        'bio' => 'Designs useful digital products.',
    ]);

    $olderCv = CV::query()->create([
        'user_id' => $user->id,
        'title' => 'Master CV',
        'status' => 'published',
    ]);
    $recentCv = CV::query()->create([
        'user_id' => $user->id,
        'title' => 'Product Designer CV',
        'status' => 'draft',
        'target_job_title' => 'Senior Product Designer',
    ]);
    $olderCv->forceFill(['updated_at' => now()->subDays(2)])->save();
    $recentCv->forceFill(['updated_at' => now()->subHour()])->save();

    CvHistory::query()->create([
        'cv_id' => $recentCv->id,
        'user_id' => $user->id,
        'action' => 'manual_edit',
        'snapshot' => ['title' => $recentCv->title],
    ]);

    SavedJob::query()->create([
        'user_id' => $user->id,
        'company_id' => $company->id,
        'job_description_id' => $jobDescription->id,
        'title' => $jobDescription->title,
        'source_name' => 'Company website',
        'status' => 'saved',
        'saved_at' => now()->subDay(),
    ]);
    SavedJob::query()->create([
        'user_id' => $user->id,
        'company_id' => $company->id,
        'title' => 'Closed role',
        'source_name' => 'Company website',
        'status' => 'closed',
        'saved_at' => now()->subWeek(),
    ]);

    JobApplication::query()->create([
        'user_id' => $user->id,
        'company_id' => $company->id,
        'job_description_id' => $jobDescription->id,
        'status' => 'interview',
        'applied_at' => now()->subDays(3),
    ]);
    JobApplication::query()->create([
        'user_id' => $user->id,
        'company_id' => $company->id,
        'status' => 'rejected',
        'applied_at' => now()->subMonth(),
    ]);

    CoverLetter::query()->create([
        'user_id' => $user->id,
        'cv_id' => $recentCv->id,
        'company' => $company->name,
        'job_title' => $jobDescription->title,
        'content' => 'Dear hiring team...',
        'status' => 'draft',
    ]);

    AiRequest::query()->create([
        'user_id' => $user->id,
        'feature' => 'job_match_analysis',
        'prompt' => 'Sensitive prompt content',
        'response' => 'Sensitive response content',
        'model' => 'test-model',
        'tokens_used' => 1200,
        'status' => 'completed',
    ]);
    CreditTransaction::query()->create([
        'user_id' => $user->id,
        'amount' => -3,
        'type' => 'job_match_analysis',
    ]);
    Subscription::query()->create([
        'user_id' => $user->id,
        'plan' => 'professional',
        'status' => 'active',
        'credits_remaining' => 42,
        'renewal_date' => now()->addMonth()->toDateString(),
    ]);

    CV::query()->create(['user_id' => $otherUser->id, 'title' => 'Other user CV']);
    JobApplication::query()->create([
        'user_id' => $otherUser->id,
        'company_id' => $company->id,
        'status' => 'interview',
    ]);
    AiRequest::query()->create([
        'user_id' => $otherUser->id,
        'feature' => 'cv_rewrite',
        'prompt' => 'Other user prompt',
        'model' => 'test-model',
        'status' => 'completed',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('overview.totalCvs', 2)
            ->where('overview.activeApplications', 1)
            ->where('overview.savedJobs', 1)
            ->where('overview.coverLetters', 1)
            ->where('overview.interviewProcesses', 1)
            ->where('profile.percentage', 71)
            ->where('profile.completedFields', 5)
            ->where('profile.totalFields', 7)
            ->where('credits.available', 42)
            ->where('credits.plan', 'professional')
            ->where('credits.used', 3)
            ->has('recentCvs', 2)
            ->where('recentCvs.0.id', $recentCv->id)
            ->where('recentCvs.0.title', 'Product Designer CV')
            ->where('recentCvs.0.historyCount', 1)
            ->has('recentApplications', 1)
            ->where('recentApplications.0.company', 'Acme Ltd')
            ->where('recentApplications.0.role', 'Senior Product Designer')
            ->where('recentApplications.0.status', 'interview')
            ->has('recentAiRequests', 1)
            ->where('recentAiRequests.0.feature', 'job_match_analysis')
            ->where('recentAiRequests.0.tokensUsed', 1200)
            ->missing('recentAiRequests.0.prompt')
            ->missing('recentAiRequests.0.response')
            ->where('nextFocus.key', 'interviews')
            ->etc());
});

test('dashboard has truthful empty states when the user has no workflow data', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('overview.totalCvs', 0)
            ->where('overview.activeApplications', 0)
            ->where('overview.savedJobs', 0)
            ->where('overview.coverLetters', 0)
            ->where('overview.interviewProcesses', 0)
            ->where('profile.percentage', 0)
            ->where('credits.available', null)
            ->where('credits.plan', null)
            ->where('credits.used', 0)
            ->has('recentCvs', 0)
            ->has('recentApplications', 0)
            ->has('recentAiRequests', 0)
            ->where('nextFocus.key', 'profile')
            ->etc());
});
