<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * ولّد uuid ثابت لكل مستخدم جديد (مفتاح المزامنة server→branch للحسابات).
     * users ليست Syncable كاملة، لكنها تُسحب للفرع للـ offline login عبر الـ uuid.
     */
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::ulid();
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'phone',
        'pharmacy_name',
        'address',
        'syndicate_card',
        'governorate',
        'city',
        'is_approved',
        'two_factor_secret',
        'two_factor_enabled',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_enabled' => 'boolean',
        ];
    }
}
