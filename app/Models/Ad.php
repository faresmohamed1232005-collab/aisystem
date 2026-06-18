<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ad extends Model
{
    protected $fillable = ['title', 'image_path', 'is_active'];

    public static function activeAd(): ?self
    {
        return self::where('is_active', true)->latest()->first();
    }
}