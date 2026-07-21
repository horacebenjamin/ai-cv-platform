<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
