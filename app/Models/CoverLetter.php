<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a cover letter created for a job opportunity.
 *
 * Expected statuses: draft, generated, final.
 */
class CoverLetter extends Model
{
    protected $fillable = ['user_id', 'cv_id', 'company', 'job_title', 'content', 'status'];

    /** Get the user who owns the cover letter. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Get the CV used for the cover letter. */
    public function cv(): BelongsTo
    {
        return $this->belongsTo(CV::class);
    }
}
