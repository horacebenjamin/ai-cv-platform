<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a project showcased on a CV.
 */
class CvProject extends Model
{
    protected $fillable = [
        'cv_id', 'title', 'description', 'technologies', 'github_url', 'demo_url',
        'start_date', 'end_date', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['technologies' => 'array', 'start_date' => 'date', 'end_date' => 'date'];
    }

    /** Get the CV that contains this project. */
    public function cv(): BelongsTo
    {
        return $this->belongsTo(CV::class);
    }
}
