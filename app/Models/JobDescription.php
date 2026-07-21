<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Stores the source description and details for a job.
 */
class JobDescription extends Model
{
    protected $fillable = [
        'company_id', 'title', 'location', 'salary', 'employment_type', 'description', 'source_url',
    ];

    /** Get the company that published the job description. */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Get saved jobs linked to this description. */
    public function savedJobs(): HasMany
    {
        return $this->hasMany(SavedJob::class);
    }

    /** Get applications linked to this description. */
    public function jobApplications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }
}
