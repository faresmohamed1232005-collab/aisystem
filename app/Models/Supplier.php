<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
 
class Supplier extends Model
{
    protected $fillable = [
        'user_id','code','name','phone','email',
        'address','company','tax_number','balance','notes',
    ];
 
    public function purchaseInvoices() { return $this->hasMany(PurchaseInvoice::class); }
    public function payments()         { return $this->hasMany(PurchasePayment::class); }
 

    public function getTotalDebtAttribute(): float
    {
        return (float) $this->purchaseInvoices()
            ->where('payment_status', '!=', 'paid')
            ->sum('remaining');
    }

      // في Supplier Model
    public static function generateCode(int $userId): string
    {
        $last = Supplier::where('user_id', $userId)
            ->where('code', 'like', 'SUP-%')
            ->orderByRaw('CAST(SUBSTRING(code, 5) AS UNSIGNED) DESC')
            ->value('code');

        $nextNumber = $last ? ((int) substr($last, 4)) + 1 : 1;

        // تأكد إن الكود مش مكرر
        do {
            $code = 'SUP-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (Supplier::where('code', $code)->exists());

        return $code;
    }
}