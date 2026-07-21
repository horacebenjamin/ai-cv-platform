<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a curriculum vitae owned by a user.
 *
 * Master CVs are source documents; variants use parent_cv_id and variant_name
 * to remain independently editable for a specific role or application.
 */
class CV extends Model
{
    protected $table = 'cvs';

    protected $fillable = [
        'user_id', 'title', 'professional_summary', 'template_id', 'status', 'is_default',
        'is_master', 'parent_cv_id', 'variant_name', 'last_used_at', 'target_job_title',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_master' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    /** Get the user who owns the CV. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Get the presentation template used by the CV. */
    public function template(): BelongsTo
    {
        return $this->belongsTo(CVTemplate::class, 'template_id');
    }

    /** Get the master CV from which this variant was created. */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_cv_id');
    }

    /** Get the tailored variants created from this CV. */
    public function variants(): HasMany
    {
        return $this->hasMany(self::class, 'parent_cv_id');
    }

    /** Get the work experience entries on the CV. */
    public function experiences(): HasMany
    {
        return $this->hasMany(CvExperience::class, 'cv_id');
    }

    /** Get the education entries on the CV. */
    public function education(): HasMany
    {
        return $this->hasMany(CvEducation::class, 'cv_id');
    }

    /** Get the skills listed on the CV. */
    public function skills(): HasMany
    {
        return $this->hasMany(CvSkill::class, 'cv_id');
    }

    /** Get the projects showcased on the CV. */
    public function projects(): HasMany
    {
        return $this->hasMany(CvProject::class, 'cv_id');
    }

    /** Get the certifications listed on the CV. */
    public function certifications(): HasMany
    {
        return $this->hasMany(CvCertification::class, 'cv_id');
    }

    /** Get the languages listed on the CV. */
    public function languages(): HasMany
    {
        return $this->hasMany(CvLanguage::class, 'cv_id');
    }

    /** Get the professional references listed on the CV. */
    public function references(): HasMany
    {
        return $this->hasMany(CvReference::class, 'cv_id');
    }

    /** Get the cover letters based on the CV. */
    public function coverLetters(): HasMany
    {
        return $this->hasMany(CoverLetter::class, 'cv_id');
    }

    /** Get the immutable history entries for the CV. */
    public function histories(): HasMany
    {
        return $this->hasMany(CvHistory::class, 'cv_id');
    }
}
