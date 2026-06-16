<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Support\Access;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->options(fn (): array => Company::query()
                        ->when(! Access::isSuperAdmin(), fn ($query) => $query->whereKey(Access::companyId()))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->default(Access::defaultCompanyId())
                    ->searchable()
                    ->required()
                    ->hidden(! Access::isSuperAdmin()),
                Select::make('branch_id')
                    ->options(fn (): array => Branch::query()
                        ->when(! Access::isSuperAdmin(), fn ($query) => $query->where('company_id', Access::companyId()))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->default(Access::defaultBranchId())
                    ->searchable(),
                TextInput::make('name')
                    ->required(),
                Select::make('phone_country_code')
                    ->options(User::countryCodeOptions())
                    ->default('+968')
                    ->searchable()
                    ->required(),
                TextInput::make('phone')
                    ->tel()
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->nullable(),
                Select::make('role')
                    ->options(fn (): array => Access::isSuperAdmin()
                        ? User::roleOptions()
                        : [
                            User::ROLE_ADMIN => 'Admin',
                            User::ROLE_STAFF => 'Staff',
                        ])
                    ->default(User::ROLE_STAFF)
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state)),
            ]);
    }
}
