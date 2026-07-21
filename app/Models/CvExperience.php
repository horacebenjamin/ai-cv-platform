<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a work experience entry listed on a CV.
 */
class CvExperience extends Model
{
    protected $fillable = [
        'cv_id', 'company', 'job_title', 'employment_type', 'location', 'start_date',
        'end_date', 'currently_working', 'description', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'currently_working' => 'boolean'];
    }

    /** Get the CV that contains this experience entry. */
    public function cv(): BelongsTo
    {
        return $this->belongsTo(CV::class);
    }
}
