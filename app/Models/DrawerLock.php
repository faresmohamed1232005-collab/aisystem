<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DrawerLock extends Model
{
    protected $fillable = [
        'user_id', 'locked_by_name', 'locked_by_email',
        'seller_name', 'cash_amount', 'expected_amount',
        'difference', 'notes',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
