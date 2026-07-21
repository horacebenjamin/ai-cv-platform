<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a language and proficiency listed on a CV.
 */
class CvLanguage extends Model
{
    protected $fillable = ['cv_id', 'language', 'proficiency', 'sort_order'];

    /** Get the CV that contains this language. */
    public function cv(): BelongsTo
    {
        return $this->belongsTo(CV::class);
    }
}
