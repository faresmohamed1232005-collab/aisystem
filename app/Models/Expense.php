<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Syncable;
use App\Models\Concerns\Auditable;

class Expense extends Model
{
    use Syncable;
    use Auditable;

    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'category',
        'amount',
        'expense_date',
        'notes',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount'       => 'decimal:2',
    ];

    // الفئات المتاحة
    public static array $categories = [
        'إيجار'         => 'إيجار',
        'كهرباء'        => 'كهرباء',
        'مياه'          => 'مياه',
        'رواتب'         => 'رواتب',
        'صيانة'         => 'صيانة',
        'مواصلات'       => 'مواصلات',
        'تسويق'         => 'تسويق',
        'مستلزمات'      => 'مستلزمات مكتبية',
        'ضرائب ورسوم'   => 'ضرائب ورسوم',
        'أخرى'          => 'أخرى',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scope: فلتر بالتاريخ
    public function scopeForMonth($query, $month, $year)
    {
        return $query->whereMonth('expense_date', $month)
                     ->whereYear('expense_date', $year);
    }
}