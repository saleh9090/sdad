<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'level'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const LEVEL_SUPER_ADMIN = 'super_admin';

    public const LEVEL_ADMIN = 'admin';

    public const LEVEL_STAFF = 'staff';

    /**
     * @return array<string, string>
     */
    public static function levelOptions(): array
    {
        return [
            self::LEVEL_SUPER_ADMIN => 'Super admin',
            self::LEVEL_ADMIN => 'Admin',
            self::LEVEL_STAFF => 'Staff',
        ];
    }

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
        ];
    }
}
