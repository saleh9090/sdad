<?php

namespace App\Filament\Resources\SubscriptionPlans;

use App\Filament\Resources\SubscriptionPlans\Pages\ManageSubscriptionPlans;
use App\Models\Company;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Support\Access;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionPlanResource extends Resource
{
    protected static ?string $model = SubscriptionPlan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function getEloquentQuery(): Builder
    {
        return Access::scopeToCompany(parent::getEloquentQuery());
    }

    public static function canAccess(): bool
    {
        return Access::isSuperAdmin() || Access::user()?->role === User::ROLE_ADMIN;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Access::isSuperAdmin()
                ? Select::make('company_id')->options(fn (): array => Company::query()->orderBy('name')->pluck('name', 'id')->all())->searchable()->required()
                : Hidden::make('company_id')->default(Access::companyId()),
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('amount')->numeric()->required()->prefix('OMR'),
            TextInput::make('duration_days')->numeric()->nullable()->minValue(1),
            Toggle::make('allow_installments')->default(false),
            Select::make('status')->options(SubscriptionPlan::statusOptions())->default(SubscriptionPlan::STATUS_ACTIVE)->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')->searchable()->toggleable(isToggledHiddenByDefault: ! Access::isSuperAdmin()),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('amount')->money('OMR')->sortable(),
                TextColumn::make('duration_days')->label('Duration')->suffix(' days')->sortable(),
                IconColumn::make('allow_installments')->boolean(),
                TextColumn::make('status')->badge()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(SubscriptionPlan::statusOptions()),
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
            'index' => ManageSubscriptionPlans::route('/'),
        ];
    }
}
