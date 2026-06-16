<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'branch_id', 'customer_id', 'subscription_plan_id', 'total_amount', 'start_date', 'end_date', 'status'])]
class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:3',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Subscription $subscription): void {
            $customer = $subscription->customer;

            if ((! $customer) && $subscription->customer_id) {
                $customer = Customer::find($subscription->customer_id);
            }

            $plan = $subscription->plan;

            if ((! $plan) && $subscription->subscription_plan_id) {
                $plan = SubscriptionPlan::find($subscription->subscription_plan_id);
            }

            if ($customer) {
                $subscription->company_id = $customer->company_id;
                $subscription->branch_id ??= $customer->branch_id;
            }

            if ($plan && blank($subscription->total_amount)) {
                $subscription->total_amount = $plan->amount;
            }

            if ($plan?->duration_days && $subscription->start_date) {
                $subscription->end_date = Carbon::parse($subscription->start_date)
                    ->addDays($plan->duration_days);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function refreshStatusFromPayments(): void
    {
        if ($this->status === self::STATUS_CANCELLED) {
            return;
        }

        $paidAmount = (float) $this->payments()->sum('amount');

        if ($paidAmount >= (float) $this->total_amount) {
            $this->forceFill(['status' => self::STATUS_COMPLETED])->saveQuietly();

            return;
        }

        if ($this->end_date && $this->end_date->isPast()) {
            $this->forceFill(['status' => self::STATUS_EXPIRED])->saveQuietly();

            return;
        }

        $this->forceFill(['status' => self::STATUS_ACTIVE])->saveQuietly();
    }
}
