<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employee extends Model
{
    use Syncable;

    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'job_title', 'phone',
        'base_salary', 'hired_at', 'status', 'notes',
    ];

    protected $casts = [
        'hired_at'    => 'date',
        'base_salary' => 'decimal:2',
    ];

    /* ─── Relations ─────────────────────────────── */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(EmployeeTransaction::class);
    }

    /* ─── Helpers ────────────────────────────────── */

    /**
     * كل إجراءات شهر معيّن
     */
    public function monthTransactions(int $month, int $year)
    {
        return $this->transactions()
            ->where('month', $month)
            ->where('year',  $year)
            ->get();
    }

    /**
     * احسب صافي الراتب لشهر معيّن
     */
    public function netSalary(int $month, int $year): float
    {
        $txns = $this->monthTransactions($month, $year);

        $bonus     = $txns->where('type', 'bonus')->sum('amount');
        $deduction = $txns->where('type', 'deduction')->sum('amount');
        $advance   = $txns->where('type', 'advance')->sum('amount');
        $absence   = $txns->where('type', 'absence')->sum('amount');

        return (float) $this->base_salary + $bonus - $deduction - $advance - $absence;
    }

    /**
     * هل اتصرف الراتب لشهر معيّن؟
     */
    public function salaryPaid(int $month, int $year): bool
    {
        return $this->transactions()
            ->where('type',  'salary')
            ->where('month', $month)
            ->where('year',  $year)
            ->exists();
    }
}