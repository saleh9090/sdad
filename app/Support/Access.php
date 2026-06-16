<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class Access
{
    public static function user(): ?User
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $user;
    }

    public static function isSuperAdmin(): bool
    {
        return self::user()?->role === User::ROLE_SUPER_ADMIN;
    }

    public static function companyId(): ?int
    {
        return self::user()?->company_id;
    }

    public static function branchId(): ?int
    {
        return self::user()?->branch_id;
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param Builder<TModel> $query
     * @return Builder<TModel>
     */
    public static function scopeToCompany(Builder $query, string $column = 'company_id'): Builder
    {
        if (self::isSuperAdmin()) {
            return $query;
        }

        return $query->where($column, self::companyId() ?? 0);
    }

    public static function defaultCompanyId(): ?int
    {
        return self::isSuperAdmin() ? null : self::companyId();
    }

    public static function defaultBranchId(): ?int
    {
        return self::isSuperAdmin() ? null : self::branchId();
    }
}
