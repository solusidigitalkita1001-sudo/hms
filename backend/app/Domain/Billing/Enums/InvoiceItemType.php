<?php

namespace App\Domain\Billing\Enums;

enum InvoiceItemType: string
{
    case ROOM_CHARGE = 'room_charge';
    case AMENITY = 'amenity';
    case FOOD = 'food';
    case SERVICE = 'service';
    case DAMAGE_FEE = 'damage_fee';
    case LATE_CHECKOUT_FEE = 'late_checkout_fee';
    case LOST_KEYCARD_FEE = 'lost_keycard_fee';
    case ADJUSTMENT = 'adjustment';
    case REFUND = 'refund';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::ROOM_CHARGE => 'Room Charge',
            self::AMENITY => 'Amenity',
            self::FOOD => 'Food & Beverage',
            self::SERVICE => 'Service',
            self::DAMAGE_FEE => 'Damage Fee',
            self::LATE_CHECKOUT_FEE => 'Late Checkout Fee',
            self::LOST_KEYCARD_FEE => 'Lost Keycard Fee',
            self::ADJUSTMENT => 'Adjustment',
            self::REFUND => 'Refund',
        };
    }
}