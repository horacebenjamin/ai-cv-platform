<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a curriculum vitae owned by a user.
 *
 * Master CVs are source documents; variants use parent_cv_id and variant_name
 * to remain independently editable for a specific role or application.
 */
class CV extends Model
{
    protected $table = 'cvs';

    protected $fillable = [
        'user_id', 'title', 'professional_summary', 'template_id', 'status', 'is_default',
        'is_master', 'parent_cv_id', 'variant_name', 'last_used_at', 'target_job_title',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_master' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }
}
