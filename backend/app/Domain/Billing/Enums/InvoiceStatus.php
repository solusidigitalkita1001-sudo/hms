<?php

namespace App\Domain\Billing\Enums;

enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case UNPAID = 'unpaid';
    case PARTIAL = 'partial';
    case PAID = 'paid';
    case REFUNDED = 'refunded';
    case VOID = 'void';

    public function canBeModified(): bool
    {
        return in_array($this, [self::DRAFT, self::UNPAID, self::PARTIAL], true);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::PAID, self::REFUNDED, self::VOID], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::UNPAID => 'Unpaid',
            self::PARTIAL => 'Partially Paid',
            self::PAID => 'Paid',
            self::REFUNDED => 'Refunded',
            self::VOID => 'Void',
        };
    }
}