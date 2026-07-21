<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a user's billing subscription and credit allowance.
 */
class Subscription extends Model
{
    protected $fillable = [
        'user_id', 'stripe_id', 'plan', 'status', 'credits_remaining', 'renewal_date',
    ];

    protected function casts(): array
    {
        return ['renewal_date' => 'date'];
    }
}
