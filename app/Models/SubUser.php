<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;

class SubUser extends Model
{
    use Authorizable;

    protected $fillable = ['owner_id', 'name', 'email', 'password', 'role', 'is_active'];

    protected $hidden = ['password'];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function getRoleNameAttribute(): string
    {
        return match($this->role) {
            'pharmacist'  => 'صيدلاني',
            'cashier'     => 'كاشير',
            'supervisor'  => 'مشرف',
            'data_entry'  => 'مدخل بيانات',
            default       => $this->role,
        };
    }

    public function getRoleBadgeAttribute(): string
    {
        return match($this->role) {
            'pharmacist'  => 'bg-blue-100 text-blue-700',
            'cashier'     => 'bg-green-100 text-green-700',
            'supervisor'  => 'bg-purple-100 text-purple-700',
            'data_entry'  => 'bg-yellow-100 text-yellow-700',
            default       => 'bg-gray-100 text-gray-700',
        };
    }
}
