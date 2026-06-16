<?php

namespace App\Filament\Resources\Customers;

use App\Filament\Resources\Customers\Pages\ManageCustomers;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use App\Support\Access;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function getEloquentQuery(): Builder
    {
        return Access::scopeToCompany(parent::getEloquentQuery());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Access::isSuperAdmin()
                ? Select::make('company_id')->options(fn (): array => Company::query()->orderBy('name')->pluck('name', 'id')->all())->searchable()->required()
                : Hidden::make('company_id')->default(Access::companyId()),
            Select::make('branch_id')->options(fn (): array => Branch::query()->when(! Access::isSuperAdmin(), fn ($query) => $query->where('company_id', Access::companyId()))->orderBy('name')->pluck('name', 'id')->all())->searchable(),
            TextInput::make('name')->required()->maxLength(255),
            Select::make('phone_country_code')->options(User::countryCodeOptions())->default('+968')->searchable()->required(),
            TextInput::make('phone')->tel()->required()->maxLength(255),
            TextInput::make('email')->email()->nullable()->maxLength(255),
            TextInput::make('reference_number')->nullable()->maxLength(255),
            TextInput::make('guardian_name')->nullable()->maxLength(255),
            Select::make('guardian_phone_country_code')->options(User::countryCodeOptions())->searchable(),
            TextInput::make('guardian_phone')->tel()->nullable()->maxLength(255),
            Select::make('status')->options(Customer::statusOptions())->default(Customer::STATUS_ACTIVE)->required(),
            Textarea::make('notes')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')->searchable()->toggleable(isToggledHiddenByDefault: ! Access::isSuperAdmin()),
                TextColumn::make('branch.name')->searchable()->toggleable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('phone_country_code')->label('Code')->toggleable(),
                TextColumn::make('phone')->searchable(),
                TextColumn::make('reference_number')->searchable()->toggleable(),
                TextColumn::make('status')->badge()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(Customer::statusOptions()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCustomers::route('/'),
        ];
    }
}
