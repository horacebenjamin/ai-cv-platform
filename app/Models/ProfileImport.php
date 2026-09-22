<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Records an existing-CV import and the facts proposed from it.
 *
 * Extracted content is a proposal only. Nothing reaches the career profile
 * until the owning user reviews and approves it.
 *
 * Expected statuses: pending, ready, applied, discarded, failed.
 */
class ProfileImport extends Model
{
    public const STATUSES = ['pending', 'ready', 'applied', 'discarded', 'failed'];

    protected $fillable = [
        'user_id', 'ai_request_id', 'source_type', 'source_text', 'status', 'extracted', 'applied_at',
    ];

    protected function casts(): array
    {
        return [
            'extracted' => 'array',
            'applied_at' => 'datetime',
        ];
    }

    /** Get the user who owns the import. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Get the AI request that extracted the proposed facts. */
    public function aiRequest(): BelongsTo
    {
        return $this->belongsTo(AiRequest::class);
    }
}
