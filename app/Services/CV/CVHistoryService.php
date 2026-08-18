<?php

namespace App\Services\CV;

use App\Models\CV;
use App\Models\CvHistory;

final class CVHistoryService
{
    public const RELATIONS = [
        'experiences', 'education', 'skills', 'projects', 'languages', 'certifications', 'references',
    ];

    public function snapshot(CV $cv, string $action = 'generated'): CvHistory
    {
        $cv->loadMissing(self::RELATIONS);

        return $cv->histories()->create([
            'user_id' => $cv->user_id,
            'action' => $action,
            'snapshot' => $cv->toArray(),
            'notes' => 'Complete CV snapshot created after AI generation.',
        ]);
    }
}
