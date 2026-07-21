<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a professional reference listed on a CV.
 */
class CvReference extends Model
{
    protected $fillable = ['cv_id', 'name', 'company', 'job_title', 'email', 'phone', 'relationship'];

    /** Get the CV that contains this reference. */
    public function cv(): BelongsTo
    {
        return $this->belongsTo(CV::class);
    }
}
