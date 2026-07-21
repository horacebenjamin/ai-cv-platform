<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents an employer associated with jobs and applications.
 */
class Company extends Model
{
    protected $fillable = [
        'name', 'website', 'location', 'industry', 'company_size', 'logo', 'description',
    ];

    /** Get the saved jobs associated with the company. */
    public function savedJobs(): HasMany
    {
        return $this->hasMany(SavedJob::class);
    }

    /** Get the applications submitted to the company. */
    public function jobApplications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    /** Get the job descriptions published by the company. */
    public function jobDescriptions(): HasMany
    {
        return $this->hasMany(JobDescription::class);
    }
}
