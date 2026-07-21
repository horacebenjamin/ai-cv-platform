<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Records a change to a user's credit balance.
 */
class CreditTransaction extends Model
{
    protected $fillable = ['user_id', 'amount', 'type', 'description'];

    /** Get the user whose balance was changed. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
