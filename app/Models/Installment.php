<?php

namespace App\Models;

use Database\Factories\InstallmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['subscription_id', 'title', 'amount', 'due_date', 'paid_amount', 'status'])]
class Installment extends Model
{
    /** @use HasFactory<InstallmentFactory> */
    use HasFactory;

    public const STATUS_UNPAID = 'unpaid';

    public const STATUS_PARTIALLY_PAID = 'partially_paid';

    public const STATUS_PAID = 'paid';

    public const STATUS_OVERDUE = 'overdue';

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:3',
            'paid_amount' => 'decimal:3',
            'due_date' => 'date',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_UNPAID => 'Unpaid',
            self::STATUS_PARTIALLY_PAID => 'Partially paid',
            self::STATUS_PAID => 'Paid',
            self::STATUS_OVERDUE => 'Overdue',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function refreshPaymentStatus(): void
    {
        $paidAmount = (float) $this->payments()->sum('amount');
        $status = match (true) {
            $paidAmount >= (float) $this->amount => self::STATUS_PAID,
            $paidAmount > 0 => self::STATUS_PARTIALLY_PAID,
            $this->due_date?->isPast() => self::STATUS_OVERDUE,
            default => self::STATUS_UNPAID,
        };

        $this->forceFill([
            'paid_amount' => $paidAmount,
            'status' => $status,
        ])->saveQuietly();
    }
}
