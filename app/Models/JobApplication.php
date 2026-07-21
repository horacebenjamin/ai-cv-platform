<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tracks a user's application for a job.
 *
 * Expected statuses: saved, applied, interview, offer, rejected, withdrawn.
 */
class JobApplication extends Model
{
    protected $fillable = [
        'user_id', 'cv_id', 'company_id', 'job_description_id', 'status', 'applied_at', 'notes',
    ];

    protected function casts(): array
    {
        return ['applied_at' => 'datetime'];
    }
}
