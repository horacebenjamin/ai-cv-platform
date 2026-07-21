<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents an education entry listed on a CV.
 */
class CvEducation extends Model
{
    protected $fillable = [
        'cv_id', 'institution', 'qualification', 'field_of_study', 'grade',
        'start_date', 'end_date', 'description', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }

    /** Get the CV that contains this education entry. */
    public function cv(): BelongsTo
    {
        return $this->belongsTo(CV::class);
    }
}
