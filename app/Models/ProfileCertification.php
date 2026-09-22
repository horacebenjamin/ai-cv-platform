<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a verified certification recorded on a career profile.
 */
class ProfileCertification extends Model
{
    protected $fillable = [
        'profile_id', 'name', 'organisation', 'issue_date', 'expiry_date',
        'credential_id', 'credential_url', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['issue_date' => 'date', 'expiry_date' => 'date'];
    }

    /** Get the career profile that owns the certification. */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
