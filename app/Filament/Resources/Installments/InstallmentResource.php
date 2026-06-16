<?php

namespace App\Filament\Resources\Installments;

use App\Filament\Resources\Installments\Pages\ManageInstallments;
use App\Models\Installment;
use App\Models\Subscription;
use App\Support\Access;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InstallmentResource extends Resource
{
    protected static ?string $model = Installment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDateRange;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (Access::isSuperAdmin()) {
            return $query;
        }

        return $query->whereHas('subscription', fn (Builder $query) => $query->where('company_id', Access::companyId() ?? 0));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('subscription_id')->options(fn (): array => Subscription::query()->with('customer')->when(! Access::isSuperAdmin(), fn ($query) => $query->where('company_id', Access::companyId()))->latest()->get()->mapWithKeys(fn (Subscription $subscription): array => [$subscription->id => '#' . $subscription->id . ' - ' . $subscription->customer?->name])->all())->searchable()->required(),
            TextInput::make('title')->required()->maxLength(255),
            TextInput::make('amount')->numeric()->required()->prefix('OMR'),
            DatePicker::make('due_date')->required(),
            TextInput::make('paid_amount')->numeric()->default(0)->prefix('OMR'),
            Select::make('status')->options(Installment::statusOptions())->default(Installment::STATUS_UNPAID)->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subscription.customer.name')->label('Customer')->searchable(),
                TextColumn::make('title')->searchable(),
                TextColumn::make('amount')->money('OMR')->sortable(),
                TextColumn::make('paid_amount')->money('OMR')->sortable(),
                TextColumn::make('due_date')->date()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(Installment::statusOptions()),
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
            'index' => ManageInstallments::route('/'),
        ];
    }
}
