<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a cover letter created for a job opportunity.
 *
 * Expected statuses: draft, generated, final.
 */
class CoverLetter extends Model
{
    protected $fillable = ['user_id', 'cv_id', 'company', 'job_title', 'content', 'status'];
}
