<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a verified work experience record on a career profile.
 *
 * Career profile records are the factual source of truth used to generate CVs.
 */
class ProfileExperience extends Model
{
    protected $fillable = [
        'profile_id', 'job_title', 'company', 'location', 'employment_type', 'start_date',
        'end_date', 'currently_employed', 'summary', 'achievements', 'technologies', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'currently_employed' => 'boolean',
            'achievements' => 'array',
            'technologies' => 'array',
        ];
    }

    /** Get the career profile that owns the experience record. */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
