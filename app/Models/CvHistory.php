<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /** Get the user associated with this history entry. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Get the CV captured by this history entry. */
    public function cv(): BelongsTo
    {
        return $this->belongsTo(CV::class);
    }
}
