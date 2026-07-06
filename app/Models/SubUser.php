<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Syncable;
use Illuminate\Foundation\Auth\Access\Authorizable;

class SubUser extends Model
{
    use Syncable;

    use Authorizable;

    protected $fillable = ['owner_id', 'name', 'email', 'password', 'role', 'is_active'];

    protected $hidden = ['password'];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function getRoleNameAttribute(): string
    {
        return \App\Support\Roles::label($this->role);
    }

    public function getRoleBadgeAttribute(): string
    {
        return match($this->role) {
            \App\Support\Roles::AREA_MANAGER   => 'bg-purple-100 text-purple-700',
            \App\Support\Roles::BRANCH_MANAGER => 'bg-indigo-100 text-indigo-700',
            \App\Support\Roles::PHARMACIST     => 'bg-blue-100 text-blue-700',
            \App\Support\Roles::ACCOUNTANT     => 'bg-amber-100 text-amber-700',
            default                            => 'bg-gray-100 text-gray-700',
        };
    }
}
