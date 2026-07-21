<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a language and proficiency listed on a CV.
 */
class CvLanguage extends Model
{
    protected $fillable = ['cv_id', 'language', 'proficiency', 'sort_order'];
}
