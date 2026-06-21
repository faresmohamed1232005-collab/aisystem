<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Syncable;


class UserDrugInventory extends Model
{
    use Syncable;

    protected $table = 'user_drug_inventory'; // ✅ أضف السطر ده

    protected $fillable = [
        'user_id',
        'drug_id',
        'quantity',
        'min_quantity',
        'custom_price',
        'cost_price',
        'expiry_date',
    ];

    protected $casts = [
        'quantity'     => 'float',
        'min_quantity' => 'integer',
        'custom_price' => 'float',
        'cost_price'   => 'float',
        'expiry_date'  => 'date',
    ];

    public function drug()
    {
        return $this->belongsTo(Drug::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
 
