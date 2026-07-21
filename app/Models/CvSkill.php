<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a skill listed on a CV.
 */
class CvSkill extends Model
{
    protected $fillable = ['cv_id', 'category', 'name', 'proficiency', 'sort_order'];

    /** Get the CV that contains this skill. */
    public function cv(): BelongsTo
    {
        return $this->belongsTo(CV::class);
    }
}
