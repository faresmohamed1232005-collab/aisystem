<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Syncable;
use App\Models\Concerns\Auditable;

class Sale extends Model
{
    use Syncable;
    use Auditable;

    protected $fillable = [
        'user_id', 'customer_id', 'invoice_number',
        'total', 'discount', 'paid', 'remaining',
        'payment_method', 'card_type', 'payment_status', 'notes',
        'contract_id', 'insured_patient_id', 'covered_amount', 'patient_amount',
    ];
 
    public function user()     { return $this->belongsTo(User::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function items()    { return $this->hasMany(SaleItem::class); }
    public function payments() { return $this->hasMany(SalePayment::class); }
    public function contract() { return $this->belongsTo(Contract::class); }
    public function insuredPatient() { return $this->belongsTo(InsuredPatient::class); }
}