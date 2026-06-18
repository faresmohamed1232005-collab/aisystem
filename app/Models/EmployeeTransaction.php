<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeTransaction extends Model
{
    protected $fillable = [
        'employee_id', 'user_id', 'type',
        'amount', 'absence_days', 'month', 'year',
        'notes', 'expense_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public static array $typeLabels = [
        'bonus'     => ['label' => 'بونص',  'icon' => 'fa-star',          'color' => '#10b981'],
        'deduction' => ['label' => 'خصم',   'icon' => 'fa-minus-circle',   'color' => '#f43f5e'],
        'advance'   => ['label' => 'سلفة',  'icon' => 'fa-hand-holding-usd','color' => '#f59e0b'],
        'absence'   => ['label' => 'غياب',  'icon' => 'fa-user-times',     'color' => '#8b5cf6'],
        'salary'    => ['label' => 'راتب',  'icon' => 'fa-money-bill-wave', 'color' => '#6366f1'],
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::$typeLabels[$this->type]['label'] ?? $this->type;
    }

    public function getTypeColorAttribute(): string
    {
        return self::$typeLabels[$this->type]['color'] ?? '#64748b';
    }

    public function getTypeIconAttribute(): string
    {
        return self::$typeLabels[$this->type]['icon'] ?? 'fa-circle';
    }
}