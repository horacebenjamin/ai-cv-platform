<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Stores an immutable snapshot of a CV after a significant user or AI action.
 *
 * Expected actions: created, manual_edit, ai_summary, ai_rewrite,
 * ai_optimisation, duplicate, restore.
 */
class CvHistory extends Model
{
    protected $fillable = [
        'cv_id',
        'user_id',
        'action',
        'snapshot',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
        ];
    }
}
