<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Stores a user's professional profile details.
 */
class Profile extends Model
{
    protected $fillable = [
        'user_id', 'first_name', 'last_name', 'headline', 'phone', 'location',
        'website', 'linkedin_url', 'github_url', 'portfolio_url', 'bio', 'avatar',
    ];
}
