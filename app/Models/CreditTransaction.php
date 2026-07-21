<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Records a change to a user's credit balance.
 */
class CreditTransaction extends Model
{
    protected $fillable = ['user_id', 'amount', 'type', 'description'];
}
