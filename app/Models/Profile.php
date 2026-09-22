<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Stores a user's professional profile details.
 *
 * The career profile is the factual source of truth for CV generation: the
 * profile record holds professional information, and its child records hold
 * verified experience, skills, projects, education, and certifications.
 */
class Profile extends Model
{
    /** Ordered relations that hold the profile's verified career facts. */
    public const CAREER_RELATIONS = [
        'experiences', 'skills', 'projects', 'education', 'certifications',
    ];

    protected $fillable = [
        'user_id', 'first_name', 'last_name', 'headline', 'seniority', 'preferred_roles',
        'phone', 'location', 'website', 'linkedin_url', 'github_url', 'portfolio_url', 'bio', 'avatar',
    ];

    protected function casts(): array
    {
        return ['preferred_roles' => 'array'];
    }

    /** Get the user who owns the profile. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Get the verified work experience records. */
    public function experiences(): HasMany
    {
        return $this->hasMany(ProfileExperience::class);
    }

    /** Get the verified skills. */
    public function skills(): HasMany
    {
        return $this->hasMany(ProfileSkill::class);
    }

    /** Get the verified projects. */
    public function projects(): HasMany
    {
        return $this->hasMany(ProfileProject::class);
    }

    /** Get the verified education records. */
    public function education(): HasMany
    {
        return $this->hasMany(ProfileEducation::class);
    }

    /** Get the verified certifications. */
    public function certifications(): HasMany
    {
        return $this->hasMany(ProfileCertification::class);
    }
}
