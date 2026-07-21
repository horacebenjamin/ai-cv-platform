<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a professional reference listed on a CV.
 */
class CvReference extends Model
{
    protected $fillable = ['cv_id', 'name', 'company', 'job_title', 'email', 'phone', 'relationship'];
}
