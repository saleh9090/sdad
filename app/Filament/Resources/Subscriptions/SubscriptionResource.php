<?php

namespace App\Filament\Resources\Subscriptions;

use App\Filament\Resources\Subscriptions\Pages\ManageSubscriptions;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Support\Access;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    public static function getEloquentQuery(): Builder
    {
        return Access::scopeToCompany(parent::getEloquentQuery());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('company_id')->default(Access::companyId()),
            Select::make('branch_id')->options(fn (): array => Branch::query()->when(! Access::isSuperAdmin(), fn ($query) => $query->where('company_id', Access::companyId()))->orderBy('name')->pluck('name', 'id')->all())->searchable(),
            Select::make('customer_id')->options(fn (): array => Customer::query()->when(! Access::isSuperAdmin(), fn ($query) => $query->where('company_id', Access::companyId()))->orderBy('name')->pluck('name', 'id')->all())->searchable()->required(),
            Select::make('subscription_plan_id')->label('Subscription plan')->options(fn (): array => SubscriptionPlan::query()->when(! Access::isSuperAdmin(), fn ($query) => $query->where('company_id', Access::companyId()))->orderBy('name')->pluck('name', 'id')->all())->searchable()->required(),
            TextInput::make('total_amount')->numeric()->required()->prefix('OMR'),
            DatePicker::make('start_date'),
            DatePicker::make('end_date')->helperText('Calculated from start date and plan duration when available.'),
            Select::make('status')->options(Subscription::statusOptions())->default(Subscription::STATUS_ACTIVE)->required(),
            Toggle::make('generate_installments')->label('Generate installments')->dehydrated(false),
            TextInput::make('installment_count')->numeric()->minValue(1)->dehydrated(false),
            DatePicker::make('first_due_date')->dehydrated(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')->toggleable(isToggledHiddenByDefault: ! Access::isSuperAdmin()),
                TextColumn::make('customer.name')->searchable()->sortable(),
                TextColumn::make('plan.name')->label('Plan')->searchable(),
                TextColumn::make('total_amount')->money('OMR')->sortable(),
                TextColumn::make('start_date')->date()->sortable(),
                TextColumn::make('end_date')->date()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(Subscription::statusOptions()),
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
            'index' => ManageSubscriptions::route('/'),
        ];
    }
}
