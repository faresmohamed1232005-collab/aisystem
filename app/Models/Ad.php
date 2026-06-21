<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Syncable;

class Ad extends Model
{
    use Syncable;

    protected $fillable = ['title', 'image_path', 'is_active'];

    public static function activeAd(): ?self
    {
        return self::where('is_active', true)->latest()->first();
    }
}