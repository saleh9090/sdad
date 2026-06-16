<?php

namespace App\Filament\Resources\Subscriptions\Pages;

use App\Filament\Resources\Subscriptions\SubscriptionResource;
use App\Models\Installment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Support\Access;
use Carbon\Carbon;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSubscriptions extends ManageRecords
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateDataUsing(function (array $data): array {
                    $data['company_id'] ??= Access::companyId();

                    if (blank($data['total_amount'] ?? null) && filled($data['subscription_plan_id'] ?? null)) {
                        $data['total_amount'] = SubscriptionPlan::find($data['subscription_plan_id'])?->amount;
                    }

                    return $data;
                })
                ->after(fn (Subscription $record, array $data) => $this->generateInstallments($record, $data)),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function generateInstallments(Subscription $subscription, array $data): void
    {
        if (! ($data['generate_installments'] ?? false)) {
            return;
        }

        $plan = $subscription->plan;

        if (! $plan?->allow_installments) {
            return;
        }

        $count = max((int) ($data['installment_count'] ?? 0), 0);

        if ($count < 1) {
            return;
        }

        $dueDate = Carbon::parse($data['first_due_date'] ?? $subscription->start_date ?? now());
        $amount = round((float) $subscription->total_amount / $count, 3);

        for ($index = 1; $index <= $count; $index++) {
            Installment::create([
                'subscription_id' => $subscription->id,
                'title' => 'Installment ' . $index,
                'amount' => $amount,
                'due_date' => $dueDate->copy()->addMonthsNoOverflow($index - 1),
                'status' => Installment::STATUS_UNPAID,
            ]);
        }
    }
}
