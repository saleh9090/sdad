<?php

namespace App\Filament\Resources\Payments;

use App\Filament\Resources\Payments\Pages\ManagePayments;
use App\Models\Installment;
use App\Models\Payment;
use App\Models\Subscription;
use App\Support\Access;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function getEloquentQuery(): Builder
    {
        return Access::scopeToCompany(parent::getEloquentQuery());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('subscription_id')->options(fn (): array => Subscription::query()->with('customer')->when(! Access::isSuperAdmin(), fn ($query) => $query->where('company_id', Access::companyId()))->latest()->get()->mapWithKeys(fn (Subscription $subscription): array => [$subscription->id => '#' . $subscription->id . ' - ' . $subscription->customer?->name])->all())->searchable()->required(),
            Select::make('installment_id')->options(fn (): array => Installment::query()->whereHas('subscription', fn ($query) => $query->when(! Access::isSuperAdmin(), fn ($query) => $query->where('company_id', Access::companyId())))->orderBy('due_date')->get()->mapWithKeys(fn (Installment $installment): array => [$installment->id => $installment->title . ' - OMR ' . $installment->amount])->all())->searchable(),
            TextInput::make('amount')->numeric()->required()->prefix('OMR'),
            DatePicker::make('payment_date')->default(now())->required(),
            TextInput::make('payment_method')->nullable()->maxLength(255),
            Textarea::make('notes')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')->toggleable(isToggledHiddenByDefault: ! Access::isSuperAdmin()),
                TextColumn::make('customer.name')->searchable()->sortable(),
                TextColumn::make('subscription.id')->label('Subscription')->prefix('#')->sortable(),
                TextColumn::make('installment.title')->toggleable(),
                TextColumn::make('amount')->money('OMR')->sortable(),
                TextColumn::make('payment_date')->date()->sortable(),
                TextColumn::make('payment_method')->searchable()->toggleable(),
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
            'index' => ManagePayments::route('/'),
        ];
    }
}
