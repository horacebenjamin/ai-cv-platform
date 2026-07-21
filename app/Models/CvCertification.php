<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a certification listed on a CV.
 */
class CvCertification extends Model
{
    protected $fillable = [
        'cv_id', 'name', 'organisation', 'issue_date', 'expiry_date',
        'credential_id', 'credential_url',
    ];

    protected function casts(): array
    {
        return ['issue_date' => 'date', 'expiry_date' => 'date'];
    }

    /** Get the CV that contains this certification. */
    public function cv(): BelongsTo
    {
        return $this->belongsTo(CV::class);
    }
}
