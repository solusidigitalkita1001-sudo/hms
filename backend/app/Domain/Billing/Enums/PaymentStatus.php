<?php

namespace App\Domain\Billing\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case VOIDED = 'voided';
    case REFUNDED = 'refunded';

    public function isFinal(): bool
    {
        return in_array($this, [self::VOIDED, self::REFUNDED], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::VOIDED => 'Voided',
            self::REFUNDED => 'Refunded',
        };
    }
}