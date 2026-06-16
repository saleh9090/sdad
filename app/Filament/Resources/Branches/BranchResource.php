<?php

namespace App\Filament\Resources\Branches;

use App\Filament\Resources\Branches\Pages\ManageBranches;
use App\Models\Branch;
use App\Models\Company;
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

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

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
            TextInput::make('phone')->tel()->nullable()->maxLength(255),
            Select::make('status')->options(Company::statusOptions())->default(Company::STATUS_ACTIVE)->required(),
            Textarea::make('address')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')->searchable()->sortable()->toggleable(isToggledHiddenByDefault: ! Access::isSuperAdmin()),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('phone')->searchable(),
                TextColumn::make('status')->badge()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(Company::statusOptions()),
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
            'index' => ManageBranches::route('/'),
        ];
    }
}
