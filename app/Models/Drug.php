<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Drug extends Model
{
    use Syncable;

    protected $table = 'drugs';

    protected $fillable = [
        'name_en',
        'name_ar',
        'old_price',
        'new_price',
        'active_ingredient',
        'company',
        'category',
        'major_units',
        'minor_units',
        'size_weight',
        'concentration',
        'dosage_form',
        'barcode',
        'origin',
        'price_updated_at',
        'popularity',
    ];

    // ──────────────────────────────────────────
    //  Relations
    // ──────────────────────────────────────────

    /** كل المخزونات الخاصة بهذا الدواء عند المستخدمين */
    public function inventories(): HasMany
    {
        return $this->hasMany(UserDrugInventory::class);
    }

    // ──────────────────────────────────────────
    //  Accessors
    // ──────────────────────────────────────────

    /** الاسم المعروض (عربي أولاً) */
    public function getDisplayNameAttribute(): string
    {
        return $this->name_ar ?? $this->name_en ?? '—';
    }

    /** السعر الفعلي للعرض */
    public function getEffectivePriceAttribute(): float
    {
        return (float) ($this->new_price ?? $this->old_price ?? 0);
    }

    // ──────────────────────────────────────────
    //  Scopes
    // ──────────────────────────────────────────

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name_ar',           'like', "%{$term}%")
              ->orWhere('name_en',          'like', "%{$term}%")
              ->orWhere('barcode',          'like', "%{$term}%")
              ->orWhere('active_ingredient','like', "%{$term}%")
              ->orWhere('category',         'like', "%{$term}%");
        });
    }
}