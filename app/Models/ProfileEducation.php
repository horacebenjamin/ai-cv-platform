<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a verified education record on a career profile.
 */
class ProfileEducation extends Model
{
    protected $table = 'profile_education';

    protected $fillable = [
        'profile_id', 'institution', 'qualification', 'subject', 'grade',
        'start_date', 'end_date', 'description', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }

    /** Get the career profile that owns the education record. */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
