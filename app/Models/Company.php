<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents an employer associated with jobs and applications.
 */
class Company extends Model
{
    protected $fillable = [
        'name', 'website', 'location', 'industry', 'company_size', 'logo', 'description',
    ];
}
