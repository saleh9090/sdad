<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Installment;
use App\Models\Payment;
use App\Models\Subscription;
use App\Support\Access;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class DashboardSummary extends StatsOverviewWidget
{
    protected ?string $heading = 'Overview';

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        return [
            Stat::make('Companies', $this->companyCount())
                ->icon(Heroicon::OutlinedBuildingOffice)
                ->color('primary'),
            Stat::make('Customers', $this->companyScoped(Customer::query())->count())
                ->icon(Heroicon::OutlinedUsers)
                ->color('success'),
            Stat::make('Active subscriptions', $this->companyScoped(Subscription::query())->where('status', Subscription::STATUS_ACTIVE)->count())
                ->icon(Heroicon::OutlinedCalendarDays)
                ->color('info'),
            Stat::make('Due installments', $this->installmentCount())
                ->icon(Heroicon::OutlinedCalendarDateRange)
                ->color('warning'),
            Stat::make('Payments received', 'OMR ' . number_format((float) $this->companyScoped(Payment::query())->sum('amount'), 3))
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('primary'),
        ];
    }

    private function companyCount(): int
    {
        if (Access::isSuperAdmin()) {
            return Company::query()->count();
        }

        return Access::companyId() ? 1 : 0;
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param Builder<TModel> $query
     * @return Builder<TModel>
     */
    private function companyScoped(Builder $query): Builder
    {
        return Access::scopeToCompany($query);
    }

    private function installmentCount(): int
    {
        $query = Installment::query()
            ->whereIn('status', [
                Installment::STATUS_UNPAID,
                Installment::STATUS_PARTIALLY_PAID,
                Installment::STATUS_OVERDUE,
            ]);

        if (Access::isSuperAdmin()) {
            return $query->count();
        }

        return $query
            ->whereHas('subscription', fn (Builder $query) => $query->where('company_id', Access::companyId() ?? 0))
            ->count();
    }
}
