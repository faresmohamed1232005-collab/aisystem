<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'name',
        'phone',
        'email',
        'address',
        'credit_limit',
        'balance',
        'notes',
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function payments()
    {
        return $this->hasMany(SalePayment::class);
    }

    public function deferredSales()
    {
        return $this->hasMany(Sale::class)
            ->where('payment_status', '!=', 'paid')
            ->orderBy('created_at', 'desc');
    }

    public function getTotalDebtAttribute(): float
    {
        return (float) $this->sales()
            ->where('payment_status', '!=', 'paid')
            ->sum('remaining');
    }

    public static function generateCode(int $userId): string
    {
        $last = Customer::where('user_id', $userId)
            ->where('code', 'like', 'CUS-%')
            ->orderByRaw('CAST(SUBSTRING(code, 5) AS UNSIGNED) DESC')
            ->value('code');

        $nextNumber = $last ? ((int) substr($last, 4)) + 1 : 1;

        do {
            $code = 'CUS-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (Customer::where('code', $code)->exists());

        return $code;
    }
}