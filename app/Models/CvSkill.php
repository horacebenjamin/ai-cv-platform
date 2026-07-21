<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a skill listed on a CV.
 */
class CvSkill extends Model
{
    protected $fillable = ['cv_id', 'category', 'name', 'proficiency', 'sort_order'];
}
