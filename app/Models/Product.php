<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Syncable;

class Product extends Model
{
    use Syncable;

    protected $fillable = [
        'user_id','name','barcode','category','price','cost_price',
        'quantity','min_quantity','expiry_date','manufacturer','description','image',
        // حقول الوحدات الجديدة
        'unit_name','sub_unit_name','smallest_unit_name',
        'units_per_pack','sub_units_per_unit',
    ];

    protected $casts = ['expiry_date' => 'date'];

    public function user()      { return $this->belongsTo(User::class); }
    public function saleItems() { return $this->hasMany(SaleItem::class); }

    public function isExpiringSoon(): bool
    {
        return $this->expiry_date && $this->expiry_date->lte(now()->addDays(30));
    }

    public function isLowStock(): bool
    {
        return $this->quantity <= $this->min_quantity;
    }

    // ===== حساب سعر الوحدة الوسطى (شريط) =====
    public function getSubUnitPriceAttribute(): float
    {
        if (!$this->sub_unit_name || $this->units_per_pack <= 1) return 0;
        return round($this->price / $this->units_per_pack, 2);
    }

    // ===== حساب سعر أصغر وحدة (حبة) =====
    public function getSmallestUnitPriceAttribute(): float
    {
        if (!$this->smallest_unit_name) return 0;
        $totalSmallest = $this->units_per_pack * $this->sub_units_per_unit;
        if ($totalSmallest <= 1) return $this->price;
        return round($this->price / $totalSmallest, 2);
    }

    // ===== الوحدات المتاحة للبيع مع أسعارها =====
    public function getAvailableUnits(): array
    {
        $units = [
            [
                'key'       => 'pack',
                'name'      => $this->unit_name ?? 'علبة',
                'price'     => $this->price,
                'qty_factor'=> 1,  // كل علبة = 1 من المخزن
            ]
        ];

        if ($this->sub_unit_name && $this->units_per_pack > 1) {
            $units[] = [
                'key'       => 'sub',
                'name'      => $this->sub_unit_name,
                'price'     => $this->sub_unit_price,
                'qty_factor'=> 1 / $this->units_per_pack, // كسر من الوحدة الكبيرة
            ];
        }

        if ($this->smallest_unit_name && $this->sub_units_per_unit > 1) {
            $units[] = [
                'key'       => 'smallest',
                'name'      => $this->smallest_unit_name,
                'price'     => $this->smallest_unit_price,
                'qty_factor'=> 1 / ($this->units_per_pack * $this->sub_units_per_unit),
            ];
        }

        return $units;
    }
}