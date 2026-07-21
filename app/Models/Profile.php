<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stores a user's professional profile details.
 */
class Profile extends Model
{
    protected $fillable = [
        'user_id', 'first_name', 'last_name', 'headline', 'phone', 'location',
        'website', 'linkedin_url', 'github_url', 'portfolio_url', 'bio', 'avatar',
    ];

    /** Get the user who owns the profile. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
