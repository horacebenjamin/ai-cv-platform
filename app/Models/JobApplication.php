<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /** Get the user who owns the application. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Get the company receiving the application. */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Get the job description associated with the application. */
    public function jobDescription(): BelongsTo
    {
        return $this->belongsTo(JobDescription::class);
    }
}
