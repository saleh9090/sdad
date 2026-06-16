<?php

namespace App\Filament\Resources\Companies;

use App\Filament\Resources\Companies\Pages\ManageCompanies;
use App\Models\Company;
use App\Models\User;
use App\Support\Access;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return Access::isSuperAdmin()
            ? $query
            : $query->whereKey(Access::companyId() ?? 0);
    }

    public static function canCreate(): bool
    {
        return Access::isSuperAdmin();
    }

    public static function canAccess(): bool
    {
        return Access::isSuperAdmin() || Access::user()?->role === User::ROLE_ADMIN;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('phone')->tel()->required()->maxLength(255),
            TextInput::make('email')->email()->nullable()->maxLength(255),
            Select::make('status')->options(Company::statusOptions())->default(Company::STATUS_ACTIVE)->required(),
            Textarea::make('address')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('phone')->searchable(),
                TextColumn::make('email')->searchable()->toggleable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('branches_count')->counts('branches')->label('Branches')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(Company::statusOptions()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->visible(fn (): bool => Access::isSuperAdmin()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn (): bool => Access::isSuperAdmin()),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCompanies::route('/'),
        ];
    }
}
