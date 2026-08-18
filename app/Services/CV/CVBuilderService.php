<?php

namespace App\Services\CV;

use App\Models\CV;
use App\Models\CVTemplate;
use App\Models\Profile;

final class CVBuilderService
{
    /** @param array<string, mixed> $data */
    public function build(Profile $profile, array $data, ?CVTemplate $template = null, ?string $targetJob = null): CV
    {
        $cv = $profile->user->cvs()->create([
            'title' => $data['title'],
            'professional_summary' => $data['summary'],
            'template_id' => $template?->getKey(),
            'status' => 'draft',
            'is_master' => true,
            'target_job_title' => $targetJob,
        ]);

        $this->createMany($cv, 'experiences', $data['experience'], [
            'company', 'job_title', 'employment_type', 'location', 'start_date', 'end_date',
            'currently_working', 'description',
        ]);
        $this->createMany($cv, 'education', $data['education'], [
            'institution', 'qualification', 'field_of_study', 'grade', 'start_date', 'end_date', 'description',
        ]);
        $this->createMany($cv, 'skills', $data['skills'], ['category', 'name', 'proficiency']);
        $this->createMany($cv, 'projects', $data['projects'], [
            'title', 'description', 'technologies', 'github_url', 'demo_url', 'start_date', 'end_date',
        ]);
        $this->createMany($cv, 'languages', $data['languages'], ['language', 'proficiency']);
        $this->createMany($cv, 'certifications', $data['certifications'], [
            'name', 'organisation', 'issue_date', 'expiry_date', 'credential_id', 'credential_url',
        ]);
        $this->createMany($cv, 'references', $data['references'], [
            'name', 'company', 'job_title', 'email', 'phone', 'relationship',
        ]);

        return $cv->load(CVHistoryService::RELATIONS);
    }

    /** @param list<array<string, mixed>> $items
     * @param  list<string>  $allowed
     */
    private function createMany(CV $cv, string $relationship, array $items, array $allowed): void
    {
        foreach ($items as $index => $item) {
            $attributes = array_intersect_key($item, array_flip($allowed));

            if (in_array('sort_order', $cv->{$relationship}()->getRelated()->getFillable(), true)) {
                $attributes['sort_order'] = $index;
            }

            $cv->{$relationship}()->create($attributes);
        }
    }
}
