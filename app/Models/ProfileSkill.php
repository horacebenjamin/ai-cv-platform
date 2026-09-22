<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a verified skill recorded on a career profile.
 *
 * Categories are free text so the structure suits professions beyond software
 * engineering; suggested categories come from config/career.php.
 */
class ProfileSkill extends Model
{
    protected $fillable = ['profile_id', 'category', 'name', 'proficiency', 'sort_order'];

    /** Get the career profile that owns the skill. */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
