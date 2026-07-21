<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a user's saved job before or after applying.
 *
 * Expected statuses: saved, interested, applied, interview, offer, rejected, withdrawn.
 */
class SavedJob extends Model
{
    protected $fillable = [
        'user_id',
        'company_id',
        'job_description_id',
        'title',
        'location',
        'salary_min',
        'salary_max',
        'salary_currency',
        'employment_type',
        'source_name',
        'source_url',
        'notes',
        'status',
        'saved_at',
        'applied_at',
    ];

    protected function casts(): array
    {
        return [
            'salary_min' => 'decimal:2',
            'salary_max' => 'decimal:2',
            'saved_at' => 'datetime',
            'applied_at' => 'datetime',
        ];
    }
}
