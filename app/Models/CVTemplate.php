<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a reusable presentation template for CVs.
 */
class CVTemplate extends Model
{
    protected $table = 'cv_templates';

    protected $fillable = ['name', 'slug', 'preview_image', 'premium', 'active'];

    protected function casts(): array
    {
        return ['premium' => 'boolean', 'active' => 'boolean'];
    }
}
