<?php

namespace App\Models;

use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['company_id', 'customer_id', 'subscription_id', 'installment_id', 'amount', 'payment_date', 'payment_method', 'notes'])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:3',
            'payment_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Payment $payment): void {
            $installment = $payment->installment;

            if ((! $installment) && $payment->installment_id) {
                $installment = Installment::find($payment->installment_id);
            }

            if ($installment) {
                $payment->subscription_id = $installment->subscription_id;
            }

            $subscription = $payment->subscription;

            if ((! $subscription) && $payment->subscription_id) {
                $subscription = Subscription::find($payment->subscription_id);
            }

            if ($subscription) {
                $payment->company_id = $subscription->company_id;
                $payment->customer_id = $subscription->customer_id;
            }
        });

        static::saved(fn (Payment $payment) => $payment->refreshRelatedStatuses());
        static::deleted(fn (Payment $payment) => $payment->refreshRelatedStatuses());
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(Installment::class);
    }

    public function refreshRelatedStatuses(): void
    {
        $this->installment?->refreshPaymentStatus();
        $this->subscription?->refreshStatusFromPayments();
    }
}
