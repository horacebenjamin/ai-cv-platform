<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Stores the source description and details for a job.
 */
class JobDescription extends Model
{
    protected $fillable = [
        'company_id', 'title', 'location', 'salary', 'employment_type', 'description', 'source_url',
    ];
}
