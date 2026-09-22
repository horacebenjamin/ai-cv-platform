<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a verified project recorded on a career profile.
 */
class ProfileProject extends Model
{
    protected $fillable = [
        'profile_id', 'name', 'role', 'description', 'context', 'responsibilities', 'outcomes',
        'technologies', 'url', 'repository_url', 'start_date', 'end_date', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'technologies' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /** Get the career profile that owns the project. */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
