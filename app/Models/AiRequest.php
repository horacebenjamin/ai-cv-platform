<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Records an AI generation request and its processing outcome.
 *
 * Expected statuses: pending, processing, completed, failed.
 */
class AiRequest extends Model
{
    protected $table = 'ai_requests';

    protected $fillable = [
        'user_id', 'cv_id', 'feature', 'prompt', 'response', 'model', 'tokens_used', 'cost',
        'status', 'processing_time_ms',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:6',
            'processing_time_ms' => 'integer',
        ];
    }

    /** Get the user who initiated the AI request. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
